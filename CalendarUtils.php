<?php

namespace humhub\modules\external_calendar;

use Yii;
use DateTime;
use DateTimeZone;

/**
 * Description of CalendarUtils
 *
 * @author David Born ([staxDB](https://github.com/staxDB))
 */
class CalendarUtils
{

    private static $userTimezone;

    const DB_DATE_FORMAT = 'Y-m-d H:i:s';
    const ICAL_TIME_FORMAT        = 'Ymd\THis';

    /**
     *
     * @param DateTime $date1
     * @param DateTime $date2
     * @param bool|type $endDateMomentAfter
     * @return bool
     */
    public static function isFullDaySpan(DateTime $date1, DateTime $date2, $endDateMomentAfter = false)
    {
        $dateInterval = $date1->diff($date2, true);

        if ($endDateMomentAfter) {
            if ($dateInterval->days > 0 && $dateInterval->h == 0 && $dateInterval->i == 0 && $dateInterval->s == 0) {
                return true;
            }
        } else if ($dateInterval->h == 23 && $dateInterval->i == 59) {
                return true;
        }

        return false;
    }

    public static function checkAllDay($dtstart, $dtend)
    {
        $start = self::formatDateTimeToAppTime($dtstart);
        $end = self::formatDateTimeToAppTime($dtend);

        if (self::isFullDaySpan($start, $end, true)) {
            return 1;
        } else {
            return 0;
        }
    }



    public static function formatDateTimeToUTC($string)
    {
        $timezone = new DateTimeZone('UTC');
        $datetime = new DateTime($string);
        return $datetime->setTimezone($timezone);
    }

    public static function cleanRecurrentId($recurrentId, $targetTZ = null)
    {
        $date = ($recurrentId instanceof \DateTimeInterface) ? $recurrentId : new DateTime($recurrentId, new DateTimeZone('UTC'));

        if($targetTZ) {
            $date->setTimezone(new DateTimeZone($targetTZ));
        }

        return $date->format(static::ICAL_TIME_FORMAT);
    }

    /**
     * @return DateTimeZone
     */
    public static function getUserTimeZone()
    {
        if(!static::$userTimezone) {
            $tz =  Yii::$app->user->isGuest
                ? Yii::$app->timeZone
                : Yii::$app->user->getTimeZone();

            if(!$tz) {
                $tz = Yii::$app->timeZone;
            }

            if($tz) {
                static::$userTimezone = new DateTimeZone($tz);
            }
        }

        return static::$userTimezone;
    }


    public static function formatDateTimeToString($string)
    {
        return self::formatDateTimeToAppTime($string)->format(static::DB_DATE_FORMAT);
    }

    public static function formatDateTimeToAppTime($string)
    {
        $timezone = new DateTimeZone(Yii::$app->timeZone);
        $datetime = new DateTime($string);
        return $datetime->setTimezone($timezone);
    }


    public static function toDBDateFormat($date)
    {
        if(!$date) {
            return null;
        }

        if(is_string($date)) {
            $date = new DateTime($date);
        }

        return $date->setTimezone(new DateTimeZone(Yii::$app->timeZone))->format(static::DB_DATE_FORMAT);
    }
}
