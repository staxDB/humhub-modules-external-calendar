<?php


namespace humhub\modules\external_calendar\models;


use humhub\modules\calendar\interfaces\AbstractCalendarQuery;
use humhub\modules\space\models\Membership;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\space\models\Space;
use Yii;
use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;
use yii\helpers\Url;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $token
 * @property boolean $filter_participating
 * @property boolean $filter_mine
 * @property boolean $filter_only_public
 * @property boolean $include_profile
 * @property int $space_selection
 */
class CalendarExport extends ActiveRecord
{
    const SPACES_NONE = 0;
    const SPACES_ALL = 1;
    const SPACES_SELECTION = 2;

    public $spaceSelection = [];

    public static function tableName()
    {
        return 'external_calendar_export';
    }

    public function afterFind()
    {
        foreach ($this->spaces as $space) {
            $this->spaceSelection[] = $space->guid;
        }
    }

    public function rules()
    {
        return [
            ['name', 'required'],
            [['filter_only_public', 'filter_participating', 'filter_mine', 'include_profile'], 'boolean'],
            [['space_selection'], 'integer', 'min' => 0, 'max' => 2],
            [['name'], 'validateSpaces'],
            [['spaceSelection'], 'safe'],

        ];
    }

    public function validateSpaces()
    {
        if($this->space_selection == static::SPACES_SELECTION && empty($this->spaceSelection)) {
            $this->addError('spaceSelection', Yii::t('ExternalCalendarModule.export','Please select at least one space.'));
            return;
        }

        if(empty($this->spaceSelection)) {
            return;
        }

        foreach ($this->spaceSelection as $guid) {
            $space = Space::findOne(['guid' => $guid]);
            if(!$space || !$space->isMember()) {
                $this->addError('spaceSelection', Yii::t('ExternalCalendarModule.export','Invalid space selection'));
            }
        }
    }

    public function afterSave($insert, $changedAttributes)
    {
        CalendarExportSpaces::deleteAll(['calendar_export_id' => $this->id]);

        if($this->space_selection == static::SPACES_SELECTION && !empty($this->spaceSelection)) {
            foreach ($this->spaceSelection as $guid) {
                $space = Space::findOne(['guid' => $guid]);
                (new CalendarExportSpaces(['calendar_export_id' => $this->id, 'space_id' => $space->id]))->save();
            }
        }
    }

    public function getFilterArray()
    {
        $result = [];
        if($this->filter_participating) {
            $result[] = AbstractCalendarQuery::FILTER_PARTICIPATE;
        }

        if($this->filter_mine) {
            $result[] = AbstractCalendarQuery::FILTER_MINE;
        }

        return $result;
    }

    public function getContainers()
    {
        $result = [];
        if($this->include_profile) {
            $result[] = $this->user;
        }

        if($this->space_selection === static::SPACES_ALL) {
            $result = array_merge($result, Membership::getUserSpaceQuery($this->user)->all());
        } else if($this->space_selection === static::SPACES_SELECTION) {
            $result =  array_merge($result, $this->spaces);
        }

        return $result;
    }

    public function getExportUrl()
    {
        return Url::to(['/external_calendar/export/export', 'token' => $this->token], true);
    }

    /**
     * @param $insert
     * @return bool
     * @throws \yii\base\Exception
     */
    public function beforeSave($insert)
    {
        if(empty($this->token)) {
            $this->token = CalendarEntry::createUUid('calendar');
        }

        return parent::beforeSave($insert);
    }

    public function getSpaces()
    {
        return $this->hasMany(Space::class, ['id' => 'space_id'])->via('spaceExports');
    }

    public function getSpaceExports()
    {
        return $this->hasMany(CalendarExportSpaces::class, ['calendar_export_id' => 'id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function attributeLabels()
    {
        return [
            'name' =>  Yii::t('ExternalCalendarModule.export', 'Calendar export name'),
            'include_profile' =>  Yii::t('ExternalCalendarModule.export', 'Profile'),
            'filter_participating' => Yii::t('ExternalCalendarModule.export', 'Only include events I\'am participating'),
            'filter_mine' => Yii::t('ExternalCalendarModule.export', 'Only include events I\'ve created'),
            'filter_only_public' => Yii::t('ExternalCalendarModule.export', 'Only include public events')
        ];
    }
}