<?php

namespace humhub\modules\external_calendar\models;


use Recurr\Rule;
use Yii;
use DateTime;
use humhub\libs\DbDateValidator;
use humhub\widgets\Label;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\external_calendar\permissions\ManageEntry;
use humhub\modules\external_calendar\widgets\WallEntry;
use humhub\modules\external_calendar\helpers\CalendarUtils;
use DateTimeZone;
use humhub\libs\Html;
use humhub\modules\search\interfaces\Searchable;
use ICal\Event;
use humhub\modules\external_calendar\models\forms\ConfigForm;
use humhub\modules\external_calendar\models\ICS;
use yii\db\StaleObjectException as StaleObjectExceptionAlias;

/**
 * This is the model class for table "external_calendar_entry".
 *
 * The followings are the available columns in table 'external_calendar_entry':
 * @property integer $id
 * @property string $uid
 * @property integer $calendar_id
 * @property string $title
 * @property string $description
 * @property string $location
 * @property string $last_modified
 * @property string $dtstamp
 * @property string $start_datetime
 * @property string $end_datetime It is the moment immediately after the event has ended. For example, if the last full day of an event is Thursday, the exclusive end of the event will be 00:00:00 on Friday!
 * @property string $time_zone
 * @property integer $all_day
 * @property string $rrule
 * @property integer $parent_event_id
 * @property string $recurrence_id
 * @property string $recurrence_until
 * @property integer $is_altered
 * @property  string exdate
 *
 * @property-read  ExternalCalendarEntry $recurrences
 * @property-read ExternalCalendarEntry $parent
 * @property-read ExternalCalendar $calendar
 *
 *
 * @author David Born ([staxDB](https://github.com/staxDB))
 */
class ExternalCalendarEntry extends ContentActiveRecord implements Searchable
{
    /**
     * @inheritdoc
     */
    public $wallEntryClass = WallEntry::class;

    /**
     * @inheritdoc
     */
    public $managePermission = ManageEntry::class;

    /**
     * @inheritdoc
     * set post to stream to false
     */
    public $streamChannel = null;

    /**
     * @inheritdoc
     */
    public $silentContentCreation = true;

    /**
     * @var CalendarDateFormatter
     */
    public $formatter;

