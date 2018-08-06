<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\external_calendar\widgets;

use humhub\components\Widget;
use humhub\libs\Html;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use Yii;


/**
 * Class DownloadIcsLink
 * @package humhub\modules\external_calendar\widgets
 */
class DownloadIcsLink extends Widget
{

    /**
     * @var ExternalCalendarEntry
     */
    public $calendarEntry = null;

    public function run()
    {
        if ($this->calendarEntry === null) {
            return;
        }

        return Html::a(Yii::t('ExternalCalendarModule.base', 'Download as ICS file'), $this->calendarEntry->content->container->createUrl('/external_calendar/entry/generateics', ['id' => $this->calendarEntry->id]), ['target' => '_blank']);
    }
}