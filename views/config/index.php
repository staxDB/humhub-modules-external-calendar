<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
/* @var $this yii\web\View */
/* @var $model \humhub\modules\external_calendar\models\forms\ConfigForm */

use humhub\modules\ui\form\widgets\ActiveForm;
use \yii\helpers\Html;
use yii\helpers\Url;
use humhub\widgets\Button;

?>

<div class="panel panel-default">

    <div class="panel-heading">
        <h1><?= Yii::t('ExternalCalendarModule.views_admin', 'Calendar Extension Configuration') ?></h1>
    </div>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(); ?>

            <?= $form->field($model, 'autopost_calendar')->checkbox(); ?>
            <?= $form->field($model, 'autopost_entries')->checkbox(); ?>
            <?= $form->field($model, 'useBadgeTitle')->checkbox()
                ->hint(Yii::t('ExternalCalendarModule.views_admin', 'If this option is not checked, "Event" will be set as badge-title.')); ?>

            <hr>

            <?= Html::submitButton(Yii::t('ExternalCalendarModule.views_admin', 'Save'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
            <?= Button::defaultType(Yii::t('ExternalCalendarModule.views_admin', 'Back to modules'))->link(Url::to(['/admin/module']))->loader(false); ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>
