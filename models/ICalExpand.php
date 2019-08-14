<?php


namespace humhub\modules\external_calendar\models;


use DateTime;
use humhub\modules\calendar\interfaces\CalendarItemWrapper;
use humhub\modules\calendar\interfaces\VCalendar;
use Sabre\VObject\Component\VEvent;
use yii\base\Model;

class ICalExpand extends Model
{
    /**
     * @var ExternalCalendarEntry
     */
    public $event;

    public static function expand(ExternalCalendarEntry $event, DateTime $start, DateTime $end, array &$endResult = [])
    {
        $instance = new static(['event' => $event]);
        return $instance->expandEvent($start, $end, $endResult);
    }

    public function expandEvent(DateTime $start, DateTime $end, array &$endResult = [])
    {
        if(empty($this->event->rrule)) {
            return [$this->event];
        }

        $existingModels = $this->getExistingRecurrences($start, $end);
        $recurrences = $this->getRecurrences($start, $end);
        $this->syncRecurrences($existingModels, $recurrences, $endResult);

        return $endResult;
    }

    protected static function getStartCriteria(DateTime $date, $eq = '>=')
    {
        return [$eq, 'start_datetime', $date->format('Y-m-d H:i:s')];
    }

    protected static function getEndCriteria(DateTime $date, $eq = '<=')
    {
        return [$eq, 'end_datetime', $date->format('Y-m-d H:i:s')];
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

    private function getRecurrences(DateTime $start, DateTime $end)
    {
        $vCalendar = (new VCalendar())->add(new CalendarItemWrapper(['options' => $this->event->getFullCalendarArray()]));
        /** @var $vEvents VEvent */
        $expandedVCalendar = $vCalendar->getInstance()->expand($start, $end);
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
            $model = null;
            $vEventStart =  $vEvent->DTSTART->getDateTime();

            // Check if this recurrence is the first one
            if($this->event->getStartDateTime() == $vEventStart) {
                if(!$this->event->recurrence_id) {
                    $this->event->updateAttributes(['recurrence_id' =>  $vEvent->{'RECURRENCE-ID'}->getValue() ]);
                }
                $model = $this->event;
            }

            if(!$model) {
                $model = $this->findRecurrenceModel($existingModels, $vEvent);
            }

            if(!$model) {
                $model = $this->event->createRecurrence($vEventStart->format('Y-m-d H:i:s'), $vEvent->DTEND->getDateTime()->format('Y-m-d H:i:s'), $vEvent->{'RECURRENCE-ID'}->getValue());
            }

            $endResult[] = $model;
        }
    }

    /**
     * @param array $existingModels
     * @param VEvent $vEvent
     * @return mixed|null
     */
    private function findRecurrenceModel(array $existingModels, VEvent $vEvent)
    {
        foreach ($existingModels as $existingModel) {
            if($existingModel->recurrence_id === $vEvent->{'RECURRENCE-ID'}->getValue()) {
                return $existingModel;
            }
        }

        return null;
    }

}