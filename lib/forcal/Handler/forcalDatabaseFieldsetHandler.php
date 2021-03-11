<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Handler;


use rex_clang;
use forCal\Utils\forCalColumnHelper;

class forCalDatabaseFieldsetHandler
{
    /**
     * @param array|null $fieldset
     * @param $table
     * @author Joachim Doerr
     * @return array
     */
    public static function handleDatabaseFieldset($fieldset, $table)
    {
        $columns = forCalColumnHelper::getColumns($table);
        $create = array();
        $update = array();
        $select = array();

        if (is_array($fieldset)) {
            foreach ($fieldset as $field) {
                if (array_key_exists('panel', $field)) {
                    foreach ($field['fields'] as $panelField) {
                        $result = self::handleDatabaseField($panelField, $columns);
                        if (array_key_exists('create', $result)) {
                            $create[] = $result['create'];
                        }
                        if (array_key_exists('update', $result)) {
                            $update[] = $result['update'];
                        }
                        if (array_key_exists('select', $result)) {
                            $select[] = $result['select'];
                        }
                    }
                } else {
                    $result = self::handleDatabaseField($field, $columns);
                    if (array_key_exists('create', $result)) {
                        $create[] = $result['create'];
                    }
                    if (array_key_exists('update', $result)) {
                        $update[] = $result['update'];
                    }
                    if (array_key_exists('select', $result)) {
                        $select[] = $result['select'];
                    }
                }
            }
        }

        return array('update' => $update, 'create' => $create, 'select' => $select);
    }

    /**
     * @param $field
     * @param $columns
     * @return array
     * @author Joachim Doerr
     */
    private static function handleDatabaseField($field, $columns)
    {
        if (array_key_exists('type', $field) && array_key_exists('name', $field)) {
            // for now we check only the type
            if ($column = forCalColumnHelper::isInColumnList($columns, $field['name'])) {
                if (strpos(strtolower($field['type']), strtolower($column['Type'])) !== false) {
                    return array(
                        'select' => $field['name']
                    );
                } else {
                    return array(
                        'update' => array('Field' => $field['name'], 'Type' => self::switchColumnType($field['type'])),
                        'select' => $field['name']
                    );
                }
            } else {
                return array('create' => array('Field' => $field['name'], 'Type' => self::switchColumnType($field['type'])));
            }
        }
        return array('false');
    }

    /**
     * @param array|null $fieldset
     * @param $table
     * @author Joachim Doerr
     * @return array
     */
    public static function handleLangDatabaseFieldset($fieldset, $table)
    {
        $newFieldset = array();
        if (is_array($fieldset)) {
            foreach ($fieldset as $field) {
                if (array_key_exists('panel', $field)) {
                    foreach ($field['fields'] as $panelField) {
                        foreach (rex_clang::getAll() as $clang) {
                            if (array_key_exists('name', $panelField)) {
                                $newField         = $panelField;
                                $newField['name'] = $panelField['name'].'_'.$clang->getId();
                                $newFieldset[]    = $newField;
                            }
                        }
                    }
                }
                foreach (rex_clang::getAll() as $clang) {
                    if (array_key_exists('name', $field)) {
                        $newField         = $field;
                        $newField['name'] = $field['name'].'_'.$clang->getId();
                        $newFieldset[]    = $newField;
                    }
                }
            }
        }

        return self::handleDatabaseFieldset($newFieldset, $table);
    }

    /**
     * @param $type
     * @return string
     * @author Joachim Doerr
     */
    private static function switchColumnType($type)
    {
        switch ($type) {
            case 'varchar':
                return 'VARCHAR(255) NULL';
                break;
            case 'bool':
                return 'BOOL NOT NULL DEFAULT 0';
                break;
            case 'id':
                return 'INT(11) unsigned NOT NULL auto_increment';
                break;
            case 'date':
                return 'DATETIME NULL';
            case 'number':
            case 'int':
            case 'prio':
                return 'INT(11) NOT NULL';
                break;
            case 'text area':
            case 'markup':
            case 'select':
            case 'text':
            default:
                return 'TEXT NULL';
                break;
        }
    }
}
