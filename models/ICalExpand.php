<?php


namespace humhub\modules\external_calendar\models;


use humhub\modules\external_calendar\CalendarUtils;
use Yii;
use yii\base\Model;
use DateTime;
use DateTimeZone;
use humhub\modules\calendar\interfaces\CalendarItemWrapper;
use humhub\modules\calendar\interfaces\VCalendar;
use Sabre\VObject\Component\VEvent;


class ICalExpand extends Model
{
    /**
     * @var ExternalCalendarEntry
     */
    public $event;

    public $saveInstnace = false;

    /**
     * @var \DateTimeZone
     */
    public $targetTimezone;

    /**
     * @var \DateTimeZone
     */
    public $eventTimeZone;

    public function init()
    {
        parent::init();

        if(!$this->targetTimezone) {
            $this->targetTimezone = CalendarUtils::getUserTimeZone();
        } else if(is_string($this->targetTimezone)) {
            $this->targetTimezone = new DateTimeZone($this->targetTimezone);
        }

        if($this->event) {
            $this->eventTimeZone = new DateTimeZone($this->event->time_zone);
        }
    }

    public static function expand(ExternalCalendarEntry $event, DateTime $start, DateTime $end, array &$endResult = [], $save = true)
    {
        $instance = new static(['event' => $event, 'saveInstnace' => $save]);
        return $instance->expandEvent($start, $end, $endResult);
    }

    public static function expandSingle(ExternalCalendarEntry $event, $recurrenceId,  $save = true)
    {
        $tz = new \DateTimeZone($event->time_zone);
        $start = new DateTime($recurrenceId,$tz);
        $end = (new DateTime($recurrenceId, $tz))->modify("+1 minute");

        $instance = new static(['event' => $event, 'saveInstnace' => $save]);
        $result = $instance->expandEvent($start, $end);

        foreach ($result as $recurrence) {
            if($recurrence->recurrence_id === CalendarUtils::cleanRecurrentId($start)) {
                return $recurrence;
            }
        }
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @param array $endResult
     * @return ExternalCalendarEntry[]
     * @throws \Throwable
     */
    public function expandEvent(DateTime $start, DateTime $end, array &$endResult = [])
    {
        if(empty($this->event->rrule)) {
            return [$this->event];
        }

        if(!$end) {
            $end = (new DateTime('now', $this->targetTimezone))->add(new \DateInterval('P2Y'));
        }

        $existingModels = $this->getExistingRecurrences($start, $end);
        $recurrences = $this->calculateRecurrenceInstances($start, $end);
        $this->syncRecurrences($existingModels, $recurrences, $endResult);

        return $endResult;
    }

    private function getExistingRecurrences(DateTime $start, DateTime $end)
    {
        return  $this->event->getRecurrences()->andFilterWhere(
            ['or',
                ['and',
                    ['>=', 'start_datetime', $start->format('Y-m-d H:i:s')],
                    ['<=', 'start_datetime', $end->format('Y-m-d H:i:s')]
                ],
                ['and',
                    ['>=', 'end_datetime', $start->format('Y-m-d H:i:s')],
                    ['<=', 'end_datetime', $end->format('Y-m-d H:i:s')]
                ]
            ])->all();
    }

    private function calculateRecurrenceInstances(DateTime $start, DateTime $end)
    {
        // Note: VObject supports the EXDATE property for exclusions, but not yet the RDATE and EXRULE properties
        // Note: VCalendar expand will translate all dates with time to UTC
        $vCalendar = (new VCalendar())->add(new CalendarItemWrapper(['options' => $this->event->getFullCalendarArray()]));
        $expandedVCalendar = $vCalendar->getInstance()->expand($start, $end, $this->eventTimeZone);
        return $expandedVCalendar->select('VEVENT');
    }

    /**
     * @param ExternalCalendarEntry[} $existingModels
     * @param VEvent[] $recurrences
     * @param $endResult
     */
    private function syncRecurrences(array $existingModels, array $recurrences, &$endResult)
    {
        foreach($recurrences as $vEvent) {
            try {
                $model = null;
                $vEventStart = $vEvent->DTSTART->getDateTime();

                // Check if this recurrence is the first one
                if ($this->event->getStartDateTime() == $vEventStart) {
                    if (!$this->event->recurrence_id) {
                        $this->event->updateAttributes(['recurrence_id' => $this->getRecurrenceId($vEvent)]);
                    }
                    $model = $this->event;
                }

                if (!$model) {
                    $model = $this->findRecurrenceModel($existingModels, $vEvent);
                }

                if (!$model) {
                    $model = $this->event->createRecurrence($vEventStart,
                        $vEvent->DTEND->getDateTime(),
                        $this->getRecurrenceId($vEvent),
                        $this->saveInstnace);
                }

                $endResult[] = $model;
            } catch (\Exception $e) {
                Yii::error($e);
            }
        }
    }

    /**
     * Translates the recurrence-id from the given $vEvent into our format.
     *
     * Note VCalendar expand will translate all dates ot UTC
     */
    private function getRecurrenceId(VEvent $vEvent)
    {
        $recurrence_id = $vEvent->{'RECURRENCE-ID'}->getValue();
        // We only need to translate from UTC to event timezone for non all day events
        $tz = (strrpos($recurrence_id, 'T') === false) ? null : $this->event->time_zone;
        return  CalendarUtils::cleanRecurrentId($vEvent->{'RECURRENCE-ID'}->getValue(), $tz);
    }

    /**
     * @param ExternalCalendarEntry[] $existingModels
     * @param VEvent $vEvent
     * @return mixed|null
     */
    private function findRecurrenceModel(array $existingModels, VEvent $vEvent)
    {
        foreach ($existingModels as $existingModel) {
            if($existingModel->recurrence_id === $this->getRecurrenceId($vEvent)) {
                return $existingModel;
            }
        }

        return null;
    }

}