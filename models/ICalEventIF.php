<?php


namespace humhub\modules\external_calendar\models;


interface ICalEventIF
{
    /**
     * @return string
     */
    public function getUid();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string
     */
    public function getSummary();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return string
     */
    public function getRrule();

    /**
     * @return string
     */
    public function getLocation();

    /**
     * @return string
     */
    public function getLastModified();

    /**
     * @return mixed
     */
    public function getCreated();

    /**
     * @return string
     */
    public function getTimeStamp();

    /**
     * @return string
     */
    public function getStart();

    /**
     * @return \DateTimeInterface
     */
    public function getStartDateTime();

    /**
     * @return string
     */
    public function getEnd();

    /**
     *
     * For cases where a "VEVENT" calendar component specifies a "DTSTART" property with a DATE value type
     * but no "DTEND" nor "DURATION" property, the event's duration is taken to be one day. For cases where a "VEVENT"
     * calendar component specifies a "DTSTART" property with a DATE-TIME value type but no "DTEND" property, the event
     * ends on the same calendar date and time of day specified by the "DTSTART" property.
     *
     * @return \DateTimeInterface
     * @see https://tools.ietf.org/html/rfc5545#page-54
     */
    public function getEndDateTime();

    /**
     * @return bool
     */
    public function isAllDay();

    /**
     * @return string
     */
    public function getRecurrenceId();

    /**
     * @return string
     */
    public function getExdate();

    /**
     * @return string[]
     */
    public function getExdateArray();
}