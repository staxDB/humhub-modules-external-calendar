<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\external_calendar\tests\codeception\unit;

use DateTime;
use external_calendar\ExternalCalendarTest;
use humhub\modules\external_calendar\helpers\CalendarUtils;
use humhub\modules\external_calendar\models\ExternalCalendarEntryQuery;
use humhub\modules\space\models\Space;
use Yii;
use humhub\modules\content\models\Content;
use humhub\modules\external_calendar\models\ExternalCalendar;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use humhub\modules\user\models\User;
/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 17.09.2017
 * Time: 20:25
 */

class ICalImportTest extends ExternalCalendarTest
{
    public function testDuration()
    {
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/testDuration.ics');
        $event = $externalCalendar->entries[0];
        $this->assertFalse($event->isAllDay());

        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-05-14 00:00:00'), $event->getStartDateTime());
        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-05-14 00:15:00'), $event->getEndDateTime());
    }
    public function testStartWithoutTimeNoEnd()
    {
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/startWithoutTimeNoEnd.ics');
        $event = $externalCalendar->entries[0];
        $this->assertTrue($event->isAllDay());

        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-10-30 00:00:00'), $event->getStartDateTime());
        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-10-30 23:59:59'), $event->getEndDateTime());
    }

    public function testStartWithTimeNoEnd()
    {
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/startWithTimeNoEnd.ics');
        $event = $externalCalendar->entries[0];
        $this->assertFalse($event->isAllDay());

        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-10-30 00:00:00'), $event->getStartDateTime());
        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-10-30 00:00:00'), $event->getEndDateTime());
    }

    public function testStartEqEndNoTime()
    {
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/startEqEndNoTime.ics');
        $event = $externalCalendar->entries[0];
        $this->assertTrue($event->isAllDay());

        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-10-30 00:00:00'), $event->getStartDateTime());
        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-10-30 23:59:59'), $event->getEndDateTime());
    }

    public function testStartEqEndZeroTime()
    {
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/startEqEndWithZeroTime.ics');
        $event = $externalCalendar->entries[0];
        $this->assertFalse($event->isAllDay());

        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-10-30 00:00:00'), $event->getStartDateTime());
        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-10-30 00:00:00'), $event->getEndDateTime());
    }
    
    public function testStartEqEndWithTime()
    {
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/startEqEndWithTime.ics');
        $event = $externalCalendar->entries[0];
        $this->assertFalse($event->isAllDay());
        $this->assertFalse($event->isAllDay());

        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-10-30 16:00:00'), $event->getStartDateTime());
        $this->assertEquals( DateTime::createFromFormat(CalendarUtils::DB_DATE_FORMAT, '2019-10-30 16:00:00'), $event->getEndDateTime());
    }

    public function testSimpleEventImport()
    {
        $externalCalendar = $this->initCalendar();

        $this->assertEquals('2.0', $externalCalendar->version);
        $this->assertEquals('Europe/Berlin', $externalCalendar->time_zone);

        $this->assertEquals(1, ExternalCalendarEntry::find()->count());
        $this->assertCount(1, $externalCalendar->entries);

        $event = $externalCalendar->entries[0];

        $this->assertEquals( DateTime::createFromFormat('!Ymd', '20190514'), $event->getStartDateTime());
        $this->assertEquals( DateTime::createFromFormat('!Ymd', '20190514')->setTime(23,59,59), $event->getEndDateTime());

        $this->assertEquals('2019-05-03 16:49:37', $event->content->created_at);
        $this->assertEquals('2019-05-03 16:49:37', $event->content->stream_sort_date);
        $this->assertEquals('xxxxxxxxxxxxxx@google.com', $event->uid);
        $this->assertEquals('Test Event', $event->description);
        $this->assertEquals('Europe/Berlin', $event->time_zone);
        $this->assertEquals('2019-05-03 16:49:37', $event->last_modified);
        $this->assertEquals(true, $event->isAllDay());
    }

