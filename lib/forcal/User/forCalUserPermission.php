<?php
/**
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
    public static function hasPermission($category_id, ?rex_user $user = null)
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        // Administrator oder User mit forcal[all]-Recht hat immer Zugriff
        if ($user->isAdmin() || $user->hasPerm('forcal[all]')) {
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
    public static function hasAnyPermission(?rex_user $user = null)
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        // Administrator oder User mit forcal[all]-Recht hat immer Zugriff
        if ($user->isAdmin() || $user->hasPerm('forcal[all]')) {
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
    public static function getCategoryFilter($table_alias = 'en', ?rex_user $user = null)
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        // Administrator oder User mit forcal[all]-Recht hat immer Zugriff auf alle Kategorien
        if ($user->isAdmin() || $user->hasPerm('forcal[all]')) {
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

    /**
     * Filtert eine Benutzerliste nach Benutzern mit forcal-Rechten
     * 
     * @param array $users Die zu filternde Benutzerliste
     * @return array Die gefilterte Benutzerliste
     */
    public static function filterUsersWithForcalPermission($users)
    {
        $filtered_users = [];
        
        foreach ($users as $user) {
            // Administratoren oder Benutzer mit forcal-Rechten hinzufügen
            if ($user->isAdmin() || 
                $user->hasPerm('forcal[]') || 
                $user->hasPerm('forcal[all]') || 
                $user->hasPerm('forcal[settings]') || 
                $user->hasPerm('forcal[catspage]') || 
                $user->hasPerm('forcal[venuespage]') || 
                $user->hasPerm('forcal[userpermissions]')) {
                $filtered_users[] = $user;
            }
        }
        
        return $filtered_users;
    }
    
    /**
     * Prüft, ob ein Benutzer Bilder hochladen darf
     * 
     * @param rex_user|null $user Der Benutzer (Standard: aktueller Benutzer)
     * @return bool
     */
    public static function canUploadMedia(?rex_user $user = null)
    {
        if ($user === null) {
            $user = rex::getUser();
        }
        
        // Administrator kann immer Bilder hochladen
        if ($user->isAdmin()) {
            return true;
        }
        
        // Eigene Medienberechtigung prüfen
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT can_upload_media FROM ' . rex::getTablePrefix() . 'forcal_user_media_permissions WHERE user_id = :user_id', [
            ':user_id' => $user->getId()
        ]);
        
        if ($sql->getRows() > 0) {
            return (bool)$sql->getValue('can_upload_media');
        }
        
        // Standardmäßig keine Berechtigung
        return false;
    }
    
    /**
     * Speichert die Medienberechtigungen für einen Benutzer
     * 
     * @param int $user_id Die Benutzer-ID
     * @param bool $can_upload_media Ob der Benutzer Bilder hochladen darf
     * @return bool
     */
    public static function saveMediaPermissions($user_id, $can_upload_media)
    {
        $sql = rex_sql::factory();
        
        try {
            // Prüfen, ob bereits ein Eintrag existiert
            $sql->setQuery('SELECT id FROM ' . rex::getTablePrefix() . 'forcal_user_media_permissions WHERE user_id = :user_id', [
                ':user_id' => $user_id
            ]);
            
            $exists = $sql->getRows() > 0;
            
            $sql = rex_sql::factory();
            $sql->setTable(rex::getTablePrefix() . 'forcal_user_media_permissions');
            $sql->setValue('user_id', $user_id);
            $sql->setValue('can_upload_media', $can_upload_media ? 1 : 0);
            
            if ($exists) {
                $sql->setWhere(['user_id' => $user_id]);
                $sql->update();
            } else {
                $sql->insert();
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Erstellt eine SQL-Abfrage, die nach einer bestimmten Spalte filtert
     * 
     * @param string $table Die Tabelle
     * @param string $column Die Spalte
     * @param int $user_id Die Benutzer-ID
     * @param string $where Zusätzliche WHERE-Bedingung
     * @return string Die SQL-Abfrage
     */
    public static function createFilteredQuery($table, $column, $user_id, $where = '')
    {
        $sql = rex_sql::factory();
        $user = rex_user::get($user_id);
        
        // Basisabfrage
        $query = "SELECT * FROM " . $table;
        
        // WHERE-Bedingung hinzufügen (wenn vorhanden)
        if (!empty($where)) {
            $query .= " WHERE " . $where;
        }
        
        // Normale Benutzer dürfen nur ihre Einträge sehen
        if (!$user->isAdmin() && !$user->hasPerm('forcal[all]')) {
            if (empty($where)) {
                $query .= " WHERE ";
            } else {
                $query .= " AND ";
            }
            
            $query .= $column . " = " . $user_id;
        }
        
        return $query;
    }

    // -------------------------------------------------------------------------
    // Venue-Berechtigungen
    // -------------------------------------------------------------------------

    /**
     * Gibt die IDs der Locations zurück, die dem Benutzer explizit (geteilt) zugewiesen sind.
     *
     * @param int $user_id Die Benutzer-ID
     * @return array
     */
    public static function getUserVenues(int $user_id): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT venue_id FROM ' . rex::getTablePrefix() . 'forcal_user_venues WHERE user_id = :user_id',
            [':user_id' => $user_id]
        );

        $venues = [];
        foreach ($sql as $row) {
            $venues[] = (int) $row->getValue('venue_id');
        }

        return $venues;
    }

    /**
     * Gibt die IDs der Locations zurück, die der Benutzer selbst erstellt hat (createuser = login).
     *
     * @param string $login Der Login-Name des Benutzers
     * @return array
     */
    public static function getOwnVenueIds(string $login): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT id FROM ' . rex::getTablePrefix() . 'forcal_venues WHERE createuser = :login',
            [':login' => $login]
        );

        $ids = [];
        foreach ($sql as $row) {
            $ids[] = (int) $row->getValue('id');
        }

        return $ids;
    }

    /**
     * Prüft, ob ein Benutzer eine bestimmte Location verwalten darf.
     * Erlaubt: eigene Orte (createuser) + geteilte Orte (forcal_user_venues).
     * BC: Hat der User weder eigene noch geteilte Orte → keine Einschränkung (sieht alles).
     *
     * @param int $venue_id Die Location-ID
     * @param rex_user|null $user Der Benutzer (Standard: aktueller Benutzer)
     * @return bool
     */
    public static function hasVenuePermission(int $venue_id, ?rex_user $user = null): bool
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        if ($user->isAdmin() || $user->hasPerm('forcal[all]')) {
            return true;
        }

        // Eigene Orte (createuser)
        $ownIds = self::getOwnVenueIds($user->getLogin());
        if (in_array($venue_id, $ownIds)) {
            return true;
        }

        // Geteilte Orte (explizit zugewiesen)
        $sharedIds = self::getUserVenues($user->getId());
        if (in_array($venue_id, $sharedIds)) {
            return true;
        }

        return false;
    }

    /**
     * Prüft, ob für diesen Benutzer eine Venue-Einschränkung aktiv ist.
     * Aktiv wenn: User hat eigene Orte (createuser) ODER geteilte Orte (forcal_user_venues).
     * BC: Keine eigenen + keine geteilten → keine Einschränkung.
     *
     * @param rex_user|null $user Der Benutzer (Standard: aktueller Benutzer)
     * @return bool
     */
    public static function hasVenueRestriction(?rex_user $user = null): bool
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        if ($user->isAdmin() || $user->hasPerm('forcal[all]')) {
            return false;
        }

        return true; // Nicht-Admins sehen immer nur eigene + geteilte Orte
    }

    /**
     * Gibt eine WHERE-Bedingung für erlaubte Locations zurück (eigene + geteilte).
     * BC: Hat der User weder eigene noch geteilte Orte → '' (kein Filter).
     *
     * @param string $table_alias Der Tabellen-Alias für die Venues-Spalte (Standard: 'en', Spalte 'venue')
     * @param rex_user|null $user Der Benutzer (Standard: aktueller Benutzer)
     * @return string
     */
    public static function getVenueFilter(string $table_alias = 'en', ?rex_user $user = null): string
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        if ($user->isAdmin() || $user->hasPerm('forcal[all]')) {
            return '';
        }

        $ownIds = self::getOwnVenueIds($user->getLogin());
        $sharedIds = self::getUserVenues($user->getId());
        $allIds = array_unique(array_merge($ownIds, $sharedIds));

        // BC: keine eigenen + keine geteilten → keine Einschränkung
        if (empty($allIds)) {
            return '';
        }

        return ' AND ' . $table_alias . '.venue IN (' . implode(',', $allIds) . ') ';
    }

    /**
     * Gibt alle erlaubten Venue-IDs für einen Benutzer zurück (eigene + geteilte).
     *
     * @param rex_user $user
     * @return array
     */
    public static function getAllowedVenueIds(rex_user $user): array
    {
        $ownIds = self::getOwnVenueIds($user->getLogin());
        $sharedIds = self::getUserVenues($user->getId());
        return array_unique(array_merge($ownIds, $sharedIds));
    }

    /**
     * Speichert die Venue-Berechtigungen für einen Benutzer
     *
     * @param int $user_id Die Benutzer-ID
     * @param array $venue_ids Die Location-IDs
     * @return bool
     */
    public static function saveVenuePermissions(int $user_id, array $venue_ids): bool
    {
        $sql = rex_sql::factory();

        try {
            $sql->beginTransaction();

            $sql->setQuery(
                'DELETE FROM ' . rex::getTablePrefix() . 'forcal_user_venues WHERE user_id = :user_id',
                [':user_id' => $user_id]
            );

            foreach ($venue_ids as $venue_id) {
                $venue_id = (int) $venue_id;
                if ($venue_id <= 0) {
                    continue;
                }
                $insert = rex_sql::factory();
                $insert->setTable(rex::getTablePrefix() . 'forcal_user_venues');
                $insert->setValue('user_id', $user_id);
                $insert->setValue('venue_id', $venue_id);
                $insert->insert();
            }

            $sql->commit();
            return true;
        } catch (\Exception $e) {
            $sql->rollBack();
            return false;
        }
    }
}
