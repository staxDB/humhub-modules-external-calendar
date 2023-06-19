<?php


namespace humhub\modules\external_calendar\models;

use humhub\modules\external_calendar\helpers\CalendarUtils;
use humhub\modules\external_calendar\helpers\RRuleHelper;
use Recurr\Exception;
use Recurr\Rule;
use Yii;
use yii\base\InvalidValueException;
use yii\base\Model;
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
    public $rangeStartInterval = 'P6M';

    /**
     * @var string
     */
    public $rangeEndInterval = 'P5Y';

    /**
     * @var DateTime
     */
    public $rangeEnd;

    /**
     * @var ExternalCalendar
     */
    public $calendarModel;

    public $errorFlag = false;

    public $errorMessage;

    /**
     * @var bool whether or not to skip event synchronization
     */
    public $skipEvents = false;

    /**
     * Static sync function.
     *
     * @param ExternalCalendar $calendarModel
     * @return ICalSync
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

        if(!$this->skipEvents) {
            $this->syncICalEvents();
        }

        $this->calendarModel->save();
        $this->calendarModel->refresh();
        return $this;
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
        if (!$this->rangeStart) {
            $this->rangeStart = new \DateTime('first day of this month');
            if ($this->calendarModel->event_mode === ExternalCalendar::EVENT_MODE_ALL) {
                $this->rangeStart = $this->rangeStart->sub(new \DateInterval($this->rangeStartInterval));
            }
            $this->rangeStart->setTime(0, 0, 0);
        }

        if (!$this->rangeEnd) {
            $this->rangeEnd = new \DateTime('last day of this month');
            if ($this->calendarModel->event_mode === ExternalCalendar::EVENT_MODE_ALL) {
                $this->rangeEnd = $this->rangeEnd->add(new \DateInterval($this->rangeEndInterval));
            }
            $this->rangeEnd->setTime(23, 59, 59);
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
        try {
            $this->syncNonRecurringEvents();
            $this->syncRecurringEvents();
        } catch (\Exception $e) {
            $this->error(Yii::t('ExternalCalendarModule.base', 'There was an error while synchronizing an ical calendar') . ': ' . $this->calendarModel->content->container->createUrl('/external_calendar/calendar/view', ['id' => $this->calendarModel->id]), $e);
        }
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

        // Sync ical event data for existing models
        foreach ($icalEventsInRange as $eventKey => $icalEvent) {
            // We skip recurring events
            if ($icalEvent->getRrule() || $icalEvent->getRecurrenceId()) {
                unset($icalEventsInRange[$eventKey]);
                continue;
            }

            foreach ($existingModelsInRange as $index => $model) {
                if ($model->uid === $icalEvent->getUid()) {
                    if ($model->wasModifiedSince($icalEvent)) {
                        $model->syncWithICal($icalEvent, $this->calendarModel->time_zone);
                    }
                    unset($icalEventsInRange[$eventKey], $existingModelsInRange[$index]);
                    break;
                }
            }
        }

        // create remaining non recurring events
        foreach ($icalEventsInRange as $eventKey => $icalEvent) {
            try {
                $this->createEventModel($icalEvent);
            } catch (\Exception $e) {
                $this->error(Yii::t('ExternalCalendarModule.base', 'Error creating event in ical synchronization'), $e);
            }
        }

        $this->deleteNonRecurringEvents($existingModelsInRange);
    }

    private function error($message, \Exception $e = null)
    {
        $this->errorFlag = true;
        Yii::error($message);

        if ($e) {
            Yii::error($e);
        }
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

    /**
     * @param ExternalCalendarEntry[] $eventModels
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    private function deleteNonRecurringEvents($eventModels)
    {
        // finally delete items from db
        foreach ($eventModels as $model) {
            if (!$model->isRecurring()) {
                $model->hardDelete();
            }
        }
    }

    private function syncRecurringEvents()
    {
        // This will include recurring events that start before range and end after the rangeStart
        $recurringICalEvents = $this->ical->getRecurringEvents();
        $existingModels = $this->calendarModel->getRecurringEventRoots();

        foreach ($recurringICalEvents as $eventKey => $recurringICalEvent) {
            foreach ($existingModels as $index => $model) {
                if ($model->uid !== $recurringICalEvent->getUid()) {
                    continue;
                }

                // Note: We reset all altered root events here, if the root event is still altered we overwrite it in syncAlteredEvents
                if ($model->is_altered || $model->wasModifiedSince($recurringICalEvent)) {
                    $this->syncRecurringEvent($model, $recurringICalEvent);
                }

                // Sync altered events regardless of the modification date
                $this->syncAlteredEvents($model);

                unset($recurringICalEvents[$eventKey], $existingModels[$index]);
                break;
            }
        }

        // create remaining recurring ical events and check for alterations
        foreach ($recurringICalEvents as $icalEvent) {
            try {
                $this->syncAlteredEvents($this->createEventModel($icalEvent));
            } catch (\Exception $e) {
                $this->error(Yii::t('ExternalCalendarModule.base', 'Error while synchronizing recurring ical event'), $e);
            }
        }

        // delete remaining models
        foreach ($existingModels as $model) {
            $model->hardDelete();
        }
    }

    private function syncRecurringEvent(ExternalCalendarEntry $model, ICalEventIF $icalEvent)
    {
        try {
            ExternalCalendarEntry::getDb()->transaction(function () use ($model, $icalEvent) {
                // First backup some data prior to model sync
                $currentRRule = $model->rrule;
                $currentStart = $model->getStartDateTime();
                $currentEnd = $model->getEndDateTime();
                $currentExdate = $model->exdate;

                $model->is_altered = 0;
                $model->syncWithICal($icalEvent, $this->calendarModel->time_zone);
                $this->handleRecurrenceChanges($model, $currentStart, $currentEnd, $currentRRule);
                $this->syncExdates($model, $icalEvent, $currentExdate);
            });
        } catch (\Exception $e) {
            $this->error(Yii::t('ExternalCalendarModule.base', 'Error while synchronizing recurring ical event'), $e);
        }
    }

    private function handleRecurrenceChanges(ExternalCalendarEntry $model, DateTime $currentStart, $currentEnd, $currentRRule)
    {
        // If the start time changed we delete all recurrence instances
        if ($currentStart != $model->getStartDateTime() || $currentEnd != $model->getEndDateTime()) {
            $model->deleteRecurringInstances();
            return;
        }

        // Event was transformed from non recurrent to an recurrence root, no instances to delete here
        if (empty($currentRRule)) {
            return;
        }

        // There were changes other then until
        if (!RRuleHelper::compare($currentRRule, $model->rrule, true)) {
            $model->deleteRecurringInstances();
            return;
        }

        $currentRRuleUntil = (new Rule($currentRRule))->getUntil();
        $newRRuleUntil = $model->getRecurrenceUntil();

        // Check if the new until is prior to the old until boundary and remove overlapping instances
        if ((!$currentRRuleUntil && $newRRuleUntil) || $newRRuleUntil < $currentRRuleUntil) {
            $model->deleteRecurringInstances($newRRuleUntil);
        }
    }

    private function syncExdates(ExternalCalendarEntry $model, ICalEventIF $icalEvent, $currentExdate)
    {
        // We only care if exdates were added since removed exdates will be created while expanding the event
        if ($currentExdate !== $icalEvent->getExdate() && !empty($icalEvent->getExdate())) {
            $currentExdate = empty($currentExdate) ? '' : $currentExdate;
            $recurrencesToDelete = array_diff($icalEvent->getExdateArray(), explode(',', $currentExdate));

            if (!empty($recurrencesToDelete)) {
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

        if ($icalEvent->getCreated()) {
            // We sync the created date for stream sort
            $created_at = (new DateTime($icalEvent->getCreated(), new \DateTimeZone('UTC')))
                ->format(CalendarUtils::DB_DATE_FORMAT);

            $eventModel->content->updateAttributes([
                'created_at' => $created_at,
                'stream_sort_date' => $created_at
            ]);
        }

        return $eventModel;
    }

    /**
     * @param ExternalCalendarEntry $model
     * @throws \yii\base\Exception
     * @throws \Throwable
     */
    private function syncAlteredEvents(ExternalCalendarEntry $model)
    {
        try {
            $alteredICalEvents = $this->ical->getAlteredRecurrences($model->uid);

            if (empty($alteredICalEvents)) {
                foreach ($model->getAlteredRecurrences()->all() as $remainingAlteredModels) {
                    $remainingAlteredModels->hardDelete();
                }
                return;
            }

            $alteredRecurrenceIds = [];
            foreach ($alteredICalEvents as $alteredICalEvent) {
                $alteredRecurrenceIds[] = $alteredICalEvent->getRecurrenceId();
                $recurrenceInstanceModel = $model->getRecurrenceInstance($alteredICalEvent->getRecurrenceId());

                if (!$recurrenceInstanceModel) {
                    $recurrenceInstanceModel = $this->createEventModel($alteredICalEvent);
                    $recurrenceInstanceModel->parent_event_id = $model->id;
                }

                $recurrenceInstanceModel->is_altered = 1;
                $recurrenceInstanceModel->syncWithICal($alteredICalEvent, $this->calendarModel->time_zone);
            }

            // Delete remaining db models
            $remainingAlteredModels = $model->getAlteredRecurrences()->andWhere(['NOT IN', 'recurrence_id', $alteredRecurrenceIds])->all();
            foreach ($remainingAlteredModels as $remainingAlteredModel) {
                $remainingAlteredModel->hardDelete();
            }
        } catch (\Exception $e) {
            $this->error(Yii::t('ExternalCalendarModule.base', 'Could not sync altered events of recurrent ical event'), $e);
        }
    }
}
