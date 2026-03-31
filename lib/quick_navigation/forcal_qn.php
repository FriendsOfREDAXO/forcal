<?php

class forcalQn
{
    public static function getCalHistory($ep): ?string
    {
        $subject = $ep->getSubject();

        // Prüfen, ob der Benutzer überhaupt forcal-Berechtigungen hat
        $user = rex::getUser();
        if (!$user || (!$user->isAdmin() && !$user->hasPerm('forcal[]'))) {
            return $subject;
        }

        // Suchfenster: heute bis in 2 Jahre – pageSize=10 liefert die nächsten 10 Termine
        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+2 years'));

        // SORT_ASC (Konstante, kein String), useUserPermissions filtert Kategorien automatisch
        // ignoreStatus=true: Im Backend sollen alle Termine erscheinen,
        // auch wenn Termin oder Kategorie offline sind
        $forcals = \forCal\Handler\forCalHandler::getEntries(
            $start,
            $end,
            true,       // ignoreStatus: auch offline Termine/Kategorien anzeigen
            SORT_ASC,
            null,       // alle Kategorien (Filter läuft intern per $useUserPermissions)
            null,
            10,         // pageSize: maximal 10 Einträge
            1,          // pageNumber
            true        // Admins sehen alles, andere nur eigene Kategorien
        );

        $listItems = [];

        // "Neuer Termin"-Button oben
        $attributesAdd = [
            'href'      => rex_url::backendPage('forcal/entries', ['func' => 'add']),
            'title'     => rex_i18n::msg('forcal_add_new_entry'),
            'class'     => 'btn btn-default',
            'accesskey' => 'e',
        ];
        $listItems[] = '
            <div class="quick-navigation-item-row">
                <a' . rex_string::buildAttributes($attributesAdd) . '>
                    <i class="fa-regular fa-plus" aria-hidden="true"></i>&nbsp;' . rex_i18n::msg('forcal_add_new_entry') . '
                </a>
            </div>
        ';

        if (count($forcals) > 0) {
            foreach ($forcals as $forcal) {
                /** @var object $entry */
                $entry = $forcal['entry'];

                $forcalId        = (int) $forcal['id'];
                $forcal_name     = rex_escape($entry->entry_name);
                $forcal_start    = rex_formatter::intlDate(strtotime($entry->entry_start_date->format('Y-m-d')));
                $forcal_end      = rex_formatter::intlDate(strtotime($entry->entry_end_date->format('Y-m-d')));
                $forcal_color    = rex_escape($entry->category_color);

                $start_time = (new DateTime($entry->entry_start_time))->format('H:i');
                $end_time   = (new DateTime($entry->entry_end_time))->format('H:i');

                $canEdit = $user->isAdmin() || $user->hasPerm('forcal[all]')
                    || \forCal\Utils\forCalUserPermission::hasPermission($entry->category_id);

                $attributes = [
                    'href'  => rex_url::backendPage('forcal/entries', ['func' => $canEdit ? 'edit' : 'view', 'id' => $forcalId]),
                    'title' => $entry->entry_name,
                    'style' => 'border-color:' . $forcal_color,
                    'class' => 'quick_navi_forcal_border',
                ];

                $listItems[] = '
                    <div class="quick-navigation-item-row">
                        <a' . rex_string::buildAttributes($attributes) . '>
                            ' . $forcal_name . '<br>
                            <small>' . $forcal_start . ' – ' . $forcal_end . ' &nbsp;' . rex_escape($start_time) . ' – ' . rex_escape($end_time) . '</small>
                        </a>
                    </div>
                ';
            }
        } else {
            $fragment = new rex_fragment();
            $listItems[] = $fragment->parse('QuickNavigation/NoResult.php');
        }

        $fragment = new rex_fragment([
            'label'     => rex_i18n::msg('forcal_quick_navigation_label'),
            'icon'      => 'fa-regular fa-calendar',
            'listItems' => $listItems,
        ]);

        $subject .= $fragment->parse('QuickNavigation/Dropdown.php');
        return $subject;
    }
}
