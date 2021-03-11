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

        $fields = ['name_1', 'start_date', 'teaser_1', 'start_time', 'end_time', 'end_time','type', ];

        $sql_query = '
       SELECT      * 
       FROM       ' . Watson::getTable('forcal_entries') . ' 
       WHERE       ' . $command->getSqlWhere($fields) . ' 
       ORDER BY   start_time DESC';

        $items = $this->getDatabaseResults($sql_query);

        if (count($items))
        {
            $counter = 0;

            foreach ($items as $item)
            {

                $url = Watson::getUrl(['page' => 'forcal/entries', 'base_path' => 'forcal/entries', 'id' => $item['id'], 'func' => 'edit']);

                ++$counter;
                $entry = new ResultEntry();
                if ($counter == 1)
                {
                    $entry->setLegend('forCal');
                }

                if (isset($item['name_1']))
                {


                   $date = new \DateTime($item['start_date']);
                   $forcal_start_date =  $date->format('d.m.Y');

                    $entry->setValue($item['name_1'] . ' | '.$forcal_start_date.' - '.$item['type'], '(' . $item['id'] . ')');
                }
                else
                {
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
