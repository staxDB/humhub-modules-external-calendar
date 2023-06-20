<?php

use humhub\modules\content\widgets\stream\StreamEntryWidget;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use humhub\modules\stream\assets\StreamAsset;

/* @var $model ExternalCalendarEntry */
?>
<?php StreamAsset::register($this); ?>

<div data-action-component="stream.SimpleStream">
    <?= StreamEntryWidget::renderStreamEntry($model) ?>
</div>