<?php
/**
 * This file is part of the forcal package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Watson\Workflows\forCal;

use Watson\Foundation\Command;
use Watson\Foundation\Documentation;
use Watson\Foundation\Result;
use Watson\Foundation\ResultEntry;
use Watson\Foundation\Watson;
use Watson\Foundation\Workflow;
use rex;

class forCalSearch extends Workflow
{
    /**
     * Provide the commands of the search.
     *
     * @return array
     */
    public function commands()
    {
        return ['forcal'];
    }

    /**
     * @return Documentation
     */
    public function documentation()
    {
        $documentation = new Documentation();
        $documentation->setDescription(Watson::translate('watson_forcal_documentation_description'));
        $documentation->setUsage('forcal keyword');
        $documentation->setExample('forcal Phrase');

        return $documentation;
    }

    /**
     * Return array of registered page params.
     *
     * @return array
     */
    public function registerPageParams()
    {
        return [];
    }

    /**
     * Execute the command for the given Command.
     *
     * @param Command $command
     *
     * @return Result
     */
    public function fire(Command $command)
    {
        $result = new Result();

        // Prüfen, ob der Benutzer überhaupt forcal-Berechtigungen hat
        $user = rex::getUser();
        if (!$user || (!$user->isAdmin() && !$user->hasPerm('forcal[]'))) {
            return $result; // Leeres Ergebnis, wenn keine Berechtigung
        }

        // Wenn kein Admin und keine vollständigen Rechte, erlaubte Kategorien abrufen
        $allowedCategoryIds = [];
        $restrictByCategories = false;
        
        if (!$user->isAdmin() && !$user->hasPerm('forcal[all]')) {
            $allowedCategoryIds = \forCal\Utils\forCalUserPermission::getUserCategories($user->getId());
            
            if (empty($allowedCategoryIds)) {
                // Keine Kategorien zugewiesen, leeres Ergebnis zurückgeben
                return $result;
            }
            
            $restrictByCategories = true;
        }

        $fields = ['name_1', 'start_date', 'teaser_1', 'start_time', 'end_time', 'end_time', 'type'];

        // SQL-Abfrage erstellen
        $sql_query = '
       SELECT      * 
       FROM       ' . Watson::getTable('forcal_entries') . ' AS e
       WHERE       ' . $command->getSqlWhere($fields, 'e');

        // Kategoriefilter hinzufügen, wenn nötig
        if ($restrictByCategories) {
            $sql_query .= ' AND e.category IN (' . implode(',', $allowedCategoryIds) . ')';
        }

        // Sortierung hinzufügen
        $sql_query .= ' ORDER BY e.start_date DESC, e.start_time DESC';

        try {
            $items = $this->getDatabaseResults($sql_query);
        } catch (\Exception $e) {
            // Bei Fehlern leeres Ergebnis zurückgeben
            return $result;
        }

        if (count($items)) {
            $counter = 0;

            foreach ($items as $item) {
                // Nochmal explizit prüfen, ob der Benutzer diesen Eintrag sehen darf
                if ($restrictByCategories && !in_array($item['category'], $allowedCategoryIds)) {
                    continue;
                }

                // Prüfen, ob der Benutzer den Termin bearbeiten darf
                $canEdit = $user->isAdmin() || $user->hasPerm('forcal[all]') || 
                          \forCal\Utils\forCalUserPermission::hasPermission($item['category']);

                $func = $canEdit ? 'edit' : 'view';
                $url = Watson::getUrl(['page' => 'forcal/entries', 'base_path' => 'forcal/entries', 'id' => $item['id'], 'func' => $func]);

                ++$counter;
                $entry = new ResultEntry();
                if ($counter == 1) {
                    $entry->setLegend('forCal');
                }

                if (isset($item['name_1'])) {
                    $date = new \DateTime($item['start_date']);
                    $forcal_start_date = $date->format('d.m.Y');

                    $entry->setValue($item['name_1'] . ' | '.$forcal_start_date.' - '.$item['type'], '(' . $item['id'] . ')');
                } else {
                    $entry->setValue($item['id']);
                }
                $entry->setIcon('fa-calendar-o');
                $entry->setUrl($url);
                $entry->setQuickLookUrl($url);

                $result->addEntry($entry);
            }
        }
        return $result;
    }
}
