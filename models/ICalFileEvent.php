<?php


namespace humhub\modules\external_calendar\models;


use DateTime;
use humhub\modules\external_calendar\CalendarUtils;
use ICal\Event;
use Yii;

class ICalFileEvent extends Event implements ICalEventIF
{

    public function getUid()
    {
        return $this->uid;
    }

    public function getTitle()
    {
        return empty($this->getSummary())
            ? Yii::t('ExternalCalendarModule.model_calendar_entry', '(No Title)')
            : $this->summary;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getRrule()
    {
        return isset($this->rrule) ? $this->rrule : null;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function getLastModified()
    {
        if(isset($this->lastmodified)) {
            return $this->last_modified;
        }

        return null;
    }

    public function getTimeStamp()
    {
        return $this->dtstamp;
    }

    public function getStart()
    {
        return $this->dtstart;
    }

    public function getEnd()
    {
        // dtend CAN be included. If not, dtend is same DateTime as dtstart --> https://www.kanzaki.com/docs/ical/dtend.html
        return empty($this->dtend)
            ? $this->getStart()
            : $this->dtend;
    }

    public function isAllDay()
    {
        return $this->getStartDateTime()->format('H:i:s') === '00:00:00' && $this->getEndDaTetime()->format('H:i:s') === '00:00:00';
    }

    /**
     * @return string
     */
    public function getRecurrenceId()
    {
        if($this->getRrule() && !isset($this->recurrence_id)) {
            CalendarUtils::cleanRecurrentId($this->getStart());
        }

        return isset($this->recurrence_id) ? CalendarUtils::cleanRecurrentId($this->recurrence_id) : null;
    }

    /**
     * @return string
     */
    public function getExdate()
    {
        return isset($this->exdate) ? $this->exdate : null;
    }

    public function getExdateArray()
    {
        $exdateStr = $this->getExdate();
        return empty($exdateStr) ? [] : explode( ',', $exdateStr);
    }

    /**
     * @return \DateTimeInterface
     * @throws \Exception
     */
    public function getStartDateTime()
    {
        return (new DateTime())->setTimestamp($this->dtstart_array[2]);
    }

    /**
     * @return \DateTimeInterface
     * @throws \Exception
     */
    public function getEndDaTetime()
    {
        if(!empty($this->dtend_array)) {
            return (new DateTime())->setTimestamp($this->dtend_array[2]);
        }

        return $this->getStartDateTime();
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }
}