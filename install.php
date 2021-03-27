<?php

/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */
$addon = rex_addon::get("forcal");

if (rex_addon::get('sked')->isAvailable() && !$addon->hasConfig('migrated')) {
    $old_addon = rex_addon::get("sked");
    // copy data definitions to news folder
    rex_dir::copy($old_addon->getDataPath(), $this->getDataPath());
    rex_sql_util::copyTableWithData(rex::getTablePrefix() . 'sked_categories', rex::getTablePrefix() . 'forcal_categories');
    rex_sql_util::copyTableWithData(rex::getTablePrefix() . 'sked_entries', rex::getTablePrefix() . 'forcal_entries');
    rex_sql_util::copyTableWithData(rex::getTablePrefix() . 'sked_venues', rex::getTablePrefix() . 'forcal_venues');
    $addon->setConfig('migrated', '1');

    // Copy Config

    if ($old_addon->hasConfig('sked_additional_attributes_teaser')) {
        $addon->setConfig('forcal_additional_attributes_teaser', $old_addon->getConfig('sked_additional_attributes_teaser'));
    }
    if ($old_addon->hasConfig('sked_additional_attributes_text')) {
        $addon->setConfig('forcal_additional_attributes_text', $old_addon->getConfig('sked_additional_attributes_text'));
    }
    if ($old_addon->hasConfig('sked_additional_for_title')) {
        $addon->setConfig('forcal_additional_for_title', $old_addon->getConfig('sked_additional_for_title'));
    }
    if ($old_addon->hasConfig('sked_colors')) {
        $addon->setConfig('forcal_colors', $old_addon->getConfig('sked_colors'));
    }

    if ($old_addon->hasConfig('sked_customfield_check')) {
        $addon->setConfig('forcal_customfield_check', $old_addon->getConfig('sked_customfield_check'));
    }
    if ($old_addon->hasConfig('sked_datepicker')) {
        $addon->setConfig('forcal_datepicker', $old_addon->getConfig('sked_datepicker'));
    }
    if ($old_addon->hasConfig('sked_editor')) {
        $addon->setConfig('forcal_editor', $old_addon->getConfig('sked_editor'));
    }
    if ($old_addon->hasConfig('sked_full_time_preselection')) {
        $addon->setConfig('forcal_full_time_preselection', $old_addon->getConfig('sked_full_time_preselection'));
    }
    if ($old_addon->hasConfig('sked_shortcut_save')) {
        $addon->setConfig('forcal_shortcut_save', $old_addon->getConfig('sked_shortcut_save'));
    }
    if ($old_addon->hasConfig('sked_start_page')) {
        $addon->setConfig('forcal_start_page', $old_addon->getConfig('sked_start_page'));
    }

    $addon->setProperty('successmsg', '<br><strong>' . rex_i18n::msg("forcal_change_message") . '</strong>');
    $old_addon->setProperty('install', false);
} else {
    if (!$addon->hasConfig()) {
        // colors : http://mdbootstrap.com/css/colors/
        $addon->setConfig('forcal_colors', 'rgba(135,1,101,.8), #ffffff, #000000, #ff4444, #CC0000, #ffbb33, #FF8800, #00C851, #007E33, #33b5e5, #0099CC, #2BBBAD, #00695c, #4285F4, #0d47a1, #aa66cc, #9933CC, #2E2E2E, #212121, #4B515D, #3E4551, #3F729B, #1C2331, #37474F, #263238, #00695c, #004d40, #00838f, #006064, #0277bd, #01579b, #9e9d24, #827717, #558b2f, #33691e, #2e7d32, #1b5e20, #ef6c00, #e65100, #ff8f00, #ff6f00, #f9a825, #f57f17, #d84315, #bf360c, #6a1b9a, #4a148c, #ad1457, #880e4f, #c62828, #b71c1c');
        $addon->setConfig('forcal_editor', 3);
        $addon->setConfig('forcal_customfield_check', 0);
        $addon->setConfig('forcal_datepicker', 0);
        $addon->setConfig('forcal_full_time_preselection', 1);
        $addon->setConfig('forcal_start_page', 'calendar');
    }
}

use forCal\Manager\forCalDatabaseManager;
use forCal\Utils\forCalEditorHelper;

