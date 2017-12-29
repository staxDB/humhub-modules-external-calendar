<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\external_calendar\assets;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
//    public $publishOptions = [
//        'forceCopy' => false
//    ];
    
    public $sourcePath = '@external_calendar/resources';

    public $jsOptions = ['position' => \yii\web\View::POS_END];

    public $css = [
    ];
    public $js = [
        'js/humhub.external_calendar.js'
    ];
}
