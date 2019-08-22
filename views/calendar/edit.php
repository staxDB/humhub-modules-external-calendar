<?php

use humhub\widgets\Button;
use yii\helpers\Html;
use humhub\modules\calendar\widgets\ContainerConfigMenu;


/* @var $this yii\web\View */
/* @var $model \humhub\modules\external_calendar\models\ExternalCalendar */
/* @var $contentContainer \humhub\modules\content\models\ContentContainer */

$title = $model->isNewRecord
    ? Yii::t('ExternalCalendarModule.views_calendar', 'Add external Calendar')
    : Yii::t('ExternalCalendarModule.config', 'Edit Calendar  {title}', ['title' => Html::encode($this->title) ])
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-heading"><?= Yii::t('CalendarModule.config', '<strong>Calendar</strong> module configuration'); ?></div>
    </div>

    <?= ContainerConfigMenu::widget() ?>

    <div class="panel-body">
        <div class="clearfix">

            <?= Button::back($contentContainer->createUrl('index'), Yii::t('ExternalCalendarModule.base', 'Back to overview'))->sm() ?>
            <h4>
                <?= $title ?>
            </h4>
        </div>

        <?= $this->render('_form', [
            'model' => $model,
            'contentContainer' => $contentContainer,
        ]) ?>
    </div>
</div>
