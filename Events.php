<?php

namespace humhub\modules\external_calendar;

use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use Yii;
use yii\helpers\Url;
use yii\base\Object;
use humhub\modules\external_calendar\models\ExternalCalendar;
use humhub\modules\external_calendar\integration\calendar\CalendarExtension;

class Events extends Object
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
        $calendarModels = ExternalCalendar::find()->all();

            foreach ($calendarModels as $calendarModel) {
                if (!isset($calendarModel)) {
                    continue;
                }
                if (Yii::$app->controller->action->id == 'hourly') {
                    if ($calendarModel->sync_mode == ExternalCalendar::SYNC_MODE_NONE || $calendarModel->sync_mode == ExternalCalendar::SYNC_MODE_DAILY) {
                        continue;
                    }
                } elseif (Yii::$app->controller->action->id == 'daily') {
                    if ($calendarModel->sync_mode == ExternalCalendar::SYNC_MODE_NONE || $calendarModel->sync_mode == ExternalCalendar::SYNC_MODE_HOURLY) {
                        continue;
                    }
                }
                $ical = SyncUtils::createICal($calendarModel->url);
                if (!$ical) {
                    continue;
                }

                // add info to CalendarModel
                $calendarModel->addAttributes($ical);
                $calendarModel->save();

                // check events
                if ($ical->hasEvents()) {
                    // get formatted array
                    $events = SyncUtils::getEvents($calendarModel, $ical);
                    $result = SyncUtils::checkAndSubmitModels($events, $calendarModel);
                    if (!$result) {
                        continue;
                    }
                }
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

}

