<?php


namespace humhub\modules\external_calendar\models;

use Recurr\Rule;
use Yii;
use yii\base\InvalidValueException;
use yii\base\Model;
use humhub\modules\external_calendar\CalendarUtils;
use ICal\ICal;
use ICal\Event;

class ICalSync extends Model
{
    /**
     * @var ICal
     */
    public $ical;

    /**
     * @var \DateTime
     */
    public $start;

    /**
     * @var \DateTime
     */
    public $end;

    /**
     * @var ExternalCalendar
     */
    public $calendarModel;

    /**
     * Static sync function.
     *
     * @param ExternalCalendar $calendarModel
     */
    public static function sync(ExternalCalendar $calendarModel)
    {
        return (new static(['calendarModel' => $calendarModel]))->syncICal();
    }

    /**
     * @throws \yii\base\Exception
     */
    private function syncICal()
    {
        $this->ical = $this->fetchICal($this->calendarModel->url);

        if (!$this->ical) {
            throw new InvalidValueException(Yii::t('ExternalCalendarModule.sync_result', 'Error while creating ical... Check if link is reachable.'));
        }

        $this->syncICalAttributes();
        $this->syncICalEvents();

        $this->calendarModel->save();
        $this->calendarModel->refresh();
    }

    /**
     * @param $url
     * @return bool|ICal|null
     */
    private function fetchICal($url)
    {
        if (empty($url)) {
            return false;
        }

        try {
            $ical = new ICal($url, [
                'defaultTimeZone' => Yii::$app->timeZone,
                'skipRecurrence' => true, // Default value
            ]);

            return $ical;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function syncICalAttributes()
    {
        $this->calendarModel->time_zone = $this->ical->calendarTimeZone();
        $this->calendarModel->cal_name = $this->ical->calendarName();

        if (isset($this->ical->cal['VCALENDAR']['VERSION'])) {
            $this->calendarModel->version = $this->ical->cal['VCALENDAR']['VERSION'];
        }
        if (isset($this->ical->cal['VCALENDAR']['CALSCALE'])) {
            $this->calendarModel->cal_scale = $this->ical->cal['VCALENDAR']['CALSCALE'];
        }
    }

    /**
     * @return bool|void
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    private function syncICalEvents()
    {
        if (!$this->ical->hasEvents()) {
            return;
        }

        $icalEvents = $this->getIcalEvents();

        if (empty($icalEvents)) {
            return;
        }

        //$recurringIcalEvents = static::filterRecurringEvents($icalEvents);

        $eventModels = $this->calendarModel->getEntries(false)->all();

        $this->syncExistingEvents($eventModels, $icalEvents);
        $this->deleteRemainingModels($eventModels);

        // TODO: Check for existing recurrence rules out of search interval
    }

    /**
     * @return ExternalCalendarEntry[]
     */
    private function getModelsByRange()
    {
       return $this->calendarModel->getEntries(false)->andFilterWhere(['or',
            ['and',
                ['>=', 'start_datetime', $this->start->format('Y-m-d H:i:s')],
                ['<=', 'start_datetime', $this->end->format('Y-m-d H:i:s')]
            ],
            ['and',
                ['>=', 'end_datetime', $this->start->format('Y-m-d H:i:s')],
                ['<=', 'end_datetime', $this->end->format('Y-m-d H:i:s')]
            ]
        ])->all();

    }

    /**
     * @param ExternalCalendarEntry[] $models
     * @param array $icalEvents
     * @throws \yii\base\Exception
     */
    private function syncExistingEvents(array &$models, array &$icalEvents)
    {
        foreach ($icalEvents as $eventKey => $icalEvent) {
            /** @var $icalEvent Event */
            foreach ($this->calendarModel->entries as $dbModelKey => $model) {
                if($model->uid !== $icalEvent->uid) {
                    continue;
                }

                if(empty($icalEvent->last_modified) || !$model->last_modified < $this->getLastModified($icalEvent)) {
                    // We backup the rrule in order to know if this event was an recurring event prior the update
                    $currentRRule = $model->rrule;
                    $currentStart = $model->getStartDateTime();
                    $this->syncModelData($model, $icalEvent);
                    $this->syncRecurringEvent($model, $icalEvent, $currentRRule, $currentStart);
                }

                unset($icalEvents[$eventKey], $models[$dbModelKey]);
                break;
            }
        }

        // link new values
        foreach ($icalEvents as $eventKey => $icalEvent) {
            $this->createEventModel($icalEvent);
            unset($icalEvents[$eventKey]);
        }
    }

    private function syncRecurringEvent(ExternalCalendarEntry $model, Event $icalEvent, $currentRRule, $currentStart)
    {
        if(empty($icalEvent->rrule)) {
            if(!empty($currentRRule)) {
                $model->deleteRecurringInstances();
            }
            return;
        }

        if($icalEvent->rrule !== $currentRRule) {
            // TODO: we could do some further analysis to check if recurrence-id's are still valid (only added events)
            $model->deleteRecurringInstances();
        } else if($currentStart < $model->getStartDateTime()) {
            $model->deleteRecurringInstances($model->getStartDateTime(), '<');
        }
    }



    /**
     * @param array $eventModels
     * @param array $recurringIcalEvents
     * @throws \yii\base\Exception

    private function syncRecurringEvents(array &$eventModels, array &$recurringIcalEvents)
    {
        // handle recurring events
        foreach ($recurringIcalEvents as $eventKey => $event) {
            // then check list of remaining dbModels for this event
            foreach ($eventModels as $dbModelKey => $model) {
                // compare uid & start_datetime
                if (($model->uid === $event->uid) && ($model->start_datetime == $entryModel->start_datetime)) {
                    // found & update $dbModel
                    $model->setByEvent($event);
//                    $model->content->refresh(); // refresh updated_at
                    $model->save();
                    unset($recurringIcalEvents[$eventKey]);
                    unset($eventModels[$dbModelKey]);    // can't do this here, because of recurring events
                    continue;
                } else {
                    continue;
                }
            }
            unset($entryModel);
        }

        foreach ($recurringIcalEvents as $newModelKey => $newModel) {
            $model = $this->createEventModel($newModel, $this->calendarModel);
            unset($recurringIcalEvents[$newModelKey]);
        }
    }
     *  */

    /**
     * @return array
     * @throws \Exception
     */
    private function getIcalEvents()
    {
        $this->start = new \DateTime('first day of this month');
        $this->end = new \DateTime('last day of this month');

        if ($this->calendarModel->event_mode === ExternalCalendar::EVENT_MODE_ALL) {
            $this->start = $this->start->sub(new \DateInterval('P1Y'));
            $this->end = $this->end->add(new \DateInterval('P2Y'));
        }

        $this->start->setTime(0,0,0);
        $this->end->setTime(23,59,59);
        return $this->ical->eventsFromRange($this->start->format('Y-m-d H:i:s'), $this->end->format('Y-m-d H:i:s'));
    }

    /**
     * @param array $events
     * @return array
     */
    private static function filterRecurringEvents(array &$events)
    {
        $recurringEvents = [];
        foreach ($events as $eventKey => $event) {
            /** @var $event Event **/
            if (!empty($event->rrule)) {
                array_push($recurringEvents, $event);
                unset($events[$eventKey]);
            }
        }

        return $recurringEvents;
    }

    /**
     * @param Event $event
     * @return null|string
     * @throws \Exception
     */
    private function getLastModified(Event $event)
    {
        return empty($event->last_modified)
            ? (new \DateTime('now'))->format('Y-m-d H:i:s')
            : CalendarUtils::formatDateTimeToString($event->last_modified);
    }

    /**
     * @param Event $icalEvent
     * @return ExternalCalendarEntry
     * @throws \yii\base\Exception
     */
    private function createEventModel(Event $icalEvent)
    {
        $eventModel = new ExternalCalendarEntry($this->calendarModel->content->container, $this->calendarModel->content->visibility);
        $eventModel->content->created_by = $this->calendarModel->content->created_by;
        $eventModel->calendar_id = $this->calendarModel->id;
        return $this->syncModelData($eventModel, $icalEvent);
    }

    /**
     * @param ExternalCalendarEntry $eventModel
     * @param Event $icalEvent
     * @param bool $save
     * @return ExternalCalendarEntry
     * @throws \Exception
     */
    private function syncModelData(ExternalCalendarEntry $eventModel, Event $icalEvent, $save = true)
    {
        // uid MUST be set --> https://www.kanzaki.com/docs/ical/uid.html
        $eventModel->uid = $icalEvent->uid;

        $eventModel->title = empty($icalEvent->summary)
            ? Yii::t('ExternalCalendarModule.model_calendar_entry', '(No Title)')
            : $icalEvent->summary;

        $eventModel->description = $icalEvent->description;

        if(!empty($icalEvent->rrule)) {
            $eventModel->setRRule(($icalEvent->rrule));
        }

        $eventModel->location = $icalEvent->location;

        $eventModel->last_modified = $this->getLastModified($icalEvent);

        // dtstamp MUST be included --> https://www.kanzaki.com/docs/ical/dtstamp.html
        $eventModel->dtstamp = CalendarUtils::formatDateTimeToString($icalEvent->dtstamp);

        // dtstart MUST be included --> https://www.kanzaki.com/docs/ical/dtstart.html
        $eventModel->start_datetime = CalendarUtils::formatDateTimeToString($icalEvent->dtstart);

        // dtend CAN be included. If not, dtend is same DateTime as dtstart --> https://www.kanzaki.com/docs/ical/dtend.html
        $eventModel->end_datetime = empty($icalEvent->dtend)
            ? $eventModel->start_datetime
            : CalendarUtils::formatDateTimeToString($icalEvent->dtend);

        $eventModel->time_zone = $this->calendarModel->time_zone;
        $eventModel->all_day = CalendarUtils::checkAllDay($icalEvent->dtstart, $icalEvent->dtend);

        if($save) {
            $eventModel->save();
        }

        return $eventModel;
    }

    /**
     * @param array $eventModels
     */
    private function deleteRemainingModels(array $eventModels)
    {
        // finally delete items from db
        foreach ($eventModels as $modelKey => $model) {
            $this->calendarModel->unlink('entries', $model, true);
            unset($eventModels[$modelKey]);
        }

    }
}