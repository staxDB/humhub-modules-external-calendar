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

class Events extends BaseObjectcd ../e
{

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
     * Defines what to do if cron runs.
     *
     * @param $event
     * @return void
     */
    public static function onCronRun($event)
    {
        if (Yii::$app->controller->action->id == 'hourly') {
            Yii::$app->queue->push( new SyncHourly());
        }
        elseif (Yii::$app->controller->action->id == 'daily') {
            Yii::$app->queue->push( new SyncDaily());
        }

        return;
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

