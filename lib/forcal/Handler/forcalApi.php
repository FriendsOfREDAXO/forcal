<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Handler;


use rex;
use rex_csrf_token;
use rex_request;

class forCalApi
{
    /**
     * @return string
     * @author Joachim Doerr
     */
    public static function getToken()
    {
        return rex_csrf_token::factory('forcal_api_call')->getValue();
    }

    /**
     * @return bool
     * @author Joachim Doerr
     */
    public static function isTokenValid()
    {
        $valid = rex_csrf_token::factory('forcal_api_call')->isValid();

        if ($valid === false) {
            // token was created in backend and call was executed in frontend
            if (isset($_SESSION[rex::getProperty('instname')]['csrf_tokens_backend']['forcal_api_call'])) {
                return hash_equals($_SESSION[rex::getProperty('instname')]['csrf_tokens_backend']['forcal_api_call'], rex_request::request(rex_csrf_token::PARAM, 'string'));
            }
            // token was created in frontend and call was executed in backend
            // this case is impossible but may be
            if (isset($_SESSION[rex::getProperty('instname')]['csrf_tokens_frontend']['forcal_api_call'])) {
                return hash_equals($_SESSION[rex::getProperty('instname')]['csrf_tokens_frontend']['forcal_api_call'], rex_request::request(rex_csrf_token::PARAM, 'string'));
            }
        }

        return $valid;
    }
}