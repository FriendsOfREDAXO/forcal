<?php

$func = rex_request('func', 'string');
$type = rex_request('type', 'string', 'entries'); // Default type
$content = rex_request('content', 'string');
$msg = '';

$addon = rex_addon::get('forcal');
$defaultFile = $addon->getPath('data/definitions/' . $type . '.yml');
$customFile = $addon->getDataPath('definitions/custom_' . $type . '.yml');

// Handle Save
if ($func == 'save') {
    try {
        // Validation check for valid YAML
        rex_string::yamlDecode($content);
        
        // Ensure directory exists
        if (!is_dir(dirname($customFile))) {
            rex_dir::create(dirname($customFile));
        }
        
        rex_file::put($customFile, $content);
        $msg = rex_view::success(rex_i18n::msg('forcal_custom_field_saved'));
        
        $data = rex_string::yamlDecode($content);
        if (!empty($data)) {
            // Update database structure
            \forCal\Manager\forCalDatabaseManager::executeCustomFieldHandle();
        }

    } catch (rex_yaml_parse_exception $e) {
        $msg = rex_view::error(rex_i18n::msg('forcal_custom_field_error_yaml') . ': ' . $e->getMessage());
    }
} elseif ($func == 'reset') {
    if (file_exists($customFile)) {
        rex_file::delete($customFile);
        $msg = rex_view::success(rex_i18n::msg('forcal_custom_field_reset_success'));
        
        // Sync with database (using default definition)
        $sql = rex_sql::factory();
        $sql->setQuery('DELETE FROM ' . rex::getTable('forcal_custom_fields') . ' WHERE type = ?', [$type]);
}

// Load Content
$editorContent = '';
if (file_exists($customFile)) {
    $editorContent = rex_file::get($customFile);
} elseif (file_exists($defaultFile)) {
    $editorContent = rex_file::get($defaultFile);
}

// Tabs Navigation
$tabs = '';
$tabs .= '<ul class="nav nav-tabs">';
$tabs .= '<li role="presentation" class="' . ($type == 'entries' ? 'active' : '') . '"><a href="' . rex_url::currentBackendPage(['type' => 'entries']) . '">' . rex_i18n::msg('forcal_entries') . '</a></li>';
$tabs .= '<li role="presentation" class="' . ($type == 'categories' ? 'active' : '') . '"><a href="' . rex_url::currentBackendPage(['type' => 'categories']) . '">' . rex_i18n::msg('forcal_categories') . '</a></li>';
$tabs .= '<li role="presentation" class="' . ($type == 'venues' ? 'active' : '') . '"><a href="' . rex_url::currentBackendPage(['type' => 'venues']) . '">' . rex_i18n::msg('forcal_venues') . '</a></li>';
$tabs .= '</ul>';

echo '<div class="nav-rex">' . $tabs . '</div>';

echo $msg;

/n['field'] .= ' <button class="btn btn-delete" type="submit" name="func" value="reset" onclick="return confirm(\'' . rex_i18n::msg('forcal_custom_field_reset_confirm') . '\')">' . rex_i18n::msg('forcal_custom_field_reset') . '</button>';
$/ Form
$formContent = '<div class="rex-form-group form-group">
    <label for="code-editor" class="control-label">' . rex_i18n::msg('forcal_custom_field_yaml_definition') . '</label>
    <div>
        <textarea class="form-control rex-code" id="code-editor" name="content" rows="30" spellcheck="false">' . rex_escape($editorContent) . '</textarea>
    </div>
</div>';

// Submit button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="func" value="save">' . rex_i18n::msg('form_save') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('flush', true);
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');


// Panel Construction
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', rex_i18n::msg('forcal_custom_fields_title') . ': ' . rex_i18n::msg('forcal_' . $type));
$fragment->setVar('body', '<form action="' . rex_url::currentBackendPage(['type' => $type]) . '" method="post">' . $formContent . $buttons . '</form>', false);
$content = $fragment->parse('core/page/section.php');


// Sidebar with Example/Help
$sidebarContent = '
    <div class="alert alert-info">
        <strong>' . rex_i18n::msg('help') . '</strong>
        <p>' . rex_i18n::msg('forcal_custom_field_help_intro') . '</p>
        <pre>
langfields:
  - panel: "content"
    label_de: "Inhalte"
    label_en: "Content"
    fields:
      - name: "my_title"
        type: "text"
        label_de: "Titel"
        label_en: "Title"
        attributes:
          class: "form-control"

      - name: "my_selection"
        type: "select"
        label_de: "Auswahl"
        label_en: "Selection"
        options:
          default: "Standard"
          highlight: "Hervorgehoben"
          hidden: "Versteckt"
        attributes:
          class: "selectpicker form-control"

      - name: "my_sql_selection"
        type: "select"
        label_de: "SQL Auswahl"
        label_en: "SQL Selection"
        # The query must return \'name\' (label) and \'id\' (value) columns!
        qry: "SELECT name, id FROM rex_forcal_categories ORDER BY name ASC"
        attributes:
          class: "selectpicker form-control"

      - name: "my_callback_selection"
        type: "select"
        label_de: "Callback Auswahl"
        label_en: "Callback Selection"
        # The callback must return an array of arrays with \'name\' and \'id\' keys
        callback: "MyClass::getOptions"
        attributes:
          class: "selectpicker form-control"

fields:
  - name: "event_image"
    type: "media"
    label_de: "Event Bild"
    label_en: "Event Image"

  - name: "is_featured"
    type: "checkbox"
    label_de: "Hervorheben"
    label_en: "Highlight"
    options:
      1: "Ja, dieses Event hervorheben"
        </pre>
        <ul>
            <li><strong>text</strong>: Einfaches Textfeld</li>
            <li><strong>textarea</strong>: Mehrzeiliges Textfeld</li>
            <li><strong>media</strong>: Medienpool Widget</li>
            <li><strong>medialist</strong>: Medienpool Liste</li>
            <li><strong>link</strong>: Linkmap Widget</li>
            <li><strong>linklist</strong>: Linkmap Liste</li>
            <li><strong>checkbox</strong>: Checkbox (Optionen definieren!)</li>
            <li><strong>select</strong>: Auswahlfeld (Optionen definieren!)</li>
        </ul>
    </div>
';

// Output Layout (2 Columns)
$fragment = new rex_fragment();
$fragment->setVar('content', [
    $content,
    $sidebarContent
], false);
$fragment->setVar('classes', ['col-sm-12', 'col-sm-12'], false);
echo $fragment->parse('core/page/grid.php');
