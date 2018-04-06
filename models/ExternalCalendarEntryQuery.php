<?php
namespace humhub\modules\external_calendar\models;

use humhub\modules\calendar\interfaces\AbstractCalendarQuery;


/**
 * CalendarEntryQuery class can be used for creating filter queries for [[CalendarEntry]] models.
 * 
 * The class follows the builder pattern and can be used as follows:
 * 
 *  ```php
 * // Find all CalendarEntries of user profile of $user1 
 * CalendarEntryQuery::find()->container($user1)->limit(20)->all();
 * 
 * // Find all entries from 3 days in the past till three days in the future
 * CalendarEntryQuery::find()->from(-3)->to(3)->all();
 * 
 * // Find all entries within today at 00:00 till three days in the future at 23:59
 * CalendarEntryQuery::find()->days(3)->all();
 * 
 * // Filter entries where the current user is participating
 * CalendarEntryQuery::find()->participate();
 * 
 * // Filter entries where $user1 is invited
 * CalendarEntryQuery::find($user1)->invited()->all();
 * 
 * // Only build the query of the last example
 * $query = CalendarEntryQuery::find($user1)->invited()->query(true);
 * ```
 * 
 * > Note: If [[from()]] and [[to()]] is set, the query will use an open range query by default, which
 * means either the start time or the end time of the [[CalendarEntry]] has to be within the searched interval.
 * This behaviour can be changed by using the [[openRange()]]-method. If the openRange behaviour is deactivated
 * only entries with start and end time within the search interval will be included.
 * 
 * > Note: By default we are searching in whole day intervals and get rid of the time information of from/to boundaries by setting
 * the time of the from date to 00:00:00 and the time of the end date to 23:59:59. This behaviour can be deactivated by using the [[withTime()]]-method.
 * 
 * The following filters are available:
 * 
 *  - [[from()]]: Date filter interval start
 *  - [[to()]]: Date filter interval end
 *  - [[days()]]: Filter by future or past day interval
 *  - [[months()]]: Filter by future or past month interval
 *  - [[years()]]: Filter by future or past year interval
 * 
 *  - [[container()]]: Filter by container
 *  - [[userRelated()]]: Adds a user relation by the given or default scope (e.g: Following Spaces, Member Spaces, Own Profile, etc.)
 *  - [[invited()]]: Given user is invited
 *  - [[participant()]]: Given user accepted invitation
 *  - [[mine()]]: Entries created by the given user
 *  - [[responded()]]: Entries where given user has given any response (accepted/declined...)
 *  - [[notResponded()]]: Entries where given user has not given any response yet (accepted/declined...)
 *
 * @author davidborn
 */
class ExternalCalendarEntryQuery extends AbstractCalendarQuery
{
    /**
     * @inheritdocs
     */
    protected static $recordClass = ExternalCalendarEntry::class;
//
//    public $startField = 'start_datetime';
//    public $endField = 'end_datetime';
//    public $dateFormat = 'Y-m-d H:i:s';
}
