<?php


namespace humhub\modules\external_calendar\models;


use DateTime;
use humhub\modules\external_calendar\helpers\CalendarUtils;
use ICal\Event;
use Yii;

/**
 * Class ICalFileEvent
 * @package humhub\modules\external_calendar\models
 *
 * @property-read array $dtstart_array
 * @property-read array $dtend_array
 */
class ICalFileEvent extends Event implements ICalEventIF
{
    protected $startDateTime;
    protected $endDateTime;


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
        if(isset($this->last_modified)) {
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
        return $this->dtend;
    }

    public function isAllDay()
    {
        // If one of dtstart or dtend has actual time value this can't be an all day event
        if($this->getStartDateTime()->format('H:i:s') !== '00:00:00' || $this->getEndDaTetime()->format('H:i:s') !== '00:00:00') {
            return false;
        }


        // If dtstart has a date time value (even if 00:00:00) dtend must not be euqal to dtstart
        return $this->isDateOnlyFormat($this->dtstart) || ($this->getEndDatetime() > $this->getStartDateTime());
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
     * @return \DateTime
     * @throws \Exception
     */
    public function getStartDateTime()
    {
        if(!$this->startDateTime) {
            $this->startDateTime = $this->getDateTimeFromDTArray($this->dtstart_array);
        }

        return $this->startDateTime;
    }

    private function getDateTimeFromDTArray($dtArr)
    {
        $result = nulL;
        // We need this since the ICal parser does not ignore timezone values for DATE only values
        if(isset($dtArr[0]['VALUE']) && $dtArr[0]['VALUE'] === 'DATE' || strlen($dtArr[1]) === 8)  {
            $result = DateTime::createFromFormat(CalendarUtils::ICAL_DATE_FORMAT, $dtArr[1])->setTime(0,0,0);
        }

        if(!$result) {
            $result = (new DateTime())->setTimestamp($dtArr[2]);
        }

        return $result;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getEndDatetime()
    {
        if($this->endDateTime) {
            return $this->endDateTime;
        }

        if(!empty($this->dtend_array)) {
            return $this->endDateTime = $this->getDateTimeFromDTArray($this->dtend_array);
        }

        $this->endDateTime = clone $this->getStartDateTime();

        if(!empty($this->duration_array)) {
            $this->endDateTime->add($this->duration_array[2]);
        } else if($this->isDateOnlyFormat($this->dtstart)) {
            // https://tools.ietf.org/html/rfc5545#page-54
            $this->endDateTime->modify('+1 day');
        }

        return $this->endDateTime;
    }

    private function isDateOnlyFormat($icalDate)
    {
        return !$this->isDateTimeFormat($icalDate);
    }

    private function isDateTimeFormat($icalDate)
    {
        if(is_array($icalDate)) {
            $icalDate = $icalDate[2];
        }

        return strrpos($icalDate, 'T') > 0;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }
}