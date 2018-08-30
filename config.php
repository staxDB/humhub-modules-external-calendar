<?php

use humhub\modules\dashboard\widgets\Sidebar;
use humhub\modules\external_calendar\Events;
use humhub\commands\CronController;
use humhub\modules\external_calendar\Module;
use humhub\commands\IntegrityController;

return [
    'id' => 'external_calendar',
    'class' => Module::class,
    'namespace' => 'humhub\modules\external_calendar',
    'events' => [
        ['class' => Sidebar::class, 'event' => Sidebar::EVENT_INIT, 'callback' => [Module::class, 'onDashboardSidebarInit']],
        ['class' => 'humhub\modules\calendar\interfaces\CalendarService', 'event' => 'getItemTypes', 'callback' => [Events::class, 'onGetCalendarItemTypes']],
        ['class' => 'humhub\modules\calendar\interfaces\CalendarService', 'event' => 'findItems', 'callback' => [Events::class, 'onFindCalendarItems']],
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_HOURLY_RUN, 'callback' => [Events::class, 'onCronHourlyRun']],
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_DAILY_RUN, 'callback' => [Events::class, 'onCronDailyRun']],
        ['class' => IntegrityController::class, 'event' => IntegrityController::EVENT_ON_RUN, 'callback' => [Events::class, 'onIntegrityCheck']],
        ['class' => '\humhub\modules\content\widgets\WallEntryLinks', 'event' => 'init', 'callback' => [Events::class, 'onWallEntryLinks']],
    ],
];
?>

