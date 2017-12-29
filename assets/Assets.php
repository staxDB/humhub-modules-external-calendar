<?php

namespace humhub\modules\external_calendar\assets;

use yii\web\AssetBundle;

/**
 * Assets
 *
 * @author davidborn
 */
class Assets extends AssetBundle
{
    public $sourcePath = '@external_calendar/resources';

    public $jsOptions = ['position' => \yii\web\View::POS_END];

    public $css = [
    ];
    public $js = [
        'js/humhub.external_calendar.js'
    ];
}
