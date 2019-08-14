<?php

namespace humhub\modules\external_calendar;

use humhub\modules\calendar\widgets\CalendarControls;
use humhub\modules\calendar\widgets\ContainerConfigMenu;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\helpers\ContentContainerHelper;
use humhub\modules\external_calendar\permissions\ManageEntry;
use humhub\modules\external_calendar\widgets\ExportButton;
use Yii;
use yii\base\WidgetEvent;
use yii\helpers\Url;
use yii\base\BaseObject;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use humhub\modules\external_calendar\integration\calendar\CalendarExtension;
use humhub\modules\external_calendar\jobs\SyncHourly;
use humhub\modules\external_calendar\jobs\SyncDaily;
use humhub\modules\external_calendar\widgets\DownloadIcsLink;

class Events extends BaseObject
{
    /**
     * @inheritdoc
     */
    public static function onBeforeRequest()
    {
        static::registerAutoloader();
    }

    /**
     * Register composer autoloader when Reader not found
     */
    public static function registerAutoloader()
    {
        if (class_exists('\ICal\ICal')) {
            return;
        }

        require Yii::getAlias('@external_calendar/vendor/autoload.php');
    }

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
     * @param $event WidgetEvent
     */
    public static function onContainerConfigMenuInit($event)
    {
        /* @var $container ContentContainerActiveRecord */
        if($event->sender->contentContainer) {
            $event->sender->addItem([
                'label' => Yii::t('ExternalCalendarModule.base', 'External Calendars'),
                'id' => 'tab-calendar-external',
                'url' => $event->sender->contentContainer->createUrl('/external_calendar/calendar/index'),
                'visible' => $event->sender->contentContainer->can(ManageEntry::class),
                'isActive' => (Yii::$app->controller->module
                    && Yii::$app->controller->module->id === 'external_calendar'),
            ]);
        }
    }

    /**
     * @param $event WidgetEvent
     */
    public static function onCalendarControlsInit($event)
    {
        /* @var $controls CalendarControls */
        $controls = $event->sender;

        $controls->addWidget(ExportButton::class, ['container' => $controls->container],  ['sortOrder' => 50]);
    }


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

