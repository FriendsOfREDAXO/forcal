<?php
/**
 * @author forCal - Custom Fields Manager
 * @package redaxo5
 * @license MIT
 */

// Sicherheitscheck
if (!rex::isBackend() || !rex::getUser()) {
    return;
}

// Nur Benutzer mit Einstellungs-Berechtigung dürfen die Seite aufrufen
if (!rex::getUser()->isAdmin() && !rex::getUser()->hasPerm('forcal[settings]')) {
    echo rex_view::error(rex_i18n::msg('forcal_permission_denied'));
    return;
}

// Definition-Dateien, die editiert werden können
$definition_files = [
    'entries' => [
        'title' => rex_i18n::msg('forcal_entries'),
        'path' => rex_addon::get('forcal')->getDataPath('definitions/entries.yml'),
        'default_path' => rex_addon::get('forcal')->getPath('data/definitions/entries.yml')
    ],
    'categories' => [
        'title' => rex_i18n::msg('forcal_categories'),
        'path' => rex_addon::get('forcal')->getDataPath('definitions/categories.yml'),
        'default_path' => rex_addon::get('forcal')->getPath('data/definitions/categories.yml')
    ],
    'venues' => [
        'title' => rex_i18n::msg('forcal_venues'),
        'path' => rex_addon::get('forcal')->getDataPath('definitions/venues.yml'),
        'default_path' => rex_addon::get('forcal')->getPath('data/definitions/venues.yml')
    ],
];

// Funktion zur Validierung von YAML
function validateYaml($content) {
    try {
        $data = rex_string::yamlDecode($content);
        return [true, $data];
    } catch (Exception $e) {
        return [false, $e->getMessage()];
    }
}

// Aktuell ausgewählte Definition
$current_file = rex_request('file', 'string', 'entries');
if (!isset($definition_files[$current_file])) {
    $current_file = 'entries';
}

// Pfad zur aktuellen Definitions-Datei
$current_path = $definition_files[$current_file]['path'];
$current_default_path = $definition_files[$current_file]['default_path'];

// Prüfen, ob das Verzeichnis existiert, falls nicht, erstellen
$dir = dirname($current_path);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Prüfen, ob die Datei existiert, falls nicht, aus den Standarddaten kopieren
if (!file_exists($current_path) && file_exists($current_default_path)) {
    copy($current_default_path, $current_path);
}

// YAML-Inhalt laden
$content = '';
if (file_exists($current_path)) {
    $content = rex_file::get($current_path);
}

// Bei Speicheranfragen
$success = '';
$error = '';

if (rex_request('save', 'boolean', false)) {
    $new_content = rex_request('content', 'string', '');
    
    // YAML validieren
    list($valid, $result) = validateYaml($new_content);
    
    if ($valid) {
        // Speichern
        if (rex_file::put($current_path, $new_content)) {
            // Cache leeren für die Definitionen
            $cache_file = rex_addon::get('forcal')->getCachePath('definitions/');
            if (is_dir($cache_file)) {
                rex_dir::delete($cache_file, true);
            }
            
            $success = rex_i18n::msg('forcal_custom_fields_saved');
        } else {
            $error = rex_i18n::msg('forcal_custom_fields_save_error');
        }
    } else {
        $error = rex_i18n::msg('forcal_custom_fields_invalid_yaml') . '<br>' . $result;
    }
}

// Auf Standardwerte zurücksetzen
if (rex_request('reset', 'boolean', false)) {
    if (file_exists($current_default_path)) {
        if (rex_file::copy($current_default_path, $current_path)) {
            $content = rex_file::get($current_path);
            $success = rex_i18n::msg('forcal_custom_fields_reset');
            
            // Cache leeren für die Definitionen
            $cache_file = rex_addon::get('forcal')->getCachePath('definitions/');
            if (is_dir($cache_file)) {
                rex_dir::delete($cache_file, true);
            }
        } else {
            $error = rex_i18n::msg('forcal_custom_fields_reset_error');
        }
    } else {
        $error = rex_i18n::msg('forcal_custom_fields_default_missing');
    }
}

// Navigation erstellen
$items = [];
foreach ($definition_files as $key => $file) {
    $item = [];
    $item['title'] = $file['title'];
    $item['href'] = rex_url::currentBackendPage(['file' => $key]);
    $item['active'] = $key == $current_file;
    $items[] = $item;
}

// Tabs-Navigation anzeigen
$fragment = new rex_fragment();
$fragment->setVar('left', $items, false);
echo $fragment->parse('core/navigations/tabs.php');

// Erfolgsmeldungen
if ($success != '') {
    echo rex_view::success($success);
}

// Fehlermeldungen
if ($error != '') {
    echo rex_view::error($error);
}

// Code-Editor mit JSON/YAML-Unterstützung
$editor = '';
if (rex_addon::get('codemirror')->isAvailable()) {
    $editor = 'codemirror';
} elseif (rex_addon::get('markitup')->isAvailable()) {
    $editor = 'markitup';
}

