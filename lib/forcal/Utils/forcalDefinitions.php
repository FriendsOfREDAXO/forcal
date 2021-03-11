<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;


use Definition\DefinitionProvider;
use rex;
use rex_addon;
use rex_file;

class forCalDefinitions
{
    const DATA_PATH = 'data/definitions/';
    const CACHE_PATH = 'definitions/';

    /**
     * @return array
     * @author Joachim Doerr
     */
    public static function getTables()
    {
        return array(
            'categories' => rex::getTablePrefix() . "forcal_categories",
            'entries' => rex::getTablePrefix() . "forcal_entries",
            'venues' => rex::getTablePrefix() . "forcal_venues"
        );
    }

    /**
     * @author Joachim Doerr
     * @param string $file
     * @return string
     */
    public static function definitionPath($file = '')
    {
        return rex_addon::get('forcal')->getDataPath('definitions/' . $file);
    }

    /**
     * @return array
     * @author Joachim Doerr
     */
    public static function getDefinitionFiles()
    {
        $definitionFiles = array();

        foreach (forCalDefinitions::getTables() as $key => $value) {
            if (!file_exists(self::definitionPath($key . '.yml'))) {
                if (file_exists(rex_addon::get('forcal')->getPath(self::DATA_PATH . $key . '.yml'))) {
                    if (!rex_file::copy(rex_addon::get('forcal')->getPath(self::DATA_PATH . $key . '.yml'), rex_addon::get('forcal')->getDataPath('definitions/' . $key . '.yml'))) {
                        continue;
                    }
                } else {
                    continue;
                }
            }
            if (file_exists(self::definitionPath('custom_'.$key . '.yml')))
            {
            $definitionFiles[$value] = self::definitionPath('custom_'.$key . '.yml');
            }
            else
            {
            $definitionFiles[$value] = self::definitionPath($key . '.yml');
            }

        }
        return $definitionFiles;
    }

    /**
     * @return array
     * @author Joachim Doerr
     */
    public static function getDefinitions()
    {
        $definitions = array();
        foreach (forCalDefinitions::getDefinitionFiles() as $key => $definitionFile) {
            $definitions[$key] = DefinitionProvider::load(
                '/' . pathinfo($definitionFile, PATHINFO_BASENAME), pathinfo($definitionFile, PATHINFO_DIRNAME),
                rex_addon::get('forcal')->getCachePath(self::CACHE_PATH),
                null,
                true
            );
        }
        return $definitions;
    }

    /**
     * @param $key
     * @return mixed|null
     * @author Joachim Doerr
     */
    public static function getDefinition($key)
    {
        $definitions = self::getDefinitions();
        if (array_key_exists($key, $definitions)) {
            return $definitions[$key];
        }
        return null;
    }
}
