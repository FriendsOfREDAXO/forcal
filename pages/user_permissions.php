<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

use forCal\Utils\forCalUserPermission;

$addon = rex_addon::get('forcal');

// Nur Admins und Benutzer mit den richtigen Rechten dürfen die Berechtigungen verwalten
if (rex::getUser()->isAdmin() || rex::getUser()->hasPerm('forcal[userpermissions]')) {
    
    $current_user_id = rex_request('user_id', 'int', 0);
    $message = '';
    
    // Berechtigungen speichern
    if (rex_post('btn_save', 'string') && $current_user_id > 0) {
        // Kategorien-Berechtigungen speichern
        $categories = rex_post('categories', 'array', []);

        // Venue-Edit-Scope speichern
        $venue_edit_scope = rex_post('venue_edit_scope', 'string', 'own');
        if (!in_array($venue_edit_scope, ['own', 'all', 'by_owner'], true)) {
            $venue_edit_scope = 'own';
        }
        $allowed_owner_ids = rex_post('allowed_owner_ids', 'array', []);

        // Venue-Selection-Flag (Termin-Dropdown)
        $restrict_venue_selection = rex_post('restrict_venue_selection', 'boolean', false);

        // Medienberechtigungen
        $can_upload_media = rex_post('can_upload_media', 'boolean', false);
        
        // Kategorien-Berechtigungen speichern
        if (forCalUserPermission::savePermissions($current_user_id, $categories)) {
            $message = rex_view::success(rex_i18n::msg('forcal_user_permissions_saved'));
        } else {
            $message = rex_view::error(rex_i18n::msg('forcal_user_permissions_error'));
        }

        // Venue-Edit-Scope speichern
        if (forCalUserPermission::saveVenueEditPermission($current_user_id, $venue_edit_scope, array_map('intval', $allowed_owner_ids))) {
            $message .= rex_view::success(rex_i18n::msg('forcal_venue_permissions_saved'));
        } else {
            $message .= rex_view::error(rex_i18n::msg('forcal_venue_permissions_error'));
        }

        // Venue-Selection-Flag (Termin-Dropdown) und Media speichern
        forCalUserPermission::saveVenueSelectionRestriction($current_user_id, $restrict_venue_selection);
        if (forCalUserPermission::saveMediaPermissions($current_user_id, $can_upload_media)) {
            $message .= rex_view::success(rex_i18n::msg('forcal_media_permissions_saved'));
        } else {
            $message .= rex_view::error(rex_i18n::msg('forcal_media_permissions_error'));
        }
    }
    
    // Alle Benutzer abrufen
    $sql = rex_sql::factory();
    $users = $sql->getArray('SELECT id, name, login FROM ' . rex::getTable('user') . ' ORDER BY name');
    
    // In Objekte umwandeln, damit die filterUsersWithForcalPermission-Methode funktioniert
    $user_objects = [];
    foreach ($users as $user) {
        $user_objects[] = rex_user::get($user['id']);
    }
    
    // Benutzer filtern, die forcal-Rechte haben
    $user_objects = forCalUserPermission::filterUsersWithForcalPermission($user_objects);
    
    // Kategorien abrufen
    $sql = rex_sql::factory();
    $categories = $sql->getArray('SELECT id, name_' . rex_clang::getCurrentId() . ' as name, color FROM ' . rex::getTable('forcal_categories') . ' WHERE status = 1 ORDER BY name_' . rex_clang::getCurrentId());
    
    // Kategorien in ein Format umwandeln, das für das Fragment geeignet ist
    $category_objects = [];
    foreach ($categories as $category) {
        // Wir erstellen ein einfaches Objekt mit den nötigen Eigenschaften
        $obj = new stdClass();
        $obj->id = $category['id'];
        $obj->name = $category['name'];
        $obj->color = $category['color'];
        $category_objects[] = $obj;
    }

    // Zugewiesene Kategorien abrufen
    $assigned_categories = [];
    if ($current_user_id > 0) {
        $assigned_categories = forCalUserPermission::getUserCategories($current_user_id);
    }

    // Venue-Edit-Scope + erlaubte Owner-IDs abrufen
    $venue_edit_scope = 'own';
    $allowed_owner_ids = [];
    $restrict_venue_selection = false;
    if ($current_user_id > 0) {
        $venue_edit_scope = forCalUserPermission::getVenueEditScope($current_user_id);
        $allowed_owner_ids = forCalUserPermission::getAllowedOwnerUserIds($current_user_id);
        $restrict_venue_selection = forCalUserPermission::isVenueSelectionRestricted(rex_user::get($current_user_id));
    }

    // Alle anderen forcal-User (für by_owner-Multiselect); den aktuellen User ausblenden
    $other_forcal_users = array_filter($user_objects, fn($u) => $u instanceof rex_user && $u->getId() !== $current_user_id);
    
    // Medienberechtigungen abrufen
    $can_upload_media = false;
    if ($current_user_id > 0) {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT can_upload_media FROM ' . rex::getTablePrefix() . 'forcal_user_media_permissions WHERE user_id = :user_id', [
            ':user_id' => $current_user_id
        ]);
        
        if ($sql->getRows() > 0) {
            $can_upload_media = (bool)$sql->getValue('can_upload_media');
        }
    }
    
    // Fragment anzeigen
    $fragment = new rex_fragment();
    $fragment->setVar('users', $user_objects);
    $fragment->setVar('categories', $category_objects);
    $fragment->setVar('current_user_id', $current_user_id);
    $fragment->setVar('assigned_categories', $assigned_categories);
    $fragment->setVar('venue_edit_scope', $venue_edit_scope);
    $fragment->setVar('allowed_owner_ids', $allowed_owner_ids);
    $fragment->setVar('other_forcal_users', array_values($other_forcal_users));
    $fragment->setVar('restrict_venue_selection', $restrict_venue_selection);
    $fragment->setVar('can_upload_media', $can_upload_media);
    
    // Nachricht anzeigen
    echo $message;
    
    // Inhalte ausgeben
    echo $fragment->parse('forcal_user_permissions.php');
} else {
    echo rex_view::error(rex_i18n::msg('forcal_permission_denied'));
}
