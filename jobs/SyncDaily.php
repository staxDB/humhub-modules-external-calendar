<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\external_calendar\jobs;


use humhub\modules\external_calendar\Events;
use humhub\modules\queue\ActiveJob;
use humhub\modules\external_calendar\models\ExternalCalendar;
use Yii;

class SyncDaily extends ActiveJob
{
    public function run()
    {
        Events::registerAutoloader();

        $calendarModels = ExternalCalendar::find()->where(['sync_mode' => ExternalCalendar::SYNC_MODE_DAILY])->all();

        foreach ($calendarModels as $calendarModel) {
            try {
                $calendarModel->sync();
            } catch (\Exception $e) {
                Yii::error($e);
            }
        }

        return true;
    }
}
