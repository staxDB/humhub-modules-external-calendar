<?php

namespace humhub\modules\external_calendar\permissions;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

/**
 * ManageEntry Permission
 *
 * @author davidborn
 */
class ManageEntry extends \humhub\libs\BasePermission
{
    /**
     * @inheritdoc
     */
    protected $moduleId = 'external_calendar';

    /**
     * @inheritdoc
     */
    public $defaultAllowedGroups = [
        Space::USERGROUP_OWNER,
        Space::USERGROUP_ADMIN,
        Space::USERGROUP_MODERATOR,
        User::USERGROUP_SELF,
    ];

    /**
     * @inheritdoc
     */
    protected $fixedGroups = [
        Space::USERGROUP_USER,
        User::USERGROUP_FRIEND,
        User::USERGROUP_GUEST,
        User::USERGROUP_USER,
        User::USERGROUP_FRIEND,
    ];



    public function getTitle()
    {
        return Yii::t('ExternalCalendarModule.permissions', 'Manage external entries');
    }

    public function getDescription()
    {
        return Yii::t('ExternalCalendarModule.permissions', 'Allows the user to edit/delete existing external calendar entries');
    }


}
