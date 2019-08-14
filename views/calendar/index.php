<?php

use yii\helpers\Html;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\calendar\widgets\ContainerConfigMenu;
use humhub\widgets\Button;
use humhub\widgets\GridView;
use yii\data\ArrayDataProvider;

/* @var $this yii\web\View */
/* @var $models [] \humhub\modules\external_calendar\models\ExternalCalendar */
/* @var $model \humhub\modules\external_calendar\models\ExternalCalendar */

$this->title = Yii::t('ExternalCalendarModule.views_calendar', 'External Calendars');
?>
<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('CalendarModule.config', '<strong>Calendar</strong> module configuration'); ?></div>

    <?= ContainerConfigMenu::widget() ?>

    <div class="panel-body">
        <div class="btn-group-sm clearfix">
            <?= Button::success(Yii::t('ExternalCalendarModule.views_calendar', 'Add Calendar'))
                ->icon('fa-plus')->link($contentContainer->createUrl('edit'))->right() ?>
        </div>
        <div>

            <?= GridView::widget([
                'dataProvider' => new ArrayDataProvider(['models' => $models]),
                'columns' => [
                    'title',
                    'visibility' => [
                        'header' => '&nbsp;',
                        'attribute' => 'public',
                        'options' => ['style' => 'width:40px;'],
                        'format' => 'raw',
                        'value' => function ($data) {
                            /* @var $data \humhub\modules\external_calendar\models\ExternalCalendar */
                            return $data->content->visibility ? '<i class="fa fa-globe"></i>' : '<i class="fa fa-lock"></i>';
                        },
                    ],
                    [
                        'header' => Yii::t('AdminModule.views_user_index', 'Actions'),
                        'class' => 'yii\grid\ActionColumn',
                        'options' => ['style' => 'width:80px; min-width:80px;'],
                        'buttons' => [
                            'view' => function ($url, $model) use ($contentContainer) {
                                return Html::a('<i class="fa fa-eye view"></i> ', $contentContainer
                                    ->createUrl('view', ['id' => $model->id]),
                                    ['class' => 'tt', 'data-toggle' => 'tooltip', 'data-placement' => 'top',
                                        'data-original-title' => Yii::t('ExternalCalendarModule.base', 'View')]);
                            },
                            'update' => function ($url, $model) use ($contentContainer) {
                                return Html::a('<i class="fa fa-pencil-square-o edit"></i> ',
                                    $contentContainer->createUrl('edit', ['id' => $model->id]),
                                    ['class' => 'tt', 'data-toggle' => 'tooltip', 'data-placement' => 'top',
                                        'data-original-title' => Yii::t('ExternalCalendarModule.base', 'Update')]);
                            },
                            'delete' => function ($url, $model) use ($contentContainer) {
                                return humhub\widgets\ModalConfirm::widget([
                                    'uniqueID' => 'modal_delete_task_' . $model->id,
                                    'linkOutput' => 'a',
                                    'title' => Yii::t('ExternalCalendarModule.base', '<strong>Confirm</strong> deleting'),
                                    'message' => Yii::t('ExternalCalendarModule.base', 'Are you sure you want to delete this item?'),
                                    'buttonTrue' => Yii::t('ExternalCalendarModule.base', 'Delete'),
                                    'buttonFalse' => Yii::t('ExternalCalendarModule.base', 'Cancel'),
                                    'linkContent' => '<i class="fa fa-trash-o delete"></i>',
                                    'linkHref' => $contentContainer->createUrl('delete', ['id' => $model->id]),
                                    'linkTooltipText' => Yii::t('ExternalCalendarModule.base', 'Delete'),
                                    'cssClass' => 'tt'
                                ]);

                            }
                        ],
                    ]
                ]
            ]); ?>
        </div>
        </br>
        <div>
            <?php
            if ($contentContainer instanceof Space) {
                $configUrl = $contentContainer->createUrl('/space/manage/module');
            } elseif ($contentContainer instanceof User) {
                $configUrl = $contentContainer->createUrl('/user/account/edit-modules');
            } else {
                $configUrl = '';
            }
            ?>
            <?= Html::a('<i class="fa fa-arrow-left"></i>&nbsp;' . Yii::t('ExternalCalendarModule.base', 'Back to overview'), $configUrl, ['class' => 'btn btn-sm btn-default']) ?>
        </div>

    </div>
</div>