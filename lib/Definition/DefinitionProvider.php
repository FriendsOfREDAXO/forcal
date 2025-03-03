<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */
namespace Definition;

use rex_file;
use rex_string;

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
        
        // Stellt sicher, dass das Cache-Verzeichnis existiert
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }
        
        // Find all files by schema
        $ymlFiles = glob($path . $fileSchema); // find all files
        
        // Use last modification date for cache key
        $lastModifications = array_map(function ($f) {
            return filemtime($f);
        }, $ymlFiles);
        
        // Set cache keys
        $cacheKey = md5(sprintf("%s:%s", __CLASS__, $fileSchema)) . '.' . md5(implode('.', $lastModifications));
        $cacheFile = $cachePath . '/' . $cacheKey . '.cache';
        
        // Load from cache if available and not expired
        if (file_exists($cacheFile)) {
            $cacheTime = filemtime($cacheFile);
            if ((time() - $cacheTime) < $cacheTTL) {
                $definition = rex_file::getCache($cacheFile);
                
                if ($isCached) {
                    return array(
                        'cached' => true,
                        'cache_key' => $cacheKey,
                        'data' => $definition
                    );
                }
                return $definition;
            }
        }
        
        // Parse YAML files using rex_string::yamlDecode
        $parsedContents = array_map(function ($f) {
            $content = rex_file::get($f);
            return rex_string::yamlDecode($content);
        }, $ymlFiles);
        
        // Merge definitions by parsed contents
        $definition = self::mergeParsedContents($parsedContents, $mergeHandler);
        
        // Save cache
        rex_file::putCache($cacheFile, $definition);
        
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
        
        if (empty($parsedContents)) {
            return [];
        }
        
        return call_user_func_array('array_merge_recursive', $parsedContents);
    }
}
