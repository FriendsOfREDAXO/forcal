<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

$addon = rex_addon::get('forcal');

if (rex::getUser()->hasPerm('forcal[settings]') || rex::getUser()->isAdmin()) {
    if (rex_post('config-submit', 'boolean')) {
        $addon->setConfig('forcal_colors', rex_post('forcal_colors', 'string'));
        $addon->setConfig('forcal_editor', rex_post('forcal_editor', 'int'));
        $addon->setConfig('forcal_customfield_check', rex_post('forcal_customfield_check', 'int'));
        $addon->setConfig('forcal_datepicker', rex_post('forcal_datepicker', 'int'));
        $addon->setConfig('forcal_additional_attributes_text', rex_post('forcal_additional_attributes_text', 'string'));
        $addon->setConfig('forcal_additional_attributes_teaser', rex_post('forcal_additional_attributes_teaser', 'string'));
        $addon->setConfig('forcal_additional_for_title', rex_post('forcal_additional_for_title', 'string'));
        $addon->setConfig('forcal_shortcut_save', rex_post('forcal_shortcut_save', 'boolean', false));
        $addon->setConfig('forcal_full_time_preselection', rex_post('forcal_full_time_preselection', 'boolean', false));
        $addon->setConfig('forcal_start_page', rex_post('forcal_start_page', 'string', 'calendar'));
        $addon->setConfig('forcal_multiuser', rex_post('forcal_multiuser', 'boolean', false));
        $addon->setConfig('forcal_venues_enabled', rex_post('forcal_venues_enabled', 'boolean', true));

        echo rex_view::success($addon->i18n('forcal_config_saved'));
    }

    $content = '<div class="rex-form">';

    // Formular
    $form = new rex_form_simple_tweaks();
    $form->addFieldset(rex_i18n::msg('forcal_config'));
    
    // Startseite
    $field = $form->addSelectField('forcal_start_page');
    $field->setLabel(rex_i18n::msg('forcal_start_page'));
    $select = $field->getSelect();
    $select->addOption(rex_i18n::msg('forcal_calendar'), 'calendar');
    $select->addOption(rex_i18n::msg('forcal_entries'), 'entries');
    $select->addOption(rex_i18n::msg('forcal_categories'), 'categories');
    if ($addon->getConfig('forcal_venues_enabled', true)) {
        $select->addOption(rex_i18n::msg('forcal_venues'), 'venues');
    }
    $select->setSelected($addon->getConfig('forcal_start_page', 'calendar'));
    
    // Multiuser-Einstellungen
    $field = $form->addCheckboxField('forcal_multiuser');
    $field->setLabel(rex_i18n::msg('forcal_user_permissions'));
    $field->addOption(rex_i18n::msg('forcal_user_permissions'), '1');
    
    // Orte-Tabelle aktivieren
    $field = $form->addCheckboxField('forcal_venues_enabled');
    $field->setLabel(rex_i18n::msg('forcal_venues_enabled'));
    $field->addOption(rex_i18n::msg('forcal_venues_enabled'), '1');
    
    // Shortcuts
    $field = $form->addCheckboxField('forcal_shortcut_save');
    $field->setLabel(rex_i18n::msg('forcal_shortcut_save'));
    $field->addOption(rex_i18n::msg('forcal_shortcut_save'), '1');
    
    // Ganztägige Events vorausgewählt
    $field = $form->addCheckboxField('forcal_full_time_preselection');
    $field->setLabel(rex_i18n::msg('forcal_full_time_preselection'));
    $field->addOption(rex_i18n::msg('forcal_full_time_preselection'), '1');
    
    // Farben
    $field = $form->addTextAreaField('forcal_colors');
    $field->setLabel(rex_i18n::msg('forcal_colors'));
    
    // Editor-Auswahl
    if (rex_addon::get('redactor2')->isAvailable()) {
        $field = $form->addCheckboxField('forcal_editor');
        $field->setLabel(rex_i18n::msg('forcal_redactor2'));
        $field->addOption('Redactor 2', 3);
    }
    
    // Definition Fieldcheck
    $field = $form->addCheckboxField('forcal_customfield_check');
    $field->setLabel(rex_i18n::msg('forcal_customfield_check'));
    $field->addOption(rex_i18n::msg('forcal_customfield_check'), 1);
    
    // Datepicker
    $field = $form->addCheckboxField('forcal_datepicker');
    $field->setLabel(rex_i18n::msg('forcal_datepicker'));
    $field->addOption(rex_i18n::msg('forcal_datepicker'), 1);
    
    // Individuelles Attribut für Text
    $field = $form->addTextAreaField('forcal_additional_attributes_text');
    $field->setLabel(rex_i18n::msg('forcal_additional_attributes'));
    $field->setNotice(rex_i18n::msg('forcal_additional_attributes_notice'));
    
    // Individuelles Attribut für Teaser
    $field = $form->addTextAreaField('forcal_additional_attributes_teaser');
    $field->setLabel(rex_i18n::msg('forcal_additional_attributes'));
    $field->setNotice(rex_i18n::msg('forcal_additional_attributes_notice'));
    
    // Zusätzliches Feld für Kategorie
    $field = $form->addSelectField('forcal_additional_for_title');
    $field->setLabel(rex_i18n::msg('forcal_additional_field_for_category'));
    $select = $field->getSelect();
    $select->addOption(rex_i18n::msg('forcal_please_select'), '');
    $select->addOption('name', 'name');
    
    // Zu allen Feldern die Werte aus der Config setzen
    $form->addHiddenField('config-submit', 'true');
    
    $form->setFormAttribute('method', 'post');
    
    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', $addon->i18n('forcal_config'), false);
    $fragment->setVar('body', $form->get(), false);
    
    $content .= $fragment->parse('core/page/section.php');
    $content .= '</div>';
    
    echo $content;
} else {
    echo rex_view::error(rex_i18n::msg('forcal_permission_denied'));
}

class rex_form_simple_tweaks extends rex_form
{
    function __construct()
    {
        // Dies ist ein Trick, um rex_form ohne Tabellennamen zu verwenden
        parent::__construct('rex_forcal_fake_table', '', '', 'post', false);
    }
    
    function validate()
    {
        // Validierung überspringen, da wir keine Tabelle haben
        return true;
    }
    
    function save()
    {
        // Speichern überspringen, da wir keine Tabelle haben
        return true;
    }
}
