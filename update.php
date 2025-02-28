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

// Datapane-Struktur zum Backend hinzufügen
if (rex_addon::get('forcal')->hasConfig()) {
    $config = rex_addon::get('forcal')->getConfig();
    $config['forcal_multiuser'] = 1;
    rex_addon::get('forcal')->setConfig($config);
}
