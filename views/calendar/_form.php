<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use humhub\widgets\ColorPickerField;

/* @var $this yii\web\View */
/* @var $model \humhub\modules\external_calendar\models\ExternalCalendar */
/* @var $contentContainer \humhub\modules\content\models\ContentContainer */
/* @var $form yii\widgets\ActiveForm */

if($model->color == null && isset($contentContainer->color)) {
    $model->color = $contentContainer->color;
} elseif ($model->color == null) {
    $model->color = '#d1d1d1';
}

?>
<div class="calendar-extension-calendar-form">

    <?php $form = ActiveForm::begin(); ?>

    <div id="event-color-field" class="form-group space-color-chooser-edit" style="margin-top: 5px;">
        <?= $form->field($model, 'color')->widget(ColorPickerField::className(), ['container' => 'event-color-field'])->label(Yii::t('ExternalCalendarModule.views_calendar', 'Title and Color')); ?>

        <?= $form->field($model, 'title', ['template' => '
                                    {label}
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i></i>
                                        </span>
                                        {input}
                                    </div>
                                    {error}{hint}'
        ])->textInput(['placeholder' => Yii::t('ExternalCalendarModule.model_calendar', 'Title'), 'maxlength' => true])->label(false) ?>

    </div>

    <?= $form->field($model, 'url')->textarea(['rows' => 6, 'placeholder' => Yii::t('ExternalCalendarModule.model_calendar', 'e.g. https://calendar.google.com/calendar/ical/...')]) ?>


    <?= $form->field($model, 'public')->checkbox() ?>
    <?= $form->field($model, 'sync_mode')->dropDownList($model->getSyncModeItems()) ?>
    <?= $form->field($model, 'past_events_mode')->dropDownList($model->getPastEventsModeItems()) ?>
    <?= $form->field($model, 'upcoming_events_mode')->dropDownList($model->getUpcomingEventsModeItems()) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('ExternalCalendarModule.base', 'Save') : Yii::t('ExternalCalendarModule.base', 'Save'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

