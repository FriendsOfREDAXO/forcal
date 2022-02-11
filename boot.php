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

    if (rex_addon::get('watson')->isAvailable()) {

        function forcal_search(rex_extension_point $ep)
        {
            $subject = $ep->getSubject();
            $subject[] = 'Watson\Workflows\forCal\forCalProvider';
            return $subject;
        }

        rex_extension::register('WATSON_PROVIDER', 'forcal_search', rex_extension::LATE);
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
    rex_view::addJSFile($this->getAssetsUrl('vendor/daterangepicker/moment.min.js'));
    rex_view::addJSFile($this->getAssetsUrl('vendor/daterangepicker/daterangepicker.js'));
    rex_view::addJSFile($this->getAssetsUrl('vendor/clockpicker/bootstrap-clockpicker.js'));
    rex_view::addJSFile($this->getAssetsUrl('forcal.js'));

    // add css
    rex_view::addCssFile($this->getAssetsUrl('vendor/palettecolorpicker/palette-color-picker.css'));
    rex_view::addCssFile($this->getAssetsUrl('vendor/fullcalendar/packages/core/main.css'));
    rex_view::addCssFile($this->getAssetsUrl('vendor/fullcalendar/packages/daygrid/main.css'));
    rex_view::addCssFile($this->getAssetsUrl('vendor/fullcalendar/packages/timegrid/main.css'));
    rex_view::addCssFile($this->getAssetsUrl('vendor/fullcalendar/packages/list/main.css'));
    rex_view::addCssFile($this->getAssetsUrl('vendor/daterangepicker/daterangepicker.css'));
    rex_view::addCssFile($this->getAssetsUrl('vendor/clockpicker/bootstrap-clockpicker.min.css'));
    rex_view::addCssFile($this->getAssetsUrl('forcal.css'));

    if(rex_string::versionCompare(rex::getVersion(), '5.13.0-dev', '>=')) {
        rex_view::addCssFile($this->getAssetsUrl('forcal-dark.css'));
    }

    rex_extension::register('CLANG_ADDED', function () {
        // duplicate lang columns
        forCalDatabaseManager::executeAddLangFields();
    });
    rex_view::setJsProperty('forcal_shortcut_save', isset($config['forcal_shortcut_save']) && $config['forcal_shortcut_save'] ? $config['forcal_shortcut_save'] : false);

    $page = $this->getProperty('page');
    if ($page && $config['forcal_start_page']) {
        $entry = $page['subpages'][$config['forcal_start_page']];
        unset($page['subpages'][$config['forcal_start_page']]);
        $page['subpages'] = [$config['forcal_start_page'] => $entry] + $page['subpages'];
        $this->setProperty('page', $page);
    }
}
if (rex_plugin::get('forcal', 'documentation')->isInstalled()) {
    $plugin = rex_plugin::get('forcal', 'documentation');
    $manager = rex_package_manager::factory($plugin);
    $success = $manager->delete();
}
