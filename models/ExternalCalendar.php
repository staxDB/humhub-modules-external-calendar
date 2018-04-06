<?php

namespace humhub\modules\external_calendar\models;

use Yii;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\external_calendar\permissions\ManageCalendar;
use humhub\modules\search\interfaces\Searchable;
use humhub\modules\content\models\Content;
use humhub\modules\external_calendar\vendors\ICal\ICal;


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
 * @property integer $event_mode    Set how old and new Events should be handled
 * @property ExternalCalendarEntry $ExternalCalendarEntries[]
 *
 * @author davidborn
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
     * Event Modes
     */
    const EVENT_MODE_CURRENT_MONTH = 0;
    const EVENT_MODE_ALL = 1;

    /**
     * @var array all given sync modes as array
     */
    public static $syncModes = [
        self::SYNC_MODE_NONE,
        self::SYNC_MODE_HOURLY,
        self::SYNC_MODE_DAILY
    ];

    /**
     * @var array all given sync modes as array
     */
    public static $eventModes = [
        self::EVENT_MODE_CURRENT_MONTH,
        self::EVENT_MODE_ALL
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
            [['event_mode'], 'in', 'range' => self::$eventModes],
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
        $scenarios['create'] = ['title', 'url', 'public', 'sync_mode', 'event_mode'];
        $scenarios['admin'] = ['title', 'url', 'public', 'sync_mode', 'event_mode'];
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
            'event_mode' => Yii::t('ExternalCalendarModule.model_calendar', 'Event Selection'),
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

    public static function getEventModeItems()
    {
        return [
            self::EVENT_MODE_CURRENT_MONTH => Yii::t('ExternalCalendarModule.model_calendar', 'Only sync events from current month'),
            self::EVENT_MODE_ALL => Yii::t('ExternalCalendarModule.model_calendar', 'Sync all events'),
        ];
    }

    public function getEventMode()
    {
        switch ($this->event_mode){
            case (self::EVENT_MODE_CURRENT_MONTH):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Only sync events from current month');
                break;
            case (self::EVENT_MODE_ALL):
                return Yii::t('ExternalCalendarModule.model_calendar', 'Sync all events');
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
