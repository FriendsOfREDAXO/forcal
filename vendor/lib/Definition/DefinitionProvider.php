<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace Definition;


use Doctrine\Common\Cache\FilesystemCache;
use Symfony\Component\Yaml\Parser;

class DefinitionProvider
{
    const CACHE_TTL = 345600; // 48h
    const CACHE_PATH = '%s/.cache';

    /**
     * @param $fileSchema
     * @param $path
     * @param null $cachePath
     * @param null $mergeHandler
     * @param bool $isCached
     * @param int $cacheTTL
     * @return false|mixed
     * @author Joachim Doerr
     */
    public static function load($fileSchema, $path, $cachePath = null, $mergeHandler = null, $isCached = false, $cacheTTL = self::CACHE_TTL)
    {
        if (is_null($cachePath)) {
            $cachePath = $path;
        }
        $cachePath = sprintf(self::CACHE_PATH, $cachePath);
        $cache = new FilesystemCache($cachePath);
        // find all files by schema
        $ymlFiles = glob($path . $fileSchema); // find all files
        // use last modification date for cache key
        $lastModifications = array_map(function ($f) {
            return filemtime($f);
        }, $ymlFiles);
        // set cache keys
        $cacheKey = md5(sprintf("%s:%s", __CLASS__, $fileSchema)) . '.' . md5(implode('.', $lastModifications));
        // load from cache
        if ($definition = $cache->fetch($cacheKey)) {
            if ($isCached) {
                return array(
                    'cached' => true,
                    'cache_key' => $cacheKey,
                    'data' => $definition
                );
            }
            return $definition;
        }
        // parse yml
        $parser = new Parser();
        $parsedContents = array_map(function ($f) use ($parser) {
            return $parser->parse(file_get_contents($f));
        }, $ymlFiles);
        // merge definitions by parsed contents
        $definition = self::mergeParsedContents($parsedContents, $mergeHandler);
        // save cache
        $cache->save($cacheKey, $definition, $cacheTTL);
        if ($isCached) {
            return array(
                'cached' => false,
                'cache_key' => $cacheKey,
                'data' => $definition
            );
        }
        return $definition;
    }

    /**
     * @param array $parsedContents
     * @param DefinitionMergeInterface|null $mergeHandler
     * @return mixed
     * @author Joachim Doerr
     */
    public static function mergeParsedContents(array $parsedContents, $mergeHandler = null)
    {
        if ($mergeHandler instanceof DefinitionMergeInterface) {
            return $mergeHandler::merge($parsedContents);
        }
        return call_user_func_array('array_merge_recursive', $parsedContents);
    }
}