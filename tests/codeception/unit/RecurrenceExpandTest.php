<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\external_calendar\tests\codeception\unit;

use external_calendar\ExternalCalendarTest;
use humhub\modules\external_calendar\models\ICalExpand;
use humhub\modules\external_calendar\helpers\CalendarUtils;


/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 17.09.2017
 * Time: 20:25
 */
class RecurrenceExpandTest extends ExternalCalendarTest
{
    public function testExpandSingle()
    {
        $externalCalendar = $this->initCalendar('@external_calendar/tests/codeception/data/recurrence1.ics');
        $recurrenceEvent = $externalCalendar->getRecurringEventRoots()[0];
        $recurrence = ICalExpand::expandSingle($recurrenceEvent, '20190808');
        $this->assertNotNull($recurrence);
        $this->assertEquals(CalendarUtils::cleanRecurrentId('20190808'), $recurrence->recurrence_id);
    }

    // Test root is exdate
    // Test deletion of altered recurrence root
    // Test delete exdate
    // Very complex test
}