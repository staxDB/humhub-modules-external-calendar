<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2016 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\external_calendar\components;

use yii\base\Component;
use yii\web\UrlRuleInterface;

/**
 * CustomPages URL Rule
 *
 * @author luke
 */
class PageUrlRule extends Component implements UrlRuleInterface
{

    /**
     * @var string default route to page home
     */
    public $searchRoute = 'external_calendar/export/export';

    /**
     * @var array map with space guid/url pairs
     */
    protected static $pageUrlMap = [];

    /**
     * @inheritdoc
     */
    public function createUrl($manager, $route, $params)
    {
        if ($route === $this->searchRoute  && isset($params['token'])) {
            $url = "ical/" . urlencode($params['token']). '/base.ics';

            unset($params['token']);

            if (!empty($params) && ($query = http_build_query($params)) !== '') {
                $url .= '?' . $query;
            }
            return $url;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($manager, $request)
    {
        $pathInfo = $request->getPathInfo();
        if (substr($pathInfo, 0, 5) === "ical/" && $this->endsWith($pathInfo, '/base.ics')) {
            $parts = explode('/', $pathInfo, 3);
            if (isset($parts[1])) {
                $params = $request->get();
                $params['token'] = $parts[1];
                return [$this->searchRoute, $params];
            }
        }
        return false;
    }

    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
}
