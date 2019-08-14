<?php


namespace humhub\modules\external_calendar\integration\calendar;


use humhub\modules\space\models\Space;
use Yii;
use yii\base\BaseObject;
use DateTime;
use humhub\modules\calendar\interfaces\VCalendar;
use humhub\modules\external_calendar\models\CalendarExport;
use yii\web\HttpException;
use humhub\modules\calendar\interfaces\CalendarService;

class CalendarExportService extends BaseObject
{
    /**
     * @var CalendarService
     */
    public $calendarService;

    public function __construct(CalendarService $calendarService, $config = [])
    {
        $this->calendarService = $calendarService;
        parent::__construct($config);
    }

    /**
     * @param $token
     * @return mixed
     * @throws HttpException
     * @throws \Throwable
     */
    public function createIcsByExportToken($token, $from = null, $to = null)
    {
        try {
            $export = CalendarExport::findOne(['token' => $token]);

            if (!$export) {
                throw new HttpException(404);
            }

            if (!$export->filter_only_public) {
                Yii::$app->user->setIdentity($export->user);
            }

           // $start = new DateTime('-6 year');
           // $end = new DateTime('+6 year');

            $items = [[]];
            foreach ($export->getContainers() as $container) {
                if($container instanceof Space && !$container->isMember()) {
                    continue;
                }

                $items[] = $this->calendarService->getCalendarItems($from, $to, $export->getFilterArray(), $container);
            }

            $cal = new VCalendar(['items' => array_merge(...$items)]);

            return $cal->serialize();
        } finally {
            Yii::$app->user->setIdentity(null);
        }
    }
}