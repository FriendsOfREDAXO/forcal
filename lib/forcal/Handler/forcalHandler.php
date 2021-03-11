<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Handler;


use rex;
use rex_clang;
use rex_sql;
use forCal\Utils\forCalDateTimeHelper;
use forCal\Utils\forCalDefinitions;
use forCal\Utils\forCalHelper;
use forCal\Utils\forCalTableKey;

class forCalHandler
{
    /**
     * @var array
     */
    protected static $dateList = array();

    /**
     * @var null|int
     */
    public static $pageCount = null;

    /**
     * @var null|int
     */
    public static $numberOfPages = null;

    /**
     * @var array
     */
    private static $days = array(
        'sun' => 'sunday',
        'sat' => 'saturday',
        'mon' => 'monday',
        'tue' => 'tuesday',
        'wed' => 'wednesday',
        'thu' => 'thursday',
        'fri' => 'friday',
    );

    /**
     * @param \stdClass $entry
     * @param \DateTime $startSearchDate
     * @param \DateTime $endSearchDate
     * @param bool $iterateBetween
     * @return \stdClass
     * @throws \Exception
     * @author Joachim Doerr
     */
    protected static function decorateEntryRepeats(\stdClass $entry, $startSearchDate = null, $endSearchDate = null, $iterateBetween = true)
    {
        $entry->entry_start_date = new \DateTime($entry->entry_start_date);
        $entry->entry_end_date = new \DateTime($entry->entry_end_date);
        $entry->entry_range = $entry->entry_start_date->diff($entry->entry_end_date);

        if (!empty($entry->end_repeat_date)) {
            $entry->end_repeat_date = new \DateTime($entry->end_repeat_date);
        }

        if ($entry->type == 'repeat' && $entry->end_repeat_date instanceof \DateTime) {

            switch ($entry->repeats) {
                case 'weekly':
                    $intervalRepeats = $entry->repeat_weeks * 7;
                    $intervalSpecKey = 'D';
                    break;
                case 'monthly':
                    $intervalRepeats = $entry->repeat_months;
                    $intervalSpecKey = 'M';
                    break;
                case 'yearly':
                    $intervalRepeats = $entry->repeat_years;
                    $intervalSpecKey = 'Y';
                    break;
                default:
                    $intervalRepeats = 1;
                    $intervalSpecKey = 'D';
                    continue;
            }

            /** @var \DateTime $end */
            $endDate = clone $entry->end_repeat_date;
            $endDate->modify('+1 day');
            $days = self::$days;

            if ($entry->repeats == 'monthly-week') {
                $interval = \DateInterval::createFromDateString("{$entry->repeat_month_week} {$days[$entry->repeat_day]} of next month");
            } else {
                $interval = new \DateInterval('P' . $intervalRepeats . $intervalSpecKey);
            }

            $dateRange = new \DatePeriod($entry->entry_start_date, $interval, $endDate);
            $between = false;

            /** @var \DateTime $date */
            foreach ($dateRange as $date) {

                $dateBetween = true;
                $endRepeatDate = clone $date;
                $endRepeatDate->modify('+ ' . $entry->entry_range->days . ' days');

                if ($startSearchDate instanceof \DateTime && $endSearchDate instanceof \DateTime) {
                    $dateBetween = forCalDateTimeHelper::isDateRangeBetweenDateRange($date, $endRepeatDate, $startSearchDate, $endSearchDate);
                }

                if($dateBetween === false && $iterateBetween === true) {
                    continue;
                }

                if (!property_exists($entry, 'dates')) {
                    $entry->dates = array();
                }

                // many dates

                $entryRepeatDate = new \stdClass();
                $entryRepeatDate->entry_start_date = $date;
                $entryRepeatDate->entry_end_date = $endRepeatDate;
                $entryRepeatDate->between_search_range = $dateBetween;

                $entry->dates[] = $entryRepeatDate;

                if ($entryRepeatDate->between_search_range === true) {

                    $between = true;

                    $dataListEntry = clone $entry;
                    $dataListEntry->entry_start_date = $entryRepeatDate->entry_start_date;
                    $dataListEntry->entry_end_date = $entryRepeatDate->entry_end_date;
                    unset($dataListEntry->dates);

                    self::$dateList[] = array(
                        'id' => $entry->entry_id,
                        'sort_key' => $entryRepeatDate->entry_start_date->format("Ymd") . str_replace(':','', $entry->entry_start_time),
                        'date' => $entryRepeatDate,
                        'entry' => $dataListEntry
                    );

                }
            }

            $entry->view_in_range = $between;

        } else {

            // only one time entry

            $entryRepeatDate = new \stdClass();
            $entryRepeatDate->entry_start_date = $entry->entry_start_date;
            $entryRepeatDate->entry_end_date = $entry->entry_end_date;
            $entryRepeatDate->between_search_range = true; // one time entries over the sql are ever right

            $dataListEntry = clone $entry;

            $entry->dates[] = $entryRepeatDate;

            self::$dateList[] = array(
                'id' => $entry->entry_id,
                'sort_key' => $entryRepeatDate->entry_start_date->format("Ymd") . str_replace(':','', $entry->entry_start_time),
                'date' => $entryRepeatDate,
                'entry' => $dataListEntry
            );

            $entry->view_in_range = true; // one time entries over the sql are ever right
        }

        return $entry;
    }

