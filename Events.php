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
        try {
            static::registerAutoloader();
        } catch (\Throwable $e) {
            Yii::error($e);
        }
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
     * @param $event \humhub\modules\calendar\interfaces\event\CalendarItemTypesEvent
     * @return mixed
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public static function onGetCalendarItemTypes($event)
    {
        try {
            $contentContainer = $event->contentContainer;

            if (!$contentContainer || $contentContainer->moduleManager->isEnabled('external_calendar')) {
                CalendarExtension::addItemTypes($event);
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    /**
     * @param $event WidgetEvent
     */
    public static function onContainerConfigMenuInit($event)
    {
        try {
            /* @var $container ContentContainerActiveRecord */
            $container = $event->sender->contentContainer;
            if($container && $container->moduleManager->isEnabled('external_calendar')) {
                $event->sender->addItem([
                    'label' => Yii::t('ExternalCalendarModule.base', 'External Calendars'),
                    'id' => 'tab-calendar-external',
                    'url' => $container->createUrl('/external_calendar/calendar/index'),
                    'visible' => $container->can(ManageEntry::class),
                    'isActive' => (Yii::$app->controller->module
                        && Yii::$app->controller->module->id === 'external_calendar'),
                ]);
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onFindCalendarItems($event)
    {
        try {
            /* @var ContentContainerActiveRecord $contentContainer */
            $contentContainer = $event->contentContainer;

            if (!$contentContainer || $contentContainer->moduleManager->isEnabled('external_calendar')) {
                CalendarExtension::addItems($event);
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    /**
     * @param $event WidgetEvent
     */
    public static function onCalendarControlsInit($event)
    {
        try {
            /* @var $controls CalendarControls */
            $controls = $event->sender;
            $controls->addWidget(ExportButton::class, ['container' => $controls->container],  ['sortOrder' => 50]);
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    /**
     * Defines what to do if admin menu is initialized.
     *
     * @param $event
     */
    public static function onAdminMenuInit($event)
    {
        try {
            $event->sender->addItem([
                'label' => "external_calendar",
                'url' => Url::to(['/external_calendar/admin']),
                'group' => 'manage',
                'icon' => '<i class="fa fa-certificate" style="color: #6fdbe8;"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'external_calendar' && Yii::$app->controller->id == 'admin'),
                'sortOrder' => 99999,
            ]);
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    /**
     * Defines what to do if hourly cron runs.
     *
     * @param $event
     * @return void
     */
    public static function onCronHourlyRun($event)
    {
        try {
            Yii::$app->queue->push(new SyncHourly());
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    /**
     * Defines what to do if daily cron runs.
     *
     * @param $event
     * @return void
     */
    public static function onCronDailyRun($event)
    {
        try {
            Yii::$app->queue->push( new SyncDaily());
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }


    /**
     * Callback to validate module database records.
     *
     * @param Event $event
     * @throws \Exception
     * @throws \Throwable
     */
    public static function onIntegrityCheck($event)
    {
        try {
            $integrityController = $event->sender;
            $integrityController->showTestHeadline("External Calendar Module - Entries (" . ExternalCalendarEntry::find()->count() . " entries)");
            foreach (ExternalCalendarEntry::find()->joinWith('calendar')->all() as $entry) {
                if ($entry->calendar === null) {
                    if ($integrityController->showFix("Deleting external calendar entry id " . $entry->id . " without existing calendar!")) {
                        $entry->delete();
                    }
                }
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onWallEntryLinks($event)
    {
        try {
            if ($event->sender->object instanceof ExternalCalendarEntry) {
                $event->sender->addWidget(DownloadIcsLink::class, ['calendarEntry' => $event->sender->object]);
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }
}

