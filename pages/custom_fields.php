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

// Feld speichern
if (rex_request('func', 'string') === 'save_field') {
    if ($csrf->isValid()) {
        $type = rex_request('type', 'string', 'fields');
        $fieldData = rex_request('field_data', 'string', '');
        $index = rex_request('index', 'int', -1);
        $action = rex_request('action', 'string', 'add');
        
        $field = json_decode($fieldData, true);
        if (!$field) {
            $error = rex_i18n::msg('forcal_invalid_field_data');
        } else {
            // YML-Datei laden
            $definitionFile = forCalDefinitions::definitionPath($table . '.yml');
            $customFile = forCalDefinitions::definitionPath('custom_' . $table . '.yml');
            
            // Wir arbeiten immer mit custom_ Dateien
            $useFile = $customFile;
            
            // Wenn custom_ Datei nicht existiert, aber die Standard-Datei, kopieren wir diese
            if (!file_exists($useFile) && file_exists($definitionFile)) {
                if (!rex_file::copy($definitionFile, $useFile)) {
                    $error = rex_i18n::msg('forcal_file_copy_error');
                }
            }
            
            if (empty($error)) {
                // Aktuelle Definition laden oder neue erstellen
                $definition = file_exists($useFile) ? rex_file::getConfig($useFile) : [];
                
                // Sicherstellen, dass Arrays existieren
                if (!isset($definition[$type])) {
                    $definition[$type] = [];
                }
                
                // Feld hinzufügen oder bearbeiten
                if ($action === 'add') {
                    $definition[$type][] = $field;
                    $success = rex_i18n::msg('forcal_field_added');
                } elseif ($action === 'edit' && $index >= 0 && isset($definition[$type][$index])) {
                    $definition[$type][$index] = $field;
                    $success = rex_i18n::msg('forcal_field_updated');
                } else {
                    $error = rex_i18n::msg('forcal_invalid_field_action');
                }
                
                // Änderungen speichern
                if (empty($error)) {
                    if (rex_file::putConfig($useFile, $definition)) {
                        // Erfolgreich
                    } else {
                        $error = rex_i18n::msg('forcal_save_error');
                    }
                }
            }
        }
    } else {
        $error = rex_i18n::msg('csrf_token_invalid');
    }
}

// Feld löschen
elseif (rex_request('func', 'string') === 'delete_field') {
    if ($csrf->isValid()) {
        $type = rex_request('type', 'string', 'fields');
        $index = rex_request('index', 'int', -1);
        
        if ($index < 0) {
            $error = rex_i18n::msg('forcal_invalid_field_index');
        } else {
            // Custom-Datei ermitteln
            $customFile = forCalDefinitions::definitionPath('custom_' . $table . '.yml');
            
            // Wenn die Datei nicht existiert, Fehler ausgeben
            if (!file_exists($customFile)) {
                $error = rex_i18n::msg('forcal_file_not_found');
            } else {
                // Aktuelle Definition laden
                $definition = rex_file::getConfig($customFile);
                
                // Prüfen, ob Feld existiert
                if (!isset($definition[$type]) || !isset($definition[$type][$index])) {
                    $error = rex_i18n::msg('forcal_field_not_found');
                } else {
                    // Feld entfernen
                    unset($definition[$type][$index]);
                    
                    // Array neu indizieren
                    $definition[$type] = array_values($definition[$type]);
                    
                    // Änderungen speichern
                    if (rex_file::putConfig($customFile, $definition)) {
                        $success = rex_i18n::msg('forcal_field_deleted');
                    } else {
                        $error = rex_i18n::msg('forcal_save_error');
                    }
                }
            }
        }
    } else {
        $error = rex_i18n::msg('csrf_token_invalid');
    }
}

