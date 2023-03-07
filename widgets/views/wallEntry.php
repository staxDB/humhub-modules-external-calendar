<?php

use cebe\markdown\GithubMarkdown;
use humhub\modules\ui\icon\widgets\Icon;
use yii\helpers\Html;

/* @var $calendarEntry \humhub\modules\external_calendar\models\ExternalCalendarEntry */
/* @var $stream boolean */

$color = $calendarEntry->calendar->color ? $calendarEntry->calendar->color : $this->theme->variable('info');

$description = $calendarEntry->description;

if ($description) {
    $config = \HTMLPurifier_Config::createDefault();
    $description = \yii\helpers\HtmlPurifier::process($calendarEntry->description, $config);
}

?>
<div class="media event">
    <div>
        <div class="clearfix">
            <h1 style="font-size:14px;font-weight:500">
                <?= Icon::get('clock-o')->color($color)->size(Icon::SIZE_LG)->fixedWith(true)->style('margin-top:2px') ?> <?= $calendarEntry->getFormattedTime() ?>
            </h1>
        </div>
        <?php if (!empty($calendarEntry->location)) : ?>
            <p>
                <?= Icon::get('map-marker ')->color($color)->size(Icon::SIZE_LG)->fixedWith(true)->style('margin-top:2px') ?>
                <?= Html::encode($calendarEntry->location) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($description) || !empty($calendarEntry->location)) : ?>
            <div data-ui-show-more
                 data-read-more-text="<?= Yii::t('ExternalCalendarModule.widgets', "Read full description...") ?>"
                 style="overflow:hidden">

                <p><?= nl2br((new GithubMarkdown())->parse($description)) ?></p>

            </div>
        <?php endif; ?>
    </div>
</div>
