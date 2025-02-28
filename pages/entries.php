<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

use forCal\Handler\forCalHandler;
use forCal\Manager\forCalFormManager;
use forCal\Utils\forCalAttributesHelper;
use forCal\Utils\forCalListHelper;
use forCal\Utils\forCalUserPermission;

$addon = rex_addon::get('forcal');
$user = rex::getUser();

// Benutzerrechte prüfen
if (!$user->hasPerm('forcal[]')) {
    echo rex_view::error($addon->i18n('permission_denied'));
    return;
}

// Formular-Instanz vorbereiten
$func = rex_request('func', 'string');
$id = rex_request('id', 'int', 0);
$table = rex::getTable('forcal_entries');

// Formular initialisieren für Add oder Edit
if ($func == 'add' || $func == 'edit') {
    // Prüfen ob der Benutzer Kategorien hat
    if (!$user->isAdmin() && !forCalUserPermission::hasAnyPermission()) {
        echo rex_view::warning($addon->i18n('forcal_no_permission_categories'));
        $func = '';
    }
}

// Bei Edit: Prüfen ob Benutzer Zugriff auf die Kategorie des Termins hat
if ($func == 'edit' && $id > 0 && !$user->isAdmin()) {
    $sql = rex_sql::factory();
    $sql->setQuery('
        SELECT category 
        FROM ' . $table . ' 
        WHERE id = :id', 
        ['id' => $id]
    );
    
    if ($sql->getRows() > 0) {
        $categoryId = $sql->getValue('category');
        
        if (!empty($categoryId) && !forCalUserPermission::hasPermission($categoryId)) {
            echo rex_view::error($addon->i18n('forcal_no_permission_for_category'));
            $func = '';
        }
    }
}

// Funktionen zum Hinzufügen, Bearbeiten oder Löschen von Einträgen
if ($func == 'delete' && $id > 0) {
    // Lösch-Berechtigung prüfen
    if (!$user->isAdmin()) {
        $sql = rex_sql::factory();
        $sql->setQuery('
            SELECT category 
            FROM ' . $table . ' 
            WHERE id = :id', 
            ['id' => $id]
        );
        
        if ($sql->getRows() > 0) {
            $categoryId = $sql->getValue('category');
            
            if (!empty($categoryId) && !forCalUserPermission::hasPermission($categoryId)) {
                echo rex_view::error($addon->i18n('forcal_no_permission_for_category'));
                $func = '';
            } else {
                echo forCalListHelper::deleteData($table, $id);
                $func = '';
            }
        }
    } else {
        echo forCalListHelper::deleteData($table, $id);
        $func = '';
    }
} elseif ($func == 'clone' && $id > 0) {
    // Klon-Berechtigung prüfen
    if (!$user->isAdmin()) {
        $sql = rex_sql::factory();
        $sql->setQuery('
            SELECT category 
            FROM ' . $table . ' 
            WHERE id = :id', 
            ['id' => $id]
        );
        
        if ($sql->getRows() > 0) {
            $categoryId = $sql->getValue('category');
            
            if (!empty($categoryId) && !forCalUserPermission::hasPermission($categoryId)) {
                echo rex_view::error($addon->i18n('forcal_no_permission_for_category'));
                $func = '';
            } else {
                echo forCalListHelper::cloneData($table, $id);
                $func = '';
            }
        }
    } else {
        echo forCalListHelper::cloneData($table, $id);
        $func = '';
    }
} elseif ($func == 'status' && $id > 0) {
    // Status-Änderungs-Berechtigung prüfen
    if (!$user->isAdmin()) {
        $sql = rex_sql::factory();
        $sql->setQuery('
            SELECT category 
            FROM ' . $table . ' 
            WHERE id = :id', 
            ['id' => $id]
        );
        
        if ($sql->getRows() > 0) {
            $categoryId = $sql->getValue('category');
            
            if (!empty($categoryId) && !forCalUserPermission::hasPermission($categoryId)) {
                echo rex_view::error($addon->i18n('forcal_no_permission_for_category'));
                $func = '';
            } else {
                echo forCalListHelper::toggleBoolData($table, $id, 'status');
                $func = '';
            }
        }
    } else {
        echo forCalListHelper::toggleBoolData($table, $id, 'status');
        $func = '';
    }
}