// Felder neu anordnen
elseif (rex_request('func', 'string') === 'reorder_fields') {
    if ($csrf->isValid()) {
        $type = rex_request('type', 'string', 'fields');
        $orderJson = rex_request('order', 'string', '');
        
        $order = json_decode($orderJson, true);
        if (!$order || !is_array($order)) {
            $error = rex_i18n::msg('forcal_invalid_order_data');
        } else {
            // Custom-Datei ermitteln
            $customFile = forCalDefinitions::definitionPath('custom_' . $table . '.yml');
            
            // Wenn die Datei nicht existiert, Fehler ausgeben
            if (!file_exists($customFile)) {
                $error = rex_i18n::msg('forcal_file_not_found');
            } else {
                // Aktuelle Definition laden
                $definition = rex_file::getConfig($customFile);
                
                // Prüfen, ob der Typ existiert
                if (!isset($definition[$type])) {
                    $error = rex_i18n::msg('forcal_type_not_found');
                } else {
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
                        $success = rex_i18n::msg('forcal_fields_reordered');
                    } else {
                        $error = rex_i18n::msg('forcal_save_error');
                    }
                }
            }
        }
    } else {
        $error = rex_i18n::msg('csrf_token_invalid');
    }
}

// Fehler- und Erfolgsmeldungen anzeigen
if (!empty($error)) {
    echo rex_view::error($error);
}
if (!empty($success)) {
    echo rex_view::success($success);
}

// Tabellenwechsel-Formular erstellen
$formContent = '<div class="form-group">
    <label for="forcal-table-select">' . rex_i18n::msg('forcal_select_table') . '</label>
    <select class="form-control" id="forcal-table-select" name="table">';

foreach ($tables as $key => $label) {
    $selected = ($table === $key) ? ' selected' : '';
    $formContent .= '<option value="' . $key . '"' . $selected . '>' . $label . '</option>';
}

$formContent .= '</select>
</div>';

// Ausgabe des Formulars
echo '<section class="rex-page-section">
    <div class="panel panel-edit">
        <header class="panel-heading">
            <div class="panel-title">' . rex_i18n::msg('forcal_custom_fields_editor') . ' - ' . $tables[$table] . '</div>
        </header>
        <div class="panel-body">
            <form action="' . rex_url::currentBackendPage() . '" method="get" class="pjax-form">
                <input type="hidden" name="page" value="forcal/custom_fields">
                ' . $formContent . '
            </form>
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

// Tab-Navigation
echo '<section class="rex-page-section">
    <div class="panel panel-edit">
        <header class="panel-heading">
            <div class="panel-title">' . rex_i18n::msg('forcal_field_types') . '</div>
        </header>
        <div class="panel-body">
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#normal-fields" aria-controls="normal-fields" role="tab" data-toggle="tab">' . rex_i18n::msg('forcal_normal_fields') . '</a></li>
                <li role="presentation"><a href="#lang-fields" aria-controls="lang-fields" role="tab" data-toggle="tab">' . rex_i18n::msg('forcal_lang_fields') . '</a></li>
            </ul>
            
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="normal-fields">';

// Fragment für normale Felder
$normalFieldsFragment = new rex_fragment();
$normalFieldsFragment->setVar('fields', $normalFields);
$normalFieldsFragment->setVar('type', 'fields');
$normalFieldsFragment->setVar('table', $table);
echo $normalFieldsFragment->parse('forcal_custom_fields_editor.php');

echo '      </div>
                <div role="tabpanel" class="tab-pane" id="lang-fields">';

// Fragment für Sprachfelder
$langFieldsFragment = new rex_fragment();
$langFieldsFragment->setVar('fields', $langFields);
$langFieldsFragment->setVar('type', 'langfields');
$langFieldsFragment->setVar('table', $table);
echo $langFieldsFragment->parse('forcal_custom_fields_editor.php');

echo '      </div>
            </div>
        </div>
    </div>
</section>';

// JavaScript für Tabellenwechsel
echo '
<script>
$(document).ready(function() {
    $("#forcal-table-select").on("change", function() {
        $(this).closest("form").submit();
    });
    
    // PJAX für Formulare aktivieren
    $(".pjax-form").on("submit", function(e) {
        e.preventDefault();
        var form = $(this);
        $.pjax({
            url: form.attr("action") + "?" + form.serialize(),
            container: "#rex-js-page-main-content",
            fragment: "#rex-js-page-main-content"
        });
    });
});
</script>
';
