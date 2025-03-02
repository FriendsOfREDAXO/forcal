<?php
/**
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;

use rex;
use rex_sql;
use rex_user;

class forCalSqlHelper
{
    /**
     * Erstellt eine SQL-Abfrage, die nach einer bestimmten Spalte gefiltert wird
     * basierend auf der Benutzer-ID
     * 
     * @param string $table Die Tabelle
     * @param string $column Die zu filternde Spalte
     * @param int $user_id Die Benutzer-ID
     * @param string $additionalWhere Zusätzliche WHERE-Bedingung
     * @return rex_sql Das rex_sql-Objekt mit der Abfrage
     */
    public static function createFilteredQuery($table, $column, $user_id, $additionalWhere = '')
    {
        $sql = rex_sql::factory();
        $user = rex_user::get($user_id);
        
        $query = "SELECT * FROM " . $table;
        $where = [];
        $params = [];
        
        // Zusätzliche WHERE-Bedingung hinzufügen
        if (!empty($additionalWhere)) {
            $where[] = $additionalWhere;
        }
        
        // Normale Benutzer dürfen nur ihre Einträge sehen
        if ($user && !$user->isAdmin() && !$user->hasPerm('forcal[all]')) {
            $where[] = $column . " = :user_id";
            $params['user_id'] = $user_id;
        }
        
        // WHERE-Bedingung hinzufügen
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        // Abfrage ausführen
        if (!empty($params)) {
            $sql->setQuery($query, $params);
        } else {
            $sql->setQuery($query);
        }
        
        return $sql;
    }
    
    /**
     * Erstellt eine SELECT-Abfrage mit Optionen, die nach einer bestimmten Spalte gefiltert werden
     * basierend auf der Benutzer-ID
     * 
     * @param string $table Die Tabelle
     * @param string $idColumn Die ID-Spalte
     * @param string $nameColumn Die Name-Spalte
     * @param string $filterColumn Die zu filternde Spalte
     * @param int $user_id Die Benutzer-ID
     * @param string $additionalWhere Zusätzliche WHERE-Bedingung
     * @return array Ein Array mit Optionen im Format [id => name]
     */
    public static function getFilteredOptions($table, $idColumn, $nameColumn, $filterColumn, $user_id, $additionalWhere = '')
    {
        $sql = self::createFilteredQuery($table, $filterColumn, $user_id, $additionalWhere);
        
        $options = [];
        foreach ($sql as $row) {
            $options[$row->getValue($idColumn)] = $row->getValue($nameColumn);
        }
        
        return $options;
    }
    
    /**
     * Erstellt eine SELECT-Abfrage für Formulare, die nach einer bestimmten Spalte gefiltert werden
     * basierend auf der Benutzer-ID
     * 
     * @param string $table Die Tabelle
     * @param string $idColumn Die ID-Spalte
     * @param string $nameColumn Die Name-Spalte
     * @param string $filterColumn Die zu filternde Spalte
     * @param int $user_id Die Benutzer-ID
     * @param string $additionalWhere Zusätzliche WHERE-Bedingung
     * @return string Die SQL-Abfrage
     */
    public static function getFilteredQueryString($table, $idColumn, $nameColumn, $filterColumn, $user_id, $additionalWhere = '')
    {
        $user = rex_user::get($user_id);
        
        $query = "SELECT $idColumn as id, $nameColumn as name FROM $table";
        $where = [];
        
        // Zusätzliche WHERE-Bedingung hinzufügen
        if (!empty($additionalWhere)) {
            $where[] = $additionalWhere;
        }
        
        // Normale Benutzer dürfen nur ihre Einträge sehen
        if ($user && !$user->isAdmin() && !$user->hasPerm('forcal[all]')) {
            $where[] = "$filterColumn = $user_id";
        }
        
        // WHERE-Bedingung hinzufügen
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        // Nach Name sortieren
        $query .= " ORDER BY $nameColumn";
        
        return $query;
    }
}
