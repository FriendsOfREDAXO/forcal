<?php

use FriendsOfRedaxo\QuickNavigation\Button\ButtonInterface;

class ForCalButton implements ButtonInterface
{
    public function get(): string
    {
        $user = rex::getUser();
        if (!$user || (!$user->isAdmin() && !$user->hasPerm('forcal[]'))) {
            return '';
        }

        $listItems = [];

        // --- "Neuer Termin"-Button immer oben ---
        $listItems[] = sprintf(
            '<div class="quick-navigation-item-row">
                <a href="%s" class="btn btn-default forcal-qn-add-btn" title="%s">
                    <i class="fa-regular fa-calendar-plus" aria-hidden="true"></i> %s
                </a>
            </div>',
            rex_url::backendPage('forcal/entries', ['func' => 'add']),
            rex_escape(rex_i18n::msg('forcal_add_new_entry')),
            rex_escape(rex_i18n::msg('forcal_add_new_entry'))
        );

        // --- Nächste 10 Termine ab heute laden ---
        $entries = \forCal\Handler\forCalHandler::getEntries(
            date('Y-m-d'),
            date('Y-m-d', strtotime('+2 years')),
            true,       // ignoreStatus: Backend zeigt auch offline-Termine
            SORT_ASC,
            null,       // Kategorien: Filter läuft intern per useUserPermissions
            null,
            10,         // pageSize
            1,          // pageNumber
            true        // useUserPermissions: Admins sehen alles, andere nur erlaubte Kategorien
        );

        if (count($entries) > 0) {
            $today    = new DateTime('today');
            $tomorrow = new DateTime('tomorrow');

            foreach ($entries as $forcal) {
                /** @var \stdClass $entry */
                $entry = $forcal['entry'];

                $id         = (int) $forcal['id'];
                $name       = rex_escape($entry->entry_name);
                $color      = rex_escape($entry->category_color ?? '#9ca5b2');
                $catName    = rex_escape($entry->category_name ?? '');
                $fullTime   = !empty($entry->full_time);

                $startDate = $entry->entry_start_date;
                $endDate   = $entry->entry_end_date;

                // Datumsanzeige mit "Heute" / "Morgen"-Label
                $dateLabel = '';
                if ($startDate->format('Y-m-d') === $today->format('Y-m-d')) {
                    $dateLabel = '<span class="forcal-qn-badge forcal-qn-today">Heute</span> ';
                } elseif ($startDate->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                    $dateLabel = '<span class="forcal-qn-badge forcal-qn-tomorrow">Morgen</span> ';
                }

                $startFormatted = rex_formatter::intlDate($startDate->getTimestamp());
                $endFormatted   = rex_formatter::intlDate($endDate->getTimestamp());
                $dateStr = $startDate->format('Y-m-d') === $endDate->format('Y-m-d')
                    ? $startFormatted
                    : $startFormatted . ' – ' . $endFormatted;

                // Uhrzeit (nur wenn nicht ganztägig und nicht 00:00)
                $timeStr = '';
                if (!$fullTime && !empty($entry->entry_start_time)) {
                    $st = (new DateTime($entry->entry_start_time))->format('H:i');
                    $et = (new DateTime($entry->entry_end_time))->format('H:i');
                    if ($st !== '00:00' || $et !== '00:00') {
                        $timeStr = ' &middot; ' . rex_escape($st) . '–' . rex_escape($et) . ' Uhr';
                    }
                }

                $canEdit = $user->isAdmin() || $user->hasPerm('forcal[all]')
                    || \forCal\Utils\forCalUserPermission::hasPermission($entry->category_id);

                $editUrl = rex_url::backendPage('forcal/entries', [
                    'func' => $canEdit ? 'edit' : 'view',
                    'id'   => $id,
                ]);

                $listItems[] = sprintf(
                    '<div class="quick-navigation-item-row forcal-qn-entry">
                        <a href="%s" title="%s" class="forcal-qn-link" style="--forcal-color:%s">
                            <span class="forcal-qn-name">%s%s</span>
                            <span class="forcal-qn-meta">%s%s%s</span>
                        </a>
                    </div>',
                    $editUrl,
                    rex_escape($entry->entry_name),
                    $color,
                    $dateLabel,
                    $name,
                    rex_escape($dateStr),
                    $timeStr,
                    $catName ? ' &middot; <span class="forcal-qn-cat">' . $catName . '</span>' : ''
                );
            }
        } else {
            $listItems[] = '<div class="quick-navigation-no-results"><div>'
                . rex_escape(rex_i18n::msg('forcal_no_recent_entries'))
                . '</div></div>';
        }

        $fragment = new rex_fragment([
            'label'     => rex_i18n::msg('forcal_quick_navigation_label'),
            'icon'      => 'fa-regular fa-calendar',
            'listItems' => $listItems,
        ]);

        return $fragment->parse('QuickNavigation/Dropdown.php');
    }
}