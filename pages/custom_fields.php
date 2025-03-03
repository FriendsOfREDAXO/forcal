<?php
/**
 * @author Claude (AI)
 * @package redaxo5
 * @license MIT
 */

use forCal\Utils\forCalDefinitions;

// CSRF-Schutz
$csrf = rex_csrf_token::factory('forcal_custom_fields');

// Fehlermeldungen und Erfolgsmeldungen
$error = '';
$success = '';

// Table-Parameter ermitteln
$table = rex_request('table', 'string', 'entries');

// Liste der verfügbaren Tabellen und deren Übersetzungen
$tables = [
    'entries' => rex_i18n::msg('forcal_entries'),
    'categories' => rex_i18n::msg('forcal_categories')
];

// Wenn Orte aktiviert sind, fügen wir sie zur Liste hinzu
if (rex_addon::get('forcal')->getConfig('forcal_venues_enabled', true)) {
    $tables['venues'] = rex_i18n::msg('forcal_venues');
}

// Tabellenwechsel-Formular erstellen
$selectHtml = '<div class="form-group">
    <label for="forcal-table-select">' . rex_i18n::msg('forcal_select_table') . '</label>
    <select class="form-control" id="forcal-table-select" name="table">';

foreach ($tables as $key => $label) {
    $selected = ($table === $key) ? ' selected' : '';
    $selectHtml .= '<option value="' . $key . '"' . $selected . '>' . $label . '</option>';
}

$selectHtml .= '</select>
</div>';

// Ausgabe des Formulars
echo '<section class="rex-page-section">
    <div class="panel panel-edit">
        <header class="panel-heading">
            <div class="panel-title">' . rex_i18n::msg('forcal_custom_fields_editor') . ' - ' . $tables[$table] . '</div>
        </header>
        <div class="panel-body">
            ' . $selectHtml . '
        </div>
    </div>
</section>';

// Aktuelle Konfiguration laden
$definitionFile = forCalDefinitions::definitionPath($table . '.yml');
$customFile = forCalDefinitions::definitionPath('custom_' . $table . '.yml');

// Wenn custom_ Datei existiert, nehmen wir diese, ansonsten die Standard-Datei
$useFile = file_exists($customFile) ? $customFile : $definitionFile;

// YML-Datei laden
$definition = [];
if (file_exists($useFile)) {
    $definition = rex_file::getConfig($useFile);
}

// Definitionen nach Typ (langfields/fields) aufteilen
$langFields = [];
$normalFields = [];

if (isset($definition['langfields'])) {
    $langFields = $definition['langfields'];
}

if (isset($definition['fields'])) {
    $normalFields = $definition['fields'];
}

// Tab-Formular erstellen
$tabsHtml = '<div class="nav nav-tabs" id="forcal-field-tabs" role="tablist">
    <a class="nav-item nav-link active" id="normal-fields-tab" data-toggle="tab" href="#normal-fields" role="tab">' . rex_i18n::msg('forcal_normal_fields') . '</a>
    <a class="nav-item nav-link" id="lang-fields-tab" data-toggle="tab" href="#lang-fields" role="tab">' . rex_i18n::msg('forcal_lang_fields') . '</a>
</div>';

// Tab-Inhalte starten
$tabContentHtml = '<div class="tab-content" id="forcal-field-tabContent">
    <div class="tab-pane fade show active" id="normal-fields" role="tabpanel">';

// Fragment für normale Felder laden
$normalFieldsFragment = new rex_fragment();
$normalFieldsFragment->setVar('fields', $normalFields);
$normalFieldsFragment->setVar('type', 'fields');
$normalFieldsFragment->setVar('table', $table);
$tabContentHtml .= $normalFieldsFragment->parse('forcal_custom_fields_editor.php');

$tabContentHtml .= '</div>
    <div class="tab-pane fade" id="lang-fields" role="tabpanel">';

// Fragment für Sprachfelder laden
$langFieldsFragment = new rex_fragment();
$langFieldsFragment->setVar('fields', $langFields);
$langFieldsFragment->setVar('type', 'langfields');
$langFieldsFragment->setVar('table', $table);
$tabContentHtml .= $langFieldsFragment->parse('forcal_custom_fields_editor.php');

$tabContentHtml .= '</div>
</div>';

// Ausgabe der Tabs und Inhalte
echo '<section class="rex-page-section">
    <div class="panel panel-edit">
        <header class="panel-heading">
            <div class="panel-title">' . rex_i18n::msg('forcal_field_types') . '</div>
        </header>
        <div class="panel-body">
            ' . $tabsHtml . $tabContentHtml . '
        </div>
    </div>
</section>';

