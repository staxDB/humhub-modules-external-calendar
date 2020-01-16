## Description
Calendar extension for integration of external calendars with [iCalendar-format](https://en.wikipedia.org/wiki/ICalendar).

This module supports importing, syncrhonization and exporting of iCalendar calendar feeds.

Depending on the export service you use for your ical import, you may notice different results due to differences in the used iCal format. In case you experience any unexpected results, please let us know in the issue section.

- Note: The export feature will be available without the need of a space level module installation. In order to use the import of external calendar, this module has to be installed on a space level.

**Requirements**
 - HumHub v1.3.14
 - Calendar Module v0.7.5

**Be careful!**
Some calendars are not intended for permanent synchronization. These do not send a "last_modified" timestamp. 
Please do not select the option `auto sync hourly` for these. It could slow down your system..

This module was forked from:
__Author:__ David Born ([staxDB](https://github.com/staxDB))
__Module website:__ <https://github.com/staxDB/humhub-modules-external-calendar>  
