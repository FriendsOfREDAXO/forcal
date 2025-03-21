<?php

class forcalQn
{
    public static function getCalHistory($ep): ?string
    {
        $subject = $ep->getSubject();
        $forcals = $categoryId = $filter_date = $forcalID = $start = $addLink = $filter_date = $today = $halfayear = '';

        // Prüfen, ob der Benutzer überhaupt forcal-Berechtigungen hat
        $user = rex::getUser();
        if (!$user || (!$user->isAdmin() && !$user->hasPerm('forcal[]'))) {
            return $subject; // Keine Änderung, wenn keine Berechtigung
        }

        $filter_date = ("Y-m-d");
        $categoryId = null;
        $start = date("Y-m-d");
        $today = strtotime($start);
        $halfayear = strtotime('+ 2 month', $today);
        $filter_date = date("Y-m-d", $halfayear);

        // Benutzerberechtigungen beachten bei der Abfrage
        $useUserPermissions = true;
        $forcals = \forCal\Handler\forCalHandler::getEntries($start, $filter_date, false, 'SORT_ASC', $categoryId, null, null, null, $useUserPermissions);
        $listItems = [];

        // Prüfen, ob der Benutzer neue Termine hinzufügen darf
        if ($user->isAdmin() || $user->hasPerm('forcal[]')) {
            $attributesAdd = [
                'href' => rex_url::backendPage('forcal/entries', ['func' => 'add']),
                'title' => rex_i18n::msg("forcal_add_new_entry"),
                'class' => 'btn btn-default',
                'accesskey' => 'e',
            ];

            $listItemAdd = '
                <div class="quick-navigation-item-row">
                    <a' . rex_string::buildAttributes($attributesAdd) . '>
                        <i class="fa-regular fa-plus" aria-hidden="true"></i>&nbsp' . rex_i18n::msg("forcal_add_new_entry") . '
                    </a>
                </div>
            ';

            $listItems[] = $listItemAdd;
        }

        if (count($forcals)) {
            foreach ($forcals as $forcal) {
                $forcalId = rex_escape($forcal['id']);
                $forcal_entry = rex_escape($forcal['entry']);
                $forcal_name = rex_escape($forcal_entry->entry_name);
                $forcal_start_date = rex_formatter::intlDate(strtotime($forcal_entry->entry_start_date->format('d.m.Y')));
                $forcal_end_date = rex_formatter::intlDate(strtotime($forcal_entry->entry_end_date->format('d.m.Y')));
                $entry_start_time = $forcal_entry->entry_start_time;
                $entry_start_time_date = new DateTime($entry_start_time);
                $forcal_start_time = rex_escape($entry_start_time_date->format('H:i'));

                $entry_end_time = $forcal_entry->entry_end_time;
                $entry_end_time_date = new DateTime($entry_end_time);
                $forcal_end_time = rex_escape($entry_end_time_date->format('H:i'));

                $forcal_color = rex_escape($forcal_entry->category_color);

                // Prüfen, ob der Benutzer den Termin bearbeiten darf
                $canEdit = true;
                if (!$user->isAdmin() && !$user->hasPerm('forcal[all]')) {
                    // Prüfen, ob der Benutzer die Berechtigung für die Kategorie hat
                    $canEdit = \forCal\Utils\forCalUserPermission::hasPermission($forcal_entry->category_id);
                }

                $attributes = [
                    'href' => rex_url::backendPage('forcal/entries', ['func' => $canEdit ? 'edit' : 'view', 'id' => $forcalId]),
                    'title' => $forcal_name,
                    'style' => 'border-color:' . $forcal_color,
                    'class' => 'quick_navi_forcal_border'
                ];

                $listItem = '
                    <div class="quick-navigation-item-row">
                        <a' . rex_string::buildAttributes($attributes) . '>
                            ' . $forcal_name . '<br><small>' . $forcal_start_date . ' bis ' . $forcal_end_date . ' - ' . $forcal_start_time . ' bis ' . $forcal_end_time . '</small>
                        </a>
                    </div>
                ';

                $listItems[] = $listItem;
            }
        }

        if (count($listItems) < 1) {
            $fragment = new rex_fragment();
            $listItems[] = $fragment->parse('QuickNavigation/NoResult.php');
        }

        $fragment = new rex_fragment([
            'label' => rex_i18n::msg('forcal_quick_navigation_label'),
            'icon' => 'fa-regular fa-calendar',
            'listItems' => $listItems,
        ]);

        $subject .= $fragment->parse('QuickNavigation/Dropdown.php');
        return $subject;
    }
}
