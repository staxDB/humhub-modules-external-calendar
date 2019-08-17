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
            return new ICalFile($url);
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

        foreach ($recurringICalEvents as $eventKey => $recurringICalEvent) {
            foreach ($existingModels as $index => $model) {
                if($model->uid !== $recurringICalEvent->getUid()) {
                    continue;
                }

                // Note: We reset all altered root events here, if the root event is still altered we overwrite it in syncAlteredEvents
                if($model->is_altered || $model->wasModifiedSince($recurringICalEvent)) {
                    // We backup the rrule in order to know if this event was an recurring event prior the update
                    $currentRRule = $model->rrule;
                    $currentStart = $model->getStartDateTime();
                    $currentExDate = $model->exdate;
                    $model->is_altered = 0;
                    $model->syncWithICal($recurringICalEvent, $this->calendarModel->time_zone);
                    $this->syncRecurringEvent($model, $recurringICalEvent, $currentRRule, $currentStart, $currentExDate);
                }

                // Sync altered events regardless of the modification date
                $this->syncAlteredEvents($model);

                unset($recurringICalEvents[$eventKey], $existingModels[$index]);
                break;
            }
        }

        // create remaining recurring ical events
        foreach ($recurringICalEvents as $icalEvent) {
            $model = $this->createEventModel($icalEvent);
            $this->syncAlteredEvents($model);
        }

        // delete remaining models
        foreach ($existingModels as $model) {
            $model->delete();
        }
    }

    /**
     * @param ExternalCalendarEntry $model
     * @throws \yii\base\Exception
     * @throws \Throwable
     */
    private function syncAlteredEvents(ExternalCalendarEntry $model)
    {
        $alteredICalEvents = $this->ical->getAlteredRecurrences($model->uid);

        if(empty($alteredICalEvents)) {
            foreach ($model->getAlteredRecurrences()->all() as $remainingAlteredModels) {
                $remainingAlteredModels->delete();
            }
            return;
        }

        $alteredRecurrenceIds = [];
        foreach($alteredICalEvents as $alteredEvent) {
            $alteredRecurrenceIds[] = $alteredEvent->getRecurrenceId();
            $recurrenceInstanceModel = $model->getRecurrenceInstance($alteredEvent->getRecurrenceId());

            if(!$recurrenceInstanceModel) {
                $recurrenceInstanceModel = $this->createEventModel($alteredEvent);
                $recurrenceInstanceModel->parent_event_id = $model->id;
            }

            $recurrenceInstanceModel->is_altered = 1;
            $recurrenceInstanceModel->syncWithICal($alteredEvent, $this->calendarModel->time_zone);
        }

        // Delete remaining db models
        $remainingAlteredModels = $model->getAlteredRecurrences()->andWhere(['NOT IN', 'recurrence_id', $alteredRecurrenceIds])->all();
        foreach ($remainingAlteredModels as $remainingAlteredModel) {
            $remainingAlteredModel->delete();
        }
    }

    private function syncRecurringEvent(ExternalCalendarEntry $model, ICalEventIF $icalEvent, $currentRRule, $currentStart, $currentExdate)
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

        // We only care if exdates were added since removed exdates will be created while expanding the event
        if($currentExdate !== $icalEvent->getExdate() && !empty($icalEvent->getExdate())) {
            $currentExdate = empty($currentExdate) ? '' : $currentExdate;
            $recurrencesToDelete = array_diff($icalEvent->getExdateArray(), explode(',', $currentExdate));
            if(!empty($recurrencesToDelete)) {
                $model->deleteRecurringInstances($recurrencesToDelete);
            }
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