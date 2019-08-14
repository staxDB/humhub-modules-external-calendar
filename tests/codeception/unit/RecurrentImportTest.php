<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\external_calendar\tests\codeception\unit;

use external_calendar\ExternalCalendarTest;
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
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');
        $this->assertEquals(1, ExternalCalendarEntry::find()->count());
        $this->assertCount(1, $externalCalendar->entries);

        $recurringEvent = $externalCalendar->entries[0];

        $this->assertEquals('7g0ngjre9a849s5d2sqc6k568o@google.com', $recurringEvent->uid);
        $this->assertEquals('FREQ=WEEKLY;BYDAY=TH', $recurringEvent->rrule);

        // Extract events
        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801'),
            DateTime::createFromFormat('!Ymd', '20190829'),
            Space::findOne(1));

        $this->assertCount(5, $events);
        $this->assertCount(5, ExternalCalendarEntry::find()->all());

        $this->assertEquals('2019-08-01 00:00:00', $events[0]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-01 23:59:59', $events[0]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEmpty($events[0]->parent_event_id);
        $this->assertEquals('20190801', $events[0]->recurrence_id);

        $this->assertEquals('2019-08-08 00:00:00', $events[1]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-08 23:59:59', $events[1]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('Europe/Berlin', $events[1]->time_zone);
        $this->assertEquals('20190808', $events[1]->recurrence_id);

        $this->assertEquals('2019-08-15 00:00:00', $events[2]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-15 23:59:59', $events[2]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('20190815', $events[2]->recurrence_id);

        $this->assertEquals('2019-08-22 00:00:00', $events[3]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-22 23:59:59', $events[3]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('20190822', $events[3]->recurrence_id);

        $this->assertEquals('2019-08-29 00:00:00', $events[4]->getStartDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2019-08-29 23:59:59', $events[4]->getEndDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('20190829', $events[4]->recurrence_id);
    }

    /**
     * In this test we import the simple recurring event (every thursday). And then update the calendar by splitting
     * the first RRULE (add UNTIL RRULE property) and creating a new recurring event (every thursday and friday).
     *
     * This test makes sure that the
     *
     * @throws \Throwable
     */
    public function testSplitRecurringEvent1()
    {
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');

        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801'),
            DateTime::createFromFormat('!Ymd', '20191231'),
            Space::findOne(1));

        $this->assertCount(22, ExternalCalendarEntry::find()->all());

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1Split.ics');
        $externalCalendar->sync();

        // Make sure old recurrences are deleted (this may be change in the future with smarter rrule change analysis
        $this->assertCount(2, ExternalCalendarEntry::find()->all());

        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801'),
            DateTime::createFromFormat('!Ymd', '20190929'),
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
            DateTime::createFromFormat('!Ymd', '20191001'),
            DateTime::createFromFormat('!Ymd', '20191031'),
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
            DateTime::createFromFormat('!Ymd', '20191201'),
            DateTime::createFromFormat('!Ymd', '20191231'),
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

    public function testSplitRecurringEvent2()
    {
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');
        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1Split.ics');
        $externalCalendar->sync();

        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801'),
            DateTime::createFromFormat('!Ymd', '20191031'),
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
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1Split.ics');

        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801'),
            DateTime::createFromFormat('!Ymd', '20191231'),
            Space::findOne(1));


        $this->assertCount(35, ExternalCalendarEntry::find()->all());

        $externalCalendar->url = Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1SplitLimited.ics');
        $externalCalendar->sync();

        /** @var $events ExternalCalendarEntry[] * */
        $events = ExternalCalendarEntryQuery::findForFilter(
            DateTime::createFromFormat('!Ymd', '20190801'),
            DateTime::createFromFormat('!Ymd', '20191231'),
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
}