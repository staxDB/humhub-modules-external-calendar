<?php

use humhub\modules\external_calendar\assets\Assets;
use humhub\widgets\ModalDialog;

use humhub\widgets\Tabs;

/* @var $this \humhub\modules\ui\view\components\View */
/* @var $model \humhub\modules\external_calendar\models\CalendarExport */
/* @var $showOverview boolean */


Assets::register($this);

$editLabel = $model->isNewRecord ?  Yii::t('ExternalCalendarModule.base', 'New export') :  Yii::t('ExternalCalendarModule.base', 'Edit export');
?>

<?php ModalDialog::begin(['header' => Yii::t('ExternalCalendarModule.base', '<strong>Calendar</strong> export'), 'size' => 'large']) ?>

    <?= Tabs::widget([
        'viewPath' => '@external_calendar/views/export',
        'params' => ['model' => $model],
        'items' => [
            ['label' => Yii::t('ExternalCalendarModule.base', 'New export'), 'view' => 'tab-edit'],
            ['label' => Yii::t('ExternalCalendarModule.base', 'My exports'), 'view' => 'tab-overview', 'active' => $showOverview]
        ]
    ]); ?>

<?php ModalDialog::end() ?>