// Formular zum Hinzufügen oder Bearbeiten anzeigen
if ($func == 'add' || $func == 'edit') {
    $title = $func == 'edit' ? $addon->i18n('forcal_entry_edit') : $addon->i18n('forcal_entry_add');
    
    $form = rex_form::factory($table, '', 'id = ' . $id, 'post', false);
    $form->addParam('id', $id);
    $form->addParam('func', $func);
    $form->setEditMode($func == 'edit');
    
    // START TAB 1
    forCalFormManager::addCollapsePanel($form, 'wrapper', $addon->i18n('forcal_entry_date'));
    
    // Datepicker laut Redaxo config
    $date = new rex_form_container_element();
    
    echo '<style>.forcal-form-input-inline{display:inline}</style>';
    
    $form->addRawField($date->formatElement(
        '<table cellpadding="0" cellspacing="0" class="rex-table-middle forcaldatepicker table" data-only-checkin-range="0" data-today="'.date('Y-m-d').'">'
    ));
    
    $form->addRawField($date->formatElement(
        '<thead><tr><th class="rex-table-icon date-label">'.rex_i18n::msg('forcal_from').':</th><td class="date-input forcaldate"><div class="input-group input-group-sm"><span class="input-group-addon forcal-date-input"><i class="rex-icon fa-calendar"></i></span>'));
    $dpd1 = $form->addTextField('start_date');
    $dpd1->setAttribute('id', 'dpd1');
    $dpd1->setAttribute('class', 'form-control');
    
    $form->addRawField($date->formatElement(
        '</div></td><td class="rex-table-action forcalclock"><div class="input-group input-group-sm"><span class="input-group-addon forcal-date-input"><i class="rex-icon fa-clock-o"></i></span>'));
    $tpd1 = $form->addTextField('start_time');
    $tpd1->setAttribute('id', 'tpd1');
    $tpd1->setAttribute('class', 'form-control');
    $form->addRawField($date->formatElement('</div></td></tr>'));
    
    $form->addRawField($date->formatElement('<tr><th class="rex-table-action date-label">'.rex_i18n::msg('forcal_to').':</th><td class="date-input forcaldate"><div class="input-group input-group-sm"><span class="input-group-addon forcal-date-input"><i class="rex-icon fa-calendar"></i></span>'));
    $dpd2 = $form->addTextField('end_date');
    $dpd2->setAttribute('id', 'dpd2');
    $dpd2->setAttribute('class', 'form-control');
    $form->addRawField($date->formatElement('</div></td><td class="rex-table-action forcalclock"><div class="input-group input-group-sm"><span class="input-group-addon forcal-date-input"><i class="rex-icon fa-clock-o"></i></span>'));
    $tpd2 = $form->addTextField('end_time');
    $tpd2->setAttribute('id', 'tpd2');
    $tpd2->setAttribute('class', 'form-control');
    $form->addRawField($date->formatElement('</div></td></tr></thead></table>'));
    
    // checkbox full time
    $full_time_preselection = $addon->getConfig('forcal_full_time_preselection');
    $full_time = $form->addCheckboxField('full_time');
    $full_time->setValue(1);
    if ($full_time_preselection === 1) {
        $full_time->setAttribute('checked', 'checked');
    }
    $full_time->setAttribute('class', 'forcal_fulltime_master_check');
    $full_time->setLabel(rex_i18n::msg('forcal_checkbox_full_time'));
    
    // radio field
    $type = $form->addRadioField('type');
    $type->setHeader('<div class="row"><div class="col-xs-12 col-sm-4">');
    $type->setFooter('</div>');
    $type->addOption(rex_i18n::msg('forcal_radio_one_time'), 'one_time');
    $type->setLabel('');
    
    $repeat = $form->addRadioField('repeat');
    $repeat->setHeader('<div class="col-xs-12 col-sm-4">');
    $repeat->setFooter('</div></div>');
    $repeat->addOption(rex_i18n::msg('forcal_radio_repeat'), 'repeat');
    $repeat->setAttribute('class', 'forcal_repeat_master_radio');
    $repeat->setLabel('');

    // repeat hidden
    $form->addRawField('<div class="collapse forcal_repeats_show">');
    
    $repeat_every = $form->addSelectField('repeats');
    $repeat_every->setAttribute('class', 'forcal_repeat_select');
    $repeat_every->addOption('', 'chose');
    $repeat_every->addOption(rex_i18n::msg('forcal_select_weekly'), 'weekly');
    $repeat_every->addOption(rex_i18n::msg('forcal_select_monthly'), 'monthly');
    $repeat_every->addOption(rex_i18n::msg('forcal_select_monthly_day'), 'monthly-week');
    $repeat_every->addOption(rex_i18n::msg('forcal_select_yearly'), 'yearly');
    $repeat_every->setLabel(rex_i18n::msg('forcal_repeat_every'));
    
    // views
    $form->addRawField('<div class="collapse forcal_repeat_show">');
    $form->addRawField('<div class="row">');
    $form->addRawField('<div class="forcal_repeat_view_element hidden view-weekly">');
    $form->addRawField('<div class="col-xs-12 col-sm-4">');

    $repeat_week = $form->addTextField('repeat_week');
    $repeat_week->setLabel(rex_i18n::msg('forcal_entry_everyn') . ' x ' . rex_i18n::msg('forcal_repeat_every_weekly'));
    $repeat_week->setAttribute('type', 'number');
    $repeat_week->setAttribute('min', '1');
    $repeat_week->setAttribute('value', '1');
    
    $form->addRawField('</div>');
    $form->addRawField('</div>');
    
    $form->addRawField('<div class="forcal_repeat_view_element hidden view-monthly">');
    $form->addRawField('<div class="col-xs-12 col-sm-4">');
    
    $repeat_month = $form->addTextField('repeat_month');
    $repeat_month->setLabel(rex_i18n::msg('forcal_entry_everyn') . ' x ' . rex_i18n::msg('forcal_repeat_every_monthly'));
    $repeat_month->setAttribute('type', 'number');
    $repeat_month->setAttribute('min', '1');
    $repeat_month->setAttribute('value', '1');
    
    $form->addRawField('</div>');
    $form->addRawField('</div>');
    
    $form->addRawField('<div class="forcal_repeat_view_element hidden view-monthly-week">');
    $form->addRawField('<div class="col-xs-12 col-sm-4">');
    
    $repeat_month_week = $form->addSelectField('repeat_month_week');
    $repeat_month_week->setLabel(rex_i18n::msg('forcal_entry_everyn'));
    $repeat_month_week->addOption(rex_i18n::msg('forcal_select_first_week'), 'first');
    $repeat_month_week->addOption(rex_i18n::msg('forcal_select_second_week'), 'second');
    $repeat_month_week->addOption(rex_i18n::msg('forcal_select_third_week'), 'third');
    $repeat_month_week->addOption(rex_i18n::msg('forcal_select_fourth_week'), 'fourth');
    $repeat_month_week->addOption(rex_i18n::msg('forcal_select_last_week'), 'last');
    
    $form->addRawField('</div>');
    $form->addRawField('<div class="col-xs-12 col-sm-4">');
    
    $repeat_day = $form->addSelectField('repeat_day');
    $repeat_day->setLabel(rex_i18n::msg('forcal_entry_repeats'));
    $repeat_day->addOption(rex_i18n::msg('forcal_select_mon'), 'mon');
    $repeat_day->addOption(rex_i18n::msg('forcal_select_tue'), 'tue');
    $repeat_day->addOption(rex_i18n::msg('forcal_select_wed'), 'wed');
    $repeat_day->addOption(rex_i18n::msg('forcal_select_thu'), 'thu');
    $repeat_day->addOption(rex_i18n::msg('forcal_select_fri'), 'fri');
    $repeat_day->addOption(rex_i18n::msg('forcal_select_sat'), 'sat');
    $repeat_day->addOption(rex_i18n::msg('forcal_select_sun'), 'sun');
    
    $form->addRawField('</div>');
    $form->addRawField('</div>');
    
    $form->addRawField('<div class="forcal_repeat_view_element hidden view-yearly">');
    $form->addRawField('<div class="col-xs-12 col-sm-4">');
    
    $repeat_year = $form->addTextField('repeat_year');
    $repeat_year->setLabel(rex_i18n::msg('forcal_entry_everys') . ' x ' . rex_i18n::msg('forcal_repeat_every_yearly'));
    $repeat_year->setAttribute('type', 'number');
    $repeat_year->setAttribute('min', '1');
    $repeat_year->setAttribute('value', '1');
    
    $form->addRawField('</div>');
    $form->addRawField('</div>');
    
    $form->addRawField('<div class="col-xs-12 col-md-4 col-lg-3">');
    
    $form->addRawField('<div class="form-group"><label class="control-label">'.rex_i18n::msg('forcal_repeat_ending').'</label></div>');
    
    $form->addRawField('<table class="rex-table-middle forcaldatepicker table" data-only-checkin-range="1" id="dpd2b_wrapper">');
    $form->addRawField('<tr><td class="forcaldate"><div class="input-group input-group-sm"><span class="input-group-addon forcal-date-input"><i class="rex-icon fa-calendar"></i></span>');
    
    $dpend = $form->addTextField('end_repeat_date');
    $dpend->setAttribute('id', 'dpd2b');
    $dpend->setAttribute('class', 'form-control');
    
    $form->addRawField('</div></td></tr>');
    $form->addRawField('</table>');
    $form->addRawField('</div>');
    $form->addRawField('</div>');
    
    $form->addRawField('</div>');
    $form->addRawField('</div>');
    
    // Check ob Datum via Parameter übergeben wurde, z.B. Wenn auf den Tag im Monat geklickt wurde
    $itemdate = rex_request('itemdate', 'string');
    if ($itemdate != '' && $func == 'add') {
        $dpd1->setValue($itemdate);
        $dpd2->setValue($itemdate);
    }
  
    forCalFormManager::addCollapsePanel($form, 'close_wrapper');
    
    // Informationen
    forCalFormManager::addCollapsePanel($form, 'wrapper', $addon->i18n('forcal_entry_name'));
    
    // Name als Pflichtfeld
    $name = $form->addTextField('name_' . rex_clang::getCurrentId());
    $name->setAttribute('required', 'required');
    $name->setAttribute('data-validation-message', rex_i18n::msg('forcal_entry_name_validation'));
    $name->setLabel($addon->i18n('forcal_entry_name'));
    
    $teaser = $form->addTextAreaField('teaser_' . rex_clang::getCurrentId());
    $teaser = forCalAttributesHelper::setAdditionalAttributes($teaser);
    $teaser->setLabel($addon->i18n('forcal_entry_teaser'));
    
    $text = $form->addTextAreaField('text_' . rex_clang::getCurrentId());
    $text = forCalAttributesHelper::setAdditionalAttributes($text);
    $text->setLabel($addon->i18n('forcal_entry_text'));
    
    if ($addon->getConfig('forcal_editor') == 3 && rex_addon::get('redactor2')->isAvailable()) {
        $teaser->setAttribute('class', 'redactorEditor2-forcal');
        $text->setAttribute('class', 'redactorEditor2-forcal');
    } elseif ($addon->getConfig('forcal_editor') == 1 && rex_addon::get('markitup')->isAvailable()) {
        $teaser->setAttribute('class', 'markitupEditor-markdown_full');
        $text->setAttribute('class', 'markitupEditor-markdown_full');
    } elseif ($addon->getConfig('forcal_editor') == 2 && rex_addon::get('ckeditor')->isAvailable()) {
        $teaser->setAttribute('class', 'ckeditor');
        $text->setAttribute('class', 'ckeditor');
    }

    // Kategorien-Feld anpassen für Benutzerberechtigungen
    $category = $form->addSelectField('category');
    $category->setLabel($addon->i18n('forcal_entry_category'));
    if (!$user->isAdmin()) {
        // Kategorien filtern, auf die der Benutzer Zugriff hat
        $allowedCategories = forCalUserPermission::getUserCategories($user->getId());
        
        if (!empty($allowedCategories)) {
            // SQL-Query für erlaubte Kategorien erstellen
            $sql = rex_sql::factory();
            $sql->setQuery('
                SELECT id, name_' . rex_clang::getCurrentId() . ' as name 
                FROM ' . rex::getTable('forcal_categories') . ' 
                WHERE status = 1 
                AND id IN (' . implode(',', $allowedCategories) . ') 
                ORDER BY name_' . rex_clang::getCurrentId()
            );
            
            // Select-Feld mit den erlaubten Kategorien erstellen
            $select = $category->getSelect();
            $select->resetOptions();
            
            foreach ($sql as $option) {
                $select->addOption($option->getValue('name'), $option->getValue('id'));
            }
        }
    } else {
        // Admin kann alle Kategorien sehen
        $sql = rex_sql::factory();
        $sql->setQuery('
            SELECT id, name_' . rex_clang::getCurrentId() . ' as name 
            FROM ' . rex::getTable('forcal_categories') . ' 
            WHERE status = 1 
            ORDER BY name_' . rex_clang::getCurrentId()
        );
        
        $select = $category->getSelect();
        $select->resetOptions();
        
        foreach ($sql as $option) {
            $select->addOption($option->getValue('name'), $option->getValue('id'));
        }
    }
    
    // Orte-Auswahl
    $venue = $form->addSelectField('venue');
    $venue->setLabel($addon->i18n('forcal_entry_venue'));
    $venue->getSelect()->addSqlOptions('select id, name_' . rex_clang::getCurrentId() . ' from ' . rex::getTable('forcal_venues') . ' where status = 1 order by name_' . rex_clang::getCurrentId());
    
    $status = $form->addSelectField('status');
    $status->setLabel($addon->i18n('forcal_entry_status'));
    $status->addOption('online', 1);
    $status->addOption('offline', 0);
    
    forCalFormManager::addCollapsePanel($form, 'close_wrapper');
    
    // Weitere Formularfelder zu anderen Sprachen hinzufügen
    if (rex_clang::count() > 1) {
        // für jede verfügbare Sprache (außer der aktuellen) Formular-Tab erstellen
        foreach (rex_clang::getAll() as $clang) {
            if ($clang->getId() != rex_clang::getCurrentId()) {
                forCalFormManager::addCollapsePanel($form, 'wrapper', $clang->getName());
                
                $name = $form->addTextField('name_' . $clang->getId());
                $name->setLabel($addon->i18n('forcal_entry_name'));
                
                $teaser = $form->addTextAreaField('teaser_' . $clang->getId());
                $teaser = forCalAttributesHelper::setAdditionalAttributes($teaser);
                $teaser->setLabel($addon->i18n('forcal_entry_teaser'));
                
                $text = $form->addTextAreaField('text_' . $clang->getId());
                $text = forCalAttributesHelper::setAdditionalAttributes($text);
                $text->setLabel($addon->i18n('forcal_entry_text'));
                
                if ($addon->getConfig('forcal_editor') == 3 && rex_addon::get('redactor2')->isAvailable()) {
                    $teaser->setAttribute('class', 'redactorEditor2-forcal');
                    $text->setAttribute('class', 'redactorEditor2-forcal');
                } elseif ($addon->getConfig('forcal_editor') == 1 && rex_addon::get('markitup')->isAvailable()) {
                    $teaser->setAttribute('class', 'markitupEditor-markdown_full');
                    $text->setAttribute('class', 'markitupEditor-markdown_full');
                } elseif ($addon->getConfig('forcal_editor') == 2 && rex_addon::get('ckeditor')->isAvailable()) {
                    $teaser->setAttribute('class', 'ckeditor');
                    $text->setAttribute('class', 'ckeditor');
                }
                
                forCalFormManager::addCollapsePanel($form, 'close_wrapper');
            }
        }
    }
    
    // Benutzerdefinierte Felder hinzufügen
    forCalFormManager::addCustomFormField($form, rex_clang::get(rex_clang::getCurrentId()));
    
    // Generate UID field beim Erstellen
    if ($func == 'add') {
        $form->addHiddenField('uid', \Ramsey\Uuid\Uuid::uuid4()->toString());
    }

    $content = $form->get();
    
    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit');
    $fragment->setVar('title', $title);
    $fragment->setVar('body', $content, false);
    $content = $fragment->parse('core/page/section.php');
    
    echo $content;
} else {
    // Liste anzeigen
    
    // Filtere nach Benutzerberechtigungen
    $userFilter = '';
    if (!$user->isAdmin()) {
        $allowedCategories = forCalUserPermission::getUserCategories($user->getId());
        if (!empty($allowedCategories)) {
            $userFilter = ' AND category IN (' . implode(',', $allowedCategories) . ')';
        } else {
            $userFilter = ' AND 0'; // Keine Kategorien erlaubt = keine Ergebnisse
        }
    }

    // UID sollte nie angezeigt werden
    $query = 'SELECT id, start_date, start_time, name_' . rex_clang::getCurrentId() . ', status FROM ' . $table . ' WHERE 1' . $userFilter . ' ORDER BY start_date DESC, start_time DESC';
    
    // SQL abfragen
    $list = rex_list::factory($query);
    
    // Icon hinzufügen
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '"><i class="rex-icon rex-icon-add"></i></a>';
    $tdIcon = '<i class="rex-icon fa-calendar"></i>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);
    
    // sprechende Spaltenbezeichnungen
    $list->setColumnLabel('start_date', $addon->i18n('forcal_startdate'));
    $list->setColumnFormat('start_date', 'custom', [forCalListHelper::class, 'formatStartDate']);
    
    $list->setColumnLabel('start_time', $addon->i18n('forcal_starttime'));
    $list->setColumnFormat('start_time', 'custom', [forCalListHelper::class, 'formatStartTime']);
    
    $list->setColumnLabel('name_' . rex_clang::getCurrentId(), $addon->i18n('forcal_entry_name'));
    $list->setColumnParams('name_' . rex_clang::getCurrentId(), ['func' => 'edit', 'id' => '###id###']);
    
    // Status
    $list->setColumnLabel('status', $addon->i18n('forcal_status_function'));
    $list->setColumnParams('status', ['func' => 'status', 'id' => '###id###']);
    $list->setColumnFormat('status', 'custom', [forCalListHelper::class, 'formatStatus']);
    
    // Aktionen
    $list->addColumn('clone', '<i class="rex-icon fa-copy"></i> ' . $addon->i18n('forcal_clone'));
    $list->setColumnLabel('clone', $addon->i18n('forcal_function'));
    $list->setColumnParams('clone', ['func' => 'clone', 'id' => '###id###']);
    $list->addLinkAttribute('clone', 'data-confirm', rex_i18n::msg('form_delete') . ' ?');
    
    // Aktionen
    $list->addColumn('delete', '<i class="rex-icon rex-icon-delete"></i> ' . $addon->i18n('forcal_entry_delete'));
    $list->setColumnLabel('delete', $addon->i18n('forcal_function'));
    $list->setColumnParams('delete', ['func' => 'delete', 'id' => '###id###']);
    $list->addLinkAttribute('delete', 'data-confirm', rex_i18n::msg('form_delete') . ' ?');
    
    // Suche
    $list->addParam('start', rex_request('start', 'string'));
    $list->addParam('end', rex_request('end', 'string'));
    $list->addParam('category', rex_request('category', 'string'));
    $list->addParam('venue', rex_request('venue', 'string'));
    
    // Ausgabe des Listenformulars
    $content = $list->get();
    
    $fragment = new rex_fragment();
    $fragment->setVar('title', $addon->i18n('forcal_entry_list_view'));
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');
    
    echo $content;
}

// Prüfen ob ein Termin beim Hinzufügen ein Datum bekommen soll
$itemdate = rex_request('itemdate', 'string');
if ($itemdate != '' && $func == '') {
    echo '
    <script>
    $(document).ready(function() {
        window.location.href = window.location.href+"&func=add&itemdate='.$itemdate.'";
    });
    </script>
    ';
}

// Beim Speichern auf Benutzerberechtigungen prüfen
if (rex_post('btn_save', 'string') && !$user->isAdmin()) {
    $categoryId = rex_post('category', 'int', 0);
    
    if ($categoryId > 0 && !forCalUserPermission::hasPermission($categoryId)) {
        echo rex_view::error($addon->i18n('forcal_no_permission_for_category'));
        // Weitere Verarbeitung verhindern
        exit;
    }
}