$rex_sql = rex_sql::factory();
$dbVersion = $rex_sql->getDbVersion();
$dbtype = $rex_sql->getDbType();
$minDbVersion = '5.6.15';
if ($dbtype == 'MariaDB') {
    $minDbVersion = '10.0.1';
}
if (rex_string::versionCompare($dbVersion, $minDbVersion, '<')) {
    $message = rex_i18n::hasMsg('sql_database_min_version')
        ? rex_i18n::msg('sql_database_min_version', $dbtype, $dbVersion, $minDbVersion)
        : "The Database version $dbtype $dbVersion is too old, you need at least version $minDbVersion!";
    throw new rex_functional_exception($message);
} else {

    rex_sql_table::get(rex::getTable('forcal_categories'))
        ->ensurePrimaryIdColumn()
        ->ensureColumn(new rex_sql_column('color', 'varchar(40)', true))
        ->ensureColumn(new rex_sql_column('status', 'int(1)', true, '1'))
        ->ensureColumn(new rex_sql_column('name_1', 'varchar(255)', false, ''))
        ->ensureColumn(new rex_sql_column('createdate', 'datetime', false, 'CURRENT_TIMESTAMP'))
        ->ensureColumn(new rex_sql_column('updatedate', 'datetime', false, 'CURRENT_TIMESTAMP', 'on update CURRENT_TIMESTAMP'))
        ->ensureColumn(new rex_sql_column('createuser', 'varchar(255)', true))
        ->ensureColumn(new rex_sql_column('updateuser', 'varchar(255)', true))
        ->ensure();

    rex_sql_table::get(rex::getTable('forcal_entries'))
        ->ensureColumn(new rex_sql_column('id', 'int(11) unsigned', false, null, 'auto_increment'))
        ->ensureColumn(new rex_sql_column('uid', 'varchar(192)'))
        ->ensureColumn(new rex_sql_column('start_date', 'date'))
        ->ensureColumn(new rex_sql_column('end_date', 'date'))
        ->ensureColumn(new rex_sql_column('start_time', 'time'))
        ->ensureColumn(new rex_sql_column('end_time', 'time'))
        ->ensureColumn(new rex_sql_column('category', 'int(5)', true))
        ->ensureColumn(new rex_sql_column('venue', 'int(5)', true))
        ->ensureColumn(new rex_sql_column('status', 'int(1)', true, '1'))
        ->ensureColumn(new rex_sql_column('name_1', 'varchar(255)', false, ''))
        ->ensureColumn(new rex_sql_column('teaser_1', 'text'))
        ->ensureColumn(new rex_sql_column('text_1', 'text'))
        ->ensureColumn(new rex_sql_column('type', 'varchar(25)', true))
        ->ensureColumn(new rex_sql_column('full_time', 'varchar(3)', true))
        ->ensureColumn(new rex_sql_column('repeat', 'varchar(25)', true))
        ->ensureColumn(new rex_sql_column('repeat_year', 'int(11)', true))
        ->ensureColumn(new rex_sql_column('repeat_week', 'int(11)', true))
        ->ensureColumn(new rex_sql_column('repeat_month', 'int(11)', true))
        ->ensureColumn(new rex_sql_column('repeat_month_week', 'varchar(15)', true))
        ->ensureColumn(new rex_sql_column('repeat_day', 'varchar(3)', true))
        ->ensureColumn(new rex_sql_column('end_repeat_date', 'date', true))
        ->ensureColumn(new rex_sql_column('createdate', 'datetime', false, 'CURRENT_TIMESTAMP'))
        ->ensureColumn(new rex_sql_column('updatedate', 'datetime', false, 'CURRENT_TIMESTAMP', 'on update CURRENT_TIMESTAMP'))
        ->ensureColumn(new rex_sql_column('createuser', 'varchar(255)'))
        ->ensureColumn(new rex_sql_column('updateuser', 'varchar(255)'))
        ->setPrimaryKey('id')
        ->ensure();

    rex_sql_table::get(rex::getTable('forcal_venues'))
        ->ensureColumn(new rex_sql_column('id', 'int(11)', false, null, 'auto_increment'))
        ->ensureColumn(new rex_sql_column('status', 'int(1)', true, '1'))
        ->ensureColumn(new rex_sql_column('name_1', 'text'))
        ->ensureColumn(new rex_sql_column('createdate', 'datetime', false, 'CURRENT_TIMESTAMP'))
        ->ensureColumn(new rex_sql_column('updatedate', 'datetime', false, 'CURRENT_TIMESTAMP', 'on update CURRENT_TIMESTAMP'))
        ->ensureColumn(new rex_sql_column('createuser', 'varchar(255)'))
        ->ensureColumn(new rex_sql_column('updateuser', 'varchar(255)'))
        ->ensureColumn(new rex_sql_column('city', 'text', true))
        ->ensureColumn(new rex_sql_column('zip', 'text', true))
        ->ensureColumn(new rex_sql_column('street', 'text', true))
        ->ensureColumn(new rex_sql_column('housenumber', 'text', true))
        ->ensureColumn(new rex_sql_column('country', 'text', true))
        ->setPrimaryKey('id')
        ->ensure();


    // copy default definitions to data
    rex_dir::copy($this->getPath('data'), $this->getDataPath());

    // create custom fields
    forCalDatabaseManager::executeCustomFieldHandle();

    // create for all tables the lang fields
    forCalDatabaseManager::executeAddLangFields();

    // add forcal editor sets
    forCalEditorHelper::addEditorSets();

    //default settings if redactor exists
    if (rex_addon::exists('redactor2') && array_key_exists('redactor2', rex_addon::getAvailableAddons())) {
        if (redactor2::profileExists('forcal_teaser')) {
            $this->setConfig('forcal_additional_attributes_teaser', htmlspecialchars('{"class":"redactorEditor2-forcal_teaser"}'));
        }
        if (redactor2::profileExists('forcal_text')) {
            $this->setConfig('forcal_additional_attributes_text', htmlspecialchars('{"class":"redactorEditor2-forcal_text"}'));
        }
    }
}

