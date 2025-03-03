<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace Definition;


interface DefinitionMergeInterface
{
    /**
     * @param array $array
     * @return array
     * @author Joachim Doerr
     */
    public static function merge(array $array);
}