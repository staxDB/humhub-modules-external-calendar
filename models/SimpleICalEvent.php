<?php


namespace humhub\modules\external_calendar\models;


use humhub\modules\external_calendar\CalendarUtils;
use DateTime;
use ICal\Event;
use Yii;

class SimpleICalEvent extends Event implements ICalEventIF
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
        return $this->last_modified;
    }

    public function getTimeStamp()
    {
        return $this->dtstamp;
    }

    public function getStart()
    {
        // dtstart MUST be included --> https://www.kanzaki.com/docs/ical/dtstart.html
        /*if(!$this->start) {
            $this->start =  CalendarUtils::formatDateTimeToAppTime($this->dtstart);
        }

        return $this->start;*/
        return $this->dtstart;
    }

    public function getEnd()
    {
        // dtend CAN be included. If not, dtend is same DateTime as dtstart --> https://www.kanzaki.com/docs/ical/dtend.html
        /*if(!$this->end) {
            $this->end = empty($this->dtend)
                ? $this->getStart()
                : CalendarUtils::formatDateTimeToAppTime($this->dtend);
        }

        return $this->end;*/

        return empty($this->dtend)
            ? $this->getStart()
            : $this->dtend;
    }

    public function syncWithModel(ExternalCalendarEntry $eventModel = null, $timeZone = null)
    {
        $eventModel = $eventModel ?: new ExternalCalendarEntry();
        $eventModel->uid = $this->getUid();
        $eventModel->title = $this->getTitle();
        $eventModel->description = $this->getDescription();

        if (!empty($this->getRrule())) {
            $eventModel->setRRule(($this->getRrule()));
        }

        $eventModel->location = $this->getLocation();
        $eventModel->last_modified = $this->getLastModified();
        $eventModel->dtstamp = $this->getTimeStamp();
        $eventModel->start_datetime = $this->getStart();
        $eventModel->end_datetime = $this->getEnd();
        $eventModel->time_zone = $timeZone;
        $eventModel->all_day = CalendarUtils::checkAllDay($this->dtstart, $this->dtend);
        return $eventModel;
    }

    public function isAllDay()
    {
        return (new DateTime($this->getStart()))->format('H:i:s') === '00:00:00' && (new DateTime($this->getEnd()))->format('H:i:s') === '00:00:00';
    }

    /**
     * @return string
     */
    public function getRecurrenceId()
    {
        return (isset($this->recurrence_id)) ? $this->recurrence_id : null;
    }
}