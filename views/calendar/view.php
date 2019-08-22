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
        <div class="panel-heading"><?= Yii::t('CalendarModule.config', '<strong>Calendar</strong> module configuration'); ?></div>
    </div>

    <?= ContainerConfigMenu::widget() ?>

    <div class="panel-body">

        <div class="clearfix">
            <h4>
                <?= Yii::t('ExternalCalendarModule.config', 'Calendar  {title}', ['title' => Html::encode($this->title) ]); ?>
            </h4>

            <div class="help-block">
                <?= Yii::t('ExternalCalendarModule.config', 'In this view you can review and manually synchronize the calendar {title}.', ['title' => Html::encode($this->title) ]) ?>
            </div>
        </div>

        <div class="btn-group-sm">
            <?= Button::primary(Yii::t('ExternalCalendarModule.base', 'Edit'))
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
            ]); ?>

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
                        'label' => Yii::t('ExternalCalendarModule.model_calendar', 'Synchronization Mode'),
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
        <br>
        <div>
            <?= Button::back($contentContainer->createUrl('index'), Yii::t('ExternalCalendarModule.base', 'Back to overview'))->sm() ?>
        </div>
    </div>
</div>
