<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

require_once __DIR__ . '/vendor/autoload.php';

use forCal\Manager\forCalDatabaseManager;

// Das YForm-Feld "forcal_picker" findet YForm über den Klassennamen; hier nur der Pfad zum Template.
if (rex_addon::get('yform')->isAvailable()) {
    rex_yform::addTemplatePath($this->getPath('ytemplates'));
}

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
    // Im forCal-Picker aus allen Kategorien wählen, ohne Termine pflegen zu dürfen
    rex_perm::register('forcal[pick]', null, rex_perm::OPTIONS);

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

    // Inline Venue Creation – JS + API-URL
    rex_view::addJSFile($this->getAssetsUrl('forcal-venue-inline.js'));
    rex_view::setJsProperty('forcal_venue_create_url', rex_url::backendController(
        rex_api_forcal_venue_create::getUrlParams(), false
    ));
    rex_view::setJsProperty('forcal_venue_modal_title', rex_i18n::msg('forcal_venue_add_inline'));
    rex_view::setJsProperty('forcal_venue_btn_save', rex_i18n::msg('forcal_venue_add_inline_save'));
    rex_view::setJsProperty('forcal_venue_btn_cancel', rex_i18n::msg('forcal_cancel'));
    rex_view::setJsProperty('forcal_venue_name_required', rex_i18n::msg('forcal_venue_name_validation'));
    rex_view::setJsProperty('forcal_venue_lbl_street', rex_i18n::msg('forcal_venue_lbl_street'));
    rex_view::setJsProperty('forcal_venue_lbl_housenumber', rex_i18n::msg('forcal_venue_lbl_housenumber'));
    rex_view::setJsProperty('forcal_venue_lbl_zip', rex_i18n::msg('forcal_venue_lbl_zip'));
    rex_view::setJsProperty('forcal_venue_lbl_city', rex_i18n::msg('forcal_venue_lbl_city'));
    rex_view::setJsProperty('forcal_venue_lbl_country', rex_i18n::msg('forcal_venue_lbl_country'));

    // add css - FullCalendar 6.x CSS ist in den JS-Dateien enthalten
    rex_view::addCssFile($this->getAssetsUrl('forcal-colorpicker.css')); // CSS für den neuen ColorPicker
    // Bootstrap 3 Kompatibilität
    rex_view::addCssFile($this->getAssetsUrl('fc-bootstrap3-compat.css')); 
    rex_view::addCssFile($this->getAssetsUrl('forcal.css'));
    rex_view::addCssFile($this->getAssetsUrl('forcal-dark.css'));

    // forCal-Picker: Eingabefeld-Widget für das ganze Backend (Module, MForm, YForm) und Grundlage der Schnellanlage im Kalenderblatt
    $forcalPickerBust = fn (string $file): string => $this->getAssetsUrl($file) . '?v=' . (int) @filemtime($this->getAssetsPath($file));
    rex_view::addCssFile($forcalPickerBust('forcal_picker.css'));
    rex_view::addJsFile($forcalPickerBust('forcal_picker.js'));
    rex_view::setJsProperty('forcal_picker_api', rex_url::backendController(['rex-api-call' => 'forcal_picker'], false));
    rex_view::setJsProperty('forcal_picker_token', rex_csrf_token::factory(rex_api_forcal_picker::CSRF)->getValue());
    rex_view::setJsProperty('forcal_quick_create', (bool) $this->getConfig('forcal_quick_create', true));
    $forcalPickerTexts = [];
    foreach (['title_entry', 'title_entry_multiple', 'title_category', 'title_category_multiple', 'title_venue', 'title_venue_multiple', 'search_entry', 'search_category', 'search_venue',
        'choose_entry', 'choose_entry_multiple', 'choose_category', 'choose_category_multiple', 'choose_venue', 'choose_venue_multiple', 'change_entry', 'change_entry_multiple', 'change_category',
        'change_category_multiple', 'change_venue', 'change_venue_multiple', 'missing_entry', 'missing_category', 'missing_venue', 'close', 'cancel', 'apply', 'more', 'loading', 'found', 'no_results',
        'empty', 'empty_upcoming', 'selected', 'max', 'max_reached', 'category', 'all_categories', 'include_past', 'public_only', 'offline', 'cancelled', 'clear', 'remove', 'remove_name', 'denied', 'failed',
        'create_new', 'create_entry', 'create_category', 'create_venue', 'create_title', 'create_name_placeholder', 'create_date', 'create_end_date', 'create_all_day', 'create_from', 'create_to',
        'create_and_choose', 'create_and_add', 'create_save', 'create_back', 'create_details', 'create_help', 'create_title_required'] as $forcalPickerKey) {
        $forcalPickerTexts[$forcalPickerKey] = rex_i18n::rawMsg('forcal_picker_js_' . $forcalPickerKey);
    }
    rex_view::setJsProperty('forcal_picker_i18n', $forcalPickerTexts);

    // Tagging-Widget: eigene Assets nur laden wenn fields-Addon nicht aktiv ist
    // (fields lädt dieselben Widget-Klassen; Doppelladen wird so vermieden)
    if (!rex_addon::get('fields')->isAvailable()) {
        rex_view::addJsFile($this->getAssetsUrl('forcal-tagging.js'));
        rex_view::addCssFile($this->getAssetsUrl('forcal-tagging.css'));
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

if (rex_addon::exists('builder') && rex_addon::get('builder')->isAvailable()) {
    $registerForNames = static function (string $legacyName, callable $callable): void {
        $names = [$legacyName];
        if (str_starts_with($legacyName, 'BUILDER_')) {
            $names[] = 'BUILDER_' . substr($legacyName, strlen('BUILDER_'));
        }

        foreach (array_values(array_unique($names)) as $name) {
            rex_extension::register($name, $callable, rex_extension::EARLY);
        }
    };

    $registerForNames(
        'BUILDER_ELEMENT_PATHS',
        static function (rex_extension_point $ep): array {
            $paths = $ep->getSubject();
            if (!is_array($paths)) {
                $paths = [];
            }

            $paths[] = rex_path::addon('forcal', 'elements/');

            return $paths;
        },
    );
}
