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


    public function __get($name)
    {
        return $this->$name ?? parent::__get($name) ?? null;
    }

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
        return $this->rrule;
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
        if($this->getStartDateTime()->format('H:i:s') !== '00:00:00' || $this->getEndDateTime()->format('H:i:s') !== '00:00:00') {
            return false;
        }


        // If dtstart has a date time value (even if 00:00:00) dtend must not be euqal to dtstart
        return $this->isDateOnlyFormat($this->dtstart) || ($this->getEndDateTime() > $this->getStartDateTime());
    }

    /**
     * @return string
     */
    public function getRecurrenceId()
    {
        $recurrence_id = $this->recurrence_id;
        if ($this->getRrule() && !isset($recurrence_id)) {
            CalendarUtils::cleanRecurrentId($this->getStart());
        }

        return isset($recurrence_id) ? CalendarUtils::cleanRecurrentId($recurrence_id) : null;
    }

    /**
     * @return string
     */
    public function getExdate()
    {
        return $this->exdate;
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
        return $this->startDateTime ?? $this->getDateTimeFromDTArray($this->getDtstartArray());
    }

    private function getDateTimeFromDTArray($dtArr)
    {
        $result = null;
        // We need this since the ICal parser does not ignore timezone values for DATE only values
        if((isset($dtArr[0]['VALUE']) && $dtArr[0]['VALUE'] === 'DATE') || strlen($dtArr[1]) === 8)  {
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
    public function getEndDateTime()
    {
        if ($this->endDateTime) {
            return $this->endDateTime;
        }

        if (!empty($this->getDtendArray())) {
            return $this->endDateTime = $this->getDateTimeFromDTArray($this->getDtendArray());
        }

        $this->endDateTime = clone $this->getStartDateTime();

        $duration_array = $this->duration_array;
        if (!empty($duration_array)) {
            $this->endDateTime->add($duration_array[2]);
        } else if ($this->isDateOnlyFormat($this->dtstart)) {
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

    public function getDtstartArray(): array
    {
        return $this->dtstart_array ?? [];
    }

    public function getDtstartValue($index)
    {
        return $this->getDtstartArray()[$index] ?? null;
    }

    public function getDtendArray(): array
    {
        return $this->dtend_array ?? [];
    }

    public function getDtendValue($index)
    {
        return $this->getDtendArray()[$index] ?? null;
    }
}
