<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;


class forCalHelper
{
    /**
     * @param $arr
     * @param $col
     * @param int $dir [SORT_ASC, SORT_DESC]
     * @author Joachim Doerr
     */
    public static function arraySortByColumn(&$arr, $col, $dir = SORT_ASC)
    {
        if (is_string($dir)) {
            switch ($dir) {
                case 'SORT_ASC':
                    $dir = SORT_ASC;
                    break;
                case 'SORT_DESC':
                    $dir = SORT_DESC;
                    break;
            }
        }

        $sortCol = array();
        foreach ($arr as $key => $row) {
            if (is_array($row) && !array_key_exists($col, $row)) {
                $row[$col] = NULL;
            }
            $sortCol[$key] = $row[$col];
        }
        array_multisort($sortCol, $dir, $arr);
    }

}