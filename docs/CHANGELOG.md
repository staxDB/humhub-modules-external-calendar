Changelog
=========

1.4.0 (Unreleased)
---------------------
- Fix: Hard delete calendar items on synchronization of imported calendar
- Enh #140: Use widget ContentVisibiltySelect
- Enh: Use default timezone instead of deprecated server timezone
- Enh: Added French translations
- Fix #47: Error adding an iCalendar link (updated ics-parser library to version 3)
- Enh: Validation added for better understanding

1.3.3 (March 7, 2023)
---------------------
- Fix #43: Calendar entry description is missing line breaks
- Enh #40: Render URL's as clickable links in imported events

1.3.0 (December 23, 2022)
-------------------------
- Enh: Use default timezone instead of deprecated server timezone
- Enh: Remove deprecated checkbox "regular" style
- Fix #100: Remove unnecessary migration that removed module folder on first enabling
- Fix #101: Display warning when cannot enable module
- Enh #5274: Deprecate CompatModuleManager

v1.2.0 (November 04, 2020)
----------------------
- Enh #17: Added export button tooltip
- Chng: 1.7 WallEntry migration
- Chng: Update min HumHub version to 1.7
- Fix: PHP 7.4 compatibility

v1.1.12 (September 15, 2020)
----------------------
- Fix: Fixed 1.6 compatibility global controller access is blocked by strict access

v1.1.11 (July 31, 2020)
----------------------
- Fix: Recurrent instance exceptions not respected in ICalExpand

v1.1.10 (July 29, 2020)
----------------------
- Fix: External calendar validation errors does not redirect to form
- Enh: Raised max title length to 100
- Enh #8: Improve formatting of external event wall entries (verement)
- Enh #9: Rename "Event" content type to "External Event" (verement)
- Fix #12: External calendar sync on unmodified events overwrites old event (verement)
- Fix #13: ICalFileEvent:getDateTimeFromDTArray() always using dtstart as fallback when using Datetime format (verement)
- Fix #14: Inconsistent capitalization of getEndDateTime() (verement)

v1.1.9
----------------------
- Fix #7: Sync events only if LAST-MODIFIED is set

v1.1.8
----------------------
- Fix #4: Unable to delete calendar exports

v1.1.7
----------------------
- Fix: All day events with given timezone are parsed with time
- Fix: Removed DBDatevalidator

v1.1.6
----------------------
- Fix: Patch for duplicate module directory after update due to marketplace validation bug

v1.1.5
----------------------
- Fix #1: HTML content description is encoded
- Fix #2: Unique index may throw `max key length` error

v1.1.4
---------------------------
- Enh: Updated translations

v1.1.2
----------------------
- Fix: Different cases of dtstart and dtend handled incorrectly
- Enh: Added vevent duration support

v1.1.1
----------------------
- Fix: Importing ICal events without Modification Date not working
- Fix: Importing ICal events with start = end date not working

v1.1
----------------------
- Fix: Sync Jobs not working

v1.0
----------------------
- Enh: Better calendar integration
- Chng: Requires Calendar Module version 0.7.3
- Enh: Enhanced ICal synchronization
- Enh: Add ICal export

v0.2.2
----------------------
- Changed Cron:
    - module now triggers hourly/daily-crons instead of their IDs
- Removed Deprecations:
    - Changed className()- to class-function
    - Changed arrays to short version []

v0.2.1
----------------------
- Updated to yii/base/BaseObject

v0.2
----------------------
- Updated for Humhub v.1.3 and async tasks
- Ready for PHP 7.2
- Added: Export single entries to ICS

v0.1.5
----------------------
- Added: Option for choosing calendar title as `badge-title`
- Updated: Behaviour of integrating calendar - Now each external calendar will be added separately, so you're able to disable an external calendar via `calendar-settings`.
- Updated: Title of external calendar limited to 15 chars
- Updated: Third-party code to latest version (2.1.1 to 2.1.2)

v0.1.4
----------------------
- Changed the way of integrating third-party code in `vendors` -folder

v0.1.3
----------------------
- Changed sync-method to async task using [asynchronous tasks](http://docs.humhub.org/admin-asynchronous-tasks.html)

v0.1.2
----------------------
- First stable release

