<?php

namespace humhub\modules\external_calendar;

use Yii;
use humhub\modules\content\models\Content;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use humhub\modules\external_calendar\models\ExternalCalendar;
use ICal\ICal;
use ICal\Event;


/**
 * Description of SyncUtils
 *
 * @author David Born ([staxDB](https://github.com/staxDB))
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
            $ical = new ICal($url, [
                'defaultSpan' => 2,     // Default value
                'defaultTimeZone' => Yii::$app->timeZone,
                'defaultWeekStart' => 'MO',  // Default value
                'skipRecurrence' => false, // Default value
                'useTimeZoneWithRRules' => false, // Default value
            ]);

            return $ical;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param array $events
     * @param ExternalCalendar $calendar
     * @return bool
     * @throws \yii\base\Exception
     */
    public static function checkAndSubmitModels(array &$events, ExternalCalendar &$calendar)
    {
        if (!isset($events) && !isset($calendar)) {
            return false;
        }
        $dbModels = $calendar->entries;

        // we need to filter recurring events
        $recurringEvents = self::filterRecurringEvents($events);

        // models is an array of ExternalCalendarEntry-Models
        foreach ($events as $eventKey => $event) {
            // first: check if last_modified is set. If not - set it to DateTime('now')
            $last_modified = self::getLastModified($event);

            foreach ($dbModels as $dbModelKey => $dbModel) {
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
                } else {
                    continue;
                }
            }
        }

        // handle recurring events
        foreach ($recurringEvents as $eventKey => $event) {
            // first create entryModel out of event
            $entryModel = self::getModel($event, $calendar);

            // then check list of remaining dbModels for this event
            foreach ($dbModels as $dbModelKey => $dbModel) {
                // compare uid & start_datetime
                if (($dbModel->uid == $entryModel->uid) && ($dbModel->start_datetime == $entryModel->start_datetime)) {
                    // found & update $dbModel
                    $dbModel->setByEvent($event);
//                    $dbModel->content->refresh(); // refresh updated_at
                    $dbModel->save();
                    unset($recurringEvents[$eventKey]);
                    unset($dbModels[$dbModelKey]);    // can't do this here, because of recurring events
                    continue;
                } else {
                    continue;
                }
            }
            unset($entryModel);
        }

        foreach ($recurringEvents as $newModelKey => $newModel) {
            $model = self::getModel($newModel, $calendar);
            // recurring event not in db found --> create new
            $calendar->link('entries', $model);
            unset($recurringEvents[$newModelKey]);
        }

        // link new values
        foreach ($events as $eventKey => $event) {
            $model = self::getModel($event, $calendar);
            if ($model != false) {
                $calendar->link('entries', $model);
                unset($events[$eventKey]);
            } else { // error while creating model... skip event
                unset($events[$eventKey]);  // if we want to test if there was an error, comment this and var_dump($events)
                continue;
            }
        }

        // finally delete items from db
        foreach ($dbModels as $modelKey => $model) {
            $calendar->unlink('entries', $model, true);
            unset($dbModels[$modelKey]);
        }

        return true;
    }

    /**
     * @param $event
     * @param ExternalCalendar $calendar
     * @return bool|ExternalCalendarEntry
     * @throws \yii\base\Exception
     */
    public static function getModel($event, ExternalCalendar $calendar)
    {
        // create ExternalCalendarEntry-model without safe
        if (!$event && !$calendar) {
            return false;
        }

        $model = new ExternalCalendarEntry($calendar->content->container,  $calendar->content->visibility);
        $model->setByEvent($event);
        $model->content->created_by = $calendar->content->created_by;
        return $model;
    }

    /**
     * @param ExternalCalendar $calendarModel
     * @param ICal $ical
     * @return array
     * @throws \Exception
     */
    public static function getEvents(ExternalCalendar $calendarModel, ICal $ical)
    {
        $start = new \DateTime('first day of this month');
        $end = new \DateTime('last day of this month');

        if ($calendarModel->event_mode === ExternalCalendar::EVENT_MODE_ALL) {
            $start = $start->sub(new \DateInterval('P1Y'));
            $end = $end->add(new \DateInterval('P1Y'));
        }

        $start = $start->format('Y-m-d 00:00:00');
        $end = $end->format('Y-m-d 23:59:59');

        return $ical->eventsFromRange($start, $end);
    }

    protected static function getLastModified(Event $event)
    {
        $last_modified = null;
        // first check if lastmodified is set or emtpy... for comparison...
        if (!isset($event->lastmodified) || $event->lastmodified == null) {
            if (isset($event->last_modified) && $event->last_modified != null) {
                $last_modified = CalendarUtils::formatDateTimeToString($event->last_modified);
            } else {
                $now = new \DateTime('now');
                $last_modified = $now->format('Y-m-d H:i:s');
                unset($now);
            }
        } else {
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
        foreach ($events as $eventKey => $event) {
            if(!empty($event->rrule)) {
                array_push($recurringEvents, $event);
                unset($events[$eventKey]);
            }
        }

        return $recurringEvents;


        $id_array = [];



        foreach ($events as $eventKey => $event) {
            if (!in_array($event->uid, $id_array)) {
                array_push($id_array, $event->uid);
            } else {
                array_push($recurringEvents, $event);
                unset($events[$eventKey]);
            }
        }

        // now we have filtered the double events
        // but we also need the original ones
        foreach ($events as $eventKey => $event) {
            foreach ($recurringEvents as $key => $val) {
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