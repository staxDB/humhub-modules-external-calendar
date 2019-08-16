<?php


namespace humhub\modules\external_calendar\models;

use Yii;
use yii\base\InvalidValueException;
use yii\base\Model;
use humhub\modules\external_calendar\CalendarUtils;
use DateTime;

class ICalSync extends Model
{
    /**
     * @var ICalIF
     */
    public $ical;

    /**
     * @var DateTime
     */
    public $rangeStart;

    /**
     * @var string
     */
    public $rangeStartInterval = 'P1Y';

    /**
     * @var string
     */
    public $rangeEndInterval = 'P6M';

    /**
     * @var DateTime
     */
    public $rangeEnd;

    /**
     * @var ExternalCalendar
     */
    public $calendarModel;

    /**
     * Static sync function.
     *
     * @param ExternalCalendar $calendarModel
     */
    public static function sync(ExternalCalendar $calendarModel, $rangeStart = null, $rangeEnd = null)
    {
        return (new static([
            'calendarModel' => $calendarModel,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd
        ]))->syncICal();
    }

    /**
     * @throws \yii\base\Exception
     * @throws \Throwable
     */
    public function syncICal()
    {
        $this->ical = $this->fetchICal($this->calendarModel->url);

        if (!$this->ical) {
            throw new InvalidValueException(Yii::t('ExternalCalendarModule.sync_result', 'Error while creating ical... Check if link is reachable.'));
        }

        $this->setupSearchRange();
        $this->syncICalAttributes();
        $this->syncICalEvents();

        $this->calendarModel->save();
        $this->calendarModel->refresh();
    }

