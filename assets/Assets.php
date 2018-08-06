<?php

namespace humhub\modules\external_calendar\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Assets
 *
 * @author David Born ([staxDB](https://github.com/staxDB))
 */
class Assets extends AssetBundle
{
    public $sourcePath = '@external_calendar/resources';

    public $jsOptions = ['position' => View::POS_END];

    public $css = [
    ];
    public $js = [
        'js/humhub.external_calendar.js'
    ];
}