    /**
     * @param array $dataList
     * @param bool $short
     * @param int $dateFormat
     * @param int $timeFormat
     * @return array
     * @throws \Exception
     */
    protected static function decorateEntries(array $dataList, $short = true, $dateFormat = 1, $timeFormat = 1)
    {
        if (sizeof($dataList) <= 0) {
            return array();
        }

        $decoratedEntries = array();

        foreach ($dataList as $data) {
            $decoratedEntries[] = self::decorateEntry($data['entry'], $short, $dateFormat, $timeFormat);
        }

        return $decoratedEntries;
    }

    /**
     * @param \stdClass $entry
     * @param bool $short
     * @param int $dateFormat
     * @param int $timeFormat
     * @return array
     * @throws \Exception
     */
    protected static function decorateEntry(\stdClass $entry, $short = true, $dateFormat = 1, $timeFormat = 1)
    {
        $start = new \DateTime($entry->entry_start_date->format('Y-m-d') . ' ' . $entry->entry_start_time);
        $end = new \DateTime($entry->entry_end_date->format('Y-m-d') . ' ' . $entry->entry_end_time);
        /** @var \DateInterval $interval */
        $interval = $entry->entry_range;

        $startDate = $start->format('Y-m-d\TH:i:s');

        if ($start->format('H:i:s') == '00:00:00') {
            $startDate = $start->format('Y-m-d');
        }

        $endDate = $end->format('Y-m-d\TH:i:s');

        if ($interval->days == 0 && $start->format('His') < $end->format('His')) {} else
            $end->modify('+1 day');

        if ($end->format('H:i:s') == '00:00:00') {
            $endDate = $end->format('Y-m-d');
        }


        $eventDTO = array(
            'id' => $entry->entry_id,
            'title' => $entry->entry_name,
            'date_time' => array_merge(
                forCalDateTimeHelper::getFromToDateTime($start, $end, $interval, $dateFormat, $timeFormat),
                array(
                    'full_time' => (bool)$entry->full_time,
                )
            ),
            'start' => $startDate,
            'end' => $endDate
        );

        if (!empty($entry->category_color)) {
            $eventDTO['color'] = $entry->category_color;
        }
        $short = false;
        if (!$short) {

            // repeat info
            if ($entry->type == 'repeat' && !empty($entry->repeats)) {

                $entry->repeat = $entry->repeats;

                switch ($entry->repeats) {
                    case 'weekly':
                        $entry->repeat_interval = $entry->repeat_weeks;
                        break;
                    case 'monthly':
                        $entry->repeat_interval = $entry->repeat_months;
                        break;
                    case 'monthly-week':
                        $days = self::$days;
                        $entry->repeat_interval = "{$entry->repeat_month_week} {$days[$entry->repeat_day]} of next month";
                        break;
                    case 'yearly':
                        $entry->repeat_interval = $entry->repeat_years;
                        break;
                }

                unset($entry->repeats);
                unset($entry->repeat_weeks);
                unset($entry->repeat_months);
                unset($entry->repeat_years);
            }

            // add all other properties
            foreach ($entry as $key => $values) {
                if (in_array($key, array('entry_id','entry_name','entry_range', 'view_in_range'))){
                    continue;
                }

                if ($values instanceof \DateTime) {
                    $values = $values->format(\DateTime::ISO8601);
                }
                if ($values instanceof \DateInterval) {
                    $values = $values->days;
                }

                if (is_array($values)) {
                    foreach ($values as $ky => $val) {
                        if ($val instanceof \stdClass && property_exists($val, 'entry_start_date')) {

                            $values[$ky] = (array) $val;

                            foreach ($values[$ky] as $k => $v) {

                                if (in_array($k, array('between_search_range'))) {
                                    unset($values[$ky][$k]);
                                }

                                if ($v instanceof \DateTime) {
                                    $values[$ky][$k] = $v->format(\DateTime::ISO8601);
                                }
                            }
                        }
                    }
                }

                $eventDTO[str_replace('entry_', '', $key)] = $values;
            }
        }

        return $eventDTO;
    }

