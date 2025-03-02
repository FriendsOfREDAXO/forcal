<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

// standard field set
$fields = array(
    array('name' => 'name',
        'label_de' => 'Terminname',
        'label_en' => 'Event Name'),
    array('name' => 'teaser',
        'type' => 'textarea',
        'label_de' => 'Teaser',
        'label_en' => 'Teaser / Abstract'),
    array('name' => 'text',
        'type' => 'textarea',
        'label_de' => 'Beschreibung',
        'label_en' => 'Description'),
);

// init prio object
$eventDefaultStartDate = new DateTime();
$eventDefaultStartDate->setTime(0, 0, 0);
$eventDefaultEndDate = new DateTime();
$eventDefaultEndDate->setTime(0, 0, 0);

// HANDLE REST
$func = rex_request('func', 'string');
$page = rex_request('page', 'string');
$data_id = rex_request('id', 'int');
$itemdate = rex_request('itemdate', 'string');

// create default empty list
$list = \rex_extension::registerPoint(new \rex_extension_point('FORCAL_ENTRY_LIST', rex_list::factory(
    'SELECT  * 
       FROM    ' . rex::getTablePrefix() . 'forcal_entries 
       ORDER BY CONCAT(start_date, " ", start_time) DESC'
)));

// add date format methodes
$list->addFormattingFunction('start_date', array('forCal\Utils\forCalListHelper', 'formatStartDate'));
$list->addFormattingFunction('start_time', array('forCal\Utils\forCalListHelper', 'formatStartTime'));

// set defaults
$list->addTableAttribute('class', 'table-striped table-hover');
$list->setColumnLabel('id', 'ID');
$list->setColumnParams('id', array('func' => 'edit', 'id' => '###id###'));
$list->setColumnLabel('status', rex_i18n::msg('forcal_entry_status'));
$list->setColumnParams('status', array('func' => 'toggle_status', 'id' => '###id###'));
$list->setColumnFormat('status', 'custom', array('forCal\Utils\forCalListHelper', 'formatStatus'));

$list->setColumnLabel('start_date', rex_i18n::msg('forcal_entry_date'));
$list->setColumnLabel('start_time', rex_i18n::msg('forcal_starttime'));

$list->setColumnLabel('name_' . rex_clang::getCurrentId(), rex_i18n::msg('forcal_entry_name'));
$list->setColumnParams('name_' . rex_clang::getCurrentId(), array('func' => 'edit', 'id' => '###id###'));

// add buttons
$thIcon = '<a href="' . rex_url::currentBackendPage(['func' => 'add']) . '"' . rex::getAccesskey(rex_i18n::msg('add_entry'), 'add') . '><i class="rex-icon rex-icon-add-article"></i></a>';
$tdIcon = '<i class="rex-icon fa-file-text-o"></i>';
$list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
$list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);

// add clone button
$list->addColumn('clone', '<i class="rex-icon fa-copy"></i> ' . rex_i18n::msg('forcal_clone'), -1, ['', '<td class="rex-table-action">###VALUE###</td>']);
$list->setColumnParams('clone', ['func' => 'clone', 'id' => '###id###']);

