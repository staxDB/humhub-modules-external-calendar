<?php

use yii\helpers\Html;
use humhub\widgets\ModalDialog;
use humhub\widgets\ActiveForm;
use kartik\widgets\DateTimePicker;
use humhub\widgets\ModalButton;

/* @var $this yii\web\View */
/* @var $model \humhub\modules\external_calendar\models\ExternalCalendarEntry */
/* @var $editUrl string */
/* @var $contentContainer \humhub\modules\content\models\ContentContainer */

$header = Yii::t('ExternalCalendarModule.views_entry', 'Update {modelClass}: ', [
        'modelClass' => Yii::t('ExternalCalendarModule.base', 'External Calendar Entry')
    ]) . $model->title;
$model->calendar->color = empty($model->calendar->color) ? $this->theme->variable('info') : $model->calendar->color;

?>
<?php ModalDialog::begin(['header' => $header, 'closable' => false]) ?>
<?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>

<div class="modal-body">

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'location')->textarea(['maxlength' => true]) ?>

</div>

<hr>

<div class="modal-footer">
    <?= ModalButton::submitModal($editUrl); ?>
    <?= ModalButton::cancel(); ?>
</div>
<?php ActiveForm::end(); ?>
<?php ModalDialog::end() ?>