    public $canMove = false;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'external_calendar_entry';
    }

    public function init()
    {
        parent::init();
        $this->formatter = new CalendarDateFormatter(['calendarItem' => $this]);
    }

    /**
     * @inheritdoc
     */
    public function getContentName()
    {
        return Yii::t('ExternalCalendarModule.base', "External Event");
    }

    /**
     * @inheritdoc
     */
    public function getContentDescription()
    {
        return $this->title;
    }

    /**
     * @inheritdoc
     */
    public function getIcon()
    {
        return 'fa-calendar';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'start_datetime', 'end_datetime'], 'required'],
            [['start_datetime', 'end_datetime', 'dtstamp', 'last_modified'], 'validateDate'],
            [['all_day'], 'integer'],
            [['title'], 'string', 'max' => 200],
            [['location'], 'string'],
            [['end_datetime'], 'validateEndTime'],
            [['description'], 'safe'],
        ];
    }

    public function validateDate($attribute)
    {
        if(!empty($this->$attribute) && !CalendarUtils::isInDbFormat($this->$attribute)) {
            $this->addError($attribute, "Invalid Date format used for $attribute: ".$this->$attribute);
        }
    }

    /**
     * Validator for the end_datetime field.
     * Execute this after DbDateValidator
     *
     * @param string $attribute attribute name
     * @param array $params parameters
     * @throws \Exception
     */
    public function validateEndTime($attribute, $params)
    {
        if (new DateTime($this->start_datetime) > new DateTime($this->end_datetime)) {
            $this->addError($attribute, Yii::t('ExternalCalendarModule.base', "End time must be after start time!"));
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('ExternalCalendarModule.model_calendar_entry', 'ID'),
            'uid' => Yii::t('ExternalCalendarModule.model_calendar_entry', 'UID'),
            'calendar_id' => Yii::t('ExternalCalendarModule.model_calendar_entry', 'Calendar'),
            'title' => Yii::t('ExternalCalendarModule.model_calendar_entry', 'Title'),
            'description' => Yii::t('ExternalCalendarModule.model_calendar_entry', 'Description'),
            'location' => Yii::t('ExternalCalendarModule.model_calendar_entry', 'Location'),
            'last_modified' => Yii::t('ExternalCalendarModule.model_calendar_entry', 'Last Modified'),
            'dtstamp' => Yii::t('ExternalCalendarModule.model_calendar_entry', 'DT Stamp'),
            'start_datetime' => Yii::t('ExternalCalendarModule.model_calendar_entry', 'Start Datetime'),
            'end_datetime' => Yii::t('ExternalCalendarModule.model_calendar_entry', 'End Datetime'),
            'all_day' => Yii::t('ExternalCalendarModule.model_calendar_entry', 'All Day'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'calendar' => $this->calendar->title,
        ];
    }

    public function beforeSave($insert)
    {
        $this->process();
        return parent::beforeSave($insert);
    }

    private function process()
    {
        $this->setSettings();


        // We removed this since the sync logic is responsible for checking full day events
        /*if (!$this->all_day && CalendarUtils::isFullDaySpan(new DateTime($this->start_datetime), new DateTime($this->end_datetime))) {
            $this->all_day = 1;
        }*/

        $end = new DateTime($this->end_datetime, new DateTimeZone(Yii::$app->timeZone));

        if($this->all_day && ($this->end_datetime === $this->start_datetime)) {
            $end->modify('+1 day');
        }

        if ($this->all_day && $end->format('H:i:s') === '00:00:00') {
            $end->modify('-1 second');
        }

        $this->end_datetime = $end->format('Y-m-d H:i:s');
    }

    public function setSettings()
    {
        $settings = ConfigForm::instantiate();

        if ($settings->autopost_entries && (!$this->isRecurringInstance() ||  $this->is_altered)) {
            // set back to autopost true
            $this->streamChannel = 'default';

            // Only create activities etc for upcoming events
            if($this->getStartDateTime() >= new DateTime('now')) {
                $this->silentContentCreation = false;
            }
        }
    }

    public function beforeDelete()
    {
        $this->deleteRecurringInstances();
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }

    /**
     * @inheritdoc
     */
    public function getFullCalendarArray()
    {
        $start = $this->getStartDateTime();
        $end = $this->getEndDateTime();

        if ($this->all_day) {
            $end = $end->modify('+1 second');
        }

        if($this->isRecurringInstance() && empty($this->id)) {
            $viewUrl = $this->content->container->createUrl('/external_calendar/entry/view-recurrence', ['parent_id' => $this->parent_event_id, 'recurrence_id' => $this->recurrence_id, 'cal' => '1']);
            $openUrl = $this->content->container->createUrl('/external_calendar/entry/view-recurrence', ['parent_id' => $this->parent_event_id, 'recurrence_id' => $this->recurrence_id]);
        } else {
            $viewUrl = $this->content->container->createUrl('/external_calendar/entry/view', ['id' => $this->id, 'cal' => '1']);
            $openUrl = $this->content->container->createUrl('/external_calendar/entry/view', ['id' => $this->id]);
        }

        return [
            'uid' => $this->uid,
            'start' => $start,
            'end' => $end,
            'title' => Html::encode($this->getTitle()),
            'editable' => false,
            'allDay' => $this->isAllDay(),
            'rrule' => $this->rrule,
            'exdate' => $this->exdate,
            'viewUrl' => $viewUrl,
            'viewMode' => 'redirect',
            'openUrl' => $openUrl,
            'badge' =>  Label::asColor($this->calendar->color, $this->getContentName())->icon('fa-calendar-o')->right()
        ];
    }

    public function getUrl()
    {
        return $this->content->container->createUrl('/external_calendar/entry/view', ['id' => $this->id]);
    }

    /**
     * @inheritdoc
     */
    public function getTimezone()
    {
        return $this->time_zone;
    }

    public function getStartDateTime()
    {
        return new DateTime($this->start_datetime,  CalendarUtils::getSystemTimeZone());
    }

    public function getEndDateTime()
    {
        return new DateTime($this->end_datetime, CalendarUtils::getSystemTimeZone());
    }

    public function getLastModifiedDateTime()
    {
        return new DateTime($this->last_modified,CalendarUtils::getSystemTimeZone());
    }

    public function getFormattedTime($format = 'long')
    {
        return $this->formatter->getFormattedTime($format);
    }

    /**
     * @return boolean weather or not this item spans exactly over a whole day
     */
    public function isAllDay()
    {
        if ($this->all_day === null) {
            return true;
        }

        return (boolean)$this->all_day;
    }

    public function isRecurringRoot()
    {
        return $this->isRecurring() && !$this->isRecurringInstance();
    }

    public function isRecurring()
    {
        return !empty($this->rrule);
    }

    public function isRecurringInstance()
    {
        return $this->parent_event_id !== null;
    }

    /**
     *
     * @return string the timezone this item was originally saved, note this is
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(self::class, ['id' => 'parent_event_id']);
    }

    /**
     * @param null|array|string $recurrenceIds Filters the result by either an array of recurrence ids or a single recurrence id
     * @return \yii\db\ActiveQuery
     */
    public function getRecurrences($recurrenceIds = null)
    {
        if (is_string($recurrenceIds)) {
            $recurrenceIds = [$recurrenceIds];
        } elseif (is_array($recurrenceIds) && empty($recurrenceIds)) {
            return;
        }

        $query = $this->hasMany(self::class, ['parent_event_id' => 'id'])
            ->andWhere(['calendar_id' => $this->calendar_id])
            ->andWhere(['uid' => $this->uid]);

        if(is_array($recurrenceIds)) {
            array_walk($recurrenceIds, function(&$item) {
                $item = CalendarUtils::cleanRecurrentId($item);
            });
            $query->andWhere(['IN', 'recurrence_id', $recurrenceIds]);
        }

        return $query;
    }

    /**
     * Deletes all recurrent instances of this recurrence root.
     *
     * The $filter parameter can be used to either filter the recurrence instances to delete
     * by
     *
     * - DateTimeInterFace object in order to delete instances starting after the given date.
     * - Array of recurrence ids in order to delete specific recurrences
     * - String of a single recurrence id
     *
     * @param null|\DateTimeInterFace|array|string $filter
     * @throws \Throwable
     * @throws StaleObjectExceptionAlias
     */
    public function deleteRecurringInstances($filter = null)
    {
        if (!$this->isRecurringRoot()) {
            return;
        }

        if (is_array($filter) || is_string($filter)) {
            $instances = $this->getRecurrences($filter)->all();
        } else if ($filter instanceof \DateTimeInterface) {
            $instances = $this->getRecurrences()->andFilterWhere(['>', 'start_datetime', $filter->format('Y-m-d H:i:s')])->all();
        } else {
            $instances = $this->recurrences;
        }

        foreach ($instances as $recurrence) {
            $recurrence->delete();
        }
    }

    /**
     * @param $recurrenceId
     * @return ExternalCalendarEntry
     */
    public function getRecurrenceInstance($recurrenceId)
    {
        if ($this->recurrence_id === $recurrenceId) {
            return $this;
        }
        return $this->getRecurrences()->andWhere(['recurrence_id' => CalendarUtils::cleanRecurrentId($recurrenceId)])->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAlteredRecurrences()
    {
        return $this->getRecurrences()->andWhere(['is_altered' => 1]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCalendar()
    {
        return $this->hasOne(ExternalCalendar::class, ['id' => 'calendar_id']);
    }

    public function generateIcs()
    {
        $module = Yii::$app;
        $timezone = $module->settings->get('timeZone');
        $ics = new ICS($this->title, $this->description, $this->start_datetime, $this->end_datetime, $this->location, null, $timezone, $this->all_day);
        return $ics;
    }

    public function getRecurrenceUntil()
    {
        if (empty($this->rrule)) {
            return null;
        }

        return (new Rule($this->rrule))->getUntil();
    }

    /**
     * @return ExternalCalendarEntry
     */
    public function syncWithICal(ICalEventIF $icalEvent, $timeZone = null, $save = true)
    {
        $this->uid = $icalEvent->getUid();
        $this->title = $icalEvent->getTitle();
        $this->description = $icalEvent->getDescription();

        if (!empty($icalEvent->getRrule())) {
            $this->setRRule(($icalEvent->getRrule()));

            if (!$this->recurrence_id && !$icalEvent->getRecurrenceId()) {
                $this->recurrence_id = CalendarUtils::cleanRecurrentId($icalEvent->getStart());
            }
        }

        if ($icalEvent->getRecurrenceId()) {
            $this->recurrence_id = $icalEvent->getRecurrenceId();
        }

        $this->location = $icalEvent->getLocation();
        $this->last_modified = CalendarUtils::toDBDateFormat($icalEvent->getLastModified());
        $this->dtstamp = CalendarUtils::toDBDateFormat($icalEvent->getTimeStamp());
        $this->start_datetime = CalendarUtils::toDBDateformat($icalEvent->getStartDateTime());
        $this->end_datetime = CalendarUtils::toDBDateFormat($icalEvent->getEndDaTetime());
        $this->exdate = $icalEvent->getExdate();

        if ($timeZone) {
            $this->time_zone = $timeZone;
        }

        $this->all_day = (int) $icalEvent->isAllDay();

        if ($save && !$this->save()) {
            Yii::error('Could not save ical event '.$icalEvent->getUid());
            Yii::error($this->getErrors());
        }

        return $this;
    }

    public function createRecurrence($start, $end, $recurrenceId, $save = true)
    {
        $instance = new static($this->content->container, $this->content->visibility);
        $instance->content->created_by = $this->content->created_by;
        $instance->uid = $this->uid;
        $instance->parent_event_id = $this->id;
        $instance->start_datetime = CalendarUtils::toDBDateFormat($start);
        $instance->end_datetime = CalendarUtils::toDBDateFormat($end);
        $instance->title = $this->title;
        $instance->rrule = $this->rrule;
        $instance->calendar_id = $this->calendar_id;
        $instance->description = $this->description;
        $instance->location = $this->location;
        $instance->last_modified = $this->last_modified;
        $instance->dtstamp = $this->dtstamp;
        $instance->all_day = $this->all_day;
        $instance->time_zone = $this->time_zone;
        $instance->recurrence_id = CalendarUtils::cleanRecurrentId($recurrenceId);

        if($save) {
            $instance->save();
        } else {
            // We at least have to validate in order to trigger date validation/transformation
            $instance->validate();
            $this->process();
        }
        return $instance;
    }

    public function setRRule($rrule)
    {
        if (!empty($rrule)) {
            $this->rrule = $rrule;
            $until = $this->getRecurrenceUntil();
            if ($until) {
                $this->recurrence_until = $until->format('Y-m-d H:i:s');
            } else {
                $this->recurrence_until = null;
            }
        } else {
            $this->rrule = null;
            $this->recurrence_until = null;
        }
    }

    public function wasModifiedSince(ICalEventIF $icalEvent)
    {
        if(!$icalEvent->getLastModified()) {
            return false;
        }

        return !$this->last_modified || CalendarUtils::formatDateTimeToAppTime($icalEvent->getLastModified()) > $this->getLastModifiedDateTime();
    }
}
