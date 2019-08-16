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
use humhub\modules\external_calendar\CalendarUtils;
use DateTimeZone;
use humhub\libs\Html;
use humhub\modules\search\interfaces\Searchable;
use ICal\Event;
use humhub\modules\external_calendar\models\forms\ConfigForm;
use humhub\modules\external_calendar\models\ICS;

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
 *
 * @property ExternalCalendarEntry $recurrences
 * @property ExternalCalendarEntry $parent
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
    public $silentContentCreation = true;

    /**
     * @var CalendarDateFormatter
     */
    public $formatter;

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

        $this->setSettings();
        $this->formatter = new CalendarDateFormatter(['calendarItem' => $this]);
    }

    public function setSettings()
    {
        $settings = ConfigForm::instantiate();

        if ($settings->autopost_entries) {
            // set back to autopost true
            $this->streamChannel = 'default';
            $this->silentContentCreation = false;
        }
    }



    /**
     * @inheritdoc
     */
    public function getContentName()
    {
//        return $this->calendar->title;
        return Yii::t('ExternalCalendarModule.base', "Event");
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
            [['last_modified'], DbDateValidator::class],
            [['dtstamp'], DbDateValidator::class],
            [['start_datetime'], DbDateValidator::class],
            [['end_datetime'], DbDateValidator::class],
            [['all_day'], 'integer'],
            [['title'], 'string', 'max' => 200],
            [['location'], 'string'],
            [['end_datetime'], 'validateEndTime'],
            [['description'], 'safe'],
        ];
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
        if (new DateTime($this->start_datetime) >= new DateTime($this->end_datetime)) {
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
        // TODO: Check is a full day span --> Already done in AdminController->Sync
        if (!$this->all_day && CalendarUtils::isFullDaySpan(new DateTime($this->start_datetime), new DateTime($this->end_datetime))) {
            $this->all_day = 1;
        }

        $end = new DateTime($this->end_datetime, new DateTimeZone(Yii::$app->timeZone));
        if ($this->all_day && $end->format('H:i:s') === '00:00:00') {
//            $date->setTime('23','59','59');
            $end->modify('-1 second');
        }
        $this->end_datetime = $end->format('Y-m-d H:i:s');

        return parent::beforeSave($insert);
    }

    public function beforeDelete()
    {
        foreach ($this->recurrences as $recurrence) {
            $recurrence->delete();
        }

        return parent::beforeDelete();
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
//            $end->setTime('00','00', '00');
        }

        $settings = ConfigForm::instantiate();
        if ($settings->useBadgeTitle) {
            $badgeTitle = Label::asColor($this->calendar->color, Html::encode($this->calendar->title))->icon('fa-calendar-o')->right();
        }
        else {
            $badgeTitle = Label::asColor($this->calendar->color, Yii::t('ExternalCalendarModule.base', "Event"))->icon('fa-calendar-o')->right();
        }


        return [
            'uid' => $this->uid,
            'start' => $start,
            'end' => $end,
            'title' => Html::encode($this->getTitle()),
            'editable' => false,
            'allDay' => $this->isAllDay(),
            'rrule' => $this->rrule,
            'viewUrl' => $this->content->container->createUrl('/external_calendar/entry/view', ['id' => $this->id, 'cal' => '1']),
//            'updateUrl' => $this->content->container->createUrl('/external_calendar/entry/update-ajax', ['id' => $this->id]),
            'openUrl' => $this->content->container->createUrl('/external_calendar/entry/view', ['id' => $this->id]),
            'badge' => $badgeTitle
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
        return new DateTime($this->start_datetime, new DateTimeZone(Yii::$app->timeZone));
    }

    public function getEndDateTime()
    {
        return new DateTime($this->end_datetime, new DateTimeZone(Yii::$app->timeZone));
    }

    public function getLastModifiedDateTime()
    {
        return new DateTime($this->last_modified, new DateTimeZone(Yii::$app->timeZone));
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

    public function isRecurring()
    {
        return !empty($this->rrule);
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
     * @return \yii\db\ActiveQuery
     */
    public function getRecurrences()
    {
        return $this->hasMany(self::class, ['parent_event_id' => 'id'])
            ->andWhere(['calendar_id' => $this->calendar_id])
            ->andWhere(['uid' => $this->uid]);
    }

    /**
     * @param null|DateTime $lastModification
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteRecurringInstances($lastModification = null, $eq = '>=')
    {
        $instances = (!$lastModification)
            ? $this->recurrences
            : $this->getRecurrences()->andFilterWhere([$eq, 'start_datetime', $lastModification->format('Y-m-d H:i:s')])->all();

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
        return $this->getRecurrences()->andWhere(['recurrence_id' => $recurrenceId])->one();
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
        $ics = new ICS($this->title, $this->description,$this->start_datetime, $this->end_datetime, $this->location, null, $timezone, $this->all_day);
        return $ics;
    }

    public function getRecurrenceUntil()
    {
        if(empty($this->rrule)) {
            return null;
        }

        return (new Rule($this->rrule))->getUntil();
    }

    public function syncWithICal(ICalEventIF $icalEvent, $timeZone = null, $save = true)
    {
        $this->uid = $icalEvent->getUid();
        $this->title = $icalEvent->getTitle();
        $this->description = $icalEvent->getDescription();

        if (!empty($icalEvent->getRrule())) {
            $this->setRRule(($icalEvent->getRrule()));
        }

        $this->recurrence_id = $icalEvent->getRecurrenceId();

        $this->location = $icalEvent->getLocation();
        $this->last_modified = CalendarUtils::formatDateTimeToString($icalEvent->getLastModified());
        $this->dtstamp = CalendarUtils::formatDateTimeToString($icalEvent->getTimeStamp());
        $this->start_datetime = CalendarUtils::formatDateTimeToString($icalEvent->getStart());
        $this->end_datetime = CalendarUtils::formatDateTimeToString($icalEvent->getEnd());

        if($timeZone) {
            $this->time_zone = $timeZone;
        }

        $this->all_day = $icalEvent->isAllDay();

        if($save) {
            $this->save();
        }
    }

    public function createRecurrence($start, $end, $recurrenceId)
    {
        $instance = new static($this->content->container, $this->content->visibility);
        $instance->content->created_by = $this->content->created_by;
        $instance->uid = $this->uid;
        $instance->parent_event_id = $this->id;
        $instance->start_datetime = $start;
        $instance->end_datetime = $end;
        $instance->title = $this->title;
        $instance->rrule = $this->rrule;
        $instance->calendar_id = $this->calendar_id;
        $instance->description = $this->description;
        $instance->location = $this->location;
        $instance->last_modified = $this->last_modified;
        $instance->dtstamp = $this->dtstamp;
        $instance->all_day = $this->all_day;
        $instance->time_zone = $this->time_zone;
        $instance->recurrence_id = $recurrenceId;
        $instance->save();
        return $instance;
    }

    public function setRRule($rrule)
    {
        if(!empty($rrule)) {
            $this->rrule = $rrule;
            $until = $this->getRecurrenceUntil();
            if($until) {
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
        return !$icalEvent->getLastModified() || !$this->last_modified ||  CalendarUtils::formatDateTimeToAppTime($icalEvent->getLastModified()) > $this->getLastModifiedDateTime();
    }
}
