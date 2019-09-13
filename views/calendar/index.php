<?php

use yii\helpers\Html;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\calendar\widgets\ContainerConfigMenu;
use humhub\widgets\Button;
use humhub\widgets\GridView;
use yii\data\ArrayDataProvider;

/* @var $this yii\web\View */
/* @var $dataProvider \yii\data\ActiveDataProvider */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
/* @var $model \humhub\modules\external_calendar\models\ExternalCalendar */

$this->title = Yii::t('ExternalCalendarModule.views_calendar', 'External Calendars');
$publicText = Yii::t('ExternalCalendarModule.views_calendar', 'Public');
$privateText = Yii::t('ExternalCalendarModule.views_calendar', 'Private');

$helpText = ($contentContainer instanceof Space)
    ? Yii::t('ExternalCalendarModule.config', 'This view lists all calenders configured for this space')
    : Yii::t('ExternalCalendarModule.config', 'This view lists all calenders configured in your profile');
?>
<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('CalendarModule.config', '<strong>Calendar</strong> module configuration'); ?></div>

    <?= ContainerConfigMenu::widget() ?>

    <div class="panel-body">
        <div class="clearfix">
            <?= Button::success(Yii::t('ExternalCalendarModule.views_calendar', 'Add Calendar'))
                ->icon('fa-plus')->link($contentContainer->createUrl('edit'))->right()->sm() ?>

            <h4>
                <?= Yii::t('ExternalCalendarModule.config', 'External Calendars Overview'); ?>
            </h4>

            <div class="help-block">
                <?= $helpText ?>
            </div>
        </div>

        <div>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'summary' => '',
                'showHeader' => false,
                'columns' => [
                    'title' => [
                        'format' => 'raw',
                        'value' => function ($data) use ($contentContainer) {
                            /* @var $data \humhub\modules\external_calendar\models\ExternalCalendar */
                            return \humhub\widgets\Link::to(Html::encode($data->title), $contentContainer
                                ->createUrl('view', ['id' => $data->id]));
                        },
                    ],
                    'visibility' => [
                        'header' => '&nbsp;',
                        'attribute' => 'public',
                        'options' => ['style' => 'width:40px;'],
                        'format' => 'raw',
                        'value' => function ($data) use ($privateText, $publicText) {
                            /* @var $data \humhub\modules\external_calendar\models\ExternalCalendar */
                            return $data->content->visibility
                                ? "<i class=\"fa fa-globe tt\" title=\"{$publicText}\"></i>"
                                : "<i class=\"fa fa-lock tt\"  title=\"{$privateText}\"></i>";
                        },
                    ],
                    [
                        'header' => '',
                        'class' => 'yii\grid\ActionColumn',
                        'options' => ['style' => 'width:80px; min-width:80px;'],
                        'buttons' => [
                            'view' => function ($url, $model) use ($contentContainer) {
                                $viewUrl = $contentContainer->createUrl('view', ['id' => $model->id]);
                                return Button::primary()->icon('fa-eye')
                                    ->link($viewUrl)->tooltip(Yii::t('ExternalCalendarModule.base', 'View'))->sm()->right();
                            },
                            'update' => function () {
                                return '';
                            },
                            'delete' => function () {
                                return '';
                            }
                        ],
                    ]
                ]
            ]); ?>
        </div>
    </div>
</div>