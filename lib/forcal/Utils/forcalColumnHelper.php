<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;


use rex_clang;
use rex_sql;

class forCalColumnHelper
{
    /**
     * @param $table
     * @return array
     * @author Joachim Doerr
     */
    public static function getColumns($table)
    {
        $sql = rex_sql::factory();
        $sql->setQuery("SHOW COLUMNS FROM $table");
        return $sql->getArray();
    }

    /**
     * gibt alle basis sprach columns im array zurück
     * @param $table
     * @return array
     * @author Joachim Doerr
     */
    public static function getBaseLangColumns($table)
    {
        $langColumns = array();

        foreach (self::getColumns($table) as $column) {
            $colField = explode('_', $column['Field']);
            if (substr($column['Field'], -2) == "_1") {
                $langColumns[$colField[0]] = $column;
            }
        }
        return $langColumns;
    }

    /**
     * gibt alle sprach columns im array zurück ausser basis columns
     * @param $table
     * @return array
     * @author Joachim Doerr
     */
    public static function getAllLangColumns($table)
    {
        $langColumns = array();

        foreach (self::getBaseLangColumns($table) as $key => $column) {
            foreach (rex_clang::getAll() as $clangKey => $clangName) {
                if ($clangKey > 0) {
                    $column['Field'] = $key.'_'.$clangKey;
                    $langColumns[$column['Field']] = $column;
                }
            }
        }
        return $langColumns;
    }

    /**
     * @param $columnName
     * @param $table
     * @return bool
     * @author Joachim Doerr
     */
    public static function columnExist($columnName, $table)
    {
        $sql = rex_sql::factory();
        try {
            $sql->setQuery("SELECT $columnName FROM $table LIMIT 0");
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * schreibt eine column in eine table
     * @param array $column
     * @param $table
     * @author Joachim Doerr
     */
    public static function addColumnToTable(array $column, $table)
    {
        if (!self::columnExist($column['Field'], $table)) {
            $sql = rex_sql::factory();
            $sql->setQuery("ALTER TABLE $table ADD ".$column['Field']." ".$column['Type']);
        }
    }

    /**
     * schreibt alle columns in eine table
     * @param array $columns
     * @param $table
     * @author Joachim Doerr
     */
    public static function addColumnsToTable(array $columns, $table)
    {
        if (is_array($columns)) {
            foreach ($columns as $column) {
                self::addColumnToTable($column, $table);
            }
        }
    }

    /**
     * @param array $column
     * @param $table
     * @author Joachim Doerr
     */
    public static function changeColumnInTable(array $column, $table)
    {
        $sql = rex_sql::factory();
        $sql->setQuery("ALTER TABLE $table MODIFY ".$column['Field']." ".$column['Type']);
    }

    /**
     * @param array $columns
     * @param $table
     * @author Joachim Doerr
     */
    public static function changeColumnsInTable(array $columns, $table)
    {
        if (is_array($columns)) {
            foreach ($columns as $column) {
                self::changeColumnInTable($column, $table);
            }
        }
    }

    /**
     * stösst den prozess an feder hinzu zufügen
     * @param array $tables
     * @author Joachim Doerr
     */
    public static function addProcess(array $tables)
    {
        foreach ($tables as $table) {
            self::addColumnsToTable(self::getAllLangColumns($table), $table);
        }
    }

    /**
     * @param array $columns
     * @param $name
     * @return bool
     * @author Joachim Doerr
     */
    public static function isInColumnList(array $columns, $name)
    {
        foreach ($columns as $column) {
            if ($column['Field'] == $name) {
                return $column;
            }
        }
        return false;
    }
}