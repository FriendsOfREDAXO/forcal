<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

// thanks @OliverKreischer for the cool color idea !

$func = rex_request::request('func', 'string');
$id = rex_request::request('id', 'int');
$start = rex_request::request('start', 'int', NULL);

$table = rex::getTablePrefix() . "forcal_categories";
$message = '';
$additional_for_title = self::getConfig('forcal_additional_for_title');

if ($func == 'status') {
    $message = \forCal\Utils\forCalListHelper::toggleBoolData($table, $id, 'status');
    $func = '';
}

if ($func == 'clone') {
    $message = \forCal\Utils\forCalListHelper::cloneData($table, $id);
    $func = '';
}

if ($func == 'delete') {
    $message = \forCal\Utils\forCalListHelper::deleteData($table, $id);
    $func = '';
}

if ($func == '') {

    // create group and select by clang
    $group = array(40);
    $select = array('id');
    foreach (rex_clang::getAll() as $clang) {
        $group[] = '*';
        if ($additional_for_title) {
            $select[] = 'CONCAT(name_'.$clang->getId().'," - ",'.$additional_for_title.'_'.$clang->getId().') name_'.$clang->getId();
        } else {
            $select[] = 'name_' . $clang->getId();
        }
    }
    // merge select with default
    $select = array_merge($select, array('color, status'));

    // instance list
    $list = rex_list::factory("SELECT " . implode(', ', $select) . " FROM $table ORDER BY id");
    $list->addTableAttribute('class', 'table-striped');

    // merge group with default
    $group = array_merge($group, array(180, 80,100,90,120));

    $list->addTableColumnGroup($group);

    // Hide columns
    $list->removeColumn('id');
    $list->setColumnSortable('color');

    // Column 1: Action (add/edit button)
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" title="'.rex_i18n::msg('forcal_add_category').'"><i class="rex-icon rex-icon-add-action"></i></a>';
    $tdIcon = '<i class="rex-icon fa-folder-open"></i>';

    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);

    // Column 2: Name
    foreach(rex_clang::getAll() as $clang) {
        $list->setColumnLabel('name_' . $clang->getId(), rex_i18n::msg('forcal_category_name') . ' ' . strtoupper($clang->getCode()));
        $list->setColumnParams('name_' . $clang->getId(), ['func' => 'edit', 'id' => '###id###', 'start' => $start]);
    }

    // Column 3: Color
    $list->setColumnLabel('color', rex_i18n::msg('forcal_category_color'));
    $list->setColumnLayout('color', ['<th>###VALUE###</th>', '<td data-title="'.rex_i18n::msg('forcal_category_color').'" class="td_aufgaben"><span style="border-bottom: 5px solid ###VALUE###;">###VALUE###</span></td>']);

    // Column 4: Status
    $list->setColumnLabel('status', rex_i18n::msg('forcal_status_function'));
    $list->setColumnLayout('status', array('<th colspan="4">###VALUE###</th>', '<td>###VALUE###</td>'));
    $list->setColumnParams('status', ['id' => '###id###', 'func' => 'status', 'start' => $start]);
    $list->setColumnFormat('status', 'custom', array('\forCal\Utils\forCalListHelper','formatStatus'));

    // Column 5: edit
    $list->addColumn('edit', '<i class="rex-icon fa-pencil-square-o"></i> ' . rex_i18n::msg('edit'), -1, ['', '<td>###VALUE###</td>']);
    $list->setColumnParams('edit', ['func' => 'edit', 'id' => '###id###', 'start' => $start]);

    // Column 6: Delete
    $list->addColumn('delete', '');
    $list->setColumnLayout('delete', array('', '<td>###VALUE###</td>'));
    $list->setColumnParams('delete', ['func' => 'delete', 'id' => '###id###', 'start' => $start]);
    $list->setColumnFormat('delete', 'custom', function ($params) {
        $list = $params['list'];
        return $list->getColumnLink($params['params']['name'], "<span class=\"{$params['params']['icon_type']}\"><i class=\"rex-icon {$params['params']['icon']}\"></i> {$params['params']['msg']}</span>");

    }, array('list'=> $list, 'name' => 'delete', 'icon' => 'rex-icon-delete', 'icon_type' => 'rex-offline', 'msg' => rex_i18n::msg('delete')));

    $list->addLinkAttribute('delete', 'data-confirm', rex_i18n::msg('delete') . ' ?');

    // Column 7: Clone
    $list->addColumn('clone', '<i class="rex-icon fa-clone"></i> ' . rex_i18n::msg('forcal_clone'), -1, ['', '<td>###VALUE###</td>']);
    $list->setColumnParams('clone', ['func' => 'clone', 'id' => '###id###', 'start' => $start]);
    $list->addLinkAttribute('clone', 'data-confirm', rex_i18n::msg('forcal_clone') . ' ?');

    // show
    $content = $list->get();
    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('forcal_categories_title'));
    $fragment->setVar('content', $message . $content, false);
    echo $fragment->parse('core/page/section.php');

} elseif ($func == 'edit' || $func == 'add') {

    $id = rex_request('id', 'int');
    $form = rex_form::factory($table, '', 'id=' . $id);
    $form->addParam('start', $start);
    if ($func == 'edit') $form->addParam('id', $id);

    // start lang tabs
    \forCal\Utils\forCalFormHelper::addLangTabs($form, 'wrapper', 1);

    foreach (rex_clang::getAll() as $key => $clang) { // open form wrapper
        \forCal\Utils\forCalFormHelper::addLangTabs($form, 'inner_wrapper', $clang->getId(), rex_clang::getCurrentId());

        // Column 1: Name
        $field = $form->addTextField('name_' . $clang->getId());
        $field->setLabel(rex_i18n::msg('forcal_category_name'));

        // add custom lang fields
        if (rex_clang::count() > 1) {
            \forCal\manager\forCalFormManager::addCustomLangFormField($form, $clang);
        }

        // close form wrapper
        \forCal\Utils\forCalFormHelper::addLangTabs($form, 'close_inner_wrapper');
    }

    // close lang tabs
    \forCal\Utils\forCalFormHelper::addLangTabs($form, 'close_wrapper');

    // add custom fields
    \forCal\Manager\forCalFormManager::addCustomFormField($form, $clang);

    // Column 2: Color
    $field = $form->addTextField('color');
    $field->setLabel(rex_i18n::msg('forcal_category_color'));
    $field->getValidator()->add('notEmpty', rex_i18n::msg('forcal_category_color_not_empty'));

    preg_match_all("((#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3}))|(rgba|rgb)\(\s*(?:(\d{1,3})\s*,?){3}(1|0?\.\d+)\))",str_replace(' ', '', $this->getConfig('forcal_colors')), $matches);
    $colors = array();

    if (is_array($matches) && sizeof($matches) > 0 && sizeof($matches[0]) > 0) {
        $colors = $matches[0];
    }

    $field->setAttribute('data-palette', '["'.implode('","', $colors).'"]');
    $field->setAttribute('class', 'forcal_colorpalette form-control');

    $field->setPrefix('<div class="input-group forcal-group">');
    $field->setSuffix('</div>');

    // show
    $content = $form->get();
    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', ($func == 'edit') ? rex_i18n::msg('forcal_category_edit') : rex_i18n::msg('forcal_category_add'));
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');

}
