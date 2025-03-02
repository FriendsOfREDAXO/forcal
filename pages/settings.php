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
    $content .= '<form action="' . rex_url::currentBackendPage() . '" method="post">';
    $content .= '<input type="hidden" name="config-submit" value="1" />';

    // Formular-Felder hinzufügen
    $formElements = [];

    // Startseite
    $select = new rex_select();
    $select->setId('forcal_start_page');
    $select->setAttribute('class', 'form-control');
    $select->setName('forcal_start_page');
    $select->addOption(rex_i18n::msg('forcal_calendar'), 'calendar');
    $select->addOption(rex_i18n::msg('forcal_entries'), 'entries');
    $select->addOption(rex_i18n::msg('forcal_categories'), 'categories');
    if ($addon->getConfig('forcal_venues_enabled', true)) {
        $select->addOption(rex_i18n::msg('forcal_venues'), 'venues');
    }
    $select->setSelected($addon->getConfig('forcal_start_page', 'calendar'));
    
    $field = '<div class="form-group">
        <label for="forcal_start_page">' . rex_i18n::msg('forcal_start_page') . '</label>
        ' . $select->get() . '
    </div>';
    $formElements[] = ['field' => $field];

    // Multiuser-Einstellungen
    $checked = $addon->getConfig('forcal_multiuser') ? ' checked="checked"' : '';
    $field = '<div class="form-group">
        <label class="control-label" for="forcal_multiuser">' . rex_i18n::msg('forcal_user_permissions') . '</label>
        <div class="checkbox">
            <label>
                <input type="checkbox" id="forcal_multiuser" name="forcal_multiuser" value="1"' . $checked . '> 
                ' . rex_i18n::msg('forcal_user_permissions') . '
            </label>
        </div>
    </div>';
    $formElements[] = ['field' => $field];

    // Orte-Tabelle aktivieren
    $checked = $addon->getConfig('forcal_venues_enabled', true) ? ' checked="checked"' : '';
    $field = '<div class="form-group">
        <label class="control-label" for="forcal_venues_enabled">' . rex_i18n::msg('forcal_venues_enabled') . '</label>
        <div class="checkbox">
            <label>
                <input type="checkbox" id="forcal_venues_enabled" name="forcal_venues_enabled" value="1"' . $checked . '> 
                ' . rex_i18n::msg('forcal_venues_enabled') . '
            </label>
        </div>
    </div>';
    $formElements[] = ['field' => $field];

    // Shortcuts
    $checked = $addon->getConfig('forcal_shortcut_save') ? ' checked="checked"' : '';
    $field = '<div class="form-group">
        <label class="control-label" for="forcal_shortcut_save">' . rex_i18n::msg('forcal_shortcut_save') . '</label>
        <div class="checkbox">
            <label>
                <input type="checkbox" id="forcal_shortcut_save" name="forcal_shortcut_save" value="1"' . $checked . '> 
                ' . rex_i18n::msg('forcal_shortcut_save') . '
            </label>
        </div>
    </div>';
    $formElements[] = ['field' => $field];

    // Ganztägige Events vorausgewählt
    $checked = $addon->getConfig('forcal_full_time_preselection') ? ' checked="checked"' : '';
    $field = '<div class="form-group">
        <label class="control-label" for="forcal_full_time_preselection">' . rex_i18n::msg('forcal_full_time_preselection') . '</label>
        <div class="checkbox">
            <label>
                <input type="checkbox" id="forcal_full_time_preselection" name="forcal_full_time_preselection" value="1"' . $checked . '> 
                ' . rex_i18n::msg('forcal_full_time_preselection') . '
            </label>
        </div>
    </div>';
    $formElements[] = ['field' => $field];

    // Farben
    $field = '<div class="form-group">
        <label for="forcal_colors">' . rex_i18n::msg('forcal_colors') . '</label>
        <textarea class="form-control" id="forcal_colors" name="forcal_colors" rows="5">' . $addon->getConfig('forcal_colors') . '</textarea>
    </div>';
    $formElements[] = ['field' => $field];

    // Editor-Auswahl
    if (rex_addon::get('redactor2')->isAvailable()) {
        $checked = $addon->getConfig('forcal_editor') == 3 ? ' checked="checked"' : '';
        $field = '<div class="form-group">
            <label class="control-label" for="forcal_editor">' . rex_i18n::msg('forcal_redactor2') . '</label>
            <div class="checkbox">
                <label>
                    <input type="checkbox" id="forcal_editor" name="forcal_editor" value="3"' . $checked . '> 
                    Redactor 2
                </label>
            </div>
        </div>';
        $formElements[] = ['field' => $field];
    }

    // Definition Fieldcheck
    $checked = $addon->getConfig('forcal_customfield_check') == 1 ? ' checked="checked"' : '';
    $field = '<div class="form-group">
        <label class="control-label" for="forcal_customfield_check">' . rex_i18n::msg('forcal_customfield_check') . '</label>
        <div class="checkbox">
            <label>
                <input type="checkbox" id="forcal_customfield_check" name="forcal_customfield_check" value="1"' . $checked . '> 
                ' . rex_i18n::msg('forcal_customfield_check') . '
            </label>
        </div>
    </div>';
    $formElements[] = ['field' => $field];

    // Datepicker
    $checked = $addon->getConfig('forcal_datepicker') == 1 ? ' checked="checked"' : '';
    $field = '<div class="form-group">
        <label class="control-label" for="forcal_datepicker">' . rex_i18n::msg('forcal_datepicker') . '</label>
        <div class="checkbox">
            <label>
                <input type="checkbox" id="forcal_datepicker" name="forcal_datepicker" value="1"' . $checked . '> 
                ' . rex_i18n::msg('forcal_datepicker') . '
            </label>
        </div>
    </div>';
    $formElements[] = ['field' => $field];

    // Individuelles Attribut für Text
    $field = '<div class="form-group">
        <label for="forcal_additional_attributes_text">' . rex_i18n::msg('forcal_additional_attributes') . ' (Text)</label>
        <textarea class="form-control" id="forcal_additional_attributes_text" name="forcal_additional_attributes_text" rows="3">' . $addon->getConfig('forcal_additional_attributes_text') . '</textarea>
        <p class="help-block">' . rex_i18n::msg('forcal_additional_attributes_notice') . '</p>
    </div>';
    $formElements[] = ['field' => $field];

    // Individuelles Attribut für Teaser
    $field = '<div class="form-group">
        <label for="forcal_additional_attributes_teaser">' . rex_i18n::msg('forcal_additional_attributes') . ' (Teaser)</label>
        <textarea class="form-control" id="forcal_additional_attributes_teaser" name="forcal_additional_attributes_teaser" rows="3">' . $addon->getConfig('forcal_additional_attributes_teaser') . '</textarea>
        <p class="help-block">' . rex_i18n::msg('forcal_additional_attributes_notice') . '</p>
    </div>';
    $formElements[] = ['field' => $field];

    // Zusätzliches Feld für Kategorie
    $select = new rex_select();
    $select->setId('forcal_additional_for_title');
    $select->setAttribute('class', 'form-control');
    $select->setName('forcal_additional_for_title');
    $select->addOption(rex_i18n::msg('forcal_please_select'), '');
    $select->addOption('name', 'name');
    $select->setSelected($addon->getConfig('forcal_additional_for_title', ''));
    
    $field = '<div class="form-group">
        <label for="forcal_additional_for_title">' . rex_i18n::msg('forcal_additional_field_for_category') . '</label>
        ' . $select->get() . '
    </div>';
    $formElements[] = ['field' => $field];

    // Speichern-Button
    $formElements[] = ['field' => '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="save">' . rex_i18n::msg('forcal_config_save') . '</button>'];

    // Formular-Elemente in Fragment einfügen
    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');

    $content .= '</form>';
    $content .= '</div>';

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', $addon->i18n('forcal_config'), false);
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');
} else {
    echo rex_view::error(rex_i18n::msg('forcal_permission_denied'));
}
