<?php

use humhub\modules\dashboard\widgets\Sidebar;
use humhub\modules\external_calendar\Events;
use humhub\commands\CronController;
use humhub\modules\external_calendar\Module;
use humhub\commands\IntegrityController;
use humhub\modules\content\widgets\WallEntryLinks;
use humhub\components\Application;

return [
    'id' => 'external_calendar',
    'class' => Module::class,
    'namespace' => 'humhub\modules\external_calendar',
    'urlManagerRules' => [
        ['class' => 'humhub\modules\external_calendar\components\PageUrlRule']
    ],
    'events' => [
        ['class' => Application::class, 'event' => Application::EVENT_BEFORE_REQUEST, 'callback' => [Events::class, 'onBeforeRequest']],
        ['class' => Sidebar::class, 'event' => Sidebar::EVENT_INIT, 'callback' => [Events::class, 'onDashboardSidebarInit']],
        ['class' => 'humhub\modules\calendar\interfaces\CalendarService', 'event' => 'getItemTypes', 'callback' => [Events::class, 'onGetCalendarItemTypes']],
        ['class' => 'humhub\modules\calendar\interfaces\CalendarService', 'event' => 'findItems', 'callback' => [Events::class, 'onFindCalendarItems']],
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_HOURLY_RUN, 'callback' => [Events::class, 'onCronHourlyRun']],
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_DAILY_RUN, 'callback' => [Events::class, 'onCronDailyRun']],
        ['class' => IntegrityController::class, 'event' => IntegrityController::EVENT_ON_RUN, 'callback' => [Events::class, 'onIntegrityCheck']],
        ['class' => WallEntryLinks::class, 'event' => 'init', 'callback' => [Events::class, 'onWallEntryLinks']],
        ['class' => 'humhub\modules\calendar\widgets\CalendarControls', 'event' => 'init', 'callback' => [Events::class, 'onCalendarControlsInit']],
        ['class' => 'humhub\modules\calendar\widgets\ContainerConfigMenu', 'event' => 'init', 'callback' => [Events::class, 'onContainerConfigMenuInit']],
    ],
];
?>

