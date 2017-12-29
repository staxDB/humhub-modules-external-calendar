<?php

namespace humhub\modules\external_calendar\integration\calendar;

use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use Yii;
use yii\base\Object;
use humhub\modules\external_calendar\models\ExternalCalendarEntryQuery;

/**
 * CalendarExtension implements functions for the Events.php file
 *
 * @author davidborn
 */
class CalendarExtension extends Object
{
    /**
     * Default color of external calendar type items.
     */
    const DEFAULT_COLOR = '#DC0E25';

    const ITEM_TYPE_KEY = 'external_calendar';

    /**
     * @param $event \humhub\modules\calendar\interfaces\CalendarItemTypesEvent
     * @return mixed
     */
    public static function addItemTypes($event)
    {
        $event->addType(static::ITEM_TYPE_KEY, [
            'title' => Yii::t('ExternalCalendarModule.base', 'Calendar Extension'),
            'color' => static::DEFAULT_COLOR,
            'icon' => 'fa-calendar-o',
            'format' => 'Y-m-d H:i:s',
        ]);
    }

    /**
     * @param $event \humhub\modules\calendar\interfaces\CalendarItemsEvent
     */
    public static function addItems($event)
    {
        /* @var $entries ExternalCalendarEntry[] */
        $entries = ExternalCalendarEntryQuery::findForEvent($event);

        $items = [];
        foreach ($entries as $entry) {
            $items[] = $entry->getFullCalendarArray();
        }

        $event->addItems(static::ITEM_TYPE_KEY, $items);
    }

}