<?php
use humhub\widgets\MarkdownView;
use yii\helpers\Html;

/* @var $calendar \humhub\modules\external_calendar\models\ExternalCalendar */
/* @var $stream boolean */
/* @var $collapse boolean */

$color = $calendar->color ? $calendar->color : $this->theme->variable('info');
?>
<div class="media event">
    <div class="y" style="padding-left:10px; border-left: 3px solid <?= $color ?>">
        <div class="media-body clearfix">
            <a href="<?= $calendar->getUrl(); ?>" class="pull-left" style="margin-right: 10px">
                <i class="fa fa-calendar colorDefault" style="font-size: 35px;"></i>
            </a>
            <h4 class="media-heading">
                <a href="<?= $calendar->getUrl(); ?>">
                    <?= Yii::t('ExternalCalendarModule.widgets', "External Calendar: "); ?>
                    <b><?= Html::encode($calendar->title); ?></b>
                </a>
            </h4>
            <h5>
                <?= Yii::t('ExternalCalendarModule.widgets', 'A new Calendar has been added.');?>
            </h5>
        </div>
        <?php if (!empty($calendar->description)) : ?>
            <div <?= ($collapse) ? 'data-ui-show-more' : '' ?> data-read-more-text="<?= Yii::t('ExternalCalendarModule.widgets', "Read full description...") ?>" style="overflow:hidden">
                <?= MarkdownView::widget(['markdown' => $calendar->description]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
