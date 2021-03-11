<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Manager;


use forCal\Handler\forCalDatabaseFieldsetHandler;
use forCal\Utils\forCalColumnHelper;
use forCal\Utils\forCalDefinitions;

class forCalDatabaseManager
{
    /**
     * @author Joachim Doerr
     * @internal param $definitionFile
     */
    public static function executeCustomFieldHandle()
    {
        foreach (forCalDefinitions::getDefinitions() as $table => $definition) {
            if ($definition['cached'] === true) continue;

            $create = array();
            $update = array();

            foreach ($definition['data'] as $fieldsetKey => $fieldset) {
                switch ($fieldsetKey) {
                    case 'langfields':
                        $fields = forCalDatabaseFieldsetHandler::handleLangDatabaseFieldset($fieldset, $table);
                        $create = array_merge($create, $fields['create']);
                        $update = array_merge($update, $fields['update']);
                        break;
                    case 'fields':
                        $fields = forCalDatabaseFieldsetHandler::handleDatabaseFieldset($fieldset, $table);
                        $create = array_merge($create, $fields['create']);
                        $update = array_merge($update, $fields['update']);
                        break;
                }
            }

            if (sizeof($create) > 0) {
                forCalColumnHelper::addColumnsToTable($create, $table);
            }
            if (sizeof($update) > 0) {
                forCalColumnHelper::changeColumnsInTable($update, $table);
            }
        }
    }

    /**
     * @author Joachim Doerr
     */
    public static function executeAddLangFields()
    {
        foreach (forCalDefinitions::getTables() as $table) {
            forCalColumnHelper::addColumnsToTable(forCalColumnHelper::getAllLangColumns($table), $table);
        }
    }
}