<?php


namespace humhub\modules\external_calendar\models;


use DateInterval;
use ICal\ICal;
use Recurr\Rule;
use Yii;

class ICalFile extends ICal implements ICalIF
{
    public $recurrenceRoots;
    public $alteredRecurrences;

    public function __construct($files = false, array $options = array())
    {
        $this->defaultTimeZone = Yii::$app->timeZone;
        $this->skipRecurrence = true;

        parent::__construct($files, $options);
    }

    public function eventsFromRange($rangeStart = null, $rangeEnd = null)
    {
        // Sort events before processing range
        $events = $this->sortEventsWithOrder($this->events(), SORT_ASC);

        if (empty($events)) {
            return array();
        }

        $extendedEvents = array();

        if (!is_null($rangeStart)) {
            try {
                $rangeStart = new \DateTime($rangeStart, new \DateTimeZone($this->defaultTimeZone));
            } catch (\Exception $e) {
                error_log("ICal::eventsFromRange: Invalid date passed ({$rangeStart})");
                $rangeStart = false;
            }
        } else {
            $rangeStart = new \DateTime('now', new \DateTimeZone($this->defaultTimeZone));
        }

        if (!is_null($rangeEnd)) {
            try {
                $rangeEnd = new \DateTime($rangeEnd, new \DateTimeZone($this->defaultTimeZone));
            } catch (\Exception $e) {
                error_log("ICal::eventsFromRange: Invalid date passed ({$rangeEnd})");
                $rangeEnd = false;
            }
        } else {
            $rangeEnd = new \DateTime('now', new \DateTimeZone($this->defaultTimeZone));
            $rangeEnd->modify('+20 years');
        }

        // If start and end are identical and are dates with no times...
        if ($rangeEnd->format('His') == 0 && $rangeStart->getTimestamp() == $rangeEnd->getTimestamp()) {
            $rangeEnd->modify('+1 day');
        }

        $rangeStart = $rangeStart->getTimestamp();
        $rangeEnd = $rangeEnd->getTimestamp();

        $findRecurrences = $this->recurrenceRoots === null;

        if ($findRecurrences) {
            $this->recurrenceRoots = [];
            $this->alteredRecurrences = [];
        }

        foreach ($events as $anEvent) {
            $eventStart = $anEvent->dtstart_array[2];
            $eventEnd = (isset($anEvent->dtend_array[2])) ? $anEvent->dtend_array[2] : null;

            if ($findRecurrences) {
                $this->checkForRecurrence($anEvent);
            }

            if ($this->isWithinRange($eventStart, $eventEnd, $rangeStart, $rangeEnd)) {
                $extendedEvents[] = $anEvent;
            }
        }

        if (empty($extendedEvents)) {
            return array();
        }

        return $extendedEvents;
    }

    /**
     * Returns an array of Events.
     * Every event is a class with the event
     * details being properties within it.
     *
     * @return array
     */
    public function events()
    {
        $array = $this->cal;
        $array = isset($array['VEVENT']) ? $array['VEVENT'] : array();
        $events = array();

        if (!empty($array)) {
            foreach ($array as $event) {
                $events[] = new ICalFileEvent($event);
            }
        }

        return $events;
    }


    /**
     * @return string
     */
    public function getTimeZone()
    {
        return $this->calendarTimeZone();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->calendarName();
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        if (isset($this->cal['VCALENDAR']['VERSION'])) {
            return $this->cal['VCALENDAR']['VERSION'];
        }
    }

    /**
     * e.g.: GREGORIAN
     * @return string
     */
    public function getScale()
    {
        if (isset($this->cal['VCALENDAR']['CALSCALE'])) {
            return $this->cal['VCALENDAR']['CALSCALE'];
        }
    }

