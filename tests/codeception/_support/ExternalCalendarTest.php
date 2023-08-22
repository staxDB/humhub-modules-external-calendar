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
        \humhub\modules\calendar\Module::registerAutoloader();
        Events::registerAutoloader();
    }

    public function getFileAlias($file): string
    {
        return 'file://' . str_replace('\\', '/', Yii::getAlias($file));
    }

    protected function initCalendar($file = '@external_calendar/tests/codeception/data/test1.ics', $params = [], $asAdmin = true)
    {
        if($asAdmin) {
            $this->becomeUser('Admin');
            Yii::$app->user->getIdentity()->time_zone = 'Europe/Berlin';
        }

        $params = array_merge([
            'allowFiles' => true,
            'title' => 'test',
            'event_mode' => ExternalCalendar::EVENT_MODE_ALL,
            'url' => $this->getFileAlias($file)
        ], $params);

        $externalCalendar = new ExternalCalendar(Space::findOne(1), $params);

        $this->assertTrue($externalCalendar->save());

        $externalCalendar->sync($this->defaultSyncRangeStart, $this->defaultSyncRangeEnd);

        return $externalCalendar;
    }

    protected function assertVisibility(ExternalCalendar $calendar, $visibility)
    {
        $this->assertEquals($visibility, $calendar->content->visibility);

        foreach ($calendar->getEntries()->all() as $entry) {
            $this->assertEquals($visibility, $entry->content->visibility);
        }
    }
}
