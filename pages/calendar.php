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
            <div id="forcal" 
                data-locale="<?= \Locale::getPrimaryLanguage(\Locale::getDefault()) ?>" 
                data-date="<?= date('Y-m-d') ?>" 
                data-csrf="<?= rex_csrf_token::factory('forcal_api_call')->getValue() ?>">
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    // Parameter für die API-Abfrage vorbereiten
    var apiParams = {};
    
    <?php if (!empty($categoryFilter)): ?>
    apiParams.category = <?= json_encode(implode(',', $categoryFilter)) ?>;
    <?php endif; ?>
    
    // API-URL mit Parametern ergänzen
    var apiUrl = rex.forcal_events_api_url;
    if (Object.keys(apiParams).length > 0) {
        var queryParams = [];
        for (var key in apiParams) {
            if (apiParams.hasOwnProperty(key)) {
                queryParams.push(key + '=' + encodeURIComponent(apiParams[key]));
            }
        }
        apiUrl += '&' + queryParams.join('&');
    }
    
    // Kalender mit angepasster URL initialisieren
    var forcal = $('#forcal');
    var forcal_locale = forcal.data('locale');
    var forcal_date = forcal.data('date');
    var csrf_token = forcal.data('csrf');
    
    var calendar = new FullCalendar.Calendar(document.getElementById(forcal.attr('id')), {
        plugins: ['interaction', 'dayGrid', 'timeGrid'],
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        locale: forcal_locale,
        weekNumbers: true,
        weekNumbersWithinDays: true,
        dragScroll: true,
        defaultDate: forcal_date,
        eventLimit: true,
        eventClick: function (info) {
            window.location.replace('index.php?page=forcal/entries&func=edit&id=' + info.event.id);
        },
        datesRender: function (info) {
            if (info.view.viewSpec.type === 'dayGridMonth') {
                addAddIconMonth(forcal);
            }
            if (info.view.viewSpec.type === 'timeGridWeek') {
                addAddIconWeek(forcal);
            }
            if (info.view.viewSpec.type === 'timeGridDay') {
                addAddIconDay(forcal);
            }
        },
        events: {
            url: apiUrl,
            cache: true,
            error: function (xhr, type, exception) {
                console.error("Error loading events:", exception);
                alert("Error loading events: " + exception);
            }
        }
    });
    
    calendar.render();
});
</script>
