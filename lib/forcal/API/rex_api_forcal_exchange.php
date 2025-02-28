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
        
        // Kategorie-Parameter aus der Anfrage auslesen
        $categoryParam = rex_request('category', 'string', null);
        $category = null;
        
        // Wenn der Parameter nicht leer ist, verarbeiten wir ihn
        if (!empty($categoryParam)) {
            // Wenn es ein Komma enthält, ist es eine Liste von Kategorien
            if (strpos($categoryParam, ',') !== false) {
                $category = explode(',', $categoryParam);
            } else {
                // Sonst ist es eine einzelne Kategorie
                $category = $categoryParam;
            }
        }

        $entries = \forCal\Handler\forCalHandler::exchangeEntries(
            rex_request('start','string',''),
            rex_request('end','string',''),
            rex_request('short','boolean', true),
            rex_request('ignore_status', 'boolean', false),
            rex_request('sort', 'string', 'SORT_ASC'),
            $category,
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
