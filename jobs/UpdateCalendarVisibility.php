<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\external_calendar\jobs;


use humhub\modules\queue\ActiveJob;
use humhub\modules\external_calendar\models\ExternalCalendar;

/**
 * Class UpdateCalendarVisibility
 * @package humhub\modules\external_calendar\jobs
 */
class UpdateCalendarVisibility extends ActiveJob
{
    public $calendarId;

    public function run()
    {

        $calendar = ExternalCalendar::find()->where(['external_calendar.id' => $this->calendarId])->one();

        if(!$calendar) {
            return true;
        }

        foreach ($calendar->entries as $entry) {
            $entry->content->visibility = $calendar->content->visibility;
            $entry->save();
        }

        return true;
    }
}
