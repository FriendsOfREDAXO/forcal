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

?>

<section class="rex-page-section">
    <div class="panel panel-default calendarview">
        <div class="panel-body">
            <!-- Tui Calendar Container -->
            <div id="forcal-menu" class="forcal-menu">
                <span id="menu-navi">
                    <button type="button" class="btn btn-default btn-sm move-today" data-action="move-today">
                        <?= rex_i18n::msg('forcal_today') ?>
                    </button>
                    <button type="button" class="btn btn-default btn-sm move-day" data-action="move-prev">
                        <i class="fa fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="btn btn-default btn-sm move-day" data-action="move-next">
                        <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </span>
                <span id="renderRange" class="render-range"></span>
                <span class="view-switcher">
                    <button type="button" class="btn btn-default btn-sm" data-action="change-view" data-view-name="month">
                        <?= rex_i18n::msg('forcal_month') ?>
                    </button>
                    <button type="button" class="btn btn-default btn-sm" data-action="change-view" data-view-name="week">
                        <?= rex_i18n::msg('forcal_week') ?>
                    </button>
                    <button type="button" class="btn btn-default btn-sm" data-action="change-view" data-view-name="day">
                        <?= rex_i18n::msg('forcal_day') ?>
                    </button>
                </span>
            </div>
            <div id="forcal" 
                data-locale="<?= \Locale::getPrimaryLanguage(\Locale::getDefault()) ?>" 
                data-date="<?= date('Y-m-d') ?>" 
                data-csrf="<?= rex_csrf_token::factory('forcal_api_call')->getValue() ?>">
            </div>
        </div>
    </div>
</section>
