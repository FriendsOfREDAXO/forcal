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
        
        // Berechtigungen für "forcal[all]" aktualisieren
        $has_all_perm = rex_post('has_all_perm', 'boolean', false);
        $can_upload_media = rex_post('can_upload_media', 'boolean', false);
        
        // Benutzer abrufen
        $user = rex_user::get($current_user_id);
        if ($user) {
            // forcal[all] Berechtigung setzen oder entfernen
            $perms = $user->getValue('rights');
            $perms = preg_replace('/forcal\[all\],?/', '', $perms);
            $perms = preg_replace('/forcal\[media\],?/', '', $perms);
            
            if ($has_all_perm) {
                $perms .= ',forcal[all]';
            }
            
            if ($can_upload_media) {
                $perms .= ',forcal[media]';
            }
            
            // Doppelte Kommas entfernen
            $perms = preg_replace('/,,+/', ',', $perms);
            $perms = trim($perms, ',');
            
            // Berechtigungen speichern
            $sql = rex_sql::factory();
            $sql->setTable(rex::getTable('user'));
            $sql->setWhere(['id' => $current_user_id]);
            $sql->setValue('rights', $perms);
            $sql->update();
            
            // Kategorien-Berechtigungen speichern (nur wenn nicht alle Kategorien erlaubt sind)
            if (!$has_all_perm) {
                if (forCalUserPermission::savePermissions($current_user_id, $categories)) {
                    $message = rex_view::success(rex_i18n::msg('forcal_user_permissions_saved'));
                } else {
                    $message = rex_view::error(rex_i18n::msg('forcal_user_permissions_error'));
                }
            } else {
                // Bei forcal[all] alle Kategoriezuweisungen löschen
                forCalUserPermission::savePermissions($current_user_id, []);
                $message = rex_view::success(rex_i18n::msg('forcal_user_permissions_saved'));
            }
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
    
    $category_objects = [];
    foreach ($categories as $category) {
        $obj = new stdClass();
        $obj->setValue('id', $category['id']);
        $obj->setValue('name', $category['name']);
        $obj->setValue('color', $category['color']);
        $category_objects[] = $obj;
    }
    
    // Zugewiesene Kategorien abrufen
    $assigned_categories = [];
    if ($current_user_id > 0) {
        $assigned_categories = forCalUserPermission::getUserCategories($current_user_id);
    }
    
    // Fragment anzeigen
    $fragment = new rex_fragment();
    $fragment->setVar('users', $user_objects);
    $fragment->setVar('categories', $category_objects);
    $fragment->setVar('current_user_id', $current_user_id);
    $fragment->setVar('assigned_categories', $assigned_categories);
    
    // Nachricht anzeigen
    echo $message;
    
    // Inhalte ausgeben
    echo $fragment->parse('forcal_user_permissions.php');
} else {
    echo rex_view::error(rex_i18n::msg('forcal_permission_denied'));
}
