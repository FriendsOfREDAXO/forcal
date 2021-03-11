<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

// colors : http://mdbootstrap.com/css/colors/

$config = rex_post('config', array(
    array('forcal_colors', 'string'),
    array('forcal_datepicker', 'int'),
    array('forcal_customfield_check', 'int'),
    array('forcal_additional_attributes_text', 'string'),
    array('forcal_additional_attributes_teaser', 'string'),
    array('forcal_additional_for_title', 'string'),
    array('forcal_full_time_preselection', 'int'),
    array('forcal_shortcut_save', 'int'),
    array('forcal_start_page', 'string'),
    array('submit', 'boolean')
));

// init form
$form = '';

// if submit set config
if ($config['submit']) {
    $requiresRedirect = false;

    if($this->getConfig('forcal_start_page') !== htmlspecialchars($config['forcal_start_page'])) {
        $requiresRedirect = true;
    }

    // show is saved field
    $this->setConfig('forcal_colors', $config['forcal_colors']);
    $this->setConfig('forcal_datepicker', $config['forcal_datepicker']);
    $this->setConfig('forcal_customfield_check', $config['forcal_customfield_check']);
    $this->setConfig('forcal_additional_attributes_text', htmlspecialchars($config['forcal_additional_attributes_text']));
    $this->setConfig('forcal_additional_attributes_teaser', htmlspecialchars($config['forcal_additional_attributes_teaser']));
    $this->setConfig('forcal_additional_for_title', htmlspecialchars($config['forcal_additional_for_title']));
    $this->setConfig('forcal_full_time_preselection', $config['forcal_full_time_preselection']);
    $this->setConfig('forcal_shortcut_save', $config['forcal_shortcut_save']);
    $this->setConfig('forcal_start_page', htmlspecialchars($config['forcal_start_page']));

    // add ever all editor sets
//    \forCal\Utils\forCalEditorHelper::addEditorSets();

    if($requiresRedirect) {
        // redirect to trigger reboot - update tab order
        rex_response::sendRedirect(rex_context::fromGet()->getUrl([], false));
    }

    $form .= rex_view::info(rex_i18n::msg('forcal_config_saved'));
}

// open form
$form .= '
    <form action="' . rex_url::currentBackendPage() . '" method="post">
        <fieldset>
';

// set arrays
$formElements = array();
$elements = array();
$elements['label'] = '<label for="forcal-color-config">' . rex_i18n::msg('forcal_colors') . '</label>';
$elements['field'] = '<textarea id="forcal-color-config" class="form-control" rows="3" name="config[forcal_colors]">' . $this->getConfig('forcal_colors') . '</textarea>';
$formElements[] = $elements;
// parse select element
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/form.php');


// TODO more options
// label
//$formElements = array();
//$elements = array();
//$elements['label'] = '
//  <label for="rex-mblock-config-template">' . rex_i18n::msg('forcal_editor') . '</label>
//';
//// create select
//$select = new rex_select;
//$select->setId('rex-forcal_editor');
//$select->setSize(1);
//$select->setAttribute('class', 'form-control');
//$select->setName('config[forcal_editor]');
//// add options
//$select->addOption(rex_i18n::msg('forcal_redactor2'), 3);
////$select->addOption(rex_i18n::msg('forcal_markitup_textile'), 2);
////$select->addOption(rex_i18n::msg('forcal_markitup_markdown'), 1);
////$select->addOption(rex_i18n::msg('forcal_none_editor'), 0);
//$select->setSelected($this->getConfig('forcal_editor'));
//$elements['field'] = $select->get();
//$formElements[] = $elements;
//// parse select element
//$fragment = new rex_fragment();
//$fragment->setVar('elements', $formElements, false);
//$form .= $fragment->parse('core/form/form.php');

// teaser
$formElements = array();
$elements = array();
$elements['label'] = '
  <label for="rex-mblock-config-template">' . rex_i18n::msg('forcal_additional_attributes') . '<br>'. rex_i18n::msg('forcal_entry_teaser') .'</label>
';
// create input & notice
$elements['field'] = '<input id="forcal-additional-attributes" class="form-control" name="config[forcal_additional_attributes_teaser]" value="'. $this->getConfig('forcal_additional_attributes_teaser') .'" /><p class="help-block">' . rex_i18n::msg('forcal_additional_attributes_notice') . '</p>';
$formElements[] = $elements;
// parse select element
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/form.php');


