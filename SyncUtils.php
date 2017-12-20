<?php

namespace humhub\modules\external_calendar;

use function GuzzleHttp\Promise\all;
use ICal\Event;
use Yii;
use humhub\modules\content\models\Content;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use humhub\modules\external_calendar\models\ExternalCalendar;

require_once(Yii::$app->getModule('external_calendar')->basePath . '/vendors/johngrogg/ics-parser/src/ICal/Event.php');
require_once(Yii::$app->getModule('external_calendar')->basePath . '/vendors/johngrogg/ics-parser/src/ICal/ICal.php');
use ICal\ICal;
use yii\helpers\ArrayHelper;


/**
 * Description of SyncUtils
 *
 * @author luke
 */
class SyncUtils
{
    public static function createICal($url)
    {
        if (!isset($url)) {
            return false;
        }
        try {
            // load ical and parse it
            $ical = new ICal($url, array(
                'defaultSpan' => 2,     // Default value
                'defaultTimeZone' => Yii::$app->timeZone,
                'defaultWeekStart' => 'MO',  // Default value
                'skipRecurrence' => false, // Default value
                'useTimeZoneWithRRules' => false, // Default value
            ));
            return $ical;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param array $events
     * @param ExternalCalendar $calendar
     * @return bool
     */
    public static function checkAndSubmitModels(array &$events, ExternalCalendar &$calendar)
    {
        if (!isset($events) && !isset($calendar))
        {
            return false;
        }
        $dbModels = $calendar->externalCalendarEntries;

        // we need to filter recurring events
        $recurringEvents = self::filterRecurringEvents($events);

        // models is an array of ExternalCalendarEntry-Models
        foreach ($events as $eventKey => $event)
        {
            // first: check if last_modified is set. If not - set it to DateTime('now')
            $last_modified = self::getLastModified($event);

            foreach ($dbModels as $dbModelKey => $dbModel)
            {
                if (($dbModel->uid == $event->uid) && ($dbModel->last_modified >= $last_modified)) {
                    // found, but nothing to change
                    unset($events[$eventKey]);
                    unset($dbModels[$dbModelKey]);
                } elseif ($dbModel->uid == $event->uid) {
                    // found & update $dbModel
                    $dbModel->setByEvent($event);
                    $dbModel->save();
                    unset($events[$eventKey]);
                    unset($dbModels[$dbModelKey]);    // can't do this here, because of recurring events
                }
                else {
                    continue;
                }
            }
        }

        // handle recurring events
        foreach ($recurringEvents as $eventKey => $event)
        {
            // first create entryModel out of event
            $entryModel = self::getModel($event, $calendar);

            // then check list of remaining dbModels for this event
            foreach ($dbModels as $dbModelKey => $dbModel)
            {
                // compare uid & start_datetime
                if (($dbModel->uid == $entryModel->uid) && ($dbModel->start_datetime == $entryModel->start_datetime)) {
                    // found & update $dbModel
                    $dbModel->setByEvent($event);
                    $dbModel->save();
                    unset($recurringEvents[$eventKey]);
                    unset($dbModels[$dbModelKey]);    // can't do this here, because of recurring events
                    continue;
                }
                else {
                    continue;
                }
            }
            unset($entryModel);
        }

        foreach ($recurringEvents as $newModelKey => $newModel)
        {
            $model = self::getModel($newModel, $calendar);
            // recurring event not in db found --> create new
            $calendar->link('externalCalendarEntries', $model);
            unset($recurringEvents[$newModelKey]);
        }

        // link new values
        foreach ($events as $eventKey => $event)
        {
            $model = self::getModel($event, $calendar);
            if ($model != false) {
                $calendar->link('externalCalendarEntries', $model);
                unset($events[$eventKey]);
            } else { // error while creating model... skip event
                unset($events[$eventKey]);  // if we want to test if there was an error, comment this and var_dump($events)
                continue;
            }
        }

        // finally delete items from db
        foreach ($dbModels as $modelKey => $model)
        {
            $calendar->unlink('externalCalendarEntries', $model, true);
            unset($dbModels[$modelKey]);
        }

        return true;
    }

    public static function getModel($event, ExternalCalendar $calendar)
    {
        // create ExternalCalendarEntry-model without safe
        if (!isset($events) && !isset($calendar))
        {
            return false;
        }

        $model = new ExternalCalendarEntry();
        $model->setByEvent($event);

        // add contentContainer of ExternalCalendar-Model
        $model->content->setContainer($calendar->content->container);
        $model->content->created_by = $calendar->content->created_by;
//        $model->content->created_at = $model->dtstamp;  // set created_at to original created timestamp
        $model->content->visibility = ($calendar->public) ?  Content::VISIBILITY_PUBLIC : Content::VISIBILITY_PRIVATE;

        return $model;
    }

    public static function getEvents(ExternalCalendar $calendarModel, ICal $ical)
    {
        $start = false;
        switch ($calendarModel->past_events_mode) {
            case ExternalCalendar::PAST_EVENTS_ALL:
                $start = false;
                break;
            case ExternalCalendar::PAST_EVENTS_NONE:
                $start = new \DateTime('today');
                $start = $start->format('Y-m-d H:i:s');
                break;
            case ExternalCalendar::PAST_EVENTS_ONE_WEEK:
                $start = new \DateTime('today - 1 week');
                $start = $start->format('Y-m-d H:i:s');
                break;
            case ExternalCalendar::PAST_EVENTS_ONE_MONTH:
                $start = new \DateTime('today - 1 month');
                $start = $start->format('Y-m-d H:i:s');
                break;
            default:
                $start = new \DateTime('today - 5 years');
                $start = $start->format('Y-m-d H:i:s');
                break;
        }

        $end = false;
        switch ($calendarModel->upcoming_events_mode) {
            case ExternalCalendar::UPCOMING_EVENTS_ALL:
                $end = false;
                break;
            case ExternalCalendar::UPCOMING_EVENTS_ONE_DAY:
                $end = new \DateTime('today + 1 day');
                $end = $end->format('Y-m-d H:i:s');
                break;
            case ExternalCalendar::UPCOMING_EVENTS_ONE_WEEK:
                $end = new \DateTime('today + 1 day + 1 week');
                $end = $end->format('Y-m-d H:i:s');
                break;
            case ExternalCalendar::UPCOMING_EVENTS_ONE_MONTH:
                $end = new \DateTime('today + 1 day + 1 month');
                $end = $end->format('Y-m-d H:i:s');
                break;
            case ExternalCalendar::UPCOMING_EVENTS_TWO_MONTH:
                $end = new \DateTime('today + 1 day + 2 months');
                $end = $end->format('Y-m-d H:i:s');
                break;
            case ExternalCalendar::UPCOMING_EVENTS_THREE_MONTH:
                $end = new \DateTime('today + 1 day + 3 months');
                $end = $end->format('Y-m-d H:i:s');
                break;
            case ExternalCalendar::UPCOMING_EVENTS_ONE_YEAR:
                $end = new \DateTime('today + 1 day + 1 year');
                $end = $end->format('Y-m-d H:i:s');
                break;
            default:
                $end = new \DateTime('today + 5 years');
                $end = $end->format('Y-m-d H:i:s');
                break;
        }

        $events = [];
        if (!$start && !$end) {
            // if start & end set to false, all events should be synced
            $events = $ical->events();
        } else if ($start || $end) {

            // either start or end is restricted
            if ($start === false) {
                $now = new \DateTime('today - 5 years');
                $start = $now->format('Y-m-d H:i:s');
            }
            if ($end === false){
                $now = new \DateTime('today + 5 years');
                $end = $now->format('Y-m-d H:i:s');
            }

            $events = $ical->eventsFromRange($start, $end);
        } else {
            $events = $ical->events();
        }

        return $events;
    }

    protected static function getLastModified(Event $event)
    {
        $last_modified = null;
        // first check if lastmodified is set or emtpy... for comparison...
        if(!isset($event->lastmodified) || $event->lastmodified == null) {
            if (isset($event->last_modified) && $event->last_modified != null) {
                $last_modified = CalendarUtils::formatDateTimeToString($event->last_modified);
            }
            else {
                $now = new \DateTime('now');
                $last_modified = $now->format('Y-m-d H:i:s');
                unset($now);
            }
        }
        else {
            $last_modified = CalendarUtils::formatDateTimeToString($event->lastmodified);
        }
        return $last_modified;
    }

    /**
     * @param array $events
     * @return array
     */
    protected static function filterRecurringEvents(array &$events)
    {
        $recurringEvents = [];
        $id_array = [];
        foreach ($events as $eventKey => $event)
        {
            if (!in_array($event->uid, $id_array)) {
                array_push($id_array, $event->uid);
            } else {
                array_push($recurringEvents, $event);
                unset($events[$eventKey]);
            }
        }

        // now we have filtered the double events
        // but we also need the original ones
        foreach ($events as $eventKey => $event)
        {
            foreach ($recurringEvents as $key => $val)
            {
                if ($event->uid == $val->uid) {
                    array_push($recurringEvents, $event);
                    unset($events[$eventKey]);
                } else {
//                    array_push($recurringEvents, $event);
                }
            }
        }

        unset($id_array);
        return $recurringEvents;
    }
}