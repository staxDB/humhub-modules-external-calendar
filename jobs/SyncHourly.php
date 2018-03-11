<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 *
 * @var $reminder \humhub\modules\task\models\TaskReminder;
 *
 */

namespace humhub\modules\external_calendar\jobs;


use humhub\components\queue\ActiveJob;
use humhub\modules\external_calendar\SyncUtils;
use humhub\modules\external_calendar\models\ExternalCalendar;

class SyncHourly extends ActiveJob
{
    public function run()
    {
        $calendarModels = ExternalCalendar::find()->where(['sync_mode' => ExternalCalendar::SYNC_MODE_HOURLY])->all();

        foreach ($calendarModels as $calendarModel) {
            if (!isset($calendarModel)) {
                continue;
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

        return true;
    }
}
