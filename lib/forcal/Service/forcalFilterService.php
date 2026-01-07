<?php

/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Service;

use rex;
use rex_sql;

/**
 * Service-Klasse für gespeicherte Filter
 */
class forcalFilterService
{
    /**
     * Speichert einen Filter für einen Benutzer
     *
     * @param int $userId
     * @param string $name
     * @param array<string, mixed> $filterData
     * @param bool $isDefault
     * @return bool
     */
    public static function saveFilter(int $userId, string $name, array $filterData, bool $isDefault = false): bool
    {
        try {
            $sql = rex_sql::factory();
            
            // Wenn dieser Filter als Standard gesetzt werden soll, alle anderen Standard-Filter für diesen User deaktivieren
            if ($isDefault) {
                $sql->setQuery('UPDATE ' . rex::getTable('forcal_saved_filters') . ' 
                               SET is_default = 0 
                               WHERE user_id = :user_id', 
                               ['user_id' => $userId]);
            }
            
            $sql->setTable(rex::getTable('forcal_saved_filters'));
            $sql->setValue('user_id', $userId);
            $sql->setValue('name', $name);
            $sql->setValue('filter_data', json_encode($filterData));
            $sql->setValue('is_default', $isDefault ? 1 : 0);
            $sql->insert();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Lädt alle Filter für einen Benutzer
     *
     * @param int $userId
     * @return array
     */
    public static function getUserFilters(int $userId): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . rex::getTable('forcal_saved_filters') . ' 
                       WHERE user_id = :user_id 
                       ORDER BY is_default DESC, name ASC', 
                       ['user_id' => $userId]);
        
        $filters = [];
        for ($i = 0; $i < $sql->getRows(); $i++) {
            $filters[] = [
                'id' => $sql->getValue('id'),
                'name' => $sql->getValue('name'),
                'filter_data' => json_decode($sql->getValue('filter_data'), true),
                'is_default' => (bool) $sql->getValue('is_default'),
                'createdate' => $sql->getValue('createdate'),
                'updatedate' => $sql->getValue('updatedate'),
            ];
            $sql->next();
        }
        
        return $filters;
    }
    
    /**
     * Lädt einen einzelnen Filter
     *
     * @param int $filterId
     * @param int $userId
     * @return array|null
     */
    public static function getFilter(int $filterId, int $userId): ?array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . rex::getTable('forcal_saved_filters') . ' 
                       WHERE id = :id AND user_id = :user_id', 
                       ['id' => $filterId, 'user_id' => $userId]);
        
        if ($sql->getRows() === 0) {
            return null;
        }
        
        return [
            'id' => $sql->getValue('id'),
            'name' => $sql->getValue('name'),
            'filter_data' => json_decode($sql->getValue('filter_data'), true),
            'is_default' => (bool) $sql->getValue('is_default'),
            'createdate' => $sql->getValue('createdate'),
            'updatedate' => $sql->getValue('updatedate'),
        ];
    }
    
    /**
     * Lädt den Standard-Filter für einen Benutzer
     *
     * @param int $userId
     * @return array|null
     */
    public static function getDefaultFilter(int $userId): ?array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . rex::getTable('forcal_saved_filters') . ' 
                       WHERE user_id = :user_id AND is_default = 1', 
                       ['user_id' => $userId]);
        
        if ($sql->getRows() === 0) {
            return null;
        }
        
        return [
            'id' => $sql->getValue('id'),
            'name' => $sql->getValue('name'),
            'filter_data' => json_decode($sql->getValue('filter_data'), true),
            'is_default' => true,
            'createdate' => $sql->getValue('createdate'),
            'updatedate' => $sql->getValue('updatedate'),
        ];
    }
    
    /**
     * Löscht einen Filter
     *
     * @param int $filterId
     * @param int $userId
     * @return bool
     */
    public static function deleteFilter(int $filterId, int $userId): bool
    {
        try {
            $sql = rex_sql::factory();
            $sql->setQuery('DELETE FROM ' . rex::getTable('forcal_saved_filters') . ' 
                           WHERE id = :id AND user_id = :user_id', 
                           ['id' => $filterId, 'user_id' => $userId]);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Setzt einen Filter als Standard
     *
     * @param int $filterId
     * @param int $userId
     * @return bool
     */
    public static function setDefaultFilter(int $filterId, int $userId): bool
    {
        try {
            $sql = rex_sql::factory();
            
            // Alle anderen Standard-Filter für diesen User deaktivieren
            $sql->setQuery('UPDATE ' . rex::getTable('forcal_saved_filters') . ' 
                           SET is_default = 0 
                           WHERE user_id = :user_id', 
                           ['user_id' => $userId]);
            
            // Diesen Filter als Standard setzen
            $sql->setQuery('UPDATE ' . rex::getTable('forcal_saved_filters') . ' 
                           SET is_default = 1 
                           WHERE id = :id AND user_id = :user_id', 
                           ['id' => $filterId, 'user_id' => $userId]);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
