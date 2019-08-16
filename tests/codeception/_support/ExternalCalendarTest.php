<?php


namespace external_calendar;


use DateTime;
use humhub\modules\space\models\Space;
use Yii;
use humhub\modules\external_calendar\Events;
use humhub\modules\external_calendar\models\ExternalCalendar;
use tests\codeception\_support\HumHubDbTestCase;

class ExternalCalendarTest extends HumHubDbTestCase
{
    public $defaultSyncRangeStart;
    public $defaultSyncRangeEnd;

    public function _before()
    {
        $this->defaultSyncRangeStart = DateTime::createFromFormat('!Ymd', '20180101');
        $this->defaultSyncRangeEnd = DateTime::createFromFormat('!Ymd', '20200101');
        Events::registerAutoloader();
    }

    protected function initCalendar($file = '@external_calendar/tests/codeception/data/test1.ics', $mode = ExternalCalendar::EVENT_MODE_ALL, $asAdmin = true)
    {
        if($asAdmin) {
            $this->becomeUser('Admin');
        }

        $externalCalendar = new ExternalCalendar(Space::findOne(1), [
            'allowFiles' => true,
            'title' => 'test',
            'event_mode' => ExternalCalendar::EVENT_MODE_ALL,
            'url' => Yii::getAlias($file)
        ]);

        $this->assertTrue($externalCalendar->save());

        $externalCalendar->sync();

        return $externalCalendar;
    }

    protected function assertVisibility(ExternalCalendar $calendar, $visibility)
    {
        $this->assertEquals($visibility, $calendar->content->visibility);

        foreach ($calendar->entries as $entry) {
            $this->assertEquals($visibility, $entry->content->visibility);
        }
    }
}