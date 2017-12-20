<?php
use humhub\modules\content\widgets\PinLink;
use humhub\modules\stream\assets\StreamAsset;
use humhub\modules\stream\actions\Stream;

/* @var $entry \humhub\modules\external_calendar\models\ExternalCalendarEntry */
/* @var $collapse boolean */
?>
<?php StreamAsset::register($this); ?>

<div data-action-component="stream.SimpleStream">
    <?= Stream::renderEntry($model, [
        'stream' => $stream,
        'controlsOptions' => [
            'prevent' => [PinLink::class]
        ],
    ])?>
</div>