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
    // Venue-Berechtigungen (ab 6.6.0)
    //
    // Modell A – Orte VERWALTEN (venues.php-Liste):
    //   Owner (createuser) darf immer alles inkl. Delete.
    //   rex_forcal_user_venues.owner_user_id:
    //     keine Rows  → scope 'own':      nur eigene Orte sichtbar/bearbeitbar
    //     row ouid=0  → scope 'all':      alle Orte sichtbar, bearbeitbar, aber kein Delete fremder
    //     rows ouid>0 → scope 'by_owner': Orte dieser Owner sichtbar + bearbeitbar, kein Delete
    //
    // Modell B – Ort AUSWÄHLEN bei Terminen (entries.php-Dropdown):
    //   Default: alle Orte wählbar.
    //   Flag restrict_venue_selection=1 → nur eigene Orte im Dropdown.
    // -------------------------------------------------------------------------

    /**
     * Gibt den Venue-Edit-Scope des Benutzers zurück.
     * 'own'      = nur eigene Orte verwalten (default)
     * 'all'      = alle Orte bearbeiten (kein Delete fremder)
     * 'by_owner' = Orte bestimmter Owner bearbeiten (kein Delete)
     *
     * @param int $user_id
     * @return string 'own'|'all'|'by_owner'
     */
    public static function getVenueEditScope(int $user_id): string
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT owner_user_id FROM ' . rex::getTablePrefix() . 'forcal_user_venues WHERE user_id = :uid',
            [':uid' => $user_id]
        );

        if ($sql->getRows() === 0) {
            return 'own';
        }

        foreach ($sql as $row) {
            if ((int) $row->getValue('owner_user_id') === 0) {
                return 'all';
            }
        }

        return 'by_owner';
    }

    /**
     * Gibt die User-IDs der Owner zurück, deren Venues der User bearbeiten darf.
     * Nur relevant wenn scope = 'by_owner'.
     *
     * @param int $user_id
     * @return int[]
     */
    public static function getAllowedOwnerUserIds(int $user_id): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT owner_user_id FROM ' . rex::getTablePrefix() . 'forcal_user_venues WHERE user_id = :uid AND owner_user_id > 0',
            [':uid' => $user_id]
        );

        $ids = [];
        foreach ($sql as $row) {
            $ids[] = (int) $row->getValue('owner_user_id');
        }

        return $ids;
    }

    /**
     * Gibt die Logins der Owner zurück, deren Venues der User bearbeiten darf.
     * Nur relevant wenn scope = 'by_owner'.
     *
     * @param int $user_id
     * @return string[]
     */
    public static function getAllowedOwnerLogins(int $user_id): array
    {
        $ownerIds = self::getAllowedOwnerUserIds($user_id);
        if (empty($ownerIds)) {
            return [];
        }

        $logins = [];
        foreach ($ownerIds as $ownerId) {
            $owner = rex_user::get($ownerId);
            if ($owner instanceof rex_user) {
                $logins[] = $owner->getLogin();
            }
        }

        return $logins;
    }

    /**
     * Gibt die IDs der Locations zurück, die der Benutzer selbst erstellt hat (createuser = login).
     *
     * @param string $login Der Login-Name des Benutzers
     * @return int[]
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
     * Prüft ob ein User eine bestimmte Venue bearbeiten (edit/status/clone) darf.
     * Admin + forcal[all]: immer.
     * Scope 'all': immer.
     * Scope 'by_owner': wenn Owner der Venue in der Erlaubt-Liste.
     * Default 'own': nur eigene.
     *
     * @param int $venue_id
     * @param rex_user|null $user
     * @return bool
     */
    public static function hasVenueEditPermission(int $venue_id, ?rex_user $user = null): bool
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        if ($user->isAdmin() || $user->hasPerm('forcal[all]')) {
            return true;
        }

        $scope = self::getVenueEditScope($user->getId());

        if ($scope === 'all') {
            return true;
        }

        // Eigene Orte darf jeder immer
        $ownIds = self::getOwnVenueIds($user->getLogin());
        if (in_array($venue_id, $ownIds, true)) {
            return true;
        }

        if ($scope === 'by_owner') {
            $allowedLogins = self::getAllowedOwnerLogins($user->getId());
            if (empty($allowedLogins)) {
                return false;
            }
            $placeholders = implode(',', array_fill(0, count($allowedLogins), '?'));
            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT id FROM ' . rex::getTablePrefix() . 'forcal_venues WHERE id = ? AND createuser IN (' . $placeholders . ')',
                array_merge([$venue_id], $allowedLogins)
            );
            return $sql->getRows() > 0;
        }

        return false; // scope 'own', nicht im eigenen Bestand
    }

    /**
     * Alias für hasVenueEditPermission (Rückwärtskompatibilität).
     *
     * @param int $venue_id
     * @param rex_user|null $user
     * @return bool
     */
    public static function hasVenuePermission(int $venue_id, ?rex_user $user = null): bool
    {
        return self::hasVenueEditPermission($venue_id, $user);
    }

    /**
     * Prüft ob ein User eine Venue LÖSCHEN darf.
     * Nur Owner (createuser = login) und Admin/forcal[all] dürfen löschen.
     *
     * @param int $venue_id
     * @param rex_user|null $user
     * @return bool
     */
    public static function canDeleteVenue(int $venue_id, ?rex_user $user = null): bool
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        if ($user->isAdmin() || $user->hasPerm('forcal[all]')) {
            return true;
        }

        $ownIds = self::getOwnVenueIds($user->getLogin());
        return in_array($venue_id, $ownIds, true);
    }

    /**
     * Gibt einen WHERE-String für die Venues-Listen-Abfrage zurück.
     * Admin/forcal[all]: '' (kein Filter, alle sichtbar)
     * scope 'all': '' (alle sichtbar)
     * scope 'by_owner': WHERE createuser IN (logins)
     * scope 'own': WHERE createuser = login
     *
     * @param rex_user|null $user
     * @return string SQL WHERE-Klausel inkl. ' WHERE ' oder ''
     */
    public static function getVenueListWhere(?rex_user $user = null): string
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        if ($user->isAdmin() || $user->hasPerm('forcal[all]')) {
            return '';
        }

        $scope = self::getVenueEditScope($user->getId());

        if ($scope === 'all') {
            return '';
        }

        if ($scope === 'by_owner') {
            $allowedLogins = self::getAllowedOwnerLogins($user->getId());
            // Eigene immer dazu
            $logins = array_unique(array_merge([$user->getLogin()], $allowedLogins));
            $escaped = array_map(static fn(string $l) => rex_sql::factory()->escape($l), $logins);
            return ' WHERE createuser IN (' . implode(',', $escaped) . ')';
        }

        // scope 'own'
        $login = rex_sql::factory()->escape($user->getLogin());
        return ' WHERE createuser = ' . $login;
    }

    /**
     * Prüft ob das Venue-Dropdown in Terminen auf eigene Orte eingeschränkt ist.
     * Default: false (alle Orte wählbar).
     * true nur wenn restrict_venue_selection=1 gesetzt ist.
     *
     * @param rex_user|null $user
     * @return bool
     */
    public static function isVenueSelectionRestricted(?rex_user $user = null): bool
    {
        if ($user === null) {
            $user = rex::getUser();
        }

        if ($user->isAdmin() || $user->hasPerm('forcal[all]')) {
            return false;
        }

        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT restrict_venue_selection FROM ' . rex::getTablePrefix() . 'forcal_user_media_permissions WHERE user_id = :uid',
            [':uid' => $user->getId()]
        );

        if ($sql->getRows() === 0) {
            return false;
        }

        return (bool) $sql->getValue('restrict_venue_selection');
    }

    /**
     * Speichert den Venue-Edit-Scope für einen Benutzer.
     *
     * @param int $user_id
     * @param string $scope 'own'|'all'|'by_owner'
     * @param int[] $owner_user_ids Relevant nur bei scope='by_owner'
     * @return bool
     */
    public static function saveVenueEditPermission(int $user_id, string $scope, array $owner_user_ids = []): bool
    {
        $sql = rex_sql::factory();

        try {
            $sql->beginTransaction();

            $sql->setQuery(
                'DELETE FROM ' . rex::getTablePrefix() . 'forcal_user_venues WHERE user_id = :uid',
                [':uid' => $user_id]
            );

            if ($scope === 'all') {
                $insert = rex_sql::factory();
                $insert->setTable(rex::getTablePrefix() . 'forcal_user_venues');
                $insert->setValue('user_id', $user_id);
                $insert->setValue('owner_user_id', 0);
                $insert->insert();
            } elseif ($scope === 'by_owner') {
                foreach ($owner_user_ids as $ownerId) {
                    $ownerId = (int) $ownerId;
                    if ($ownerId <= 0) {
                        continue;
                    }
                    $insert = rex_sql::factory();
                    $insert->setTable(rex::getTablePrefix() . 'forcal_user_venues');
                    $insert->setValue('user_id', $user_id);
                    $insert->setValue('owner_user_id', $ownerId);
                    $insert->insert();
                }
            }
            // scope 'own' → einfach alle Rows gelöscht lassen

            $sql->commit();
            return true;
        } catch (\Exception $e) {
            $sql->rollBack();
            return false;
        }
    }

    /**
     * Speichert das Venue-Selection-Flag (Termin-Dropdown) für einen Benutzer.
     *
     * @param int $user_id
     * @param bool $restricted
     * @return bool
     */
    public static function saveVenueSelectionRestriction(int $user_id, bool $restricted): bool
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT id FROM ' . rex::getTablePrefix() . 'forcal_user_media_permissions WHERE user_id = :uid',
            [':uid' => $user_id]
        );

        try {
            $upd = rex_sql::factory();
            if ($sql->getRows() > 0) {
                $upd->setTable(rex::getTablePrefix() . 'forcal_user_media_permissions');
                $upd->setValue('restrict_venue_selection', $restricted ? 1 : 0);
                $upd->setWhere(['user_id' => $user_id]);
                $upd->update();
            } else {
                $upd->setTable(rex::getTablePrefix() . 'forcal_user_media_permissions');
                $upd->setValue('user_id', $user_id);
                $upd->setValue('restrict_venue_selection', $restricted ? 1 : 0);
                $upd->setValue('can_upload_media', 0);
                $upd->insert();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
