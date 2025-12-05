<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

use FriendsOfRedaxo\QuickNavigation\Button\ButtonInterface;

class ForCalButton implements ButtonInterface
{
    public function get(): string
    {
        $user = rex::getUser();
        if (!$user || !$user->hasPerm('forcal[]')) {
            return '';
        }

        // Get recent entries
        $listItems = $this->getRecentEntries();

        if (empty($listItems)) {
            $newEntryUrl = rex_url::backendPage('forcal/entries', ['func' => 'add']);
            $listItems[] = '<div style="padding: 10px; text-align: center;">
                <p style="margin-bottom: 10px;">' . rex_i18n::msg('forcal_no_recent_entries') . '</p>
                <a href="' . $newEntryUrl . '" class="btn btn-default btn-sm">
                    <i class="fa fa-plus"></i> ' . rex_i18n::msg('forcal_add_new_entry') . '
                </a>
            </div>';
        }

        // Aktueller Tag des Monats
        $currentDay = date('j');
        
        $fragment = new rex_fragment([
            'label' => rex_i18n::msg('forcal_title'),
            'icon' => 'rex-icon fa-calendar',
            'iconBadge' => $currentDay,
            'listItems' => $listItems,
        ]);
        
        return $fragment->parse('QuickNavigation/Dropdown.php');
    }

    private function getRecentEntries(): array
    {
        $user = rex::getUser();
        $listItems = [];

        try {
            $sql = rex_sql::factory();
            $query = '
                SELECT id, name, updatedate, updateuser
                FROM ' . rex::getTablePrefix() . 'forcal_entries
                WHERE updatedate > 0
                ORDER BY updatedate DESC
                LIMIT 10
            ';

            $sql->setQuery($query);
            $rows = $sql->getRows();

            for ($i = 0; $i < $rows; ++$i) {
                $id = $sql->getValue('id');
                $name = $sql->getValue('name');
                $updateuser = $sql->getValue('updateuser');
                $updatedate = $sql->getValue('updatedate');

                $date = date('d.m. H:i', strtotime($updatedate));

                $editUrl = rex_url::backendPage('forcal/entries', [
                    'func' => 'edit',
                    'entry_id' => $id,
                ]);

                $listItems[] = sprintf(
                    '<div class="quick-navigation-item-row">
                        <a href="%s" title="%s">
                            <div>%s</div>
                            <div class="quick-navigation-item-info">
                                <small>%s • %s</small>
                            </div>
                        </a>
                    </div>',
                    $editUrl,
                    rex_escape($name),
                    rex_escape($this->truncateString($name, 30)),
                    rex_escape($updateuser),
                    $date
                );

                $sql->next();
            }
        } catch (Exception $e) {
            // Silent fail
        }

        return $listItems;
    }

    private function truncateString(string $string, int $length): string
    {
        if (mb_strlen($string) <= $length) {
            return $string;
        }

        return mb_substr($string, 0, $length - 3) . '...';
    }
}