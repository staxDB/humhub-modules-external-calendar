<?php

namespace humhub\modules\external_calendar\models;

use Yii;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\external_calendar\permissions\ManageCalendar;
use humhub\modules\search\interfaces\Searchable;
use humhub\modules\content\models\Content;

require_once(Yii::$app->getModule('external_calendar')->basePath . '/vendors/johngrogg/ics-parser/src/ICal/Event.php');
require_once(Yii::$app->getModule('external_calendar')->basePath . '/vendors/johngrogg/ics-parser/src/ICal/ICal.php');

use ICal\ICal;



/**
 * This is the model class for table "external_calendar".
 *
 * @property integer $id
 * @property string $title
 * @property string $url
 * @property integer $public    Set if the Content should be public or private
 * @property string $time_zone The timeZone these entries was saved, note the dates itself are always saved in app timeZone
 * @property string $color
 * @property string $version    The ical-version, the calendar is stored
 * @property string $cal_name    The original calendar-name
 * @property string $cal_scale    The original calendar scale format, e.g. Gregorian
 * @property integer $sync_mode    Set if the Content should be autosynced
 * @property integer $past_events_mode    Set if old Events should be deleted
 * @property integer $upcoming_events_mode    Set if old Events should be deleted
 *
 * property ExternalCalendarEvent[] $ExternalCalendarEvents
 * @property ExternalCalendarEntry[] $ExternalCalendarEntries
 */
class ExternalCalendar extends ContentActiveRecord implements Searchable
{
    /**
     * @inheritdoc
     */
    public $wallEntryClass = 'humhub\modules\external_calendar\widgets\WallEntryCalendar';

    /**
     * @inheritdoc
     */
    public $managePermission = ManageCalendar::class;

    /**
     * @inheritdoc
     * set post to stream to false
     */
    public $streamChannel = null;
    public $silentContentCreation = true;

    /**
     * Sync Modes
     */
    const SYNC_MODE_NONE = 0;
    const SYNC_MODE_HOURLY = 1;
    const SYNC_MODE_DAILY = 2;

    /**
     * Delete Past Events Modes
     */
    const PAST_EVENTS_ALL = 0;
    const PAST_EVENTS_NONE = 1;
    const PAST_EVENTS_ONE_WEEK = 2;
    const PAST_EVENTS_ONE_MONTH = 3;

    /**
     * Sync Upcoming Events Modes
     */
    const UPCOMING_EVENTS_ALL = 0;
    const UPCOMING_EVENTS_ONE_DAY = 1;
    const UPCOMING_EVENTS_ONE_WEEK = 2;
    const UPCOMING_EVENTS_ONE_MONTH = 3;
    const UPCOMING_EVENTS_TWO_MONTH = 4;
    const UPCOMING_EVENTS_THREE_MONTH = 5;
    const UPCOMING_EVENTS_ONE_YEAR = 6;

    /**
     * @var array all given sync modes as array
     */
    public static $syncModes = [
        self::SYNC_MODE_NONE,
        self::SYNC_MODE_HOURLY,
        self::SYNC_MODE_DAILY
    ];

    /**
     * @var array all given past events modes as array
     */
    public static $pastEventsModes = [
        self::PAST_EVENTS_ALL,
        self::PAST_EVENTS_NONE,
        self::PAST_EVENTS_ONE_WEEK,
        self::PAST_EVENTS_ONE_MONTH,
];

    /**
     * @var array all given upcoming events modes as array
     */
    public static $upcomingEventsMode = [
       self::UPCOMING_EVENTS_ALL,
       self::UPCOMING_EVENTS_ONE_DAY,
       self::UPCOMING_EVENTS_ONE_WEEK,
       self::UPCOMING_EVENTS_ONE_MONTH,
       self::UPCOMING_EVENTS_TWO_MONTH,
       self::UPCOMING_EVENTS_THREE_MONTH,
       self::UPCOMING_EVENTS_ONE_YEAR
    ];

    /**
     *  init by settings
     */
    public function init()
    {
        parent::init();

        $this->setSettings();
    }

    public function setSettings()
    {
        // Set autopost settings for calendar
        $module = Yii::$app->getModule('external_calendar');
        $autopost_calendar = $module->settings->get('autopost_calendar');

        if ($autopost_calendar) {
            // set back to autopost true
            $this->streamChannel = 'default';
            $this->silentContentCreation = false;
        }
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'external_calendar';
    }

