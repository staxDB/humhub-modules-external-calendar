<?php

use yii\helpers\Html;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;

/* @var $this yii\web\View */
/* @var $models[] \humhub\modules\external_calendar\models\ExternalCalendar */
/* @var $model \humhub\modules\external_calendar\models\ExternalCalendar */

$this->title = Yii::t('ExternalCalendarModule.views_calendar', 'External Calendars');
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="panel-body">
        <div class="btn-group-sm">
            <?= Html::a('<i class="fa fa-pencil-square-o edit"></i> ' . Yii::t('ExternalCalendarModule.views_calendar', 'Add Calendar'), $contentContainer->createUrl('create'), ['class' => 'btn-sm btn-success']) ?>
        </div>
        <div>
            <table class="table table-responsive">
                <thead>
                <tr>
                    <th scope="col"><?= Yii::t('ExternalCalendarModule.model_calendar', 'ID'); ?></th>
                    <th scope="col"><?= Yii::t('ExternalCalendarModule.model_calendar', 'Title'); ?></th>
                    <th scope="col"><?= Yii::t('ExternalCalendarModule.model_calendar', 'Public'); ?></th>
                    <th scope="col"><?= Yii::t('ExternalCalendarModule.views_calendar', 'Actions'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($models as $model): ?>
                    <tr>
                        <td><?= $model->id ?></td>
                        <td><?= $model->title ?></td>
                        <td><?= ($model->public) ? Yii::t('ExternalCalendarModule.views_calendar', 'Public') : Yii::t('ExternalCalendarModule.views_calendar', 'Private') ?></td>
                        <td>
                            <?= Html::a('<i class="fa fa-eye view"></i> ', $contentContainer->createUrl('view', ['id' => $model->id]), ['class' => 'tt', 'data-toggle'=>'tooltip', 'data-placement'=>'top', 'data-original-title'=>Yii::t('ExternalCalendarModule.base', 'View')]) ?>
                            <?= Html::a('<i class="fa fa-pencil-square-o edit"></i> ', $contentContainer->createUrl('update', ['id' => $model->id]), ['class' => 'tt', 'data-toggle'=>'tooltip', 'data-placement'=>'top', 'data-original-title'=>Yii::t('ExternalCalendarModule.base', 'Update')]) ?>
                            <?= humhub\widgets\ModalConfirm::widget([
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
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
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