    public function testSimpleCalendarUpdate()
    {
        $externalCalendar = $this->initCalendar();

        $firstEvent = $externalCalendar->entries[0];

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/test1Update.ics');
        $externalCalendar->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $this->assertEquals(2, ExternalCalendarEntry::find()->count());
        $this->assertCount(2, $externalCalendar->entries);

        $firstEvent->refresh();

        $this->assertEquals('xxxxxxxxxxxxxx@google.com', $firstEvent->uid);
        $this->assertEquals('Test Event Updated', $firstEvent->description);
        $this->assertEquals('Europe/Berlin', $firstEvent->time_zone);
        $this->assertEquals('2019-06-03 16:49:37', $firstEvent->last_modified);
        $this->assertEquals(true, $firstEvent->isAllDay());
        $this->assertEquals(DateTime::createFromFormat('!Ymd', '20190515'), $firstEvent->getStartDateTime());
        $this->assertEquals(DateTime::createFromFormat('!Ymd', '20190515')->setTime(23,59,59), $firstEvent->getEndDateTime());

        $newEvent = $externalCalendar->entries[1];
        $this->assertEquals('xxxxxxxxxxxxxx2@google.com', $newEvent->uid);
        $this->assertEquals('New Event', $newEvent->description);
        $this->assertEquals('Europe/Berlin', $newEvent->time_zone);
        $this->assertEquals('2019-06-13 16:49:37', $newEvent->last_modified);
        $this->assertEquals(true, $newEvent->isAllDay());
        $this->assertEquals(DateTime::createFromFormat('!Ymd', '20190517'), $newEvent->getStartDateTime());
        $this->assertEquals(DateTime::createFromFormat('!Ymd', '20190517')->setTime(23,59,59), $newEvent->getEndDateTime());

    }

    public function testImportAndDeleteEvent()
    {
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/test1Update.ics');

        $this->assertEquals(2, ExternalCalendarEntry::find()->count());
        $this->assertCount(2, $externalCalendar->entries);

        $externalCalendar->url =  Yii::getAlias('@external_calendar/tests/codeception/data/test1.ics');

        $externalCalendar->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $this->assertEquals(1, ExternalCalendarEntry::find()->count());
        $this->assertCount(1, $externalCalendar->entries);
        $this->assertEquals('xxxxxxxxxxxxxx@google.com', $externalCalendar->entries[0]->uid);
    }


    public function testImportPublicVisibility()
    {
        $this->becomeUser('Admin');
        $externalCalendar =  new ExternalCalendar(User::findOne(1), [
            'allowFiles' => true,
            'title' => 'test',
            'public' => Content::VISIBILITY_PUBLIC,
            'url' => Yii::getAlias('@external_calendar/tests/codeception/data/test1.ics')
        ]);

        $externalCalendar->save();

        $this->assertVisibility($externalCalendar, Content::VISIBILITY_PUBLIC);

        $externalCalendar->url =  Yii::getAlias('@external_calendar/tests/codeception/data/test1Update.ics');
        $externalCalendar->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $externalCalendar->refresh();

        $this->assertVisibility($externalCalendar, Content::VISIBILITY_PUBLIC);
    }

    public function testImportPrivateVisibility()
    {
        $this->becomeUser('Admin');
        $externalCalendar =  new ExternalCalendar(User::findOne(1), [
            'allowFiles' => true,
            'title' => 'test',
            'public' => Content::VISIBILITY_PRIVATE,
            'url' => Yii::getAlias('@external_calendar/tests/codeception/data/test1.ics')
        ]);

        $externalCalendar->save();

        $this->assertVisibility($externalCalendar, Content::VISIBILITY_PRIVATE);

        $externalCalendar->url =  Yii::getAlias('@external_calendar/tests/codeception/data/test1Update.ics');
        $externalCalendar->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $externalCalendar->refresh();

        $this->assertVisibility($externalCalendar, Content::VISIBILITY_PRIVATE);
    }

    /**
     * @throws \yii\base\Exception
     */
    public function testImportChangeVisibility()
    {
        $this->becomeUser('Admin');
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/test1.ics', [
            'public' => Content::VISIBILITY_PRIVATE,
            'event_mode' => ExternalCalendar::EVENT_MODE_ALL,
        ])->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $this->assertVisibility($externalCalendar, Content::VISIBILITY_PRIVATE);

        $externalCalendar->public = Content::VISIBILITY_PUBLIC;
        $externalCalendar->save();
        $externalCalendar->refresh();

        $this->assertVisibility($externalCalendar, Content::VISIBILITY_PUBLIC);
    }

    public function testTimezone1()
    {
        $this->initCalendar('@external_calendar/tests/codeception/data/timezone.ics');

        /** @var $events ExternalCalendarEntry[] */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190821', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190822', new \DateTimeZone('Europe/Berlin'))->modify('-1 Minute'),
            Space::findOne(1));

        $this->assertCount(1, $events);
        $this->assertEquals('Test2', $events[0]->title);
        $this->assertEquals('2019-08-21 17:30:00', $events[0]->getStartDateTime()->setTimezone(CalendarUtils::getUserTimeZone())->format('Y-m-d H:i:s'));
    }

    public function testTimezone2()
    {
        $this->initCalendar('@external_calendar/tests/codeception/data/timezone.ics');

        /** @var $events ExternalCalendarEntry[] */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190822', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190823', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertCount(1, $events);
        $this->assertEquals('Test All Day', $events[0]->title);
        $this->assertEquals('2019-08-22 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
    }


}