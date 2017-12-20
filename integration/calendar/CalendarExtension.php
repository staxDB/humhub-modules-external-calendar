<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\external_calendar\integration\calendar;

use humhub\modules\external_calendar\models\ExternalCalendar;
use Yii;
use yii\base\Object;
use humhub\modules\external_calendar\models\ExternalCalendarEntryQuery;

/**
 * Created by PhpStorm.
 * User: David Born
 * Date: 18.11.2017
 * Time: 15:19
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
        /* @var $meetings Meeting[] */
        $entries = ExternalCalendarEntryQuery::findForEvent($event);

        $items = [];
        foreach ($entries as $entry) {
            $items[] = $entry->getFullCalendarArray();
        }

        $event->addItems(static::ITEM_TYPE_KEY, $items);
    }

}