<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

$func = rex_request::request('func', 'string');
$itemDate = rex_request::request('itemdate', 'string', null);
$id = rex_request::request('id', 'int');
$start = rex_request::request('start', 'int', NULL);
$categoryFilter = rex_request::request('category_filter', 'int', NULL);

$tableEvent = rex::getTablePrefix() . "forcal_entries";
$tableCategories = rex::getTablePrefix() . "forcal_categories";
$tableVenues = rex::getTablePrefix() . "forcal_venues";
$message = '';

$additional_for_title = self::getConfig('forcal_additional_for_title');

if ($func == 'status') {
    $message = \forCal\Utils\forCalListHelper::toggleBoolData($tableEvent, $id, 'status');
    $func = '';
}

if ($func == 'clone') {
    $message = \forCal\Utils\forCalListHelper::cloneData($tableEvent, $id);
    $func = '';
}

if ($func == 'delete') {
    $message = \forCal\Utils\forCalListHelper::deleteData($tableEvent, $id);
    $func = '';
}

if ($func == '' || $func == 'filter') {

    $select = array('en.id');
    $group = array(40);

    foreach (rex_clang::getAll() as $clang) {
        if ($additional_for_title) {
            $select[] = 'CONCAT(en.name_'.$clang->getId().'," - ",ca.'.$additional_for_title.'_'.$clang->getId().') name_'.$clang->getId();
        } else {
            $select[] = 'en.name_' . $clang->getId();
        }
        $group[] = '*';
    }


    // merge select with default
    $select = array_merge($select, array('en.type', 'en.repeat', 'en.repeat_year', 'en.repeat_week', 'en.repeat_month', 'en.start_date', 'en.start_time', 'en.end_date', 'en.end_time', 'ca.name_' . rex_clang::getCurrentId() . ' AS category', 'ca.color', 'en.status', 'ca.id AS category_id'));

    // where statements
    $where = array();
    if (!is_null($categoryFilter)) {
        $where[] = 'en.category = ' . $categoryFilter;
    }
    if (count($where)>0) {
        $where = 'WHERE ' . implode(' ', $where);
    } else {
        $where = '';
    }

    // init list
    $list = rex_list::factory('SELECT ' . implode(', ',$select) . '
            FROM ' . $tableEvent . ' AS en
            LEFT JOIN ' . $tableCategories . ' AS ca ON en.category = ca.id
            '.$where.'
            ORDER BY en.start_date, en.start_time DESC', 30, null, false);
    $list->addTableAttribute('class', 'table-striped');

    // merge group with default
    $group = array_merge($group, array('*','*','*',80,100,90,120));

    $list->addTableColumnGroup($group);

    // Hide columns
    $list->removeColumn('id');
    $list->removeColumn('color');
    $list->removeColumn('category_id');
    $list->removeColumn('end_date');
    $list->removeColumn('end_time');
    $list->removeColumn('type');
    $list->removeColumn('repeat');
    $list->removeColumn('repeat_year');
    $list->removeColumn('repeat_week');
    $list->removeColumn('repeat_month');

    $list->setColumnSortable('start_date');
    $list->setColumnSortable('category');
//    $list->setColumnSortable('venue');

    // Column 1: Action (add/edit button)
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add', 'itemdate' => date('Y-m-d')]) . '" title="' . rex_i18n::msg('forcal_add_entry') . '" accesskey="a"><i class="rex-icon rex-icon-add-action"></i></a>';
    $tdIcon = '<i class="rex-icon fa-file-o"></i>';

    // thanks to Oliver Kreischer for the cool color idea !
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon forcal-fa-###type###" style="margin-left:5px;border-left:5px solid ###color###">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);

    // Column 2: Name
    foreach(rex_clang::getAll() as $clang) {
        $list->setColumnLabel('name_' . $clang->getId(), rex_i18n::msg('forcal_entry_name') . ' ' . strtoupper($clang->getCode()));
        $list->setColumnParams('name_' . $clang->getId(), ['func' => 'edit', 'id' => '###id###', 'start' => $start]);
    }

    // Column 3: StartDate
    $list->setColumnLabel('start_date', rex_i18n::msg('forcal_startdate'));
    $list->setColumnFormat('start_date', 'custom', array('\forCal\Utils\forCalListHelper','formatStartDate'));

    // Column 4: StartTime
    $list->setColumnLabel('start_time', rex_i18n::msg('forcal_starttime'));
    $list->setColumnFormat('start_time', 'custom', array('\forCal\Utils\forCalListHelper','formatStartTime'));

    // Column 5: Category
    $list->setColumnLabel('category', rex_i18n::msg('forcal_category'));
    $list->setColumnParams('category', ['category_filter' => '###category_id###']);
    $list->setColumnFormat('category', 'custom', array('\forCal\Utils\forCalListHelper','formatCategory'));

    // Column 6: Status
    $list->setColumnLabel('status', rex_i18n::msg('forcal_status_function'));
    $list->setColumnLayout('status', array('<th colspan="4">###VALUE###</th>', '<td>###VALUE###</td>'));
    $list->setColumnParams('status', ['id' => '###id###', 'func' => 'status', 'start' => $start]);
    $list->setColumnFormat('status', 'custom', array('\forCal\Utils\forCalListHelper','formatStatus'));

    // Column 7: edit
    $list->addColumn('edit', '<i class="rex-icon fa-pencil-square-o"></i> ' . rex_i18n::msg('edit'), -1, ['', '<td>###VALUE###</td>']);
    $list->setColumnParams('edit', ['func' => 'edit', 'id' => '###id###', 'start' => $start]);

    // Column 8: Delete
    $list->addColumn('delete', '');
    $list->setColumnLayout('delete', array('', '<td>###VALUE###</td>'));
    $list->setColumnParams('delete', ['func' => 'delete', 'id' => '###id###', 'start' => $start]);
    $list->setColumnFormat('delete', 'custom', function ($params) {
        $list = $params['list'];
        return $list->getColumnLink($params['params']['name'], "<span class=\"{$params['params']['icon_type']}\"><i class=\"rex-icon {$params['params']['icon']}\"></i> {$params['params']['msg']}</span>");

    }, array('list'=> $list, 'name' => 'delete', 'icon' => 'rex-icon-delete', 'icon_type' => 'rex-offline', 'msg' => rex_i18n::msg('delete')));

    $list->addLinkAttribute('delete', 'data-confirm', rex_i18n::msg('delete') . ' ?');

    // Column 9: Clone
    $list->addColumn('clone', '<i class="rex-icon fa-clone"></i> ' . rex_i18n::msg('forcal_clone'), -1, ['', '<td>###VALUE###</td>']);
    $list->setColumnParams('clone', ['func' => 'clone', 'id' => '###id###', 'start' => $start]);
    $list->addLinkAttribute('clone', 'data-confirm', rex_i18n::msg('forcal_clone') . ' ?');

    // show
    $content = $list->get();
    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('forcal_entry_list_view'));
    $fragment->setVar('content', $message . $content, false);
    echo $fragment->parse('core/page/section.php');

} elseif ($func == 'edit' || $func == 'add') {

    if(rex_request('entry_form_load') == true) {
        ob_end_clean();
    }

    $id = rex_request('id', 'int');
    $form = rex_form::factory($tableEvent, '', 'id=' . $id, 'post', 0);
    $form->addParam('start', $start);

    // Column: uid
    $field = $form->addHiddenField('uid');
    if ($func == 'add' && !is_null($itemDate)) {
        $field->setValue(uniqid());
    }

    // Column: start
    $field = $form->addHiddenField('start_date');
    $field->setAttribute('id', 'dpd1');
    $field->setAttribute('required', 'required');
    $startDate = $field->getValue();
    if ($func == 'add' && !is_null($itemDate)) {
        $field->setValue($itemDate);
        $startDate = $itemDate;
    }

    $field = $form->addHiddenField('start_time');
    $field->setAttribute('id', 'tpd1');
    $startTime = $field->getValue();
    if ($func == 'add' && !is_null($itemDate)) {
        $field->setValue("00:00:00");
        $startTime = "00:00:00";
    }

    // Column: End
    $field = $form->addHiddenField('end_date');
    $field->setAttribute('id', 'dpd2');
    $field->setAttribute('required', 'required');
    $endDate = $field->getValue();
    if ($func == 'add' && !is_null($itemDate)) {
        $field->setValue($itemDate);
        $endDate = $itemDate;
    }

    $field = $form->addHiddenField('end_time');
    $field->setAttribute('id', 'tpd2');
    $endTime = $field->getValue();
    if ($func == 'add' && !is_null($itemDate)) {
        $field->setValue("00:00:00");
        $endTime = "00:00:00";
    }

    // Column: End
    $field = $form->addHiddenField('end_repeat_date');
    $field->setAttribute('id', 'dpd2b');
    $endDate = $field->getValue();
    if ($func == 'add' && !is_null($itemDate)) {
        $field->setValue($itemDate);
        $endDate = $itemDate;
    }

    $today = new DateTime();

    if ($func == 'edit') {
        $form->addParam('id', $id);
        $startDateTime = new DateTime($startDate . ' ' . $startTime);
        $endDateTime = new DateTime($endDate . ' ' . $endTime);
        $startDate = $startDateTime->format("Y-m-d");
        $startTime = $startDateTime->format("H:i");
        $endDate = $endDateTime->format("Y-m-d");
        $endTime = $endDateTime->format("H:i");
    }

    switch ($this->getConfig('forcal_datepicker')) {
        case 0:
        case 1:
            break;
        case 2:
            break;
    }


    $form->addRawField('<div class="forcal-first-group"><dl class="rex-form-group form-group">
        <dt><label class="control-label">'.rex_i18n::msg('forcal_entry_full_time').'</label></dt>
        <dd><div class="forcal-form-checkboxes-inline forcal-check-checkstyle">');

    $field = $form->addCheckboxField('full_time');
    $field->addOption(rex_i18n::msg('forcal_checkbox_full_time'), 1);
    $field->setAttribute('class', 'check-btn forcal_fulltime_master_check');
    if($this->getConfig('forcal_full_time_preselection') && $func=='add') {
        $field->setAttribute('checked', 'checked');
    }

    $form->addRawField('</div></dd></dl>
    <dl class="rex-form-group form-group form-group-np">
    <dt><label class="control-label">'.rex_i18n::msg('forcal_entry_date').'</label></dt>
    <dd>

    <div class="row"><div class="col-md-6">

      <table class="table forcaldatepicker lang_'.strtolower(rex_clang::getCurrent()->getCode()).'" data-today="'.$today->format("Y-m-d").'" data-only-checkin-range="'.$this->getConfig('forcal_datepicker').'">
        <thead>
          <tr>
            <td class="date-label"><label>'.rex_i18n::msg('forcal_from').'</label></td>
          </tr>
          <tr>
            <td class="date-input forcaldate">
              <div class="input-group forcal-group" id="dpd1_wrapper">
                <div class="input-group-addon forcal-date-input"><i class="rex-icon fa-calendar"></i></div>
			  </div>
            </td>
            <td class="date-input forcalclock">
              <div class="input-group forcal-group clockpicker" id="tpd1_wrapper">
                <div class="input-group-addon forcal-time-input"><i class="glyphicon glyphicon-time"></i></div>
			  </div>
            </td>
          </tr>
        </thead>
      </table>

    </div><div class="col-md-6">

      <table class="table forcaldatepicker">
        <thead>
          <tr>
            <td class="date-label"><label>'.rex_i18n::msg('forcal_to').'</label></td>
          </tr>
          <tr>
            <td class="date-input forcaldate">
              <div class="input-group forcal-group" id="dpd2_wrapper">
                <div class="input-group-addon forcal-date-input"><i class="rex-icon fa-calendar"></i></div>
			  </div>
            </td>
            <td class="date-input forcalclock">
              <div class="input-group forcal-group clockpicker" id="tpd2_wrapper">
                <div class="input-group-addon forcal-time-input"><i class="glyphicon glyphicon-time"></i></div>
			  </div>
            </td>
          </tr>
        </thead>
      </table>

    </div></div>

    </dd></dl>
    </div>
    ');



    ## MASTER SELECT
    ## REPEAT or ONE TIME
    $form->addRawField('<dl class="rex-form-group form-group forcal_repeat_fields">
        <dt><label class="control-label" for="rex-forcal-entries-28f06d8c55ea171dcc6a38ea996b4b1b-repeats">'.rex_i18n::msg('forcal_entry_type').'</label></dt>
        <dd><div class="forcal-form-radioboxes-inline forcal-check-radiostyle">');

    $field = $form->addRadioField('type');
    $field->addOption(rex_i18n::msg('forcal_radio_one_time'),'one_time');
    $field->addOption(rex_i18n::msg('forcal_radio_repeat'),'repeat');
    $field->setAttribute('class', 'radio-btn forcal_repeat_master_radio');
    if ($func == 'add') $field->setValue('one_time');

    $form->addRawField('</div></dd></dl>');



    ## REPEAT TYPE
    $form->addRawField('<div class="forcal_repeats_show panel-collapse collapse">');

        $form->addRawField('<dl class="rex-form-group form-group">
            <dt><label class="control-label" for="rex-forcal-entries-28f06d8c55ea171dcc6a38ea996b4b1b-repeats">'.rex_i18n::msg('forcal_entry_repeats').'</label></dt>
            <dd><div class="forcal-form-select-inline forcal-repeat-type">');

            $field = $form->addSelectField('repeat');
            $select = $field->getSelect();
            $select->addOption(rex_i18n::msg('forcal_select_weekly'), 'weekly');
            $select->addOption(rex_i18n::msg('forcal_select_monthly'), 'monthly');
            $select->addOption(rex_i18n::msg('forcal_select_monthly_day'), 'monthly-week');
            $select->addOption(rex_i18n::msg('forcal_select_yearly'), 'yearly');
            if ($func == 'add') $field->setValue('weekly');
            $field->setAttribute('class', 'selectpicker forcal_repeat_select');

            ## REPEAT SETTINGS
                $form->addRawField('</div><div class="forcal-form-select-inline forcal_repeat_view_element view-monthly">');

                    ## MONTHLY
                    $field = $form->addSelectField('repeat_month');
                    $select = $field->getSelect();
                    for ($i=1;$i<13;$i++) {
                        $select->addOption($i, $i);
                    }
                    $field->setLabel(rex_i18n::msg('forcal_entry_everyn'));
                    $field->setAttribute('class', 'selectpicker');

                $form->addRawField('</div><div class="forcal-form-select-inline forcal_repeat_view_element view-monthly-week">');

                    ## MONTHLY
                    $field = $form->addSelectField('repeat_month_week');
                    $select = $field->getSelect();
                    $select->addOption(rex_i18n::msg('forcal_select_first_week'), 'first');
                    $select->addOption(rex_i18n::msg('forcal_select_second_week'), 'second');
                    $select->addOption(rex_i18n::msg('forcal_select_third_week'), 'third');
                    $select->addOption(rex_i18n::msg('forcal_select_fourth_week'), 'fourth');
                    $select->addOption(rex_i18n::msg('forcal_select_last_week'), 'last');
                    $field->setLabel(rex_i18n::msg('forcal_entry_everyn'));
                    $field->setAttribute('class', 'selectpicker');

                    $field = $form->addSelectField('repeat_day');
                    $select = $field->getSelect();
                    $select->addOption(rex_i18n::msg('forcal_select_sun'), 'sun');
                    $select->addOption(rex_i18n::msg('forcal_select_mon'), 'mon');
                    $select->addOption(rex_i18n::msg('forcal_select_tue'), 'tue');
                    $select->addOption(rex_i18n::msg('forcal_select_wed'), 'wed');
                    $select->addOption(rex_i18n::msg('forcal_select_thu'), 'thu');
                    $select->addOption(rex_i18n::msg('forcal_select_fri'), 'fri');
                    $select->addOption(rex_i18n::msg('forcal_select_sat'), 'sat');
                    $field->setAttribute('class', 'selectpicker');

                $form->addRawField('</div><div class="forcal-form-select-inline forcal_repeat_view_element view-weekly">');

                    ## WEEKLY
                    $field = $form->addSelectField('repeat_week');
                    $select = $field->getSelect();
                    for ($i=1;$i<53;$i++) {
                        $select->addOption($i, $i);
                    }
                    $field->setLabel(rex_i18n::msg('forcal_entry_everye'));
                    $field->setAttribute('class', 'selectpicker');

                $form->addRawField('</div><div class="forcal-form-select-inline forcal_repeat_view_element view-yearly">');

                    ## YEARLY
                    $field = $form->addSelectField('repeat_year');
                    $select = $field->getSelect();
                    for ($i=1;$i<11;$i++) {
                        $select->addOption($i, $i);
                    }
                    $field->setLabel(rex_i18n::msg('forcal_entry_everys'));
                    $field->setAttribute('class', 'selectpicker');

                ## DESCRIPTION
                $form->addRawField('</div>
                        <div class="forcal-form-inline-description forcal_repeat_view_element view-weekly">'.rex_i18n::msg('forcal_repeat_every_weekly').'</div>
                        <div class="forcal-form-inline-description forcal_repeat_view_element view-monthly">'.rex_i18n::msg('forcal_repeat_every_monthly').'</div>
                        <div class="forcal-form-inline-description forcal_repeat_view_element view-yearly">'.rex_i18n::msg('forcal_repeat_every_yearly').'</div>');

                $form->addRawField('
                    <div class="forcal-form-inline-description forcal-repeat-ending"><strong>'.rex_i18n::msg('forcal_repeat_ending').'</strong></div>
                    <div class="forcal-form-input-inline">
                      <div class="input-group" id="dpd2b_wrapper">
                        <div class="input-group-addon forcal-date-input"><i class="rex-icon fa-calendar"></i></div>
                      </div>
                    </div>
                ');

        $form->addRawField('</dd></dl></div>');

    ## TODO bring it to live
    ## DAYS for WEEKLY
    /*
    $form->addRawField('<dl class="rex-form-group form-group forcal_repeat_view_element view-weekly">
        <dt><label class="control-label" for="rex-forcal-entries-28f06d8c55ea171dcc6a38ea996b4b1b-repeats">'.rex_i18n::msg('forcal_check_days').'</label></dt>
        <dd><div class="forcal-form-checkboxes-inline">');

        $field = $form->addCheckboxField('repeat_sun', 0);
        $field->addOption(rex_i18n::msg('sun'), '1');

        $field = $form->addCheckboxField('repeat_mon', 0);
        $field->addOption(rex_i18n::msg('mon'), '1');

        $field = $form->addCheckboxField('repeat_tue', 0);
        $field->addOption(rex_i18n::msg('tue'), '1');

        $field = $form->addCheckboxField('repeat_wed', 0);
        $field->addOption(rex_i18n::msg('wed'), '1');

        $field = $form->addCheckboxField('repeat_thu', 0);
        $field->addOption(rex_i18n::msg('thu'), '1');

        $field = $form->addCheckboxField('repeat_fri', 0);
        $field->addOption(rex_i18n::msg('fri'), '1');

        $field = $form->addCheckboxField('repeat_sat', 0);
        $field->addOption(rex_i18n::msg('sat'), '1');

    $form->addRawField('</div></dd></dl>');
    */

    // start lang tabs
    \forCal\Utils\forCalFormHelper::addLangTabs($form, 'wrapper', 1);

    foreach (rex_clang::getAll() as $key => $clang) {
        // open form wrapper
        \forCal\Utils\forCalFormHelper::addLangTabs($form, 'inner_wrapper', $clang->getId(), rex_clang::getCurrentId());

        // Column: Name
        $field = $form->addTextField('name_' . $clang->getId());
        $field->setLabel(rex_i18n::msg('forcal_entry_name'));
        $field->setAttribute('class','forcal_entry_name form-control');

        if ($key == 1) {
            $field->getValidator()->add('notEmpty', rex_i18n::msg('forcal_entry_name_validation'));
        }

        // Column: Teaser
        $field = $form->addTextAreaField('teaser_' . $clang->getId());
        //set additional attributes
        $field = \forCal\Utils\forCalAttributesHelper::setAdditionalAttributes($field);
        $field->setLabel(rex_i18n::msg('forcal_entry_teaser'));

        // Column: Text
        $field = $form->addTextAreaField('text_' . $clang->getId());
        //set additional attributes
        $field = \forCal\Utils\forCalAttributesHelper::setAdditionalAttributes($field);
        $field->setLabel(rex_i18n::msg('forcal_entry_text'));

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

    // Column: Category
    $field = $form->addSelectField('category');
    $select = $field->getSelect();
    if ($additional_for_title) {
        $select->addSqlOptions('SELECT CONCAT(name_' . rex_clang::getCurrentId() . '," - ",'.$additional_for_title.'_'. rex_clang::getCurrentId() . ') name_'.rex_clang::getCurrentId().', id FROM ' . $tableCategories . ' ORDER BY name_' . rex_clang::getCurrentId());
    } else {
        $select->addSqlOptions('SELECT name_' . rex_clang::getCurrentId() . ', id FROM ' . $tableCategories . ' ORDER BY name_' . rex_clang::getCurrentId());
    }
    $field->setLabel(rex_i18n::msg('forcal_entry_category'));
    $field->setAttribute('class', 'forcal_category_select selectpicker form-control');
    $field->setAttribute('data-live-search', 'true');
    $field->setAttribute('required', 'required');

    // Column: Location
    $field = $form->addSelectField('venue');
    $select = $field->getSelect();
    $select->addSqlOptions('SELECT name_' . rex_clang::getCurrentId() . ', id FROM ' . $tableVenues . ' ORDER BY name_' . rex_clang::getCurrentId());
    $field->setLabel(rex_i18n::msg('forcal_entry_venue'));
    $field->setAttribute('class', 'forcal_venue_select selectpicker form-control');
    $field->setAttribute('data-live-search', 'true');

    // Column: Status
    $field = $form->addSelectField('status');
    $select = $field->getSelect();
    $select->addOptions(array(1=>'online', 0=>'offline'));
    $field->setLabel(rex_i18n::msg('forcal_entry_status'));
    $field->setAttribute('style', 'width:200px');
    $field->setAttribute('class', 'forcal_status_select selectpicker form-control');


    $tempform = preg_replace('/&(?!amp;|quot;|nbsp;|gt;|lt;|laquo;|raquo;|copy;|reg;|bul;|rsquo;)/', '&amp;', $form->get());
    $doc = new DOMDocument();
    $doc->loadHTML(mb_convert_encoding($tempform, 'HTML-ENTITIES', 'UTF-8'));

    // replace datein
    foreach (array('dpd1'=>'dpd1_wrapper', 'dpd2'=>'dpd2_wrapper', 'dpd2b'=>'dpd2b_wrapper') as $key => $value) {
        $source = $doc->getElementById($key);
        $source->setAttribute('type', 'text');
        $source->setAttribute('class', 'form-control');
        $source->setAttribute('size', '10');

        $target = $doc->getElementById($value);
        $target->appendChild($source);
    }

    // replace datein
    foreach (array('tpd1'=>'tpd1_wrapper', 'tpd2'=>'tpd2_wrapper') as $key => $value) {
        $source = $doc->getElementById($key);
        $source->setAttribute('type', 'text');
        $source->setAttribute('class', 'form-control');
        $source->setAttribute('size', '8');

        $target = $doc->getElementById($value);
        $button = $target->firstChild;
        $target->insertBefore($source, $button);
    }

    // show
    $content = $doc->saveHTML();

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', ($func == 'edit') ? rex_i18n::msg('forcal_entry_edit') : rex_i18n::msg('forcal_entry_add'));
    $fragment->setVar('body', $content, false);

    echo $fragment->parse('core/page/section.php');

    if(rex_request('entry_form_load') == true) {
        die;
    }
}
