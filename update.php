<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

// Benutzer-Kategorie-Rechte-Tabelle erstellen
rex_sql_table::get(rex::getTablePrefix() . 'forcal_user_categories')
    ->ensureColumn(new rex_sql_column('id', 'int(11) unsigned', false, null, 'auto_increment'))
    ->ensureColumn(new rex_sql_column('user_id', 'int(11)'))
    ->ensureColumn(new rex_sql_column('category_id', 'int(11)'))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime', false, 'CURRENT_TIMESTAMP'))
    ->setPrimaryKey('id')
    ->ensure();

/**
 * Erweitert den install.php Code, um die Tabelle für Medienberechtigungen zu erstellen
 */

// Neue Tabelle für Media-Berechtigungen erstellen
rex_sql_table::get(rex::getTablePrefix() . 'forcal_user_media_permissions')
    ->ensureColumn(new rex_sql_column('id', 'int(11) unsigned', false, null, 'auto_increment'))
    ->ensureColumn(new rex_sql_column('user_id', 'int(11)'))
    ->ensureColumn(new rex_sql_column('can_upload_media', 'tinyint(1)', false, '0'))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime', false, 'CURRENT_TIMESTAMP'))
    ->setPrimaryKey('id')
    ->ensure();

// Datapane-Struktur zum Backend hinzufügen
if (rex_addon::get('forcal')->hasConfig()) {
    $config = rex_addon::get('forcal')->getConfig();
    $config['forcal_multiuser'] = 1;
    rex_addon::get('forcal')->setConfig($config);
}

// Neue Rechte für Benutzer registrieren
if (rex::isBackend() && rex::getUser()) {
    rex_perm::register('forcal[userpermissions]', null, rex_perm::OPTIONS);
}

// Tabelle für gespeicherte Filter erstellen (Update-Script)
rex_sql_table::get(rex::getTablePrefix() . 'forcal_saved_filters')
    ->ensureColumn(new rex_sql_column('id', 'int(11) unsigned', false, null, 'auto_increment'))
    ->ensureColumn(new rex_sql_column('user_id', 'int(11)', false))
    ->ensureColumn(new rex_sql_column('name', 'varchar(100)', false))
    ->ensureColumn(new rex_sql_column('filter_data', 'text', false))
    ->ensureColumn(new rex_sql_column('is_default', 'tinyint(1)', false, '0'))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime', false, 'CURRENT_TIMESTAMP'))
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime', false, 'CURRENT_TIMESTAMP', 'on update CURRENT_TIMESTAMP'))
    ->setPrimaryKey('id')
    ->ensureIndex(new rex_sql_index('user_id', ['user_id']))
    ->ensure();