    /**
     * @inheritdoc
     */
    public function getContentName()
    {
        return Yii::t('ExternalCalendarModule.base', "External Calendar");
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
            [['title', 'url'], 'string', 'max' => 255],
            [['title', 'url'], 'required'],
            [['time_zone'], 'string', 'max' => 60],
            [['color'], 'string', 'max' => 7],
            [['url'],'url','defaultScheme' => 'http', 'message' => Yii::t('ExternalCalendarModule.sync_result', "No valid ical url! Try an url with http / https.")],
            [['url'], 'validateURL'],
            [['public'], 'integer'],
            [['sync_mode'], 'in', 'range' => self::$syncModes],
            [['past_events_mode'], 'in', 'range' => self::$pastEventsModes],
            [['upcoming_events_mode'], 'in', 'range' => self::$upcomingEventsMode],
        ];
    }

    /**
     * Validator for the url field.
     *
     * @param string $attribute attribute name
     * @param array $params parameters
     */
    public function validateURL($attribute, $params)
    {
        try {
            new ICal($this->url, array(
                'defaultTimeZone' => Yii::$app->timeZone,
            ));
        } catch (\Exception $e) {
            $this->addError($attribute, Yii::t('ExternalCalendarModule.sync_result', "No valid ical url! Try an url with http / https."));
        }
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['title', 'url', 'public', 'sync_mode', 'past_events_mode', 'upcoming_events_mode'];
        $scenarios['admin'] = ['title', 'url', 'public', 'sync_mode', 'past_events_mode', 'upcoming_events_mode'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('ExternalCalendarModule.model_calendar', 'ID'),
            'title' => Yii::t('ExternalCalendarModule.model_calendar', 'Title'),
            'url' => Yii::t('ExternalCalendarModule.model_calendar', 'Url'),
            'public' => Yii::t('ExternalCalendarModule.model_calendar', 'Public'),
            'time_zone' => Yii::t('ExternalCalendarModule.model_calendar', 'Timezone'),
            'color' => Yii::t('ExternalCalendarModule.model_calendar', 'Color'),
            'version' => Yii::t('ExternalCalendarModule.model_calendar', 'iCal Version'),
            'cal_name' => Yii::t('ExternalCalendarModule.model_calendar', 'Original Calendar Name'),
            'cal_scale' => Yii::t('ExternalCalendarModule.model_calendar', 'Original Calendar Scale'),
            'sync_mode' => Yii::t('ExternalCalendarModule.model_calendar', 'Sync Mode'),
            'past_events_mode' => Yii::t('ExternalCalendarModule.model_calendar', 'Past Events'),
            'upcoming_events_mode' => Yii::t('ExternalCalendarModule.model_calendar', 'Upcoming Events'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        return [
            'title' => $this->title,
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        foreach (ExternalCalendarEntry::findAll(['calendar_id' => $this->id]) as $item) {
            $item->delete();
        }

        return parent::beforeDelete();
    }

    /**
     * @param $insert
     * @return bool
     * @throws \yii\base\Exception
     */
    public function beforeSave($insert)
    {
        if ($this->isAttributeChanged('public', false))
        {
            $this->changeVisibility();
            $this->changeEntriesVisibility();
        }
        
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    /**
     * @param $insert
     * @param $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        return parent::afterSave($insert, $changedAttributes);
    }

    public function getUrl()
    {
        return $this->content->container->createUrl('//external_calendar/calendar/view', array('id' => $this->id));
    }


    public static function getSyncModeItems()
    {
        return [
            self::SYNC_MODE_NONE => Yii::t('ExternalCalendarModule.model_calendar', 'No auto synchronization'),
            self::SYNC_MODE_HOURLY => Yii::t('ExternalCalendarModule.model_calendar', 'Hourly synchronization'),
            self::SYNC_MODE_DAILY => Yii::t('ExternalCalendarModule.model_calendar', 'Daily synchronization'),
        ];
    }

    public function getSyncMode()
    {
        switch ($this->sync_mode){
            case (self::SYNC_MODE_NONE):
                return Yii::t('ExternalCalendarModule.model_calendar', 'No auto synchronization');
                break;
            case (self::SYNC_MODE_HOURLY):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Hourly synchronization');
                break;
            case (self::SYNC_MODE_DAILY):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Daily synchronization');
                break;
            default:
                return;
        }
    }

    public static function getPastEventsModeItems()
    {
        return [
            self::PAST_EVENTS_ALL => Yii::t('ExternalCalendarModule.model_calendar', 'Keep all past events'),
            self::PAST_EVENTS_NONE => Yii::t('ExternalCalendarModule.model_calendar', 'Delete all past events'),
            self::PAST_EVENTS_ONE_WEEK => Yii::t('ExternalCalendarModule.model_calendar', 'Keep all one week old events'),
            self::PAST_EVENTS_ONE_MONTH => Yii::t('ExternalCalendarModule.model_calendar', 'Keep all one month old events'),
        ];
    }

    public function getPastEventsMode()
    {
        switch ($this->past_events_mode){
            case (self::PAST_EVENTS_ALL):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Keep all past events');
                break;
            case (self::PAST_EVENTS_NONE):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Delete all past events');
                break;
            case (self::PAST_EVENTS_ONE_WEEK):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Keep all one week old events');
                break;
            case (self::PAST_EVENTS_ONE_MONTH):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Keep all one month old events');
                break;
            default:
                return;
        }
    }

    public static function getUpcomingEventsModeItems()
    {
        return [
            self::UPCOMING_EVENTS_ALL => Yii::t('ExternalCalendarModule.model_calendar', 'Sync all upcoming events'),
            self::UPCOMING_EVENTS_ONE_DAY => Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for today'),
            self::UPCOMING_EVENTS_ONE_WEEK => Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for one week'),
            self::UPCOMING_EVENTS_ONE_MONTH => Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for one month'),
            self::UPCOMING_EVENTS_TWO_MONTH => Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for two months'),
            self::UPCOMING_EVENTS_THREE_MONTH => Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for three months'),
            self::UPCOMING_EVENTS_ONE_YEAR => Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for one year'),
        ];
    }

    public function getUpcomingEventsMode()
    {
        switch ($this->upcoming_events_mode){
            case (self::UPCOMING_EVENTS_ALL):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Sync all upcoming events');
                break;
            case (self::UPCOMING_EVENTS_ONE_DAY):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for today');
                break;
            case (self::UPCOMING_EVENTS_ONE_WEEK):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for one week');
                break;
            case (self::UPCOMING_EVENTS_ONE_MONTH):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for one month');
                break;
            case (self::UPCOMING_EVENTS_TWO_MONTH):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for two months');
                break;
            case (self::UPCOMING_EVENTS_THREE_MONTH):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for three months');
                break;
            case (self::UPCOMING_EVENTS_ONE_YEAR):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Sync only upcoming events for one year');
                break;
            default:
                return;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExternalCalendarEntries()
    {
        return $this->hasMany(ExternalCalendarEntry::className(), ['calendar_id' => 'id']);
    }

    public function addAttributes(ICal $ical)
    {
        // add info to CalendarModel
        $this->time_zone = $ical->calendarTimeZone();
        $this->cal_name = $ical->calendarName();
        if (isset($ical->cal['VCALENDAR']['VERSION'])) {
            $this->version = $ical->cal['VCALENDAR']['VERSION'];
        }
        if (isset($ical->cal['VCALENDAR']['CALSCALE'])) {
            $this->cal_scale = $ical->cal['VCALENDAR']['CALSCALE'];
        }
    }

    /**
     *
     */
    public function changeVisibility()
    {
        switch ($this->public) {
            case Content::VISIBILITY_PRIVATE:
                $this->content->visibility = Content::VISIBILITY_PRIVATE;
                break;
            default:
                $this->content->visibility = Content::VISIBILITY_PUBLIC;
                break;
        }
    }

    /**
     *
     */
    public function changeEntriesVisibility()
    {
        switch ($this->public) {
            case Content::VISIBILITY_PRIVATE:
                // change content visibility of each CalendarExensionCalendarEntry
                foreach ($this->getExternalCalendarEntries()->all() as $entry) {
                    $entry->content->visibility = Content::VISIBILITY_PRIVATE;
                    $entry->save();
                }
                break;
            default:
                // change content visibility of each CalendarExensionCalendarEntry
                foreach ($this->getExternalCalendarEntries()->all() as $entry) {
                    $entry->content->visibility = Content::VISIBILITY_PUBLIC;
                    $entry->save();
                }
                break;
        }
    }
}
