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

    // http://redaxo5/index.php?rex-api-call=forcal_range_exchange&test=123

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

        /*
        if (\forCal\Handler\forCalApi::isTokenValid() !== true) {
            rex_response::setHeader('status', 401);
            rex_response::sendContent('');
            exit;
        }
        */

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

        $entries = \forCal\Handler\forCalHandler::exchangeEntries(
            rex_request('start','string',''),
            rex_request('end','string',''),
            rex_request('short','boolean', true),
            rex_request('ignore_status', 'boolean', false),
            rex_request('sort', 'string', 'SORT_ASC'),
            rex_request('category','string',null),
            rex_request('venue','integer',null),
            rex_request('date_format','string',1),
            rex_request('time_format','string',1),
            $page_size,
            $page_number
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
