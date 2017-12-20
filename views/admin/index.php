<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \humhub\modules\external_calendar\models\ConfigForm */

$this->title = Yii::t('ExternalCalendarModule.views_admin', 'Calendar Extension Configuration');
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="panel-body">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->errorSummary($model); ?>

        <div class="form-group">
            <div class="checkbox">
                <label>
                    <?= $form->field($model, 'autopost_calendar')->checkbox(); ?>
                </label>
            </div>
        </div>

        <div class="form-group">
            <div class="checkbox">
                <label>
                    <?= $form->field($model, 'autopost_entries')->checkbox(); ?>
                </label>
            </div>
        </div>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('ExternalCalendarModule.views_admin', 'Save'), ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>