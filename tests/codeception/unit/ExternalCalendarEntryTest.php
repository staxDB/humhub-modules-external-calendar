<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\external_calendar\tests\codeception\unit;

use external_calendar\ExternalCalendarTest;
use DateTime;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 17.09.2017
 * Time: 20:25
 */
class ExternalCalendarEntryTest extends ExternalCalendarTest
{
    public function testLastModifiedEqualsModel()
    {
        $dummyEvent = new ICalEventDummy(['options' => ['last_modified' => $this->toICalDate(new DateTime('2019-08-15 19:44:00'))]]);
        $model = new ExternalCalendarEntry(['last_modified' => '2019-08-15 19:44:00']);
        $this->assertFalse($model->wasModifiedSince($dummyEvent));
    }

    public function testLastModifiedBeforeModel()
    {
        $dummyEvent = new ICalEventDummy(['options' => ['last_modified' => $this->toICalDate(new DateTime('2019-08-15 19:43:00'))]]);
        $model = new ExternalCalendarEntry(['last_modified' => '2019-08-15 19:44:00']);
        $this->assertFalse($model->wasModifiedSince($dummyEvent));
    }

    public function testLastModifiedAfterModel()
    {
        $dummyEvent = new ICalEventDummy(['options' => ['last_modified' => $this->toICalDate(new DateTime('2019-08-15 19:45:00'))]]);
        $model = new ExternalCalendarEntry(['last_modified' => '2019-08-15 19:44:00']);
        $this->assertTrue($model->wasModifiedSince($dummyEvent));
    }

    public function testLastModifiedNull1()
    {
        $dummyEvent = new ICalEventDummy();
        $model = new ExternalCalendarEntry();
        $this->assertTrue($model->wasModifiedSince($dummyEvent));
    }

    public function testLastModifiedNull2()
    {
        $dummyEvent = new ICalEventDummy(['options' => ['last_modified' => $this->toICalDate(new DateTime('2019-08-15 19:45:00'))]]);
        $model = new ExternalCalendarEntry();
        $this->assertTrue($model->wasModifiedSince($dummyEvent));
    }

    public function testLastModifiedNull3()
    {
        $dummyEvent = new ICalEventDummy();
        $model = new ExternalCalendarEntry(['last_modified' => '2019-08-15 19:44:00']);
        $this->assertTrue($model->wasModifiedSince($dummyEvent));
    }

    private function toICalDate(DateTime $date)
    {
        return date('Ymd\THis\Z', $date->getTimestamp());
    }
}