// AJAX-Handler für Feldoperationen
if (rex_request::isXmlHttpRequest()) {
    $func = rex_request('func', 'string', '');
    
    // CSRF-Token überprüfen
    if (!$csrf->isValid()) {
        rex_response::setStatus(rex_response::HTTP_FORBIDDEN);
        rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('csrf_token_invalid')]);
        exit;
    }
    
    if ($func === 'save_field') {
        $type = rex_request('type', 'string', 'fields');
        $table = rex_request('table', 'string', 'entries');
        $fieldJson = rex_request('field', 'string', '');
        $index = rex_request('index', 'int', -1);
        $action = rex_request('action', 'string', 'add');
        
        $field = json_decode($fieldJson, true);
        if (!$field) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_invalid_field_data')]);
            exit;
        }
        
        // YML-Datei laden
        $definitionFile = forCalDefinitions::definitionPath($table . '.yml');
        $customFile = forCalDefinitions::definitionPath('custom_' . $table . '.yml');
        
        // Wir arbeiten immer mit custom_ Dateien
        $useFile = $customFile;
        
        // Wenn custom_ Datei nicht existiert, aber die Standard-Datei, kopieren wir diese
        if (!file_exists($useFile) && file_exists($definitionFile)) {
            if (!rex_file::copy($definitionFile, $useFile)) {
                rex_response::setStatus(rex_response::HTTP_INTERNAL_SERVER_ERROR);
                rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_file_copy_error')]);
                exit;
            }
        }
        
        // Aktuelle Definition laden oder neue erstellen
        $definition = file_exists($useFile) ? rex_file::getConfig($useFile) : [];
        
        // Sicherstellen, dass Arrays existieren
        if (!isset($definition[$type])) {
            $definition[$type] = [];
        }
        
        // Feld hinzufügen oder bearbeiten
        if ($action === 'add') {
            $definition[$type][] = $field;
        } elseif ($action === 'edit' && $index >= 0 && isset($definition[$type][$index])) {
            $definition[$type][$index] = $field;
        } else {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_invalid_field_action')]);
            exit;
        }
        
        // Änderungen speichern
        if (rex_file::putConfig($useFile, $definition)) {
            rex_response::sendJson(['success' => true]);
        } else {
            rex_response::setStatus(rex_response::HTTP_INTERNAL_SERVER_ERROR);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_save_error')]);
        }
        exit;
    }
    
    elseif ($func === 'delete_field') {
        $type = rex_request('type', 'string', 'fields');
        $table = rex_request('table', 'string', 'entries');
        $index = rex_request('index', 'int', -1);
        
        if ($index < 0) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_invalid_field_index')]);
            exit;
        }
        
        // Custom-Datei ermitteln
        $customFile = forCalDefinitions::definitionPath('custom_' . $table . '.yml');
        
        // Wenn die Datei nicht existiert, Fehler ausgeben
        if (!file_exists($customFile)) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_file_not_found')]);
            exit;
        }
        
        // Aktuelle Definition laden
        $definition = rex_file::getConfig($customFile);
        
        // Prüfen, ob Feld existiert
        if (!isset($definition[$type]) || !isset($definition[$type][$index])) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_field_not_found')]);
            exit;
        }
        
        // Feld entfernen
        unset($definition[$type][$index]);
        
        // Array neu indizieren
        $definition[$type] = array_values($definition[$type]);
        
        // Änderungen speichern
        if (rex_file::putConfig($customFile, $definition)) {
            rex_response::sendJson(['success' => true]);
        } else {
            rex_response::setStatus(rex_response::HTTP_INTERNAL_SERVER_ERROR);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_save_error')]);
        }
        exit;
    }
    
    elseif ($func === 'reorder_fields') {
        $type = rex_request('type', 'string', 'fields');
        $table = rex_request('table', 'string', 'entries');
        $orderJson = rex_request('order', 'string', '');
        
        $order = json_decode($orderJson, true);
        if (!$order || !is_array($order)) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_invalid_order_data')]);
            exit;
        }
        
        // Custom-Datei ermitteln
        $customFile = forCalDefinitions::definitionPath('custom_' . $table . '.yml');
        
        // Wenn die Datei nicht existiert, Fehler ausgeben
        if (!file_exists($customFile)) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_file_not_found')]);
            exit;
        }
        
        // Aktuelle Definition laden
        $definition = rex_file::getConfig($customFile);
        
        // Prüfen, ob der Typ existiert
        if (!isset($definition[$type])) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_type_not_found')]);
            exit;
        }
        
        // Neue geordnete Feldliste erstellen
        $newFields = [];
        foreach ($order as $index) {
            if (isset($definition[$type][$index])) {
                $newFields[] = $definition[$type][$index];
            }
        }
        
        // Neue Feldliste speichern
        $definition[$type] = $newFields;
        
        // Änderungen speichern
        if (rex_file::putConfig($customFile, $definition)) {
            rex_response::sendJson(['success' => true]);
        } else {
            rex_response::setStatus(rex_response::HTTP_INTERNAL_SERVER_ERROR);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('forcal_save_error')]);
        }
        exit;
    }
}

// JavaScript für Tabellenwechsel
echo '
<script>
$(document).ready(function() {
    $("#forcal-table-select").on("change", function() {
        window.location.href = "' . rex_url::currentBackendPage() . '&table=" + $(this).val();
    });
    
    // Bootstrap 4 Tabs aktivieren
    $("#normal-fields-tab").tab("show");
    
    $(".nav-tabs a").on("click", function(e) {
        e.preventDefault();
        $(this).tab("show");
    });
});
</script>
';
