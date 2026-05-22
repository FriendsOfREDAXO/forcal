<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

$addon = rex_addon::get('forcal');

if (rex::getUser()->hasPerm('forcal[settings]') || rex::getUser()->isAdmin()) {
    // Speichern der Einstellungen, wenn das Formular abgeschickt wurde
    if (rex_request_method() === 'post' && rex_post('btn_save', 'string', '') !== '') {
        // Einstellungen aus dem Formular übernehmen
        $addon->setConfig('forcal_colors', rex_post('forcal_colors', 'string', ''));
        $addon->setConfig('forcal_editor', rex_post('forcal_editor', 'int', 0));
        $addon->setConfig('forcal_customfield_check', rex_post('forcal_customfield_check', 'int', 0));
        $addon->setConfig('forcal_datepicker', rex_post('forcal_datepicker', 'int', 0));
        $addon->setConfig('forcal_additional_attributes_text', rex_post('forcal_additional_attributes_text', 'string', ''));
        $addon->setConfig('forcal_additional_attributes_teaser', rex_post('forcal_additional_attributes_teaser', 'string', ''));
        $addon->setConfig('forcal_additional_for_title', rex_post('forcal_additional_for_title', 'string', ''));
        $addon->setConfig('forcal_shortcut_save', rex_post('forcal_shortcut_save', 'boolean', false));
        $addon->setConfig('forcal_full_time_preselection', rex_post('forcal_full_time_preselection', 'boolean', false));
        $addon->setConfig('forcal_start_page', rex_post('forcal_start_page', 'string', 'calendar'));
        $addon->setConfig('forcal_multiuser', rex_post('forcal_multiuser', 'boolean', false));
        $addon->setConfig('forcal_venues_enabled', rex_post('forcal_venues_enabled', 'boolean', false));

        // Erfolgsmeldung anzeigen
        echo rex_view::success($addon->i18n('forcal_config_saved'));
    }

    // Formular ausgeben
    $content = '<div class="rex-form">';
    $content .= '<form action="" method="post">';

    // Startseite
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="forcal_start_page">' . rex_i18n::msg('forcal_start_page') . '</label>';
    $select = new rex_select();
    $select->setId('forcal_start_page');
    $select->setName('forcal_start_page');
    $select->addOption(rex_i18n::msg('forcal_calendar'), 'calendar');
    $select->addOption(rex_i18n::msg('forcal_entries'), 'entries');
    $select->addOption(rex_i18n::msg('forcal_categories'), 'categories');
    $select->addOption(rex_i18n::msg('forcal_venues'), 'venues');
    $select->setSelected($addon->getConfig('forcal_start_page', 'calendar'));
    $n['field'] = $select->get();
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');

    // Multiuser-Einstellungen
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="forcal_multiuser">' . rex_i18n::msg('forcal_user_permissions') . '</label>';
    $n['field'] = '<input type="checkbox" id="forcal_multiuser" name="forcal_multiuser" value="1" ' . ($addon->getConfig('forcal_multiuser') ? 'checked="checked"' : '') . ' />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/checkbox.php');

    // Orte-Tabelle aktivieren
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="forcal_venues_enabled">' . rex_i18n::msg('forcal_venues_enabled') . '</label>';
    $n['field'] = '<input type="checkbox" id="forcal_venues_enabled" name="forcal_venues_enabled" value="1" ' . ($addon->getConfig('forcal_venues_enabled', true) ? 'checked="checked"' : '') . ' />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/checkbox.php');

    // Shortcuts
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="forcal_shortcut_save">' . rex_i18n::msg('forcal_shortcut_save') . '</label>';
    $n['field'] = '<input type="checkbox" id="forcal_shortcut_save" name="forcal_shortcut_save" value="1" ' . ($addon->getConfig('forcal_shortcut_save') ? 'checked="checked"' : '') . ' />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/checkbox.php');

    // Ganztägige Events vorausgewählt
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="forcal_full_time_preselection">' . rex_i18n::msg('forcal_full_time_preselection') . '</label>';
    $n['field'] = '<input type="checkbox" id="forcal_full_time_preselection" name="forcal_full_time_preselection" value="1" ' . ($addon->getConfig('forcal_full_time_preselection') ? 'checked="checked"' : '') . ' />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/checkbox.php');

    // Farben
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="forcal_colors">' . rex_i18n::msg('forcal_colors') . '</label>';
    $n['field'] = '<textarea id="forcal_colors" name="forcal_colors" class="form-control" rows="5">' . $addon->getConfig('forcal_colors') . '</textarea>';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');

    // Editor-Auswahl
    if (rex_addon::get('redactor2')->isAvailable()) {
        $formElements = [];
        $n = [];
        $n['label'] = '<label for="forcal_editor">' . rex_i18n::msg('forcal_redactor2') . '</label>';
        $n['field'] = '<input type="checkbox" id="forcal_editor" name="forcal_editor" value="3" ' . ($addon->getConfig('forcal_editor') == 3 ? 'checked="checked"' : '') . ' />';
        $formElements[] = $n;

        $fragment = new rex_fragment();
        $fragment->setVar('elements', $formElements, false);
        $content .= $fragment->parse('core/form/checkbox.php');
    }

    // Definition Fieldcheck
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="forcal_customfield_check">' . rex_i18n::msg('forcal_customfield_check') . '</label>';
    $n['field'] = '<input type="checkbox" id="forcal_customfield_check" name="forcal_customfield_check" value="1" ' . ($addon->getConfig('forcal_customfield_check') == 1 ? 'checked="checked"' : '') . ' />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/checkbox.php');

    // Datepicker
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="forcal_datepicker">' . rex_i18n::msg('forcal_datepicker') . '</label>';
    $n['field'] = '<input type="checkbox" id="forcal_datepicker" name="forcal_datepicker" value="1" ' . ($addon->getConfig('forcal_datepicker') == 1 ? 'checked="checked"' : '') . ' />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/checkbox.php');

    // Individuelles Attribut für Text
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="forcal_additional_attributes_text">' . rex_i18n::msg('forcal_additional_attributes') . ' (Text)</label>';
    $n['field'] = '<textarea id="forcal_additional_attributes_text" name="forcal_additional_attributes_text" class="form-control" rows="3">' . $addon->getConfig('forcal_additional_attributes_text') . '</textarea>';
    $n['note'] = rex_i18n::msg('forcal_additional_attributes_notice');
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');

    // Individuelles Attribut für Teaser
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="forcal_additional_attributes_teaser">' . rex_i18n::msg('forcal_additional_attributes') . ' (Teaser)</label>';
    $n['field'] = '<textarea id="forcal_additional_attributes_teaser" name="forcal_additional_attributes_teaser" class="form-control" rows="3">' . $addon->getConfig('forcal_additional_attributes_teaser') . '</textarea>';
    $n['note'] = rex_i18n::msg('forcal_additional_attributes_notice');
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');

    // Zusätzliches Feld für Kategorie
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="forcal_additional_for_title">' . rex_i18n::msg('forcal_additional_field_for_category') . '</label>';
    $select = new rex_select();
    $select->setId('forcal_additional_for_title');
    $select->setName('forcal_additional_for_title');
    $select->addOption(rex_i18n::msg('forcal_please_select'), '');
    $select->addOption('name', 'name');
    $select->setSelected($addon->getConfig('forcal_additional_for_title', ''));
    $n['field'] = $select->get();
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');

    // Submit-Button
    $formElements = [];
    $n = [];
    $n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="btn_save" value="1">' . rex_i18n::msg('forcal_config_save') . '</button>';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/submit.php');

    $content .= '</form>';
    $content .= '</div>';

    // In Sektion ausgeben
    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', $addon->i18n('forcal_config'), false);
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');
} else {
    echo rex_view::error(rex_i18n::msg('forcal_permission_denied'));
}
