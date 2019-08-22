<?php

namespace humhub\modules\external_calendar\models\forms;

use Yii;
use yii\base\Model;

/**
 * ConfigForm uses for AdminController to set the configs for all external calendars
 *
 * @author David Born ([staxDB](https://github.com/staxDB))
 */
class ConfigForm extends Model
{
    /**
     * @var boolean determines whether external calendars should be posted to stream
     */
    public $autopost_calendar = true;

    /**
     * @var boolean determines whether external calendar entries should be posted to stream
     */
    public $autopost_entries = true;

    /**
     * @inheritdocs
     */
    public function init()
    {
        $settings = Yii::$app->getModule('external_calendar')->settings;
        $this->autopost_calendar = $settings->get('autopost_calendar', $this->autopost_calendar);
        $this->autopost_entries = $settings->get('autopost_entries', $this->autopost_entries);
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
            [['autopost_calendar', 'autopost_entries'], 'required'],
            [['autopost_calendar', 'autopost_entries'], 'boolean'],
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
            'autopost_calendar' => Yii::t('ExternalCalendarModule.model_config', 'Post new calendar on stream'),
            'autopost_entries' => Yii::t('ExternalCalendarModule.model_config', 'Post new entries on stream'),
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

        return true;

    }

}
