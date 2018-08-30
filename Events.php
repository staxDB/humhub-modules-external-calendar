<?php

namespace humhub\modules\external_calendar;

use Yii;
use yii\helpers\Url;
use yii\base\BaseObject;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use humhub\modules\external_calendar\integration\calendar\CalendarExtension;
use humhub\modules\external_calendar\jobs\SyncHourly;
use humhub\modules\external_calendar\jobs\SyncDaily;
use humhub\modules\external_calendar\widgets\DownloadIcsLink;
use humhub\modules\stream\models\WallStreamQuery;
use humhub\modules\stream\widgets\WallStreamFilterNavigation;
use humhub\modules\external_calendar\models\filters\ExternalCalendarStreamFilter;

class Events extends BaseObject
{
    const FILTER_BLOCK_EXTERNAL_CALENDAR = 'external_calendar';
    const FILTER_EXTERNAL_CALENDAR = 'external_calendar';


    /**
     * @param $event \humhub\modules\calendar\interfaces\CalendarItemTypesEvent
     * @return mixed
     */
    public static function onGetCalendarItemTypes($event)
    {
        $contentContainer = $event->contentContainer;

        if (!$contentContainer || $contentContainer->isModuleEnabled('external_calendar')) {
            CalendarExtension::addItemTypes($event);
        }
    }


    /**
     * @param $event
     */
    public static function onFindCalendarItems($event)
    {
        $contentContainer = $event->contentContainer;

        if (!$contentContainer || $contentContainer->isModuleEnabled('external_calendar')) {
            CalendarExtension::addItems($event);
        }
    }


    /**
     * Defines what to do if admin menu is initialized.
     *
     * @param $event
     */
    public static function onAdminMenuInit($event)
    {
        $event->sender->addItem(array(
            'label' => "external_calendar",
            'url' => Url::to(['/external_calendar/admin']),
            'group' => 'manage',
            'icon' => '<i class="fa fa-certificate" style="color: #6fdbe8;"></i>',
            'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'external_calendar' && Yii::$app->controller->id == 'admin'),
            'sortOrder' => 99999,
        ));
    }

    /**
     * Defines what to do if hourly cron runs.
     *
     * @param $event
     * @return void
     */
    public static function onCronHourlyRun($event)
    {
        Yii::$app->queue->push( new SyncHourly());
    }

    /**
     * Defines what to do if daily cron runs.
     *
     * @param $event
     * @return void
     */
    public static function onCronDailyRun($event)
    {
        Yii::$app->queue->push( new SyncDaily());
    }

    public static function onStreamFilterBeforeRun($event)
    {
//        $settings = EditForm::instantiate();
//        if (!$settings->showFilters) {
//            return;
//        }
        /** @var $wallFilterNavigation WallStreamFilterNavigation */
        $wallFilterNavigation = $event->sender;

        // Add a new filter block to the last filter panel
        $wallFilterNavigation->addFilterBlock(static::FILTER_BLOCK_EXTERNAL_CALENDAR, [
            'title' => Yii::t('ExternalCalendarModule.base', 'External Calendar'),
            'sortOrder' => 400
        ], WallStreamFilterNavigation::PANEL_POSITION_LEFT);

        // Add the filter to the new filter block
        $wallFilterNavigation->addFilter([
            'id' => ExternalCalendarStreamFilter::FILTER_SHOW_ENTRIES,
            'title' => Yii::t('ExternalCalendarModule.models', 'Include hidden entries'),
            'sortOrder' => 100
        ],static::FILTER_BLOCK_EXTERNAL_CALENDAR);

        // Add the filter to the new filter block
        $wallFilterNavigation->addFilter([
            'id' => ExternalCalendarStreamFilter::FILTER_SHOW_CALENDARS,
            'title' => Yii::t('ExternalCalendarModule.models', 'Include hidden calendars'),
            'sortOrder' => 200
        ],static::FILTER_BLOCK_EXTERNAL_CALENDAR);
    }

    public static function onStreamFilterBeforeFilter($event)
    {
//        $settings = EditForm::instantiate();
//        if (!$settings->showFilters) {
//            return;
//        }
        /** @var $streamQuery WallStreamQuery */
        $streamQuery = $event->sender;

        // Add a new filterHandler to WallStreamQuery
        $streamQuery->filterHandlers[] = ExternalCalendarStreamFilter::class;
    }



    /**
     * Callback to validate module database records.
     *
     * @param Event $event
     * @throws \Exception
     */
    public static function onIntegrityCheck($event)
    {
        $integrityController = $event->sender;
        $integrityController->showTestHeadline("External Calendar Module - Entries (" . ExternalCalendarEntry::find()->count() . " entries)");
        foreach (ExternalCalendarEntry::find()->joinWith('calendar')->all() as $entry) {
            if ($entry->calendar === null) {
                if ($integrityController->showFix("Deleting external calendar entry id " . $entry->id . " without existing calendar!")) {
                    $entry->delete();
                }
            }
        }
    }

    public static function onWallEntryLinks($event)
    {
        if ($event->sender->object instanceof ExternalCalendarEntry) {
            $event->sender->addWidget(DownloadIcsLink::class, ['calendarEntry' => $event->sender->object]);
        }
    }

}

