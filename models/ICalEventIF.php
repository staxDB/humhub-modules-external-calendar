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
     * @return \DateTimeInterface
     */
    public function getEndDaTetime();

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