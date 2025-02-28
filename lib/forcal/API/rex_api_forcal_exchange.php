<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

class rex_api_forcal_exchange extends rex_api_function
{
    /**
     * @var bool
     */
    protected $published = true;

    /**
     * This method have to be overriden by a subclass and does all logic which the api function represents.
     *
     * In the first place this method may retrieve and validate parameters from the request.
     * Afterwards the actual logic should be executed.
     *
     * This function may also throw exceptions e.g. in case when permissions are missing or the provided parameters are invalid.
     *
     * @return rex_api_result The result of the api-function
     */
    public function execute()
    {
        rex_response::cleanOutputBuffers();
        rex_response::sendContentType('application/json');

        // Benutzer-Berechtigung berücksichtigen
        $useUserPermissions = true;
        
        // Wenn der Benutzer die Option "alle anzeigen" gewählt hat, ignorieren wir die Benutzerfilter
        if (rex_request::get('show_all', 'boolean', false)) {
            $useUserPermissions = false;
        }

        if (rex_request::get('id', 'int', 0) > 0) {
            $entry = \forCal\Handler\forCalHandler::exchangeEntry(
                rex_request('id'),
                rex_request('short','boolean', false),
                rex_request('date_format','string',1),
                rex_request('time_format','string',1)
            );
            rex_response::setStatus(rex_response::HTTP_OK);
            rex_response::sendContent(json_encode($entry));

            exit;
        }

        $page_number = rex_request('page','integer',null);
        $page_size = rex_request('page_size','integer',null);
        
        // ÄNDERUNG: Verbesserte Verarbeitung von Kategorie-Filtern
        $category = null;
        
        // Prüfen, ob Kategorie-Parameter vorhanden sind
        if (rex_request::hasArgs()) {
            $requestParams = rex_request::requestArray();
            
            // Nach 'category' als Array oder Einzel-Parameter suchen
            if (isset($requestParams['category']) && is_array($requestParams['category'])) {
                $category = $requestParams['category'];
            } elseif (isset($requestParams['category']) && !empty($requestParams['category'])) {
                // Komma-getrennte Liste verarbeiten
                $categoryStr = rex_request('category', 'string', '');
                if (strpos($categoryStr, ',') !== false) {
                    $category = explode(',', $categoryStr);
                } else {
                    $category = $categoryStr;
                }
            }
        }
        
        // Debug-Log für die Fehlerbehebung
        if (rex::isDebugMode()) {
            error_log('Kategorie-Filter: ' . print_r($category, true));
        }

        $entries = \forCal\Handler\forCalHandler::exchangeEntries(
            rex_request('start','string',''),
            rex_request('end','string',''),
            rex_request('short','boolean', true),
            rex_request('ignore_status', 'boolean', false),
            rex_request('sort', 'string', 'SORT_ASC'),
            $category,   // Der Kategorie-Filter mit verbesserter Verarbeitung
            rex_request('venue','integer',null),
            rex_request('date_format','string',1),
            rex_request('time_format','string',1),
            $page_size,
            $page_number,
            $useUserPermissions
        );

        if (is_int($page_size)) {
            rex_response::setHeader('Total-Count', \forCal\Handler\forCalHandler::$pageCount);
            rex_response::setHeader('Total-Pages', \forCal\Handler\forCalHandler::$numberOfPages);
        }
        rex_response::setStatus(rex_response::HTTP_OK);
        rex_response::sendContent(json_encode($entries));

        exit;
    }
}
