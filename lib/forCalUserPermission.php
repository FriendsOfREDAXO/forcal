<?php
/**
 * @author YourName
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;

use rex;
use rex_sql;
use rex_user;

class forCalUserPermission
{
    /**
     * Prüft, ob ein Benutzer Zugriff auf eine bestimmte Kategorie hat
     *
     * @param int $category_id Die Kategorie-ID
     * @param rex_user|null $user Der Benutzer (Standard: aktueller Benutzer)
     * @return bool
     */
    public static function hasPermission($category_id, rex_user $user = null)
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        // Administrator hat immer Zugriff
        if ($user->isAdmin()) {
            return true;
        }

        // Die erlaubten Kategorien des Benutzers abrufen
        $allowed_categories = self::getUserCategories($user->getId());

        // Prüfen, ob die Kategorie-ID in den erlaubten Kategorien ist
        return in_array($category_id, $allowed_categories);
    }

    /**
     * Gibt die IDs der erlaubten Kategorien für einen Benutzer zurück
     *
     * @param int $user_id Die Benutzer-ID
     * @return array
     */
    public static function getUserCategories($user_id)
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT category_id FROM ' . rex::getTablePrefix() . 'forcal_user_categories WHERE user_id = :user_id', [
            ':user_id' => $user_id
        ]);

        $categories = [];
        foreach ($sql as $row) {
            $categories[] = $row->getValue('category_id');
        }

        return $categories;
    }

    /**
     * Prüft, ob ein Benutzer überhaupt Kategorien zugewiesen hat
     *
     * @param rex_user|null $user Der Benutzer (Standard: aktueller Benutzer)
     * @return bool
     */
    public static function hasAnyPermission(rex_user $user = null)
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        // Administrator hat immer Zugriff
        if ($user->isAdmin()) {
            return true;
        }

        $categories = self::getUserCategories($user->getId());
        return !empty($categories);
    }

    /**
     * Gibt eine WHERE-Bedingung zurück, die nur die erlaubten Kategorien enthält
     *
     * @param string $table_alias Der Tabellen-Alias (Standard: 'en')
     * @param rex_user|null $user Der Benutzer (Standard: aktueller Benutzer)
     * @return string
     */
    public static function getCategoryFilter($table_alias = 'en', rex_user $user = null)
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        // Administrator hat immer Zugriff auf alle Kategorien
        if ($user->isAdmin()) {
            return '';
        }

        $categories = self::getUserCategories($user->getId());
        
        if (empty($categories)) {
            // Falls keine Kategorien zugewiesen sind, soll nichts angezeigt werden
            return ' AND 0 ';
        }

        return ' AND ' . $table_alias . '.category IN (' . implode(',', $categories) . ') ';
    }
    
    /**
     * Speichert die Benutzerberechtigungen
     *
     * @param int $user_id Die Benutzer-ID
     * @param array $category_ids Die Kategorie-IDs
     * @return bool
     */
    public static function savePermissions($user_id, array $category_ids)
    {
        $sql = rex_sql::factory();
        
        try {
            // Transaktion starten
            $sql->beginTransaction();
            
            // Alte Einträge löschen
            $sql->setQuery('DELETE FROM ' . rex::getTablePrefix() . 'forcal_user_categories WHERE user_id = :user_id', [
                ':user_id' => $user_id
            ]);
            
            // Neue Einträge speichern
            if (!empty($category_ids)) {
                foreach ($category_ids as $category_id) {
                    $insert = rex_sql::factory();
                    $insert->setTable(rex::getTablePrefix() . 'forcal_user_categories');
                    $insert->setValue('user_id', $user_id);
                    $insert->setValue('category_id', $category_id);
                    $insert->insert();
                }
            }
            
            // Transaktion abschließen
            $sql->commit();
            
            return true;
        } catch (\Exception $e) {
            // Bei Fehler: Transaktion zurückrollen
            $sql->rollBack();
            return false;
        }
    }
}
