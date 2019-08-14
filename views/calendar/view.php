<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use humhub\widgets\ModalButton;
use humhub\modules\calendar\widgets\ContainerConfigMenu;
use humhub\widgets\Button;

/* @var $this yii\web\View */
/* @var $model \humhub\modules\external_calendar\models\ExternalCalendar */

$this->title = $model->title;
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h1><?= Yii::t('ExternalCalendarModule.views_calendar', 'Calendar: {modelClass}', ['modelClass' => Html::encode($this->title),
            ]) ?></h1>
    </div>

    <?= ContainerConfigMenu::widget() ?>

    <div class="panel-body">

        <div class="btn-group-sm">
            <?= Button::primary(Yii::t('ExternalCalendarModule.base', 'Update'))
                ->link( $contentContainer->createUrl('edit', ['id' => $model->id]))->icon('fa-pencil-square-o'); ?>
            <?= humhub\widgets\ModalConfirm::widget([
                'uniqueID' => 'modal_delete_task_' . $model->id,
                'linkOutput' => 'a',
                'title' => Yii::t('ExternalCalendarModule.base', '<strong>Confirm</strong> deleting'),
                'message' => Yii::t('ExternalCalendarModule.base', 'Are you sure you want to delete this item?'),
                'buttonTrue' => Yii::t('ExternalCalendarModule.base', 'Delete'),
                'buttonFalse' => Yii::t('ExternalCalendarModule.base', 'Cancel'),
                'linkContent' => '<i class="fa fa-trash-o delete"></i>&nbsp;' . Yii::t('ExternalCalendarModule.base', 'Delete'),
                'linkHref' => $contentContainer->createUrl('delete', ['id' => $model->id]),
                'cssClass' => 'btn btn-danger'
            ]);
            ?>
            <?= ModalButton::primary(Yii::t('ExternalCalendarModule.views_calendar', 'Sync Calendar'))->load($contentContainer->createUrl('sync', ['id' => $model->id]))->loader(true)->icon('fa-refresh')->right(); ?>
        </div>

        </br>
        <div>
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    //            'id',
                    'title',
                    'url:ntext',
                    'public:boolean',
//                    'sync_mode',
//                    'event_mode',
                    [
                        'label' => Yii::t('ExternalCalendarModule.model_calendar', 'Sync Mode'),
                        'value' => $model->getSyncMode(),
                    ],
                    [
                        'label' => Yii::t('ExternalCalendarModule.model_calendar', 'Event Mode'),
                        'value' => $model->getEventMode(),
                    ],
                    'time_zone',
                    [
                        'attribute' => 'color',
                        'format' => 'raw',
                        'value' => "<code style='background-color: {$model->color}; color: transparent'>{$model->color}</code>",
//                        'type'=>DetailView::INPUT_COLOR,
//                        'inputWidth'=>'40%'
                    ],
                    'version',
                    'cal_name',
                    'cal_scale'
                ],
            ]) ?>
        </div>
        </br>
        <div>
            <?= Button::back($contentContainer->createUrl('index'), Yii::t('ExternalCalendarModule.base', 'Back to overview'))?>
        </div>

    </div>
</div>