    /**
     * @param $url
     * @return bool|ICalIF|null
     */
    private function fetchICal($url)
    {
        if (empty($url)) {
            return false;
        }

        try {
            return new SimpleICal($url);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function setupSearchRange()
    {
        if(!$this->rangeStart) {
            $this->rangeStart = new \DateTime('first day of this month');
            if ($this->calendarModel->event_mode === ExternalCalendar::EVENT_MODE_ALL) {
                $this->rangeStart = $this->rangeStart->sub(new \DateInterval($this->rangeStartInterval));
            }
            $this->rangeStart->setTime(0,0,0);
        }

        if(!$this->rangeEnd) {
            $this->rangeEnd = new \DateTime('last day of this month');
            if ($this->calendarModel->event_mode === ExternalCalendar::EVENT_MODE_ALL) {
                $this->rangeEnd = $this->rangeEnd->add(new \DateInterval($this->rangeEndInterval));
            }
            $this->rangeEnd->setTime(23,59,59);
        }
    }

    private function syncICalAttributes()
    {
        $this->calendarModel->time_zone = $this->ical->getTimeZone();
        $this->calendarModel->cal_name = $this->ical->getName();
        $this->calendarModel->version = $this->ical->getVersion();
        $this->calendarModel->cal_scale = $this->ical->getScale();
    }

    /**
     * @return bool|void
     * @throws \yii\base\Exception
     * @throws \Exception
     * @throws \Throwable
     */
    private function syncICalEvents()
    {
        $this->syncNonRecurringEvents();
        $this->syncRecurringEvents();
    }

    /**
     * @param ExternalCalendarEntry[] $models
     * @param ICalEventIF[] $icalEvents
     * @param ICalEventIF[] $recurringEvents
     * @throws \yii\base\Exception
     * @throws \Throwable
     */
    private function syncNonRecurringEvents()
    {
        $existingModelsInRange = $this->getNonRecurringEventsWithinRange();
        $icalEventsInRange = $this->ical->getEventsFromRange($this->rangeStart, $this->rangeEnd);

        foreach ($icalEventsInRange as $eventKey => $icalEvent) {

            // We skip recurring events
            if($icalEvent->getRrule() || $icalEvent->getRecurrenceId()) {
                unset($icalEventsInRange[$eventKey]);
                continue;
            }

            foreach ($existingModelsInRange as $index => $model) {
                if($model->uid === $icalEvent->getUid() && $model->wasModifiedSince($icalEvent)) {
                    $model->syncWithICal($icalEvent, $this->calendarModel->time_zone);
                    unset($icalEventsInRange[$eventKey], $existingModelsInRange[$index]);
                    break;
                }
            }
        }

        // create remaining non recurring events
        foreach ($icalEventsInRange as $eventKey => $icalEvent) {
            // TODO: Make sure there is no existing model outside of range
            $this->createEventModel($icalEvent);
        }

        $this->deleteNonRecurringEvents($existingModelsInRange);
    }

    /**
     * @return ExternalCalendarEntry[]
     */
    private function getNonRecurringEventsWithinRange()
    {
        return $this->calendarModel->getEntries(false)->andFilterWhere(['or',
            ['and',
                ['>=', 'start_datetime', $this->rangeStart->format('Y-m-d H:i:s')],
                ['<=', 'start_datetime', $this->rangeEnd->format('Y-m-d H:i:s')]
            ],
            ['and',
                ['>=', 'end_datetime', $this->rangeStart->format('Y-m-d H:i:s')],
                ['<=', 'end_datetime', $this->rangeEnd->format('Y-m-d H:i:s')]
            ]
        ])->andWhere('external_calendar_entry.rrule IS NULL')->all();
    }

    private function syncRecurringEvents()
    {
        // This will include recurring events that start before range and end after the rangeStart
        $recurringICalEvents = $this->ical->getRecurringEvents();
        $existingModels = $this->calendarModel->getRecurringEventRoots();

        foreach ($recurringICalEvents as $eventKey => $recurringICalEvent)
        {
            foreach ($existingModels as $index => $model) {
                if($model->uid !== $recurringICalEvent->getUid()) {
                    continue;
                }

                if($model->wasModifiedSince($recurringICalEvent)) {
                    // We backup the rrule in order to know if this event was an recurring event prior the update
                    $currentRRule = $model->rrule;
                    $currentStart = $model->getStartDateTime();
                    $model->syncWithICal($recurringICalEvent, $this->calendarModel->time_zone);
                    $this->syncRecurringEvent($model, $recurringICalEvent, $currentRRule, $currentStart);
                }

                $this->syncAlteredEvents($model, $recurringICalEvent);

                unset($recurringICalEvents[$eventKey], $existingModels[$index]);
                break;
            }
        }

        // create remaining recurring events
        foreach ($recurringICalEvents as $icalEvent) {
            $model = $this->createEventModel($icalEvent);
            $this->syncAlteredEvents($model, $icalEvent);
        }

        // delete remaining models
        foreach ($existingModels as $model) {
            $model->delete();
        }
    }

    /**
     * @param array $alteredEventMap
     * @param ExternalCalendarEntry $model
     * @param ICalEventIF $recurringICalEvent
     * @throws \yii\base\Exception
     */
    private function syncAlteredEvents(ExternalCalendarEntry $model, ICalEventIF $recurringICalEvent)
    {
        $alteredEvents = $this->ical->getAlteredRecurrences($model->uid);

        if(empty($alteredEvents)) {
            return;
        }

        foreach($alteredEvents as $alteredEvent) {
            /** @var ICalEventIF $alteredEvent **/
            $recurrenceInstance = $model->getRecurrenceInstance($alteredEvent->getRecurrenceId());
            if(!$recurrenceInstance) {
                $recurrenceInstance = $this->createEventModel($alteredEvent);
            }

            $recurrenceInstance->parent_event_id = $model->id;
            $recurrenceInstance->syncWithICal($alteredEvent, $this->calendarModel->time_zone);
        }
    }

    private function syncRecurringEvent(ExternalCalendarEntry $model, ICalEventIF $icalEvent, $currentRRule, $currentStart)
    {
        if(empty($icalEvent->getRrule())) {
            if(!empty($currentRRule)) {
                $model->deleteRecurringInstances();
            }
            return;
        }

        // TODO: only until changed test

        if($icalEvent->getRrule() !== $currentRRule || $currentStart != $model->getStartDateTime()) {
            // TODO: we could do some further analysis to check if recurrence-id's are still valid (only added events)
            $model->deleteRecurringInstances();
        }
    }

    /**
     * @param ICalEventIF $icalEvent
     * @return ExternalCalendarEntry
     * @throws \yii\base\Exception
     */
    private function createEventModel(ICalEventIF $icalEvent)
    {
        $eventModel = new ExternalCalendarEntry($this->calendarModel->content->container, $this->calendarModel->content->visibility);
        $eventModel->content->created_by = $this->calendarModel->content->created_by;
        $eventModel->calendar_id = $this->calendarModel->id;
        $eventModel->syncWithICal($icalEvent, $this->calendarModel->time_zone);
        return $eventModel;
    }

    /**
     * @param ExternalCalendarEntry[] $eventModels
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    private function deleteNonRecurringEvents($eventModels)
    {
        // finally delete items from db
        foreach ($eventModels as $model) {
            if(!$model->isRecurring()) {
                $model->delete();
            }
        }
    }
}