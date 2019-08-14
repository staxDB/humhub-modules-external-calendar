<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\external_calendar\tests\codeception\fixtures;

use humhub\modules\external_calendar\models\ExternalCalendar;
use yii\test\ActiveFixture;

class ExternalCalendarFixture extends ActiveFixture
{
    public $modelClass = ExternalCalendar::class;
    public $dataFile = '@external_calendar/tests/codeception/fixtures/data/externalCalendar.php';

}
