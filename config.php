<?php

use humhub\modules\dashboard\widgets\Sidebar;
use humhub\modules\external_calendar\Events;
use humhub\commands\CronController;
use humhub\modules\external_calendar\Module;
use humhub\commands\IntegrityController;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\content\widgets\WallEntryLinks;
use humhub\modules\stream\models\WallStreamQuery;
use humhub\modules\stream\widgets\WallStreamFilterNavigation;

return [
    'id' => 'external_calendar',
    'class' => Module::class,
    'namespace' => 'humhub\modules\external_calendar',
    'events' => [
        ['class' => Sidebar::class, 'event' => Sidebar::EVENT_INIT, 'callback' => [Module::class, 'onDashboardSidebarInit']],
        ['class' => CalendarService::class, 'event' => 'getItemTypes', 'callback' => [Events::class, 'onGetCalendarItemTypes']],
        ['class' => CalendarService::class, 'event' => 'findItems', 'callback' => [Events::class, 'onFindCalendarItems']],
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_HOURLY_RUN, 'callback' => [Events::class, 'onCronHourlyRun']],
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_DAILY_RUN, 'callback' => [Events::class, 'onCronDailyRun']],
        ['class' => IntegrityController::class, 'event' => IntegrityController::EVENT_ON_RUN, 'callback' => [Events::class, 'onIntegrityCheck']],
        ['class' => WallEntryLinks::class, 'event' => 'init', 'callback' => [Events::class, 'onWallEntryLinks']],
        ['class' => WallStreamQuery::class, 'event' =>  WallStreamQuery::EVENT_BEFORE_FILTER, 'callback' => [Events::class, 'onStreamFilterBeforeFilter']],
        ['class' => WallStreamFilterNavigation::class, 'event' =>  WallStreamFilterNavigation::EVENT_BEFORE_RUN, 'callback' => [Events::class, 'onStreamFilterBeforeRun']],
    ],
];
?>

