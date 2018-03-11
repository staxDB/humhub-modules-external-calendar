## Installation
- Download the [Latest Release](https://github.com/staxDB/humhub-modules-external-calendar/releases) and upload contents to **/protected/modules/external_calendar**
- or simply clone this repo by `git clone https://github.com/staxDB/humhub-modules-external-calendar.git external_calendar` into folder **/protected/modules**

then go to `Administration -> Modules` and **Enable** the **External Calendar** module.

**_Note:_** You need to enable the [original calendar module](https://github.com/humhub/humhub-modules-calendar) first. The external_calendar module only works if it is activated!

If you want to add external calendars, go to a specific space (or your own profile), activate the external_calendar module in the space settings (or profile settings) and start the configuration of the module here.
**If there is an error, something went wrong with your sync.**

## Settings
By default, this module prevents the calendar events from being posted to the stream. You can change this by editing the settings in the admin area:
> administration->module->calendar extension->configuration


*Hints*:
- When you try to add an external calendar, the module first checks whether the URL you added is correct and can be converted to an iCal file.
**Therefore you need a url with** `http://` or `https://`
For example:
```
Wrong: webcal://calendar.google.com/calendar/ical/....
Right: https://calendar.google.com/calendar/ical/...
```

## Tested Calendars
- Google Calendar ([private adress](https://support.google.com/calendar/answer/37648?hl=en) only)
- [MEP24 Calendar](https://www.mep24software.de/kalender-freigeben/)

__Module website:__ <https://github.com/staxDB/humhub-modules-external-calendar.git>    
__Author:__ David Born    

## Changelog
<https://github.com/staxDB/humhub-modules-external-calendar/commits/master>

## Bugtracker
<https://github.com/staxDB/humhub-modules-external-calendar/issues>

## ToDos
- fix bug with calendar-widget "upcoming events"
- test other calendars than google (only private link tested)
- add more translations


This Module uses the Calendar UI Interface in v0.6 - [see dokumentation](https://github.com/humhub/humhub-modules-calendar/blob/master/docs/interface.md)
