<?php

use yii\helpers\Html;
use humhub\modules\ui\form\widgets\ActiveForm;
use kartik\widgets\DateTimePicker;

/* @var $this yii\web\View */
/* @var $model \humhub\modules\external_calendar\models\ExternalCalendarEntry */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="external-calendar-entry-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'uid')->textInput(['maxlength' => true, 'disabled' => true]) ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'location')->textarea(['maxlength' => true]) ?>

    <?= $form->field($model, 'start_datetime')->widget(DateTimePicker::class, ['type' => DateTimePicker::TYPE_COMPONENT_APPEND, 'pluginOptions' => ['format' => 'yyyy-mm-dd hh:ii:ss', 'autoclose' => false]]) ?>

    <?= $form->field($model, 'end_datetime')->widget(DateTimePicker::class, ['type' => DateTimePicker::TYPE_COMPONENT_APPEND, 'pluginOptions' => ['format' => 'yyyy-mm-dd hh:ii:ss', 'autoclose' => false]]) ?>

    <?= $form->field($model, 'all_day')->checkbox() ?>


    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('ExternalCalendarModule.base', 'Create') : Yii::t('ExternalCalendarModule.base', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
