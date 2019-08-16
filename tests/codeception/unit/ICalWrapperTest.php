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
use humhub\modules\external_calendar\models\SimpleICal;
use humhub\modules\external_calendar\models\SimpleICalEvent;
use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 17.09.2017
 * Time: 20:25
 */
class ICalWrapperTest extends ExternalCalendarTest
{
    /**
     * This test makes sure that the `getEventsFromRange()` and `getRecurringEventsFromRange` call includes both recurring root events from since the
     * search interval includes both start times of the recurrent events.
     *
     * @throws \Recurr\Exception\InvalidRRule
     */
    public function testIncludingRecurringEvent()
    {
        $cal = new SimpleICal(Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1Split.ics'));
        $events = $cal->getEventsFromRange(DateTime::createFromFormat('!Ymd', '20190801'), DateTime::createFromFormat('!Ymd', '20191212'));
        $this->assertCount(2, $events);

        $recurringEvents = $cal->getRecurringEventsFromRange(DateTime::createFromFormat('!Ymd', '20190801'), DateTime::createFromFormat('!Ymd', '20191212'));
        $this->assertCount(2, $recurringEvents);
    }

    /**
     * This test makes sure that `getEventsFromRange()` and `getRecurringEventsFromRange` call include only the first recurring root event, since the second
     * recurring event starts after the search interval
     * @throws \Recurr\Exception\InvalidRRule
     */
    public function testRecurringEventAfterRange()
    {
        $cal = new SimpleICal(Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1Split.ics'));
        $events = $cal->getEventsFromRange(DateTime::createFromFormat('!Ymd', '20190801'), DateTime::createFromFormat('!Ymd', '20190912'));
        $this->assertCount(1, $events);
        $this->assertEquals('7g0ngjre9a849s5d2sqc6k568o@google.com', $events[0]->uid);

        $recurringEvents = $cal->getRecurringEventsFromRange(DateTime::createFromFormat('!Ymd', '20190801'), DateTime::createFromFormat('!Ymd', '20190912'));
        $this->assertCount(1, $recurringEvents);
        $this->assertEquals('7g0ngjre9a849s5d2sqc6k568o@google.com', $recurringEvents[0]->uid);
    }

    /**
     * This test makes sure that `getEventsFromRange()` and `getRecurringEventsFromRange` call include only the second recurring root event, since the first
     * recurring event stops before the search interval
     * @throws \Recurr\Exception\InvalidRRule
     */
    public function testRecurringEventStopsBeforeRange()
    {
        $cal = new SimpleICal(Yii::getAlias('@external_calendar/tests/codeception/data/recurrence1Split.ics'));
        $events = $cal->getEventsFromRange(DateTime::createFromFormat('!Ymd', '20191003'), DateTime::createFromFormat('!Ymd', '20191005'));
        $this->assertCount(1, $events);
        $this->assertEquals('chhm8cr474q34b9mc9j6ab9k71hmab9p68r36bb3ccpjgp9i6di6achi60@google.com', $events[0]->uid);

        $recurringEvents = $cal->getRecurringEventsFromRange(DateTime::createFromFormat('!Ymd', '20191003'), DateTime::createFromFormat('!Ymd', '20191005'));
        $this->assertCount(1, $recurringEvents);
        $this->assertEquals('chhm8cr474q34b9mc9j6ab9k71hmab9p68r36bb3ccpjgp9i6di6achi60@google.com', $recurringEvents[0]->uid);
    }

    /**
     * This test makes sure that `getRecurringEventsFromRange` also includes infinit events starting before the search range ends. The first recurring event
     * is not included in the `getEventsFromRange()` call, since the root recurrence event is outside of the search range.
     * @throws \Recurr\Exception\InvalidRRule
     */
    public function testIncludeStartingRecurrenceOutsideOfRange()
    {
        $cal = new SimpleICal(Yii::getAlias('@external_calendar/tests/codeception/data/twoIndependentRecurrences.ics'));
        $events = $cal->getEventsFromRange(DateTime::createFromFormat('!Ymd', '20191003'), DateTime::createFromFormat('!Ymd', '20191005'));
        $this->assertCount(1, $events);
        $this->assertEquals('chhm8cr474q34b9mc9j6ab9k71hmab9p68r36bb3ccpjgp9i6di6achi60x@google.com', $events[0]->uid);

        $this->assertInstanceOf(SimpleICalEvent::class, $events[0]);

        $recurringEvents = $cal->getRecurringEventsFromRange(DateTime::createFromFormat('!Ymd', '20191003'), DateTime::createFromFormat('!Ymd', '20191005'));
        $this->assertCount(2, $recurringEvents);
        $this->assertEquals('7g0ngjre9a849s5d2sqc6k568ox@google.com', $recurringEvents[0]->uid);
        $this->assertEquals('chhm8cr474q34b9mc9j6ab9k71hmab9p68r36bb3ccpjgp9i6di6achi60x@google.com', $recurringEvents[1]->uid);
        $this->assertInstanceOf(SimpleICalEvent::class, $recurringEvents[0]);
    }

    public function testICalEventAllDay()
    {
        $cal = new SimpleICal(Yii::getAlias('@external_calendar/tests/codeception/data/twoIndependentRecurrences.ics'));
        $events = $cal->getEventsFromRange(DateTime::createFromFormat('!Ymd', '20191003'), DateTime::createFromFormat('!Ymd', '20191005'));
        $this->assertEquals('chhm8cr474q34b9mc9j6ab9k71hmab9p68r36bb3ccpjgp9i6di6achi60x@google.com', $events[0]->getUid());
        $this->assertTrue($events[0]->isAllDay());
    }

    public function testICalEventNonAllDay()
    {
        $cal = new SimpleICal(Yii::getAlias('@external_calendar/tests/codeception/data/test1WithTime.ics'));
        $events = $cal->getEventsFromRange(DateTime::createFromFormat('!Ymd', '20190816'), DateTime::createFromFormat('!Ymd', '20190817'));
        $this->assertEquals('xxxxxxxxxxxxxx@google.com', $events[0]->getUid());
        $this->assertFalse($events[0]->isAllDay());
    }
}