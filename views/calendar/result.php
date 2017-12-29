<?php

use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;

/* @var $this \humhub\components\View */
/* @var $module \humhub\modules\external_calendar\models\ExternalCalendar */
/* @var $canManageEntries boolean */
/* @var $editUrl string */

?>

<?php ModalDialog::begin(['size' => 'small', 'closable' => true]); ?>
    <div class="modal-body" style="padding-bottom:0px">
        <div class="text-center">
            <?= $message ?>
        </div>
    </div>
    <div class="modal-footer">
        <?= ModalButton::cancel(Yii::t('ExternalCalendarModule.base', 'Close')) ?>
    </div>
<?php ModalDialog::end(); ?>