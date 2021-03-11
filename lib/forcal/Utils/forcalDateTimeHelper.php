<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;


class forCalDateTimeHelper
{
    /**
     * @param \DateTime $startDateTime
     * @param \DateTime $endDateTime
     * @param \DateInterval $interval
     * @param int $dateFormat
     * @param int $timeFormat
     * @return array
     * @author Joachim Doerr
     */
    public static function getFromToDateTime(\DateTime $startDateTime, \DateTime $endDateTime, \DateInterval $interval, $dateFormat = 1, $timeFormat = 1)
    {
        $endDate = clone $endDateTime;
        $endDate1 = clone $endDateTime;


        if ($interval->days == 0 && $startDateTime->format('His') < $endDateTime->format('His')) {} else
            $endDate->modify("-1 day");

        if ($interval->days > 0 && $startDateTime->format('His') != $endDateTime->format('His'))
            $endDate1->modify("-1 day");

        return array('date' => self::getFromToDate($startDateTime, $endDate, $dateFormat),
            'time' => self::getFromToTime($startDateTime, $endDate, $timeFormat),
            'start' => $startDateTime->format(\DateTime::ISO8601),
            'end' => $endDate1->format(\DateTime::ISO8601),
            'date_interval_days' => $interval->days,
        );
    }

    /**
     * @param \DateTime $startDateTime
     * @param \DateTime $endDateTime
     * @param int $format
     * @return string
     * @author Joachim Doerr
     */
    public static function getFromToDate(\DateTime $startDateTime, \DateTime $endDateTime, $format = 1, $intervalDays = "")
    {
        $otherYear = ($startDateTime->format("Y") < $endDateTime->format("Y")) ? true : false;
        $otherMonth = ($startDateTime->format("m") != $endDateTime->format("m")) ? true : false;
        $otherDay = ($startDateTime->format("d") != $endDateTime->format("d")) ? true : false;

        switch ($format) {
            case 1:		$format = "d.m.";
						break;

            case 2:		$format = "d.m.Y";
						break;
        }

        if (($otherMonth or $otherDay or $otherYear) and ($intervalDays > 0 or !is_numeric($intervalDays)) ) {
            return $startDateTime->format($format) . ' - ' . $endDateTime->format($format);
        }
        return $startDateTime->format($format);
    }

    /**
     * @param \DateTime $startDateTime
     * @param \DateTime $endDateTime
     * @param int $format
     * @return bool|string
     * @author Joachim Doerr
     */
    public static function getFromToTime(\DateTime $startDateTime, \DateTime $endDateTime, $format = 1)
    {
        $otherTime = ($startDateTime->format("Hi") < $endDateTime->format("Hi")) ? true : false;

        if ($startDateTime->format("Hi") == '0000' && $endDateTime->format("Hi") == '0000') {
            return false;
        }

        switch ($format) {
            case 1:
                $format = "H:i";
                break;
        }

        if ($otherTime) {
            return $startDateTime->format($format) . ' - ' . $endDateTime->format($format);
        }
        return $startDateTime->format($format);
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param \DateTime $startSearch
     * @param \DateTime $endSearch
     * @return bool
     * @author Joachim Doerr
     */
    public static function isDateRangeBetweenDateRange(\DateTime $start, \DateTime $end, \DateTime $startSearch, \DateTime $endSearch)
    {
        $startBetween = true;
        $endBetween = true;

        if ($end > $endSearch or $end < $startSearch) {
            $endBetween = false;
        }
        if ($start < $startSearch or $start > $endSearch) {
            $startBetween = false;
        }

        return ($endBetween === false && $startBetween === false) ? false : true;
    }

}
