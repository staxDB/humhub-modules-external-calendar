<?php

namespace humhub\modules\external_calendar\integration\calendar;

use humhub\modules\external_calendar\models\ExternalCalendar;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use humhub\modules\external_calendar\models\ExternalCalendarEntryQuery;
use yii\base\BaseObject;

/**
 * CalendarExtension implements functions for the Events.php file
 *
 * @author David Born ([staxDB](https://github.com/staxDB))
 * @author buddh4 ([buddh4](https://github.com/buddh4))
 */
class CalendarExtension extends BaseObject
{
    /**
     * @param $event \humhub\modules\calendar\interfaces\event\CalendarItemTypesEvent
     * @return mixed
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public static function addItemTypes($event)
    {
        $calendars = self::getCalendarsForEvent($event);

        foreach ($calendars as $calendar) {
            $event->addType($calendar->getItemTypeKey(), $calendar->getFullCalendarArray());
        }
    }

    /**
     * @param $event \humhub\modules\calendar\interfaces\event\CalendarItemsEvent
     * @throws \Throwable
     */
    public static function addItems($event)
    {
        /* @var $entries ExternalCalendarEntry[] */
        $entries = ExternalCalendarEntryQuery::findForEvent($event);

        $calendarsMap = [];
        $itemsMap = [];
        foreach (self::getCalendarsForEvent($event) as $calendar) {
            $calendarsMap[$calendar->id] = $calendar;
            $itemsMap[$calendar->id] = [];
        }


        foreach ($entries as $entry) {
            // Note we only allow visible calendars, there are may entries which visibility is not updated yet (see jobs/UpdateCalendarVisibility).
            if(!isset($calendarsMap[$entry->calendar_id])) {
                continue;
            }

            $calendar = $calendarsMap[$entry->calendar_id];
            $itemsMap[$calendar->id][] = $entry->getFullCalendarArray();
        }

        foreach ($calendarsMap as $id => $calendar) {
            $event->addItems($calendar->getItemTypeKey(), $itemsMap[$id]);
        }
    }

    /**
     * @param \humhub\modules\calendar\interfaces\event\CalendarItemsEvent|\humhub\modules\calendar\interfaces\event\CalendarItemTypesEvent $event
     * @return ExternalCalendar[]
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    private static function getCalendarsForEvent($event)
    {
        return !$event->contentContainer
            ? ExternalCalendar::find()->readable()->all()
            : ExternalCalendar::find()->contentContainer($event->contentContainer)->readable()->all();
    }

}