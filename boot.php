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
        // Moderne Button-Registrierung über ButtonRegistry
        if (class_exists('FriendsOfRedaxo\\QuickNavigation\\Button\\ButtonRegistry')) {
            require_once __DIR__ . '/lib/ForCalButton.php';
            FriendsOfRedaxo\QuickNavigation\Button\ButtonRegistry::registerButton(
                new ForCalButton(),
                45, // Priority zwischen ArticleHistory (40) und YForm (50)
                'forcal',
                rex_i18n::msg('forcal_title')
            );
        } else {
            // Legacy Support für ältere Quick Navigation Versionen
            rex_extension::register('QUICK_NAVI_CUSTOM', ['forCalQn','getCalHistory'], rex_extension::LATE);
        }
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
    
    // add js - FullCalendar aus npm - korrigierte Pfade
    rex_view::addJSFile($this->getAssetsUrl('forcal-colorpicker.js')); // Neuer ColorPicker ohne jQuery-Abhängigkeit
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar-6.x/core/index.global.min.js')); // Core
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar-6.x/core/locales-all.global.min.js')); // Locales
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar-6.x/interaction/index.global.min.js'));
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar-6.x/daygrid/index.global.min.js'));
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar-6.x/timegrid/index.global.min.js'));
    rex_view::addJSFile($this->getAssetsUrl('vendor/fullcalendar-6.x/list/index.global.min.js'));
    
    rex_view::addJSFile($this->getAssetsUrl('forcal.js'));

    // add css - FullCalendar 6.x CSS ist in den JS-Dateien enthalten
    rex_view::addCssFile($this->getAssetsUrl('forcal-colorpicker.css')); // CSS für den neuen ColorPicker
    // Bootstrap 3 Kompatibilität
    rex_view::addCssFile($this->getAssetsUrl('fc-bootstrap3-compat.css')); 
    rex_view::addCssFile($this->getAssetsUrl('forcal.css'));
    rex_view::addCssFile($this->getAssetsUrl('forcal-dark.css'));

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
