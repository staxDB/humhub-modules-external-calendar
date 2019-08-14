<?php


namespace humhub\modules\external_calendar\models;


use humhub\components\ActiveRecord;
use humhub\modules\space\models\Membership;
use Yii;

/**
 * @property int $id
 * @property int $calendar_export_id
 * @property int $space_id
 */
class CalendarExportSpaces extends ActiveRecord
{
    public static function tableName()
    {
        return 'external_calendar_export_spaces';
    }

    public static function getCalendarMemberSpaces($keyword = null)
    {
        $calendarMemberSpaceQuery = Membership::getUserSpaceQuery(Yii::$app->user->identity);
        $calendarMemberSpaceQuery->leftJoin('contentcontainer_module',
            'contentcontainer_module.module_id = :calendar AND contentcontainer_module.contentcontainer_id = space.contentcontainer_id',
            [':calendar' => 'calendar']
        );
        $calendarMemberSpaceQuery->andWhere('contentcontainer_module.module_id IS NOT NULL');

        if($keyword) {
            $calendarMemberSpaceQuery->andWhere(['LIKE', 'space.name', $keyword]);
        }

        return $calendarMemberSpaceQuery->all();
    }
}