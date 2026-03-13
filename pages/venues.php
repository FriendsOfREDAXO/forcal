<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

use forCal\Utils\forCalUserPermission;

$func = rex_request::request('func', 'string');
$id = rex_request::request('id', 'int');
$start = rex_request::request('start', 'int', NULL);

$table = rex::getTablePrefix() . "forcal_venues";
$message = '';

$user = rex::getUser();

if ($func == 'status') {
    if (!forCalUserPermission::hasVenueEditPermission($id)) {
        echo rex_view::error(rex_i18n::msg('forcal_no_permission_for_venue'));
        $func = '';
    } else {
        $message = \forCal\Utils\forCalListHelper::toggleBoolData($table, $id, 'status');
        $func = '';
    }
}

if ($func == 'clone') {
    if (!forCalUserPermission::hasVenueEditPermission($id)) {
        echo rex_view::error(rex_i18n::msg('forcal_no_permission_for_venue'));
        $func = '';
    } else {
        $message = \forCal\Utils\forCalListHelper::cloneData($table, $id);
        $func = '';
    }
}

if ($func == 'delete') {
    if (!forCalUserPermission::canDeleteVenue($id)) {
        echo rex_view::error(rex_i18n::msg('forcal_no_delete_permission_for_venue'));
        $func = '';
    } else {
        $message = \forCal\Utils\forCalListHelper::deleteData($table, $id);
        $func = '';
    }
}

