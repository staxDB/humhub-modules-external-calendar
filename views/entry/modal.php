<?php

use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;

/* @var $this \humhub\modules\ui\view\components\View */
/* @var $model \humhub\modules\external_calendar\models\ExternalCalendarEntry */
/* @var $canManageEntries boolean */
/* @var $editUrl string */

?>

<?php ModalDialog::begin(['size' => 'large', 'closable' => true]); ?>
    <div class="modal-body" style="padding-bottom:0px">
        <?= $this->renderAjax('view', ['model' => $model]) ?>
    </div>
    <div class="modal-footer">
        <?php if ($canManageEntries): ?>
            <?= ModalButton::primary(Yii::t('ExternalCalendarModule.base', 'Edit'))->load($editUrl)->loader(true); ?>
        <?php endif; ?>
        <?= ModalButton::cancel(Yii::t('ExternalCalendarModule.base', 'Close')) ?>
    </div>
<?php ModalDialog::end(); ?>