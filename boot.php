<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

require_once __DIR__ . '/vendor/autoload.php';

use forCal\Manager\forCalDatabaseManager;

if (rex::isBackend() && rex::getUser()) {
    $config = $this->getConfig();

    // Allgemeine Rechte für forCal registrieren
    rex_perm::register('forcal[]', null, rex_perm::OPTIONS);
    
    // Neues Recht für uneingeschränkten Zugriff (wie Admin)
    rex_perm::register('forcal[all]', null, rex_perm::OPTIONS);
    
    // Rechte für spezifische Seiten
    rex_perm::register('forcal[settings]', null, rex_perm::OPTIONS);
    rex_perm::register('forcal[catspage]', null, rex_perm::OPTIONS);
    rex_perm::register('forcal[venuespage]', null, rex_perm::OPTIONS);

    // Multiuser Einstellungen aktivieren, wenn gesetzt
    if (isset($config['forcal_multiuser']) && $config['forcal_multiuser']) {
        // Rechte für Administrations-Seiten setzen
        if (rex::getUser()->isAdmin()) {
            // User Permissions nur für Admins
            rex_perm::register('forcal[userpermissions]', null, rex_perm::OPTIONS);
        }
    }

    if (rex_addon::get('watson')->isAvailable()) {
        function forcal_search(rex_extension_point $ep)
        {
            $subject = $ep->getSubject();
            $subject[] = 'Watson\Workflows\forCal\forCalProvider';
            return $subject;
        }

        rex_extension::register('WATSON_PROVIDER', 'forcal_search', rex_extension::LATE);
    }
    
    if (rex_addon::get('quick_navigation')->isAvailable()) {
        rex_extension::register('QUICK_NAVI_CUSTOM', ['forCalQn','getCalHistory'], rex_extension::LATE);
    }

    // Tabelle für Medienberechtigungen erstellen, falls sie noch nicht existiert
    $mediaPermTable = rex_sql_table::get(rex::getTablePrefix() . 'forcal_user_media_permissions');
    if (!$mediaPermTable->exists()) {
        $mediaPermTable
            ->ensureColumn(new rex_sql_column('id', 'int(11) unsigned', false, null, 'auto_increment'))
            ->ensureColumn(new rex_sql_column('user_id', 'int(11)'))
            ->ensureColumn(new rex_sql_column('can_upload_media', 'tinyint(1)', false, '0'))
            ->ensureColumn(new rex_sql_column('createdate', 'datetime', false, 'CURRENT_TIMESTAMP'))
            ->setPrimaryKey('id')
            ->ensure();
    }

    // create custom fields
    forCalDatabaseManager::executeCustomFieldHandle();
    rex_view::setJsProperty('forcal_events_api_url', rex_url::backendController(['rex-api-call' => 'forcal_exchange', '_csrf_token' => \forCal\Handler\forCalApi::getToken()]));
    
    // add js
    rex_view::addJSFile($this->getAssetsUrl('vendor/palettecolorpicker/palette-color-picker.js'));
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar/packages/core/main.js'));
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar/packages/interaction/main.js'));
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar/packages/daygrid/main.js'));
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar/packages/timegrid/main.js'));
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar/packages/list/main.js'));
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar/packages/core/locales-all.js'));
    
     rex_view::addJSFile($this->getAssetsUrl('forcal.js'));

    // add css
    rex_view::addCssFile($this->getAssetsUrl('vendor/palettecolorpicker/palette-color-picker.css'));
    rex_view::addCssFile($this->getAssetsUrl('vendor/fullcalendar/packages/core/main.css'));
    rex_view::addCssFile($this->getAssetsUrl('vendor/fullcalendar/packages/daygrid/main.css'));
    rex_view::addCssFile($this->getAssetsUrl('vendor/fullcalendar/packages/timegrid/main.css'));
    rex_view::addCssFile($this->getAssetsUrl('vendor/fullcalendar/packages/list/main.css'));
    rex_view::addCssFile($this->getAssetsUrl('forcal.css'));

    if(rex_string::versionCompare(rex::getVersion(), '5.13.0-dev', '>=')) {
        rex_view::addCssFile($this->getAssetsUrl('forcal-dark.css'));
    }

    // Register clang added event
    rex_extension::register('CLANG_ADDED', function () {
        // duplicate lang columns
        forCalDatabaseManager::executeAddLangFields();
    });
    
    // Einstellung für optionale Orte-Tabelle
    rex_view::setJsProperty('forcal_venues_enabled', isset($config['forcal_venues_enabled']) ? (bool)$config['forcal_venues_enabled'] : true);
    
    // Shortcut-Einstellung
    rex_view::setJsProperty('forcal_shortcut_save', isset($config['forcal_shortcut_save']) && $config['forcal_shortcut_save'] ? $config['forcal_shortcut_save'] : false);

    $page = $this->getProperty('page');
    if ($page && isset($config['forcal_start_page'])) {
        $entry = $page['subpages'][$config['forcal_start_page']];
        unset($page['subpages'][$config['forcal_start_page']]);
        $page['subpages'] = [$config['forcal_start_page'] => $entry] + $page['subpages'];
        
        // Wenn Orte deaktiviert sind, die Seite ausblenden
        if (isset($config['forcal_venues_enabled']) && !$config['forcal_venues_enabled'] && isset($page['subpages']['venues'])) {
            unset($page['subpages']['venues']);
        }
        
        $this->setProperty('page', $page);
    }
}

if (rex_plugin::get('forcal', 'documentation')->isInstalled()) {
    $plugin = rex_plugin::get('forcal', 'documentation');
    $manager = rex_package_manager::factory($plugin);
    $success = $manager->delete();
}
