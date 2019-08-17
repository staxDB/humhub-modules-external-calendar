<?php
namespace humhub\modules\external_calendar\tests\codeception\unit;

use yii\base\Model;
use humhub\modules\external_calendar\models\ICalEventIF;

class ICalEventDummy extends Model implements ICalEventIF
{
    public $options = [];

    private function getOption($key, $default = null)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->getOption('uid');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getOption('title', $this->getSummary());
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->getOption('summary');
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getOption('description');
    }

    /**
     * @return string
     */
    public function getRrule()
    {
        return $this->getOption('rrule');
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->getOption('location');
    }

    /**
     * @return string
     */
    public function getLastModified()
    {
        return $this->getOption('last_modified');
    }

    /**
     * @return string
     */
    public function getTimeStamp()
    {
        return $this->getOption('dtstamp', $this->getOption('time_stamp'));
    }

    /**
     * @return string
     */
    public function getStart()
    {
        return $this->getOption('dtstart', $this->getOption('start'));
    }

    /**
     * @return string
     */
    public function getEnd()
    {
        return $this->getOption('dtend', $this->getOption('end'));
    }

    /**
     * @return bool
     */
    public function isAllDay()
    {
        return $this->getOption('all_day');
    }

    /**
     * @return string
     */
    public function getRecurrenceId()
    {
        return $this->getOption('recurrence_id');
    }

    /**
     * @return string
     */
    public function getExdate()
    {
        return $this->getOption('exdate');
    }

    /**
     * @return array
     */
    public function getExdateArray()
    {
        return (empty($this->getExdate())) ? [] : explode(',', $this->getExdate());
    }
}