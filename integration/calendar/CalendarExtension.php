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
     * @param $event \humhub\modules\calendar\interfaces\CalendarItemTypesEvent
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
     * @param $event \humhub\modules\calendar\interfaces\CalendarItemsEvent
     * @throws \Throwable
     */
    public static function addItems($event)
    {
        /* @var $entries ExternalCalendarEntry[] */
        $entries = ExternalCalendarEntryQuery::findForEvent($event);

        $calendarsMap = [];
        foreach (self::getCalendarsForEvent($event) as $calendar) {
            $calendarsMap[$calendar->id] = $calendar;
        }

        $items = [];
        foreach ($entries as $entry) {
            $calendar = (isset($calendarsMap[$entry->calendar_id]))
                ? $calendarsMap[$entry->calendar_id]
                : $entry->calendar;

            if(!$calendar) {
                continue;
            }

            $items[] = $entry->getFullCalendarArray();
        }
        $event->addItems($calendar->getItemTypeKey(), $items);
    }

    /**
     * @param \humhub\modules\calendar\interfaces\CalendarItemsEvent|\humhub\modules\calendar\interfaces\CalendarItemTypesEvent $event
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