// Formular für den Editor erstellen
$form = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
    <input type="hidden" name="file" value="' . $current_file . '">
    
    <fieldset>
        <legend>' . rex_i18n::msg('forcal_custom_fields_edit') . ' "' . $definition_files[$current_file]['title'] . '"</legend>
        
        <div class="form-group">
            <p>' . rex_i18n::msg('forcal_custom_fields_help') . '</p>
            <div class="alert alert-info">
                ' . rex_i18n::msg('forcal_custom_fields_help_text') . '
            </div>
        </div>
        
        <div class="form-group">
            <label for="content">' . rex_i18n::msg('forcal_custom_fields_content') . '</label>';

// CodeMirror verwenden, falls verfügbar
if ($editor == 'codemirror') {
    $cm = new rex_codemirror();
    $cm->setMode('yaml');
    $cm->setTheme('twilight');
    $cm->setHeight('400px');
    $form .= $cm->factory('content', htmlspecialchars($content));
} 
// MarkItUp verwenden, falls verfügbar
elseif ($editor == 'markitup') {
    $form .= '<textarea id="content" name="content" rows="25" class="form-control markitup-yaml">' . htmlspecialchars($content) . '</textarea>';
} 
// Fallback auf normales Textarea
else {
    $form .= '<textarea id="content" name="content" rows="25" class="form-control">' . htmlspecialchars($content) . '</textarea>';
}

$form .= '
        </div>
        
        <div class="form-group">
            <button type="submit" name="save" value="1" class="btn btn-primary">' . rex_i18n::msg('forcal_custom_fields_save') . '</button>
            <button type="submit" name="reset" value="1" class="btn btn-danger" onclick="return confirm(\'' . rex_i18n::msg('forcal_custom_fields_reset_confirm') . '\')">' . rex_i18n::msg('forcal_custom_fields_reset') . '</button>
        </div>
    </fieldset>
</form>

<fieldset>
    <legend>' . rex_i18n::msg('forcal_custom_fields_help_title') . '</legend>
    <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title">' . rex_i18n::msg('forcal_custom_fields_example') . '</h3></div>
        <div class="panel-body">
<pre>langfields:
  - name: description
    type: textarea
    label_de: Beschreibung
    label_en: Description

fields:
  - name: city
    type: text
    label_de: Stadt
    label_en: City
  - name: image
    type: media
    label_de: Bild
    label_en: Image</pre>
        </div>
    </div>
    
    <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title">' . rex_i18n::msg('forcal_custom_fields_field_types') . '</h3></div>
        <div class="panel-body">
            <ul>
                <li><code>text</code> - ' . rex_i18n::msg('forcal_custom_fields_type_text') . '</li>
                <li><code>textarea</code> - ' . rex_i18n::msg('forcal_custom_fields_type_textarea') . '</li>
                <li><code>media</code> - ' . rex_i18n::msg('forcal_custom_fields_type_media') . '</li>
                <li><code>medialist</code> - ' . rex_i18n::msg('forcal_custom_fields_type_medialist') . '</li>
                <li><code>link</code> - ' . rex_i18n::msg('forcal_custom_fields_type_link') . '</li>
                <li><code>linklist</code> - ' . rex_i18n::msg('forcal_custom_fields_type_linklist') . '</li>
                <li><code>select</code> - ' . rex_i18n::msg('forcal_custom_fields_type_select') . '</li>
                <li><code>checkbox</code> - ' . rex_i18n::msg('forcal_custom_fields_type_checkbox') . '</li>
                <li><code>radio</code> - ' . rex_i18n::msg('forcal_custom_fields_type_radio') . '</li>
            </ul>
        </div>
    </div>
    
    <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title">' . rex_i18n::msg('forcal_custom_fields_advanced') . '</h3></div>
        <div class="panel-body">
<pre>langfields:
  - panel: 'images'
    label_de: 'Sprachbezogene Bildelemente'
    label_en: 'Language-related images elements'
    fields:
      - name: 'lang_image'
        type: 'media'
        label_de: 'Bild'
        label_en: 'Image'
      - name: 'lang_images'
        type: 'medialist'
        label_de: 'Bilder'
        label_en: 'Images'</pre>
        </div>
    </div>
</fieldset>
';

echo $form;

// Wenn MarkItUp verwendet wird, das YAML-Profil hinzufügen
if ($editor == 'markitup') {
    echo '
<script>
$(document).ready(function() {
    $(".markitup-yaml").markItUp({
        nameSpace: "yaml",
        previewParser: function(content) {
            return content;
        },
        onShiftEnter: {
            keepDefault: false,
            openWith: "\\n"
        },
        markupSet: [
            {
                name: "' . rex_i18n::msg('forcal_yaml_field') . '",
                key: "F",
                openWith: "- name: \\n  type: ",
                closeWith: "\\n  label_de: \\n  label_en: "
            },
            {
                name: "' . rex_i18n::msg('forcal_yaml_panel') . '",
                key: "P",
                openWith: "- panel: \'\'\\n  label_de: \'\'\\n  label_en: \'\'\\n  fields:\\n    ",
                closeWith: ""
            },
            {
                name: "' . rex_i18n::msg('forcal_yaml_indent') . '",
                key: "I",
                openWith: "  "
            }
        ]
    });
});
</script>
    ';
}