if ($func == '') {

    // create group and select by clang
    $group = array(40);
    $select = array('id');
    foreach (rex_clang::getAll() as $clang) {
        $group[] = '*';
        $select[] = 'name_' . $clang->getId();
    }
    // merge select with default
    $select = array_merge($select, array('status'));

    // Venue-Filter: scope basiert auf Benutzer-Rechten
    $whereClause = forCalUserPermission::getVenueListWhere($user);

    // instance list – createuser + updateuser für Anzeige
    $select[] = 'createuser';
    $select[] = 'updateuser';
    $list = rex_list::factory("SELECT " . implode(', ', $select) . " FROM $table" . $whereClause . " ORDER BY id");
    $list->addTableAttribute('class', 'table-striped');

    // merge group with default
    $group = array_merge($group, array(80,100,90,120));

    $list->addTableColumnGroup($group);

    // Hide columns
    $list->removeColumn('id');

    // Column 1: Action (add/edit button)
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" title="'.rex_i18n::msg('forcal_add_venue').'"><i class="rex-icon rex-icon-add-action"></i></a>';
    $tdIcon = '<i class="rex-icon fa-map-marker"></i>';

    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);

    // Column 2: Name
    foreach(rex_clang::getAll() as $clang) {
        $list->setColumnLabel('name_' . $clang->getId(), rex_i18n::msg('forcal_venue_name') . ' ' . strtoupper($clang->getCode()));
        $list->setColumnParams('name_' . $clang->getId(), ['func' => 'edit', 'id' => '###id###', 'start' => $start]);
    }

    // Column 3: Status
    $list->setColumnLabel('status', rex_i18n::msg('forcal_status_function'));
    $list->setColumnLayout('status', array('<th colspan="4">###VALUE###</th>', '<td>###VALUE###</td>'));
    $list->setColumnParams('status', ['id' => '###id###', 'func' => 'status', 'start' => $start]);
    $list->setColumnFormat('status', 'custom', array('\forCal\Utils\forCalListHelper','formatStatus'));

    // createuser + updateuser anzeigen
    $list->setColumnLabel('createuser', rex_i18n::msg('forcal_venue_owner'));
    $list->setColumnLayout('createuser', ['', '<td class="rex-table-tabular-nums">###VALUE###</td>']);
    $list->setColumnFormat('createuser', 'custom', function ($params) {
        $val = $params['list']->getValue('createuser');
        return '<span class="text-muted"><i class="rex-icon fa-user"></i> ' . rex_escape($val) . '</span>';
    });

    $list->setColumnLabel('updateuser', rex_i18n::msg('forcal_venue_last_editor'));
    $list->setColumnLayout('updateuser', ['', '<td class="rex-table-tabular-nums">###VALUE###</td>']);
    $list->setColumnFormat('updateuser', 'custom', function ($params) {
        $val = $params['list']->getValue('updateuser');
        return $val ? '<span class="text-muted"><i class="rex-icon fa-pencil"></i> ' . rex_escape($val) . '</span>' : '';
    });

    // Column 4: edit
    $list->addColumn('edit', '<i class="rex-icon fa-pencil-square-o"></i> ' . rex_i18n::msg('edit'), -1, ['', '<td>###VALUE###</td>']);
    $list->setColumnParams('edit', ['func' => 'edit', 'id' => '###id###', 'start' => $start]);

    // Column 5: Delete – nur für Owner (createuser = aktueller User) und Admin
    $list->addColumn('delete', '');
    $list->setColumnLayout('delete', array('', '<td>###VALUE###</td>'));
    $list->setColumnParams('delete', ['func' => 'delete', 'id' => '###id###', 'start' => $start]);
    $list->setColumnFormat('delete', 'custom', function ($params) use ($user) {
        $listObj = $params['list'];
        $venueId = (int) $listObj->getValue('id');
        if (!forCalUserPermission::canDeleteVenue($venueId, $user)) {
            return '<span class="text-muted" title="' . rex_escape(rex_i18n::msg('forcal_no_delete_permission_for_venue')) . '"><i class="rex-icon fa-lock"></i></span>';
        }
        return $listObj->getColumnLink($params['params']['name'], "<span class=\"{$params['params']['icon_type']}\"><i class=\"rex-icon {$params['params']['icon']}\"></i> {$params['params']['msg']}</span>");
    }, array('list' => $list, 'name' => 'delete', 'icon' => 'rex-icon-delete', 'icon_type' => 'rex-offline', 'msg' => rex_i18n::msg('delete')));

    $list->addLinkAttribute('delete', 'data-confirm', rex_i18n::msg('delete') . ' ?');

    // Column 6: Clone
    $list->addColumn('clone', '<i class="rex-icon fa-clone"></i> ' . rex_i18n::msg('forcal_clone'), -1, ['', '<td>###VALUE###</td>']);
    $list->setColumnParams('clone', ['func' => 'clone', 'id' => '###id###', 'start' => $start]);
    $list->addLinkAttribute('clone', 'data-confirm', rex_i18n::msg('forcal_clone') . ' ?');

    // show
    $content = $list->get();
    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('forcal_venues_title'));
    $fragment->setVar('content', $message . $content, false);
    echo $fragment->parse('core/page/section.php');

} elseif ($func == 'edit' || $func == 'add') {

    $id = rex_request('id', 'int');

    // Berechtigungsprüfung für bestehende Venue
    if ($func == 'edit' && $id > 0 && !forCalUserPermission::hasVenuePermission($id)) {
        echo rex_view::error(rex_i18n::msg('forcal_no_permission_for_venue'));
        return;
    }

    $form = rex_form::factory($table, '', 'id=' . $id);
    $form->addParam('start', $start);
    if ($func == 'edit') $form->addParam('id', $id);

    // start lang tabs
    \forCal\Utils\forCalFormHelper::addLangTabs($form, 'wrapper', 1);

    foreach (rex_clang::getAll() as $key => $clang) { // open form wrapper
        \forCal\Utils\forCalFormHelper::addLangTabs($form, 'inner_wrapper', $clang->getId(), rex_clang::getCurrentId());

        // Column 1: Name
        $field = $form->addTextField('name_' . $clang->getId());
        $field->setLabel(rex_i18n::msg('forcal_venue_name'));

        if ($key == 0) {
            $field->getValidator()->add('notEmpty', rex_i18n::msg('forcal_venue_name_validation'));
        }

        // add custom lang fields
        if (rex_clang::count() > 1) {
            \forCal\Manager\forCalFormManager::addCustomLangFormField($form, $clang);
        }

        // close form wrapper
        \forCal\Utils\forCalFormHelper::addLangTabs($form, 'close_inner_wrapper');
    }

    // close lang tabs
    \forCal\Utils\forCalFormHelper::addLangTabs($form, 'close_wrapper');

    // add custom fields
    \forCal\Manager\forCalFormManager::addCustomFormField($form, $clang);

    // show
    $content = $form->get();
    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', ($func == 'edit') ? rex_i18n::msg('forcal_venue_edit') : rex_i18n::msg('forcal_venue_add'));
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');
}