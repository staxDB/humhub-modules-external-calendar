<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\external_calendar\tests\codeception\unit;

use external_calendar\ExternalCalendarTest;
use humhub\modules\external_calendar\helpers\RRuleHelper;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 17.09.2017
 * Time: 20:25
 */
class RRuleHelperTest extends ExternalCalendarTest
{
    public function testRruleCompareEqual()
    {
        $rruleOld = 'RRULE:FREQ=WEEKLY;BYDAY=TH';
        $rruleNew = 'RRULE:FREQ=WEEKLY;BYDAY=TH';
        $this->assertTrue(RRuleHelper::compare($rruleOld, $rruleNew));
    }

    public function testRruleCompareDiffOrder()
    {
        $rruleOld = 'RRULE:FREQ=WEEKLY;BYDAY=TH';
        $rruleNew = 'RRULE:BYDAY=TH;FREQ=WEEKLY';
        $this->assertTrue(RRuleHelper::compare($rruleOld, $rruleNew));
    }

    public function testRruleCompareNotEqual()
    {
        $rruleOld = 'RRULE:FREQ=WEEKLY;BYDAY=TH';
        $rruleNew = 'RRULE:BYDAY=FR;FREQ=WEEKLY';
        $this->assertFalse(RRuleHelper::compare($rruleOld, $rruleNew));
    }

    public function testCompareRrulesUntilNoFlag()
    {
       $rruleOld = 'RRULE:FREQ=WEEKLY;BYDAY=TH';
       $rruleNew = 'FREQ=WEEKLY;UNTIL=20191002;BYDAY=TH';
       $this->assertFalse(RRuleHelper::compare($rruleOld, $rruleNew));
    }

    public function testCompareRrulesUntilWithFlag()
    {
        $rruleOld = 'RRULE:FREQ=WEEKLY;BYDAY=TH';
        $rruleNew = 'FREQ=WEEKLY;UNTIL=20191002;BYDAY=TH';
        $this->assertTrue(RRuleHelper::compare($rruleOld, $rruleNew, true));
    }

    public function testCompareRrulesUntilEmpty()
    {
        $rruleOld = '';
        $rruleNew = 'FREQ=WEEKLY;UNTIL=20191002;BYDAY=TH';
        $this->assertFalse(RRuleHelper::compare($rruleOld, $rruleNew, true));
    }

    public function testCompareRrulesUntilBothEmpty()
    {
        $rruleOld = '';
        $rruleNew = '';
        $this->assertTrue(RRuleHelper::compare($rruleOld, $rruleNew, true));
    }

    public function testCompareRrulesUntilNullAndEmpty()
    {
        $rruleOld = '';
        $rruleNew = null;
        $this->assertTrue(RRuleHelper::compare($rruleOld, $rruleNew, true));
    }

    public function testCompareRrulesUntilBothNull()
    {
        $rruleOld = null;
        $rruleNew = null;
        $this->assertTrue(RRuleHelper::compare($rruleOld, $rruleNew, true));
    }

    public function testCompareRrulesUntilNull()
    {
        $rruleOld = 'FREQ=WEEKLY;UNTIL=20191002;BYDAY=TH';
        $rruleNew = null;
        $this->assertFalse(RRuleHelper::compare($rruleOld, $rruleNew, true));
    }

}