<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\external_calendar\tests\codeception\unit;

use external_calendar\ExternalCalendarTest;
use humhub\modules\external_calendar\CalendarUtils;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use humhub\modules\external_calendar\models\ExternalCalendarEntryQuery;
use DateTime;
use humhub\modules\space\models\Space;
use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 17.09.2017
 * Time: 20:25
 */
class RecurrentImportTest extends ExternalCalendarTest
{
    /**
     * Import of a single recurrence event with
     *
     *  RRULE: FREQ=WEEKLY;BYDAY=TH
     *
     * => Every Thursday
     *
     * @throws \Throwable
     */
    public function testSimpleRecurrenceEventImport()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');
        $this->assertEquals(1, ExternalCalendarEntry::find()->count());
        $this->assertCount(1, $externalCalendar->entries);

        $recurringEvent = $externalCalendar->entries[0];

        $this->assertEquals('7g0ngjre9a849s5d2sqc6k568o@google.com', $recurringEvent->uid);
        $this->assertEquals('FREQ=WEEKLY;BYDAY=TH', $recurringEvent->rrule);

        // Extract events
        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertCount(5, $events);
        $this->assertCount(5, ExternalCalendarEntry::find()->all());

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-01 23:59:59', $events[0]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEmpty($events[0]->parent_event_id);
        $this->assertEquals(CalendarUtils::cleanRecurrentId('20190801'), $events[0]->recurrence_id);

        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 23:59:59', $events[1]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('Europe/Berlin', $events[1]->time_zone);
        $this->assertEquals(CalendarUtils::cleanRecurrentId('20190808'), $events[1]->recurrence_id);

        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 23:59:59', $events[2]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals(CalendarUtils::cleanRecurrentId('20190815'), $events[2]->recurrence_id);

        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 23:59:59', $events[3]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals(CalendarUtils::cleanRecurrentId('20190822'), $events[3]->recurrence_id);

        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 23:59:59', $events[4]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals(CalendarUtils::cleanRecurrentId('20190829'), $events[4]->recurrence_id);
    }

    public function testRecurrentEvent1Until()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');
        $oldEvents = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190930', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertCount(9, $oldEvents );
        $this->assertCount(9, ExternalCalendarEntry::find()->all());

        // Stop the recurrence on 01-09-2019
        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1Until.ics');
        $externalCalendar->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        // Make sure all overlapping recurrence instances were deleted and other existing ones remained
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190930', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertCount(5, $events);
        $this->assertCount(5, ExternalCalendarEntry::find()->all());

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-01 23:59:59', $events[0]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEmpty($events[0]->parent_event_id);
        $this->assertEquals('20190801T000000', $events[0]->recurrence_id);
        $this->assertEquals($oldEvents[0]->id, $events[0]->id);

        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 23:59:59', $events[1]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('Europe/Berlin', $events[1]->time_zone);
        $this->assertEquals('20190808T000000', $events[1]->recurrence_id);
        $this->assertEquals($oldEvents[1]->id, $events[1]->id);

        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 23:59:59', $events[2]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('20190815T000000', $events[2]->recurrence_id);
        $this->assertEquals($oldEvents[2]->id, $events[2]->id);

        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 23:59:59', $events[3]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('20190822T000000', $events[3]->recurrence_id);
        $this->assertEquals($oldEvents[3]->id, $events[3]->id);

        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 23:59:59', $events[4]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('20190829T000000', $events[4]->recurrence_id);
        $this->assertEquals($oldEvents[4]->id, $events[4]->id);
    }


    /**
     * In this test we import the simple recurring event (every thursday). And then update the calendar by splitting
     * the first RRULE (add RRULE UNTIL property) and creating a new recurring event (every thursday and friday) after
     * the first event.
     *
     * @throws \Throwable
     */
    public function testSplitRecurringEvent1()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');

        ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20191231', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertCount(22, ExternalCalendarEntry::find()->all());

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1Split.ics');
        $externalCalendar->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        // Make sure old overlapping recurrences are deleted
        $this->assertCount(10, ExternalCalendarEntry::find()->all());

        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190929', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertCount(9, $events);

        // We have our 9 events of the first recurring and the new split recurring event present in db
        $this->assertCount(10, ExternalCalendarEntry::find()->all());

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-01 23:59:59', $events[0]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 23:59:59', $events[1]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 23:59:59', $events[2]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 23:59:59', $events[3]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 23:59:59', $events[4]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-05 00:00:00', $events[5]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-05 23:59:59', $events[5]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-12 00:00:00', $events[6]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-12 23:59:59', $events[6]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-19 00:00:00', $events[7]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-19 23:59:59', $events[7]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-26 00:00:00', $events[8]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-26 23:59:59', $events[8]->getEndDateTime()->format('Y-m-d H:i:s'));

        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20191001', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20191031', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertCount(9, $events);
        $this->assertCount(18, ExternalCalendarEntry::find()->all());

        $this->assertEquals('2019-10-03 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-03 23:59:59', $events[0]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-04 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-04 23:59:59', $events[1]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-10 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-10 23:59:59', $events[2]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-11 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-11 23:59:59', $events[3]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-17 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-17 23:59:59', $events[4]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-18 00:00:00', $events[5]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-18 23:59:59', $events[5]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-24 00:00:00', $events[6]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-24 23:59:59', $events[6]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-25 00:00:00', $events[7]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-25 23:59:59', $events[7]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-31 00:00:00', $events[8]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-31 23:59:59', $events[8]->getEndDateTime()->format('Y-m-d H:i:s'));

        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20191201', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20191231', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertCount(8, $events);

        $this->assertEquals('2019-12-05 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-12-05 23:59:59', $events[0]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-12-06 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-12-06 23:59:59', $events[1]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-12-12 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-12-12 23:59:59', $events[2]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-12-13 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-12-13 23:59:59', $events[3]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-12-19 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-12-19 23:59:59', $events[4]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-12-20 00:00:00', $events[5]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-12-20 23:59:59', $events[5]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-12-26 00:00:00', $events[6]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-12-26 23:59:59', $events[6]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-12-27 00:00:00', $events[7]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-12-27 23:59:59', $events[7]->getEndDateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * This test also splits the first recurring event in 2 (as in testSplitRecurringEvent1) but we use a wider search
     * range from August to October to test a search range spanning over multiple recurring events.
     *
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function testSplitRecurringEvent2()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');
        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1Split.ics');
        $externalCalendar->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20191031', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertCount(18, $events);
        $this->assertCount(18, ExternalCalendarEntry::find()->all());

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-01 23:59:59', $events[0]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 23:59:59', $events[1]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 23:59:59', $events[2]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 23:59:59', $events[3]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 23:59:59', $events[4]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-05 00:00:00', $events[5]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-05 23:59:59', $events[5]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-12 00:00:00', $events[6]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-12 23:59:59', $events[6]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-19 00:00:00', $events[7]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-19 23:59:59', $events[7]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-26 00:00:00', $events[8]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-26 23:59:59', $events[8]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-03 00:00:00', $events[9]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-03 23:59:59', $events[9]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-04 00:00:00', $events[10]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-04 23:59:59', $events[10]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-10 00:00:00', $events[11]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-10 23:59:59', $events[11]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-11 00:00:00', $events[12]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-11 23:59:59', $events[12]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-17 00:00:00', $events[13]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-17 23:59:59', $events[13]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-18 00:00:00', $events[14]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-18 23:59:59', $events[14]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-24 00:00:00', $events[15]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-24 23:59:59', $events[15]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-25 00:00:00', $events[16]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-25 23:59:59', $events[16]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-31 00:00:00', $events[17]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-31 23:59:59', $events[17]->getEndDateTime()->format('Y-m-d H:i:s'));
    }

    public function testEndSplittedRecurringEvent()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1Split.ics');

        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20191231', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));


        $this->assertCount(35, ExternalCalendarEntry::find()->all());

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1SplitLimited.ics');
        $externalCalendar->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20191231', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertCount(27, ExternalCalendarEntry::find()->all());

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-01 23:59:59', $events[0]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 23:59:59', $events[1]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 23:59:59', $events[2]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 23:59:59', $events[3]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 23:59:59', $events[4]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-05 00:00:00', $events[5]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-05 23:59:59', $events[5]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-12 00:00:00', $events[6]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-12 23:59:59', $events[6]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-19 00:00:00', $events[7]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-19 23:59:59', $events[7]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-09-26 00:00:00', $events[8]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-09-26 23:59:59', $events[8]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-03 00:00:00', $events[9]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-03 23:59:59', $events[9]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-04 00:00:00', $events[10]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-04 23:59:59', $events[10]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-10 00:00:00', $events[11]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-10 23:59:59', $events[11]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-11 00:00:00', $events[12]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-11 23:59:59', $events[12]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-17 00:00:00', $events[13]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-17 23:59:59', $events[13]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-18 00:00:00', $events[14]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-18 23:59:59', $events[14]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-24 00:00:00', $events[15]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-24 23:59:59', $events[15]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-25 00:00:00', $events[16]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-25 23:59:59', $events[16]->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertEquals('2019-10-31 00:00:00', $events[17]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-10-31 23:59:59', $events[17]->getEndDateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * Thist test should make sure recurrent root events plus its recurrence instances are deleted if not present in an
     * snyc.
     *
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function testDeleteRecurrence1()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1Split.ics');

        // Make sure the sync is not skipped
        $recurrence1 = $externalCalendar->entries[0];
        $recurrence1->last_modified = null;
        $recurrence1->save();

        $this->assertCount(2, ExternalCalendarEntry::find()->all());

        /** @var $events ExternalCalendarEntry[] * */
        ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20191231', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertCount(35, ExternalCalendarEntry::find()->all());

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1.ics');
        $externalCalendar->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $this->assertCount(1, ExternalCalendarEntry::find()->all());
    }

    /**
     * Make sure recurrences which are not within the sync range are also deleted.
     *
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function testDeleteRecurrenceOutOfRange()
    {
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1Split.ics');
        $this->assertCount(2, ExternalCalendarEntry::find()->all());

        // Change start/end and RRULE->until of first event to a date before sync range
        $firstEntry = $externalCalendar->entries[0];
        $firstEntry->setRRule('FREQ=WEEKLY;UNTIL=20161002;WKST=SU;BYDAY=FR,TH');
        $firstEntry->start_datetime = '2016-08-01 00:00:00';
        $firstEntry->end_datetime    = '2016-08-01 23:59:59';
        $firstEntry->save();

        $secondEntry = $externalCalendar->entries[1];
        // Change start/end and RRULE->until of first event to a date after sync range
        $secondEntry->start_datetime = '2020-08-01 00:00:00';
        $secondEntry->end_datetime = '2020-08-01 23:59:59';
        $secondEntry->save();

        // Expand some events of first event
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20160101', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20161231', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertNotEmpty($events);

        // Expand some events of second event
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20210101', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20211231', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertNotEmpty($events);

        // Sync with empty ical, this should remove all entries and recurrences
        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/empty.ics');
        $externalCalendar->save();
        $externalCalendar->refresh();

        $externalCalendar->sync( $this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $this->assertEmpty($externalCalendar->entries);
        $this->assertEmpty(ExternalCalendarEntry::find()->all());
    }

    /**
     * Make sure altered events are respected.
     *
     * This test imports a single recurrence event and expands some events.
     * Then we update the calendar with the same recurrence and one altered recurrence instance.
     *
     * The test makes sure the existing recurrence is overwritten with the altered instance.
     *
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function testRecurrenceWithAlteredEvent1Simple()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));

        $recurrentEvent = $externalCalendar->getRecurringEventRoots()[0];
        $this->assertEquals('20190808T000000', $recurrentEvent->recurrences[0]->recurrence_id);

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1WithAlteredEvent.ics');
        $externalCalendar->sync( $this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $recurrentEvent = $externalCalendar->getRecurringEventRoots()[0];
        $this->assertEquals('20190808T000000', $recurrentEvent->recurrences[0]->recurrence_id);

        $alteredEvent = $recurrentEvent->getRecurrenceInstance('20190808');
        $this->assertEquals('Recurring Test Overwritten', $alteredEvent->getTitle());
        $this->assertEquals('2019-08-09 00:00:00', $alteredEvent->start_datetime);
        $this->assertEquals('2019-08-09 23:59:59', $alteredEvent->end_datetime);

        // Make sure there is only one recurrence instance
        $this->assertCount(1, ExternalCalendarEntry::find()->where(['recurrence_id' => '20190808T000000'])->all());

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-09 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * Same test as testRecurrenceWithAlteredEvent but without recurrence expansion after the first sync.
     *
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function testRecurrenceWithAlteredEvent1NoExpansion()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');
        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1WithAlteredEvent.ics');
        $externalCalendar->sync( $this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $recurrentEvent = $externalCalendar->getRecurringEventRoots()[0];
        $this->assertEquals('20190808T000000', $recurrentEvent->recurrences[0]->recurrence_id);

        $alteredEvent = $recurrentEvent->getRecurrenceInstance('20190808');
        $this->assertEquals('Recurring Test Overwritten', $alteredEvent->getTitle());
        $this->assertEquals('2019-08-09 00:00:00', $alteredEvent->start_datetime);
        $this->assertEquals('2019-08-09 23:59:59', $alteredEvent->end_datetime);

        // Make sure there is only one recurrence instance
        $this->assertCount(1, ExternalCalendarEntry::find()->where(['recurrence_id' => '20190808T000000'])->all());

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-09 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * Same test as `testRecurrenceWithAlteredEvent` but we do not expand after the first import
     *
     * This test makes sure altered events are created if the recurrence instance does not exist in the db yet.
     *
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function testRecurrenceWithAlteredEvent1NonExpanded()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');
        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1WithAlteredEvent.ics');
        $externalCalendar->sync( $this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $recurrentEvent = $externalCalendar->getRecurringEventRoots()[0];

        $alteredEvent = $recurrentEvent->getRecurrenceInstance('20190808');
        $this->assertEquals('Recurring Test Overwritten', $alteredEvent->getTitle());
        $this->assertEquals('2019-08-09 00:00:00', $alteredEvent->start_datetime);
        $this->assertEquals('2019-08-09 23:59:59', $alteredEvent->end_datetime);

        // Make sure there is only one recurrence instance for the altered event
        $this->assertCount(1, ExternalCalendarEntry::find()->where(['recurrence_id' => '20190808T000000'])->all());

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-09 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
    }

    public function testAlteredEvent1Deletion()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');
        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1WithAlteredEvent.ics');
        $externalCalendar->sync( $this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1.ics');
        $externalCalendar->sync( $this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function testRecurrenceWithAlteredEvent2()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');

        ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1WithAlteredEvent2.ics');
        $externalCalendar->sync( $this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertEquals('2019-08-02 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-16 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));

        $this->assertCount(5, ExternalCalendarEntry::find()->all());

        $recurrenceRoot = $externalCalendar->getRecurringEventRoots()[0];

        $this->assertEquals(1, $recurrenceRoot->is_altered);
        $this->assertEquals('20190801T000000', $recurrenceRoot->recurrence_id);
        $this->assertEquals('2019-08-02 00:00:00', $recurrenceRoot->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-02 23:59:59', $recurrenceRoot->getEndDateTime()->format('Y-m-d H:i:s'));

        $otherAlteredEvent = $recurrenceRoot->getRecurrenceInstance('20190815');
        $this->assertEquals(1, $otherAlteredEvent->is_altered);
        $this->assertEquals('20190815T000000', $otherAlteredEvent->recurrence_id);
        $this->assertEquals('2019-08-16 00:00:00', $otherAlteredEvent->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-16 23:59:59', $otherAlteredEvent->getEndDateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * This tests syncs a recurrent event with to altered events and then syncs an ICal with those altered events deleted.
     * One of the altered events is the recurrence root itself.
     *
     * This test makes sure altered events are deleted after the sync.
     *
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function testAlteredEvent2Deletion()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');

        ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1WithAlteredEvent2.ics');
        $externalCalendar->sync( $this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1.ics');
        $externalCalendar->sync( $this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $recurrenceRoot = $externalCalendar->getRecurringEventRoots()[0];

        $this->assertEquals(0, $recurrenceRoot->is_altered);
        $this->assertEquals('20190801T000000', $recurrenceRoot->recurrence_id);
        $this->assertEquals('2019-08-01 00:00:00', $recurrenceRoot->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-01 23:59:59', $recurrenceRoot->getEndDateTime()->format('Y-m-d H:i:s'));

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));

        $otherAlteredEvent = $recurrenceRoot->getRecurrenceInstance('20190815');
        $this->assertEquals(0, $otherAlteredEvent->is_altered);
        $this->assertEquals('20190815T000000', $otherAlteredEvent->recurrence_id);
        $this->assertEquals('2019-08-15 00:00:00', $otherAlteredEvent->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 23:59:59', $otherAlteredEvent->getEndDateTime()->format('Y-m-d H:i:s'));

        $this->assertCount(5, ExternalCalendarEntry::find()->all());
    }

    public function testRecurrentEventWithAlteredEvent2Edited()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1WithAlteredEvent2.ics');

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertEquals('2019-08-02 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-16 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1WithAlteredEvent2Edited.ics');
        $externalCalendar->sync( $this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertEquals('2019-08-03 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-17 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));

        $recurringRoot = $externalCalendar->getRecurringEventRoots()[0];
        $this->assertEquals('2019-08-03 00:00:00', $recurringRoot->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-03 23:59:59', $recurringRoot->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('Recurring Test Overwritten Edited', $recurringRoot->title);
        $this->assertEquals('Root event was altered and edited', $recurringRoot->description);

        $otherEditedEvent = $recurringRoot->getRecurrenceInstance('20190815');
        $this->assertEquals('2019-08-17 00:00:00', $otherEditedEvent->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-17 23:59:59', $otherEditedEvent->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('I was also edited', $otherEditedEvent->title);
        $this->assertEquals('Another altered event edited', $otherEditedEvent->description);

        $this->assertCount(5, ExternalCalendarEntry::find()->all());
    }

    public function testRecurrentEvent1WithExdate()
    {
        Yii::$app->getModule('external_calendar')->autoSaveExpansions = true;

        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');

        $recurrentEvent = $externalCalendar->getRecurringEventRoots()[0];

        ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertNotNull($recurrentEvent->getRecurrenceInstance('20190815'));
        $this->assertNotNull($recurrentEvent->getRecurrenceInstance('20190822'));

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1WithExdate.ics');
        $externalCalendar->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        $this->assertNull($recurrentEvent->getRecurrenceInstance('20190815'));
        $this->assertNull($recurrentEvent->getRecurrenceInstance('20190822'));

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190829', new \DateTimeZone('Europe/Berlin')),
            Space::findOne(1));

        $this->assertNull($recurrentEvent->getRecurrenceInstance('20190815'));
        $this->assertNull($recurrentEvent->getRecurrenceInstance('20190822'));

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertCount(3, ExternalCalendarEntry::find()->all());
    }

    public function testTimezone1()
    {
        $this->initCalendar('@external_calendar/tests/codeception/data/timezone.ics');

        /** @var $events ExternalCalendarEntry[] */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190820', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190821', new \DateTimeZone('Europe/Berlin'))->modify('-1 Minute'),
            Space::findOne(1));

        $this->assertCount(1, $events);
        $this->assertEquals('Recurrent with time', $events[0]->title);
        $this->assertEquals('2019-08-20 19:00:00', $events[0]->getStartDateTime()->setTimezone(CalendarUtils::getUserTimeZone())->format('Y-m-d H:i:s'));

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190827', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190828', new \DateTimeZone('Europe/Berlin'))->modify('-1 Minute'),
            Space::findOne(1));

        $this->assertCount(1, $events);
        $this->assertEquals('Recurrent with time', $events[0]->title);
        $this->assertEquals('2019-08-27 19:00:00', $events[0]->getStartDateTime()->setTimezone(CalendarUtils::getUserTimeZone())->format('Y-m-d H:i:s'));
    }

    public function testTimezone2()
    {
        Yii::$app->timeZone = 'Europe/Berlin';
        $this->initCalendar('@external_calendar/tests/codeception/data/timezone.ics');

        /** @var $events ExternalCalendarEntry[] */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190820', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190821', new \DateTimeZone('Europe/Berlin'))->modify('-1 Minute'),
            Space::findOne(1));

        $this->assertCount(1, $events);
        $this->assertEquals('Recurrent with time', $events[0]->title);
        $this->assertEquals('2019-08-20 19:00:00', $events[0]->getStartDateTime()->setTimezone(CalendarUtils::getUserTimeZone())->format('Y-m-d H:i:s'));

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190827', new \DateTimeZone('Europe/Berlin')),
            DateTime::createFromFormat('!Ymd', '20190828', new \DateTimeZone('Europe/Berlin'))->modify('-1 Minute'),
            Space::findOne(1));

        $this->assertCount(1, $events);
        $this->assertEquals('Recurrent with time', $events[0]->title);
        $this->assertEquals('2019-08-27 19:00:00', $events[0]->getStartDateTime()->setTimezone(CalendarUtils::getUserTimeZone())->format('Y-m-d H:i:s'));
    }


    // Test root is exdate
    // Test deletion of altered recurrence root
    // Test delete exdate
    // Very complex test
}