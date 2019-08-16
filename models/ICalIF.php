<?php


namespace humhub\modules\external_calendar\models;


interface ICalIF
{

    /**
     * @return string
     */
    public function getTimeZone();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getVersion();

    /**
     * e.g.: GREGORIAN
     * @return string
     */
    public function getScale();

    /**
     * @return bool
     */
    public function hasEvents();

    /**
     * @return ICalEventIF[]
     */
    public function getEventsFromRange(\DateTime $start, \DateTime $end);

    /**
     * Returns all recurring events active within $start and $end date.
     *
     * Note, this function does not expand the actual recurrences but but only return the root events.
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @return ICalEventIF[]
     */
    public function getRecurringEventsFromRange(\DateTime $start, \DateTime $end);

    /**
     * Returns all recurring events
     *
     * @return ICalEventIF[]
     */
    public function getRecurringEvents();

    /**
     * @return ICalEventIF[]
     */
    public function getAlteredRecurrences($uid);
}