// text
$formElements = array();
$elements = array();
$elements['label'] = '
  <label for="rex-mblock-config-template">' . rex_i18n::msg('forcal_additional_attributes') . '<br>'. rex_i18n::msg('forcal_entry_text') .'</label>
';
// create input & notice
$elements['field'] = '<input id="forcal-additional-attributes" class="form-control" name="config[forcal_additional_attributes_text]" value="'. $this->getConfig('forcal_additional_attributes_text') .'" /><p class="help-block">' . rex_i18n::msg('forcal_additional_attributes_notice') . '</p>';
$formElements[] = $elements;
// parse select element
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/form.php');



$formElements = array();
$elements = array();


$fields = rex_sql::factory()
//        ->setDebug()
        ->setQuery('SELECT * FROM ' . rex::getTable('forcal_categories') . ' LIMIT 1')
        ->getFieldnames();

$elements = [];
$elements['label'] = '<label for="rex-mblock-config-template">' . rex_i18n::msg('forcal_additional_field_for_category') . '</label>';
$elements['field'] = '<select id="forcal-additional-title" class="form-control" name="config[forcal_additional_for_title]">';
$elements['field'] .= '<option value="">'. rex_i18n::msg('forcal_please_select') . '</option>';
foreach ($fields as $field) {
    if (substr($field,-2) == '_1') {
        $field = substr($field,0,-2);
    } else {
        continue;
    }
    $elements['field'] .= '<option value="'.$field.'" '. ($this->getConfig('forcal_additional_for_title') == $field ? ' selected="selected" ' : '') . '>'.$field.'</option>';
}

$elements['field'] .= '</select><p class="help-block">' . rex_i18n::msg('forcal_only_multilanguage_fields_shown') . '</p>';
$formElements[] = $elements;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/form.php');

$pages = ['calendar' => rex_i18n::msg('forcal_calendar'), 'entries' => rex_i18n::msg('forcal_entries')];
$formElements = array();
$elements = array();
$elements['label'] = '<label for="forcal_start_page">' . rex_i18n::msg('forcal_start_page') . '</label>';
$elements['field'] = '<select id="forcal_start_page" class="form-control" name="config[forcal_start_page]">';
foreach ($pages as $page => $pageName) {
    $elements['field'] .= '<option value="'.$page.'" '. ($this->getConfig('forcal_start_page') == $page ? ' selected="selected" ' : '') . '>'.$pageName.'</option>';
}

$elements['field'] .= '</select>';
$formElements[] = $elements;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/form.php');

// text
$formElements = array();
$elements = array();
$elements['label'] = '
  <label for="forcal-full-time-preselection">' . rex_i18n::msg('forcal_full_time_preselection') .'</label>
';
// create input & notice
$elements['field'] = '<input type="checkbox" id="forcal-full-time-preselection" name="config[forcal_full_time_preselection]"'.($this->getConfig('forcal_full_time_preselection') ? ' checked="checked"' : '').' value="1" />';
$formElements[] = $elements;
// parse select element
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/form.php');

// text
$formElements = array();
$elements = array();
$elements['label'] = '
  <label for="forcal-shortcut-save">' . rex_i18n::msg('forcal_shortcut_save') .'</label>
';
// create input & notice
$elements['field'] = '<input type="checkbox" id="forcal-shortcut-save" name="config[forcal_shortcut_save]"'.($this->getConfig('forcal_shortcut_save') ? ' checked="checked"' : '').' value="1" />';
$formElements[] = $elements;
// parse select element
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/form.php');

$form .= '
        </fieldset>
        <fieldset class="rex-form-action">
';

// create submit button
$formElements = array();
$elements = array();
$elements['field'] = '
  <input type="submit" class="btn btn-save rex-form-aligned" name="config[submit]" value="' . rex_i18n::msg('forcal_config_save') . '" ' . rex::getAccesskey(rex_i18n::msg('forcal_config_save'), 'save') . ' />
';
$formElements[] = $elements;

// parse submit element
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/submit.php');

// close form
$form .= '
    </fieldset>
  </form>
';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit');
$fragment->setVar('title', rex_i18n::msg('forcal_config'));
$fragment->setVar('body', $form, false);
echo $fragment->parse('core/page/section.php');
