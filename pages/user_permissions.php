<?php
/**
 * @author YourName
 * @package redaxo5
 * @license MIT
 */

// Benutzerberechtigungen für forCal Kategorien verwalten

$addon = rex_addon::get('forcal');
$content = '';

// Berechtigung prüfen
if (!rex::getUser()->isAdmin()) {
    echo rex_view::error($addon->i18n('permission_denied'));
    return;
}

// Formular zum Speichern der Berechtigungen
if (rex_post('btn_save', 'boolean')) {
    $user_id = rex_post('user_id', 'int');
    $categories = rex_post('categories', 'array', []);
    
    if ($user_id > 0) {
        // Alte Einträge löschen
        $sql = rex_sql::factory();
        $sql->setQuery('DELETE FROM ' . rex::getTablePrefix() . 'forcal_user_categories WHERE user_id = :user_id', [
            ':user_id' => $user_id
        ]);
        
        // Neue Einträge speichern
        if (!empty($categories)) {
            foreach ($categories as $category_id) {
                $sql = rex_sql::factory();
                $sql->setTable(rex::getTablePrefix() . 'forcal_user_categories');
                $sql->setValue('user_id', $user_id);
                $sql->setValue('category_id', $category_id);
                $sql->insert();
            }
        }
        
        $content .= rex_view::success($addon->i18n('user_permissions_saved'));
    }
}

// Benutzerliste abrufen
$users = rex_sql::factory();
$users->setQuery('SELECT id, name, login FROM ' . rex::getTablePrefix() . 'user ORDER BY name');

// Kategorienliste abrufen
$categories = rex_sql::factory();
$categories->setQuery('SELECT id, name_' . rex_clang::getCurrentId() . ' as name FROM ' . rex::getTablePrefix() . 'forcal_categories WHERE status = 1 ORDER BY name_' . rex_clang::getCurrentId());

// Fragment für die Benutzerliste erstellen
$fragment = new rex_fragment();
$fragment->setVar('users', $users);
$fragment->setVar('categories', $categories);

// Aktuell ausgewählter Benutzer
$current_user_id = rex_request('user_id', 'int', 0);

if ($current_user_id > 0) {
    // Aktuell zugewiesene Kategorien abrufen
    $user_categories = rex_sql::factory();
    $user_categories->setQuery('SELECT category_id FROM ' . rex::getTablePrefix() . 'forcal_user_categories WHERE user_id = :user_id', [
        ':user_id' => $current_user_id
    ]);
    
    $assigned_categories = [];
    foreach ($user_categories as $cat) {
        $assigned_categories[] = $cat->getValue('category_id');
    }
    
    $fragment->setVar('current_user_id', $current_user_id);
    $fragment->setVar('assigned_categories', $assigned_categories);
}

$content .= $fragment->parse('forcal_user_permissions.php');

// Ausgabe
$title = $addon->i18n('user_permissions');
$content = rex_view::content('ForCal user permissions', $content, $title);

echo $content;