    /**
     * @return []
     * @throws \Exception
     */
    public function getEventsFromRange(\DateTime $start, \DateTime $end)
    {
        return $this->eventsFromRange($start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'));
    }

    /**
     * Returns all recurring events active within $start and $end date.
     *
     * Note, this function does not expand the actual recurrences but but only return the root events.
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @return []
     * @throws \Recurr\Exception\InvalidRRule
     */
    public function getRecurringEventsFromRange(\DateTime $start, \DateTime $end)
    {
        $this->checkForRecurrences();
        $result = [];
        foreach ($this->recurrenceRoots as $recurrenceRoot) {
            $recurrenceStart = $recurrenceRoot->dtstart_array[2];
            $rrule = new Rule($recurrenceRoot->rrule);
            $recurrenceEnd = $rrule->getUntil();

            if ($recurrenceEnd) {
                $recurrenceEnd = $recurrenceEnd->getTimestamp();
            } else {
                /**
                 *  In case an infinite recurrence starts after the search interval, we use any time after the recurrence start as end time.
                 *  If an infinite recurrence starts before the search range end, we use any time after the range end as end time.
                 **/
                $recurrenceEnd = max($end->add(new DateInterval('P1D'))->getTimestamp(), $recurrenceStart + 1);
            }

            if ($this->isWithinRange($recurrenceStart, $recurrenceEnd, $start->getTimestamp(), $end->getTimestamp())) {
                $result[] = $recurrenceRoot;
            }
        }

        return $result;
    }

    public function getRecurringEvents()
    {
        $this->checkForRecurrences();

        return $this->recurrenceRoots;
    }

    private function isWithinRange($eventStart, $eventEnd, $rangeStart, $rangeEnd)
    {
        return (($eventStart >= $rangeStart && $eventStart < $rangeEnd)         // Event start date contained in the range
            || ($eventEnd !== null
                && (
                    ($eventEnd > $rangeStart && $eventEnd <= $rangeEnd)     // Event end date contained in the range
                    || ($eventStart < $rangeStart && $eventEnd > $rangeEnd) // Event starts before and finishes after range
                )
            )
        );
    }

    private function checkForRecurrences()
    {
        if ($this->recurrenceRoots !== null) {
            return;
        }

        $this->recurrenceRoots = [];
        $this->alteredRecurrenceInstances = [];

        $events = $this->sortEventsWithOrder($this->events(), SORT_ASC);

        foreach ($events as $event) {
            $this->checkForRecurrence($event);
        }
    }

    /**
     * Checks if the given event is an recurring root event which is still active within $start
     * @param $anEvent ICalEventIF
     * @param $start
     * @param $end
     * @throws \Recurr\Exception\InvalidRRule
     */
    private function checkForRecurrence(ICalEventIF $anEvent)
    {
       $this->checkForAlteredRecurrence($anEvent);

        if (!empty($anEvent->rrule) && empty($anEvent->getRecurrenceId())) {
            $this->recurrenceRoots[] = $anEvent;
        }
    }

    /**
     * @param ICalEventIF $anEvent
     */
    private function checkForAlteredRecurrence(ICalEventIF $anEvent)
    {
        // Check if this is a simple recurrence
        if (!empty($anEvent->getRecurrenceId())) {
            $this->addAlteredRecurrence($anEvent);
            return;
        }

        // Check if the root recurrence instance was altered itself.
        if (isset($this->alteredRecurrenceInstances[$anEvent->getUid()]['altered-event'])) {
            $alteredRecurrenceInstance = $this->alteredRecurrenceInstances[$anEvent->getUid()];
            $alteredEvent = $alteredRecurrenceInstance['altered-event'];
            $key = key($alteredEvent);
            $this->addAlteredRecurrence(new ICalFileEvent($alteredEvent[$key]));
        }
    }

    private function addAlteredRecurrence(ICalEventIF $event)
    {
        if (!isset($this->alteredRecurrences[$event->getUid()])) {
            $this->alteredRecurrences[$event->getUid()] = [];
        }
        $this->alteredRecurrences[$event->getUid()][] = $event;
    }

    /**
     * @return ICalEventIF[]
     */
    public function getAlteredRecurrences($uid)
    {
        if (empty($this->alteredRecurrences[$uid])) {
            return [];
        }

        $result = [];
        foreach ($this->alteredRecurrences[$uid] as $alteredEvent) {
            /** @var ICalEventIF $alteredEvent * */
            $result[] = $alteredEvent;
        }
        return $result;
    }
}