// add delete button
$list->addColumn('delete', '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('forcal_entry_delete'), -1, ['', '<td class="rex-table-action">###VALUE###</td>']);
$list->setColumnParams('delete', ['func' => 'delete', 'id' => '###id###']);
$list->addLinkAttribute('delete', 'data-confirm', rex_i18n::msg('delete') . ' ?');

// function
switch ($func) {
    // toggle status
    case 'toggle_status':
        echo \forCal\Utils\forCalListHelper::toggleBoolData(rex::getTablePrefix() . 'forcal_entries', $data_id, 'status');
        // list needs no reloaded, via ajax only the status field
        break;

    // delete
    case 'delete' :
        echo \forCal\Utils\forCalListHelper::deleteData(rex::getTablePrefix() . 'forcal_entries', $data_id);
        // reload page to update status view
        break;

    case 'clone' :
        echo \forCal\Utils\forCalListHelper::cloneData(rex::getTablePrefix() . 'forcal_entries', $data_id);
        // reload page to update status view
        break;

    // add entry or edit entry
    case 'add' :
    case 'edit' :

        // default object
        $formUid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $form = rex_form::factory(rex::getTablePrefix() . 'forcal_entries', '', 'id=' . $data_id, 'post', false);
        $form->addParam('id', $data_id);
        $form->addParam('func', $func);
        $form->addParam('page', $page);
        $form->setApplyUrl(rex_url::currentBackendPage());
        $form->setEditMode($func == 'edit');

        if ($data_id === 0) {
            $formUid = rex_post('uid', 'string', $formUid);
            $form->addHiddenField('uid', $formUid);
        }

        // add language tabs
        \forCal\Utils\forCalFormHelper::addLangTabs($form, 'wrapper', null, null);

        // add tabs with lang function
        foreach (rex_clang::getAll() as $id => $clang) {
            \forCal\Utils\forCalFormHelper::addLangTabs($form, 'inner_wrapper', $id, $id);

            // add lang fields
            if (rex::getUser()->getComplexPerm('clang')->hasPerm($id)) {
                if (rex::getUser()->getComplexPerm('clang')->hasPerm($id)) {

                    // use default fields
                    foreach ($fields as $field) {
                        if (array_key_exists('type', $field)) {
                            if (rex_addon::get('forcal')->getConfig('forcal_editor') == 3 && $field['type'] == 'textarea') {
                                \forCal\Utils\forCalAttributesHelper::setAdditionalAttributes(
                                    $form->addTextAreaField($field['name'] . '_' . $id, null, [
                                        'class' => 'redactorEditor2-full'
                                    ])
                                )->setLabel(array_key_exists('label_de', $field) ? $field['label_de'] : $field['name']);
                            } else {
                                \forCal\Utils\forCalAttributesHelper::setAdditionalAttributes(
                                    $form->addTextAreaField($field['name'] . '_' . $id)
                                )->setLabel(array_key_exists('label_de', $field) ? $field['label_de'] : $field['name']);
                            }
                        } else {
                            if (rex_addon::get('forcal')->getConfig('forcal_editor') == 3) {
                                \forCal\Utils\forCalAttributesHelper::setAdditionalAttributes(
                                    $form->addTextField($field['name'] . '_' . $id)
                                )->setLabel(array_key_exists('label_de', $field) ? $field['label_de'] : $field['name']);
                            } else {
                                \forCal\Utils\forCalAttributesHelper::setAdditionalAttributes(
                                    $form->addTextField($field['name'] . '_' . $id)
                                )->setLabel(array_key_exists('label_de', $field) ? $field['label_de'] : $field['name']);
                            }
                        }
                    }

                    // add custom lang fields
                    \forCal\Manager\forCalFormManager::addCustomLangFormField($form, $clang);
                }
            }

            \forCal\Utils\forCalFormHelper::addLangTabs($form, 'close_inner_wrapper');
        }
        \forCal\Utils\forCalFormHelper::addLangTabs($form, 'close_wrapper');

        // category field
        $field = $form->addSelectField('category');
        $field->setLabel(rex_i18n::msg('forcal_entry_category'));
        $field->setAttribute('required', true);
        $select = $field->getSelect();
        $select->addOption('- ' . rex_i18n::msg('forcal_please_select') . ' -', '');
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT id, name_' . rex_clang::getCurrentId() . ' as name, color FROM ' . rex::getTable('forcal_categories') . ' WHERE status = 1 ORDER BY name');
        foreach ($sql as $row) {
            $select->addOption('<span style="background-color:' . $row->getValue('color') . '; padding:1px 5px; display:inline-block;"></span>&nbsp;&nbsp;' . $row->getValue('name'), $row->getValue('id'));
        }

        // Prüfen, ob Orte aktiviert sind
        $venuesEnabled = rex_addon::get('forcal')->getConfig('forcal_venues_enabled', true);

        if ($venuesEnabled) {
            // venue field
            $field = $form->addSelectField('venue');
            $field->setLabel(rex_i18n::msg('forcal_entry_venue'));
            $select = $field->getSelect();
            $select->addOption('- ' . rex_i18n::msg('forcal_please_select') . ' -', '');
            $sql = rex_sql::factory();
            $sql->setQuery('SELECT id, name_' . rex_clang::getCurrentId() . ' as name FROM ' . rex::getTable('forcal_venues') . ' WHERE status = 1 ORDER BY name');
            foreach ($sql as $row) {
                $select->addOption($row->getValue('name'), $row->getValue('id'));
            }
        }

        // Date Table
        $field = $form->addRawField('
        <div class="rex-form-group form-group">
            <label class="control-label" for="fordatepicker">' . rex_i18n::msg('forcal_entry_date') . '</label>
            <table class="forcaldatepicker table" data-today="' . date("Y-m-d") . '" data-only-checkin-range="0">
                <thead>
                    <tr>
                        <th class="date-label">' . rex_i18n::msg('forcal_from') . '</th>
                        <td class="date-input">
                            <div class="input-group date">
                                <input id="dpd1" class="form-control" name="start_date" value="' . ($form->isEditMode() ? "" : ($itemdate ? $itemdate : date("Y-m-d"))) . '" type="text">
                                <div class="input-group-addon forcal-date-input"><i class="glyphicon glyphicon-calendar"></i></div>
                            </div>
                        </td>
                        <td class="forcalclock">
                            <div class="input-group clockpicker forcalclock">
                                <input id="tpd1" class="form-control" name="start_time" value="' . ($form->isEditMode() ? "" : "00:00:00") . '" type="text">
                                <div class="input-group-addon"><i class="glyphicon glyphicon-time"></i></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th class="date-label">' . rex_i18n::msg('forcal_to') . '</th>
                        <td class="date-input">
                            <div class="input-group date">
                                <input id="dpd2" class="form-control" name="end_date" value="' . ($form->isEditMode() ? "" : ($itemdate ? $itemdate : date("Y-m-d"))) . '" type="text">
                                <div class="input-group-addon forcal-date-input"><i class="glyphicon glyphicon-calendar"></i></div>
                            </div>
                        </td>
                        <td class="forcalclock">
                            <div class="input-group clockpicker forcalclock">
                                <input id="tpd2" class="form-control" name="end_time" value="' . ($form->isEditMode() ? "" : "00:00:00") . '" type="text">
                                <div class="input-group-addon"><i class="glyphicon glyphicon-time"></i></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th class="date-label">' . rex_i18n::msg('forcal_checkbox_full_time') . '</th>
                        <td>
                            <div class="forcal-check-checkstyle">
                                <input ' . (rex_addon::get('forcal')->getConfig('forcal_full_time_preselection') ? 'checked="checked"' : '') . ' class="forcal_fulltime_master_check" type="checkbox" id="fulltime_switch_add" name="full_time" value="1"> 
                                <label for="fulltime_switch_add">' . rex_i18n::msg('forcal_checkbox_full_time') . '</label>
                            </div>
                        </td>
                    </tr>
                </thead>
            </table>
         </div>');

        // Type Tabs
        $field = $form->addRawField('
        <div class="rex-form-group form-group">
            <label class="control-label">' . rex_i18n::msg('forcal_entry_type') . '</label>
            <div class="forcal-check-radiostyle">
                <div class="btn-group" role="group">
                    <input ' . ($form->isEditMode() && $form->getSql()->getValue('type') == 'repeat' ? 'checked="checked"' : '') . ' id="repeat" class="forcal_repeat_master_radio" name="type" value="repeat" type="radio">
                    <label for="repeat">' . rex_i18n::msg('forcal_radio_repeat') . '</label>
                    <input ' . (($form->isEditMode() && $form->getSql()->getValue('type') == 'one_time') || !$form->isEditMode() ? 'checked="checked"' : '') . ' id="one_time" class="forcal_repeat_master_radio" name="type" value="one_time" type="radio">
                    <label for="one_time">' . rex_i18n::msg('forcal_radio_one_time') . '</label>
                </div>
            </div>
         </div>');

        // Repeats and Picker
        $field = $form->addRawField('
        <div class="panel-group forcal-panel forcal_repeats_show">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><a data-toggle="collapse" href="#collapse1">' . rex_i18n::msg('forcal_entry_repeats') . '</a></h4>
                </div>
                <div id="collapse1" class="panel-collapse collapse' . ($form->isEditMode() && $form->getSql()->getValue('type') == 'repeat' ? ' in' : '') . '">
                    <div class="panel-body">
                        <div class="forcal-repeat-type forcal-form-select-inline">
                            <dl>
                                <dt>' . rex_i18n::msg('forcal_repeat_every') . ':</dt>
                                <dd>
                                    <div class="btn-group bootstrap-select">
                                        <select id="inputNot" class="selectpicker form-control forcal_repeat_select" name="repeat">
                                            <option value="chose">-</option>
                                            <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat') == 'yearly' ? 'selected="selected"' : '') . ' value="yearly">' . rex_i18n::msg('forcal_select_yearly') . '</option>
                                            <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat') == 'monthly' ? 'selected="selected"' : '') . ' value="monthly">' . rex_i18n::msg('forcal_select_monthly') . '</option>
                                            <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat') == 'monthly-week' ? 'selected="selected"' : '') . ' value="monthly-week">' . rex_i18n::msg('forcal_select_monthly_day') . '</option>
                                            <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat') == 'weekly' ? 'selected="selected"' : '') . ' value="weekly">' . rex_i18n::msg('forcal_select_weekly') . '</option>
                                        </select>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                        <div class="forcal_repeat_show ' . ($form->isEditMode() && $form->getSql()->getValue('repeat') == 'yearly' ? 'view-yearly' : ($form->isEditMode() && $form->getSql()->getValue('repeat') == 'monthly-week' ? 'view-monthly-week' : ($form->isEditMode() && $form->getSql()->getValue('repeat') == 'monthly' ? 'view-monthly' : ($form->isEditMode() && $form->getSql()->getValue('repeat') == 'weekly' ? 'view-weekly' : '')))) . '">
                            <div class="forcal-repeat-type forcal-form-input-inline view-yearly forcal_repeat_view_element hidden">
                                <dl>
                                    <dt>' . rex_i18n::msg('forcal_entry_everys') . '</dt>
                                    <dd>
                                        <div class="btn-group bootstrap-select">
                                            <select id="inputNot" class="selectpicker form-control" name="repeat_year">
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_year') == 1 ? 'selected="selected"' : '') . ' value="1">1</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_year') == 2 ? 'selected="selected"' : '') . ' value="2">2</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_year') == 3 ? 'selected="selected"' : '') . ' value="3">3</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_year') == 4 ? 'selected="selected"' : '') . ' value="4">4</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_year') == 5 ? 'selected="selected"' : '') . ' value="5">5</option>
                                            </select>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                            <div class="forcal-form-input-inline">
                                <dl>
                                    <dt class="view-yearly forcal_repeat_view_element hidden">' . rex_i18n::msg('forcal_repeat_every_yearly') . '</dt>
                                    <dt class="view-monthly view-monthly-week forcal_repeat_view_element hidden">' . rex_i18n::msg('forcal_repeat_every_monthly') . '</dt>
                                    <dt class="view-weekly forcal_repeat_view_element hidden">' . rex_i18n::msg('forcal_repeat_every_weekly') . '</dt>
                                </dl>
                            </div>
                            <div class="forcal-repeat-type forcal-form-input-inline view-monthly-week forcal_repeat_view_element hidden">
                                <dl>
                                    <dt>' . rex_i18n::msg('forcal_entry_everye') . '</dt>
                                    <dd>
                                        <div class="btn-group bootstrap-select">
                                            <select id="inputNot" class="selectpicker form-control" name="repeat_month_week">
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month_week') == 'first' ? 'selected="selected"' : '') . ' value="first">' . rex_i18n::msg('forcal_select_first_week') . '</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month_week') == 'second' ? 'selected="selected"' : '') . ' value="second">' . rex_i18n::msg('forcal_select_second_week') . '</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month_week') == 'third' ? 'selected="selected"' : '') . ' value="third">' . rex_i18n::msg('forcal_select_third_week') . '</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month_week') == 'fourth' ? 'selected="selected"' : '') . ' value="fourth">' . rex_i18n::msg('forcal_select_fourth_week') . '</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month_week') == 'last' ? 'selected="selected"' : '') . ' value="last">' . rex_i18n::msg('forcal_select_last_week') . '</option>
                                            </select>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                            <div class="forcal-repeat-type forcal-form-input-inline view-monthly-week forcal_repeat_view_element hidden">
                                <dl>
                                    <dd>
                                        <div class="btn-group bootstrap-select">
                                            <select id="inputNot" class="selectpicker form-control" name="repeat_day">
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_day') == 'mon' ? 'selected="selected"' : '') . ' value="mon">' . rex_i18n::msg('forcal_select_mon') . '</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_day') == 'tue' ? 'selected="selected"' : '') . ' value="tue">' . rex_i18n::msg('forcal_select_tue') . '</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_day') == 'wed' ? 'selected="selected"' : '') . ' value="wed">' . rex_i18n::msg('forcal_select_wed') . '</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_day') == 'thu' ? 'selected="selected"' : '') . ' value="thu">' . rex_i18n::msg('forcal_select_thu') . '</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_day') == 'fri' ? 'selected="selected"' : '') . ' value="fri">' . rex_i18n::msg('forcal_select_fri') . '</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_day') == 'sat' ? 'selected="selected"' : '') . ' value="sat">' . rex_i18n::msg('forcal_select_sat') . '</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_day') == 'sun' ? 'selected="selected"' : '') . ' value="sun">' . rex_i18n::msg('forcal_select_sun') . '</option>
                                            </select>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                            <div class="forcal-repeat-type forcal-form-input-inline view-monthly forcal_repeat_view_element hidden">
                                <dl>
                                    <dt>' . rex_i18n::msg('forcal_entry_everyn') . '</dt>
                                    <dd>
                                        <div class="btn-group bootstrap-select">
                                            <select id="inputNot" class="selectpicker form-control" name="repeat_month">
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 1 ? 'selected="selected"' : '') . ' value="1">1</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 2 ? 'selected="selected"' : '') . ' value="2">2</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 3 ? 'selected="selected"' : '') . ' value="3">3</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 4 ? 'selected="selected"' : '') . ' value="4">4</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 5 ? 'selected="selected"' : '') . ' value="5">5</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 6 ? 'selected="selected"' : '') . ' value="6">6</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 7 ? 'selected="selected"' : '') . ' value="7">7</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 8 ? 'selected="selected"' : '') . ' value="8">8</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 9 ? 'selected="selected"' : '') . ' value="9">9</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 10 ? 'selected="selected"' : '') . ' value="10">10</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 11 ? 'selected="selected"' : '') . ' value="11">11</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_month') == 12 ? 'selected="selected"' : '') . ' value="12">12</option>
                                            </select>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                            <div class="forcal-repeat-type forcal-form-input-inline view-weekly forcal_repeat_view_element hidden">
                                <dl>
                                    <dt>' . rex_i18n::msg('forcal_entry_everye') . '</dt>
                                    <dd>
                                        <div class="btn-group bootstrap-select">
                                            <select id="inputNot" class="selectpicker form-control" name="repeat_week">
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_week') == 1 ? 'selected="selected"' : '') . ' value="1">1</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_week') == 2 ? 'selected="selected"' : '') . ' value="2">2</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_week') == 3 ? 'selected="selected"' : '') . ' value="3">3</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_week') == 4 ? 'selected="selected"' : '') . ' value="4">4</option>
                                                <option ' . ($form->isEditMode() && $form->getSql()->getValue('repeat_week') == 5 ? 'selected="selected"' : '') . ' value="5">5</option>
                                            </select>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                            <div class="forcal-repeat-ending rex-form-group form-group">
                                <label for="dpd2b">' . rex_i18n::msg('forcal_repeat_ending') . ':</label>
                                <div id="dpd2b_wrapper">
                                    <div class="input-group date"><input id="dpd2b" class="form-control end_repeat_date" name="end_repeat_date" value="' . ($form->isEditMode() ? "" : "") . '" type="text">
                                        <div class="input-group-addon forcal-date-input"><i class="glyphicon glyphicon-calendar"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>');

        // Status
        $field = $form->addCheckboxField('status');
        $field->setLabel(rex_i18n::msg('forcal_entry_status'));
        $field->addOption(rex_i18n::msg('forcal_online'), '1');
        if (!$field->getValue()) {
            $field->setValue('1');
        }

        // Validation
        $field = $form->addRawField('<script type="text/javascript" defer>
        $(document).ready(function() {
            $(\'form[action*="func=add"],form[action*="func=edit"]\').on("submit", function() {
                // Name validation
                var nameField = $(\'input[name="name_' . rex_clang::getCurrentId() . '"]\');
                
                if (nameField.length > 0 && nameField.val() === "") {
                    alert("' . rex_i18n::msg('forcal_entry_name_validation') . '");
                    nameField.focus();
                    return false;
                }
                
                // Category validation
                var categoryField = $(\'select[name="category"]\');
                
                if (categoryField.length > 0 && categoryField.val() === "") {
                    alert("' . rex_i18n::msg('forcal_category_select') . '");
                    categoryField.focus();
                    return false;
                }
                
                return true;
            });
        });
        </script>');

        // add custom fields
        \forCal\Manager\forCalFormManager::addCustomFormField($form, rex_clang::getCurrent());

        $content = $form->get();

        $fragment = new rex_fragment();
        $fragment->setVar('class', 'edit', false);
        $fragment->setVar('title', $func == 'edit' ? rex_i18n::msg('forcal_entry_edit') : rex_i18n::msg('forcal_entry_add'), false);
        $fragment->setVar('body', $content, false);
        echo $fragment->parse('core/page/section.php');

        break;

    default:
        $content = $list->get();

        $fragment = new rex_fragment();
        $fragment->setVar('title', rex_i18n::msg('forcal_entry_list_view'), false);
        $fragment->setVar('content', $content, false);
        echo $fragment->parse('core/page/section.php');
        break;
}

// Formular-Validierung für Terminname hinzufügen
if ($func == 'add' || $func == 'edit') {
    ?>
    <script type="text/javascript">
        $(document).ready(function() {
            var nameField = $('input[name="name_<?= rex_clang::getCurrentId() ?>"]');
            nameField.attr('required', 'required');
        });
    </script>
    <?php
}
