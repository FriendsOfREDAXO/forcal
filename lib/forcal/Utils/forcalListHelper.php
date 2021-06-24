<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;


use rex_i18n;
use rex_list;
use rex_sql;
use rex_view;

class forCalListHelper
{
    public static function formatStartDate($params)
    {
        /** @var rex_list $list */
        $list = $params["list"];
        $startTime = new \DateTime($list->getValue('start_date'));
        $endTime = new \DateTime($list->getValue('end_date'));
        $date = forCalDateTimeHelper::getFromToDate($startTime, $endTime, "d.m.Y");
        return $date;
    }

    public static function formatStartTime($params)
    {
        /** @var rex_list $list */
        $list = $params["list"];
        $startTime = new \DateTime($list->getValue('start_date') . ' ' . $list->getValue('start_time'));
        $endTime = new \DateTime($list->getValue('end_date') . ' ' . $list->getValue('end_time'));
        $time = forCalDateTimeHelper::getFromToTime($startTime, $endTime, 1);
        return $time;
    }

    public static function formatCategory($params)
    {
        /** @var rex_list $list */
        $list = $params["list"];
        return $list->getColumnLink("category", "<span style=\"color:###color###\">###category###</span>");
    }

    /**
     * gibt alle categorie-namen als string zurück
     * @param array $params
     * @return string
     * @author Joachim Doerr
     */
    static public function formatVenue($params)
    {
    }

    /**
     * toggle link on off
     * @param array $params
     * @return mixed
     * @author Joachim Doerr
     */
    public static function formatStatus($params)
    {
        /** @var rex_list $list */
        $list = $params["list"];
        if ($list->getValue("status") == 1) {
            $str = $list->getColumnLink("status", "<span class=\"rex-online\"><i class=\"rex-icon rex-icon-online\"></i> " . rex_i18n::msg('forcal_online') . "</span>");
        } else {
            $str = $list->getColumnLink("status", "<span class=\"rex-offline\"><i class=\"rex-icon rex-icon-offline\"></i> " . rex_i18n::msg('forcal_offline') . "</span>");
        }
        return $str;
    }

    /**
     * togglet bool data column
     * @param $table
     * @param $id
     * @param null $column
     * @return string
     * @author Joachim Doerr
     * @throws \rex_sql_exception
     */
    public static function toggleBoolData($table, $id, $column = NULL)
    {
        if (!is_null($column)) {
            $sql = rex_sql::factory();
            $sql->setQuery("UPDATE $table SET $column=ABS(1-$column) WHERE id=$id");
            return rex_view::info(rex_i18n::msg($table . '_toggle_' . $column . '_success'));
        } else {
            return rex_view::warning(rex_i18n::msg($table . '_toggle_' . $column . '_error'));
        }
    }

    /**
     * clone data
     * @param $table
     * @param $id
     * @return string
     * @author Joachim Doerr
     * @throws \rex_sql_exception
     */
    static public function cloneData($table, $id)
    {
        $sql = rex_sql::factory();
        $fields = $sql->getArray('DESCRIBE `' . $table . '`');
        if (is_array($fields) && count($fields) > 0) {
            foreach ($fields as $field) {
                if ($field['Key'] != 'PRI' && $field['Field'] != 'status') {
                    $queryFields[] = $field['Field'];
                }
            }
        }
        $sql->setQuery('INSERT INTO ' . $table . ' (`' . implode('`, `', $queryFields) . '`) SELECT `' . implode('`, `', $queryFields) . '` FROM ' . $table . ' WHERE id =' . $id);
        $lastId = $sql->getLastId();
        $sql->setQuery('SELECT name_1 FROM ' . $table . ' WHERE id = '. $lastId);
        $newName = $sql->getValue('name_1').' - '.rex_i18n::msg('rex_forcal_entries_copy');
        $sql->setQuery('UPDATE ' . $table . ' SET name_1 = "'.$newName.'", status = 0 WHERE id = '.$lastId);
        return rex_view::info(rex_i18n::msg($table . '_cloned'));
    }

    /**
     * delete data
     * @param $table
     * @param $id
     * @return string
     * @author Joachim Doerr
     * @throws \rex_sql_exception
     */
    static public function deleteData($table, $id)
    {
        $sql = rex_sql::factory();
        $sql->setQuery("DELETE FROM $table WHERE id=$id");
        return rex_view::info(rex_i18n::msg($table . '_deleted'));
    }
}