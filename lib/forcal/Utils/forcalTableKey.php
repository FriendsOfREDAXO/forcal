<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;


class forCalTableKey
{

    /**
     * @param $name
     * @return string
     */
    public static function getTableShortKey($name)
    {
        return substr(self::getTableFullKey($name), 0, 2);
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function getTableFullKey($name)
    {
        $array = explode('_', $name);
        return array_pop($array);
    }
}