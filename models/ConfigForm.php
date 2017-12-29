<?php

namespace humhub\modules\external_calendar\models;

use Yii;

/**
 * ConfigForm uses for AdminController to set the configs for all external calendars
 *
 * @author davidborn
 */
class ConfigForm extends \yii\base\Model
{

    public $autopost_calendar;
    public $autopost_entries;

    /**
     * @inheritdocs
     */
    public function init()
    {
        $settings = Yii::$app->getModule('external_calendar')->settings;
        $this->autopost_calendar = $settings->get('autopost_calendar');
        $this->autopost_entries = $settings->get('autopost_entries');
    }
    
    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return array(
            array(['autopost_calendar', 'autopost_entries'], 'safe')
        );
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels()
    {
        return array(
            'autopost_calendar' => Yii::t('ExternalCalendarModule.model_config', 'Auto post Calendar'),
            'autopost_entries' => Yii::t('ExternalCalendarModule.model_config', 'Auto post Entries'),
        );
    }
    
    /**
     * Saves the given form settings.
     */
    public function save()
    {
        $module = Yii::$app->getModule('external_calendar');

        if ($this->autopost_calendar) {
            $module->settings->set('autopost_calendar', true);
        } else {
            $module->settings->set('autopost_calendar', false);
        }

        if ($this->autopost_entries) {
            $module->settings->set('autopost_entries', true);
        } else {
            $module->settings->set('autopost_entries', false);
        }

        return true;
    }

}
