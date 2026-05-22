<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

$addon = rex_addon::get('forcal');
$user = rex::getUser();

// Benutzerrechte prüfen
if (!$user->hasPerm('forcal[]')) {
    echo rex_view::error($addon->i18n('permission_denied'));
    return;
}

// Prüfen, ob der Benutzer überhaupt Kategorien hat (wenn kein Admin)
if (!$user->isAdmin() && !\forCal\Utils\forCalUserPermission::hasAnyPermission()) {
    echo rex_view::warning($addon->i18n('forcal_no_permission_categories'));
}

// Kalender-Filter-Einstellungen
$show_all = rex_request('show_all', 'bool', false);
$user_filter = rex_request('user_filter', 'array', []);

// Kategorien für SQL-Abfrage vorbereiten
$categoryFilter = null;

if (!$user->isAdmin() && !$show_all) {
    // Nur Kategorien anzeigen, für die der Benutzer Berechtigung hat
    $categoryFilter = \forCal\Utils\forCalUserPermission::getUserCategories($user->getId());
} elseif (!empty($user_filter)) {
    // Nur ausgewählte Kategorien anzeigen
    $categoryFilter = $user_filter;
}

// Inhalt
$fragment = new rex_fragment();
echo $fragment->parse('forcal_category_filter.php');

// Hinweis: Der zusätzliche Button für "Neuer Termin" wurde entfernt,
// da er bereits in der FullCalendar-Toolbar integriert ist
?>

<section class="rex-page-section">
    <div class="panel panel-default calendarview">
        <div class="panel-body">
            <div id="forcal" 
                data-locale="<?= \Locale::getPrimaryLanguage(\Locale::getDefault()) ?>" 
                data-date="<?= date('Y-m-d') ?>" 
                data-csrf="<?= rex_csrf_token::factory('forcal_api_call')->getValue() ?>">
            </div>
        </div>
    </div>
</section>
