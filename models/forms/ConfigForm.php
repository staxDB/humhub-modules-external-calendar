<?php

namespace humhub\modules\external_calendar\models\forms;

use Yii;

/**
 * ConfigForm uses for AdminController to set the configs for all external calendars
 *
 * @author davidborn
 */
class ConfigForm extends \yii\base\Model
{
    /**
     * @var boolean determines whether external calendars should be posted to stream
     */
    public $autopost_calendar = false;

    /**
     * @var boolean determines whether external calendar entries should be posted to stream
     */
    public $autopost_entries = false;

    /**
     * @var boolean determines if the title of the calendar should be used as badge-title for the upcoming-events widget
     */
    public $useBadgeTitle = false;

    /**
     * @inheritdocs
     */
    public function init()
    {
        $settings = Yii::$app->getModule('external_calendar')->settings;
        $this->autopost_calendar = $settings->get('autopost_calendar', $this->autopost_calendar);
        $this->autopost_entries = $settings->get('autopost_entries', $this->autopost_entries);
        $this->useBadgeTitle = $settings->get('useBadgeTitle', $this->useBadgeTitle);
    }

    /**
     * Static initializer
     * @return \self
     */
    public static function instantiate()
    {
        return new self;
    }
    
    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return [
            [['autopost_calendar', 'autopost_entries', 'useBadgeTitle'], 'required'],
            [['autopost_calendar', 'autopost_entries', 'useBadgeTitle'], 'boolean'],
        ];
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels()
    {
        return array(
            'autopost_calendar' => Yii::t('ExternalCalendarModule.model_config', 'Auto post calendar'),
            'autopost_entries' => Yii::t('ExternalCalendarModule.model_config', 'Auto post entries'),
            'useBadgeTitle' => Yii::t('ExternalCalendarModule.model_config', 'Use calendar title for badge-title in widget'),
        );
    }
    
    /**
     * Saves the given form settings.
     */
    public function save()
    {
        if(!$this->validate()) {
            return false;
        }

        $settings = Yii::$app->getModule('external_calendar')->settings;
        $settings->set('autopost_calendar', $this->autopost_calendar);
        $settings->set('autopost_entries', $this->autopost_entries);
        $settings->set('useBadgeTitle', $this->useBadgeTitle);

        return true;

    }

}
