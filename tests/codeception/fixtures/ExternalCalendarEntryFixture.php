<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\external_calendar\tests\codeception\fixtures;

use humhub\modules\external_calendar\models\ExternalCalendar;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use yii\test\ActiveFixture;

class ExternalCalendarEntryFixture extends ActiveFixture
{
    public $modelClass = ExternalCalendarEntry::class;
    public $dataFile = '@external_calendar/tests/codeception/fixtures/data/externalCalendarEntry.php';

    public $depends = [
        ExternalCalendarFixture::class
    ];

}