    /**
     * @return array
     */
    protected static function createSelect()
    {
        $additional_for_title = \rex_config::get('forcal','forcal_additional_for_title');

        $name = 'en.name_' . rex_clang::getCurrentId() . ' AS entry_name';
        if ($additional_for_title) {
            $name = 'CONCAT(en.name_'.rex_clang::getCurrentId().'," - ",ca.'.$additional_for_title.'_'.rex_clang::getCurrentId().') entry_name';
        }

        $select = array(
            'en.id AS entry_id',
            $name,
            'en.start_date AS entry_start_date',
            'en.end_date AS entry_end_date',
            'en.start_time AS entry_start_time',
            'en.end_time AS entry_end_time',
            'en.end_repeat_date AS end_repeat_date',
            'en.type AS type',
            'en.repeat AS repeats',
            'en.repeat_week AS repeat_weeks',
            'en.repeat_month AS repeat_months',
            'en.repeat_year AS repeat_years',
            'en.repeat_month_week AS repeat_month_week',
            'en.repeat_day AS repeat_day',
            'ca.color AS category_color',
            'en.status AS entry_status',
            'en.full_time AS full_time',
            'PERIOD_DIFF(DATE_FORMAT(en.end_date, "%Y%m"), DATE_FORMAT(en.start_date, "%Y%m")) AS entry_month_diff',
            'IFNULL(ca.status, 1) AS category_status',
            'IFNULL(ve.status, 1) AS venue_status',
            'en.teaser_' . rex_clang::getCurrentId() . ' AS entry_teaser',
            'en.text_' . rex_clang::getCurrentId() . ' AS entry_text',
            'ca.name_' . rex_clang::getCurrentId() . ' AS category_name',
            'ca.id AS category_id',
            've.name_' . rex_clang::getCurrentId() . ' AS venue_name',
            've.id AS venue_id',
        );

        $definitionFields = array();

        foreach (forCalDefinitions::getDefinitions() as $table => $definition) {
            foreach ($definition['data'] as $fieldsetKey => $fieldset) {
                switch ($fieldsetKey) {
                    case 'langfields':
                        $fields = forCalDatabaseFieldsetHandler::handleLangDatabaseFieldset($fieldset, $table);
                        $definitionFields[] = array($table => $fields['select']);
                        break;
                    case 'fields':
                        $fields = forCalDatabaseFieldsetHandler::handleDatabaseFieldset($fieldset, $table);
                        $definitionFields[] = array($table => $fields['select']);
                        break;
                }
            }
        }

        $fields = array();

        if (sizeof($definitionFields) > 0) {
            foreach ($definitionFields as $tableDefinitionFields) {
                foreach ($tableDefinitionFields as $table => $definitionTableFields) {
                    foreach ($definitionTableFields as $definitionTableField) {
                        if (is_integer(substr($definitionTableField, -1))) {
                            if (substr($definitionTableField, -2) == '_' . rex_clang::getCurrentId()) {
                                $fields[] = forCalTableKey::getTableShortKey($table) . '.' . $definitionTableField . ' AS ' . forCalTableKey::getTableFullKey($table) . '_' . substr($definitionTableField, 0, -2);
                            }
                        } else {
                            $fields[] = forCalTableKey::getTableShortKey($table) . '.' . $definitionTableField . ' AS ' . forCalTableKey::getTableFullKey($table) . '_' . $definitionTableField;
                        }
                    }
                }
            }
        }

        if (sizeof($fields) > 0) {
            $select = array_merge($select, $fields);
        }


        return $select;
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param bool $ignoreStatus
     * @param int|null|array $categoryId
     * @param int|null $venueId
     * @return array
     * @throws \rex_sql_exception
     */
    protected static function loadEntries(\DateTime $startDate, \DateTime $endDate, $ignoreStatus = true, $categoryId = null, $venueId = null)
    {
        $select = self::createSelect();
        $statusIgnore = '';
        $statusHaving = '';
        $venue = '';
        $category = '';
        $endDate->modify("+1 days");

        if (!$ignoreStatus) {
            $statusIgnore = ' AND en.status = 1 ';
        }

        if (!$ignoreStatus) {
            $statusHaving = ' HAVING category_status = 1';
        }

        if (is_array($categoryId) && sizeof($categoryId) > 0) {
            $category = ' AND en.category IN ("' . implode('", "', $categoryId) . '")';
        } else {

            $catId = (int) $categoryId;

            if ($catId > 0) {
                $category = ' AND en.category = ' . $catId . ' ';
            }
        }

        if (!is_null($venueId)) {
            $venue = ' AND en.venue = ' . $venueId . ' ';
        }

        $sql = rex_sql::factory();
        $query = '
          SELECT
            ' .implode(', ', $select). '
          FROM
            ' . rex::getTablePrefix() . 'forcal_entries AS en
             
          LEFT JOIN
            ' . rex::getTablePrefix() . 'forcal_venues AS ve 
          ON
            ve.id = en.venue
            
          LEFT JOIN
            ' . rex::getTablePrefix() . 'forcal_categories AS ca 
          ON
            ca.id = en.category
            
          WHERE
            en.type = \'one_time\' AND
            ((en.start_date BETWEEN \'' . $startDate->format("Y-m-d H:i:s") . '\' AND \''. $endDate->format("Y-m-d H:i:s") .'\') OR
            (en.end_date BETWEEN \'' . $startDate->format("Y-m-d H:i:s") . '\' AND \''. $endDate->format("Y-m-d H:i:s") .'\') OR
            en.start_date <= \'' . $startDate->format("Y-m-d H:i:s") . '\' AND
            en.end_date >= \'' . $endDate->format("Y-m-d H:i:s") . '\')
            ' . $statusIgnore . $category . $venue . $statusHaving . '
            
          UNION
          
          SELECT
            ' .implode(', ', $select). '
          FROM
            ' . rex::getTablePrefix() . 'forcal_entries AS en
             
          LEFT JOIN
            ' . rex::getTablePrefix() . 'forcal_venues AS ve 
          ON
            ve.id = en.venue
            
          LEFT JOIN
            ' . rex::getTablePrefix() . 'forcal_categories AS ca 
          ON
            ca.id = en.category
            
          WHERE
            en.type = \'repeat\' AND
            ((en.start_date BETWEEN \'' . $startDate->format("Y-m-d H:i:s") . '\' AND \''. $endDate->format("Y-m-d H:i:s") .'\') OR
            (en.end_repeat_date BETWEEN \'' . $startDate->format("Y-m-d H:i:s") . '\' AND \''. $endDate->format("Y-m-d H:i:s") .'\') OR
            en.start_date <= \'' . $startDate->format("Y-m-d H:i:s") . '\' AND
            en.end_repeat_date >= \'' . $endDate->format("Y-m-d H:i:s") . '\')
            ' . $statusIgnore . $category . $venue . $statusHaving . '
            
          ORDER BY 
            entry_start_date, entry_start_time
        ';

        # SELECT * FROM rex_entry_calendar WHERE
        # (start_date BETWEEN "2016-09-09" AND "2016-09-12") OR
        # (end_date BETWEEN '2016-09-09' AND '2016-09-12') OR
        # start_date <= '2016-09-09' AND end_date >= '2013-09-12'

        return $sql->getArray($query);
    }

    /**
     * @param $id
     * @return array
     * @throws \rex_sql_exception
     */
    protected static function loadEntry($id)
    {
        $select = self::createSelect();
        $sql = rex_sql::factory();
        $sql->setQuery('
          SELECT
            ' .implode(', ', $select). '
          FROM
            ' . rex::getTablePrefix() . 'forcal_entries AS en
             
          LEFT JOIN
            ' . rex::getTablePrefix() . 'forcal_venues AS ve 
          ON
            ve.id = en.venue
            
          LEFT JOIN
            ' . rex::getTablePrefix() . 'forcal_categories AS ca 
          ON
            ca.id = en.category

          WHERE
            en.id = ' . $id . '
        ');

        return $sql->getArray();
    }

    /**
     * @param $id
     * @return array
     * @throws \rex_sql_exception
     * @author Joachim Doerr
     */
    public static function getEntry($id)
    {
        $result = self::loadEntry($id);
        $entries = array();

        if (is_array($result) && sizeof($result) > 0) {
            foreach ($result as $key => $entry) {
                $entries[] = self::decorateEntryRepeats((object)$entry);
            }
        }

        return $entries;
    }

    /**
     * @param string|\DateTime $start | Y-m-d
     * @param string|\DateTime $end | Y-m-d
     * @param bool $ignoreStatus
     * @param int|string $sort [SORT_ASC, SORT_DESC]
     * @param int|null|array $categoryId
     * @param int|null $venueId
     * @param null $pageSize
     * @param null $pageNumber
     * @return array <\StdClass>
     * @throws \rex_sql_exception
     * @author Joachim Doerr
     */
    public static function getEntries($start, $end, $ignoreStatus = false, $sort = SORT_ASC, $categoryId = null, $venueId = null, $pageSize = null, $pageNumber = null)
    {
        $today = new \DateTime();
        $monthEnd = clone $today;
        $monthEnd->modify("+1 months");

        // unset for multiple calls
        self::$dateList = array();

        if (!$start instanceof \DateTime) {
            $startDate = ($start != '') ? new \DateTime($start) : new \DateTime($today->format("Y-m-01"));
        } else {
            $startDate = $start;
        }

        if (!$end instanceof \DateTime) {
            $endDate = ($end != '') ? new \DateTime($end) : $monthEnd;
        } else {
            $endDate = $end;
        }

        $result = self::loadEntries($startDate, $endDate, $ignoreStatus, $categoryId, $venueId);
        $entries = array();

        if (is_array($result) && sizeof($result) > 0) {
            foreach ($result as $key => $entry) {
                $entries[] = self::decorateEntryRepeats((object)$entry, $startDate, $endDate, true);
            }
        }

        // sort data list
        forCalHelper::arraySortByColumn(self::$dateList, 'sort_key', $sort);

        // pagination
        self::$pageCount = sizeof(self::$dateList);

        if (is_int($pageSize)) {
            $pageNumber = (is_int($pageNumber)) ? $pageNumber : 1;
            self::$numberOfPages = ceil(self::$pageCount / $pageSize);

            $page = min($pageNumber, self::$numberOfPages);
            $offset = ($page - 1) * $pageSize;
            if( $offset < 0 ) $offset = 0;

            self::$dateList = array_slice( self::$dateList, $offset, $pageSize );
        }

        return self::$dateList;
    }

    /**
     * @param $id
     * @param bool $short
     * @param int $dateFormat
     * @param int $timeFormat
     * @return array
     * @throws \rex_sql_exception
     * @author Joachim Doerr
     */
    public static function exchangeEntry($id, $short = true, $dateFormat = 1, $timeFormat = 1)
    {
        $entry = self::getEntry($id);

        // use the data list
        $decoratedEntries = self::decorateEntry($entry[0], $short, $dateFormat, $timeFormat);

        return $decoratedEntries;
    }

    /**
     * @param $start
     * @param $end
     * @param bool $short
     * @param bool $ignoreStatus
     * @param string $sort [SORT_ASC, SORT_DESC]
     * @param null|int|array $categoryId
     * @param null|int $venueId
     * @param int $dateFormat
     * @param int $timeFormat
     * @param null $pageSize
     * @param null $pageNumber
     * @return array
     * @throws \rex_sql_exception
     * @author Joachim Doerr
     */
    public static function exchangeEntries($start, $end, $short = true, $ignoreStatus = false, $sort = 'SORT_ASC', $categoryId = null, $venueId = null, $dateFormat = 1, $timeFormat = 1, $pageSize = null, $pageNumber = null)
    {
        // get entries with date ranges
        // create data list
        self::getEntries($start, $end, $ignoreStatus, $sort, $categoryId, $venueId, $pageSize, $pageNumber);

        // use the data list
        $decoratedEntries = self::decorateEntries(self::$dateList, $short, $dateFormat, $timeFormat);

        return $decoratedEntries;
    }
}
