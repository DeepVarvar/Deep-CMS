<?php


/**
 * request environment,
 * get and set headers, redirect, refresh
 */

abstract class request {


    /**
     * full URL, with $_GET parameters
     */

    protected static $rawURL = null;


    /**
     * only URI, without $_GET parameters
     */

    protected static $uri = '/';


    /**
     * $_GET parameters
     */

    protected static $params = array();


    /**
     * current page number
     */

    protected static $currentPage = null;


    /**
     * stack of responsed headers
     */

    protected static $headers = array();


    /**
     * client info
     */

    protected static $client = array();


    /**
     * initialization
     */

    public static function init() {

        if (app::config()->system->block_prefetch_requests
                and isset($_SERVER['HTTP_X_MOZ'])
                and $_SERVER['HTTP_X_MOZ'] == 'prefetch') {

            self::$headers = array();
	        self::addHeader('HTTP/1.1 403 Prefetching Forbidden');
	        self::addHeader('Expires: Thu, 21 Jul 1977 07:30:00 GMT');
	        self::addHeader('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	        self::addHeader('Cache-Control: post-check=0, pre-check=0');
	        self::addHeader('Pragma: no-cache');
            self::sendHeaders();
	        exit();

        }


        /**
         * maybe this need
         * Content-Length bug fix on php 5.4
         * see more information:
         * http://www.exploit-db.com/exploits/18665/
         *
         * long request
         * i'm expected request string maximum of 2048 bytes length
         */

        if (strlen($_SERVER['REQUEST_URI']) > 2048) {
            $_SERVER['REQUEST_URI'] = '';
            throw new systemErrorException(
                'Request error', 'Request string too long'
            );
        }

        // base64
        if (stristr($_SERVER['REQUEST_URI'], 'data:')) {
            throw new systemErrorException(
                'Request error', 'Base64 data found on request URI'
            );
        }

        // double slash
        if (strstr($_SERVER['REQUEST_URI'], '//')) {
            throw new systemErrorException(
                'Request error', 'Double slash found on request URI'
            );
        }

        // bad spaces
        if (preg_match('/(%20)+$/', $_SERVER['REQUEST_URI'])) {
            throw new systemErrorException(
                'Request error', 'Bad spaces on request URI'
            );
        }


        /**
         * save request string,
         * clear REQUEST_URI value and GET array
         */

        $source = $_SERVER['REQUEST_URI'];
        $_SERVER['REQUEST_URI'] = '';
        $_GET = array();


        /**
         * I don't like the combination of "/?" in the URL,
         * but like after the "action" immediately "?"
         */

        $destination = rtrim(
            preg_replace('/([^\/=\?&]+)\/(\?)/', '$1$2', $source), '/'
        );

        if (!$destination) {
            $destination = '/';
        }

        if ($destination != $source) {
            self::redirect($destination);
        }


        /**
         * validate request string format,
         * get request parameters
         *
         * $mca - Module Controller Action
         * $gp  - GET parameters
         *
         * $parts['mca'] example: module/controller/action
         * $parts['gp'] example: agr1=val1&arg2&argN=valN
         */

        $parts = array();
        $mca   = '\/(?P<mca>[^\/=\?&]+(?:(?:\/[^\/=\?&]+)+)?)?';
        $gp    = '(?:\?(?P<gp>[^\/=\?&]+(?:=[^\/=\?&]*)';
        $gp   .= '?(?:(?:&[^\/=\?&]+(?:=[^\/=\?&]*)?)+)?))?';

        if (!preg_match('/^' . $mca . $gp . '$/u', $destination, $parts)) {
            throw new systemErrorException(
                'Request error', 'Broken query string format'
            );
        }

        if (!isset($parts['mca'])) {
            $parts['mca'] = '';
        }

        $withoutFirstPage = preg_replace(
            '/^(.+)(?:(?:page=1&(.+))|(?:\?|&)page=1$)/', '$1$2', $destination
        );

        if ($withoutFirstPage != $destination) {
            self::redirect($withoutFirstPage);
        }

        self::$rawURL = $destination;
        self::$uri = '/' . $parts['mca'];

        foreach (explode('/', $parts['mca']) as $param) {
            router::pushParam(rawurldecode($param));
        }

        if (isset($parts['gp'])) {
            self::storeGETParams($parts['gp']);
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            view::setOutputContext('json');
        }

    }


    /**
     * store GET parameters only as STRING!
     * you known parse_str and parse_url functions?
     * but it's broken functions for SEO optimization,
     * need correct stored GET params
     */

    public static function storeGETParams($str) {

        $params = explode('&', $str);
        if (!is_array($params)) {
            $params = array();
        }

        foreach ($params as $param) {

            @ list($key, $value) = explode('=', $param);
            if (!$key = rawurldecode($key)) {
                throw new systemErrorException(
                    'Request error', 'GET key is empty'
                );
            }

            if (preg_match('/^(.+)\[(.*)\]$/u', $key)) {
                throw new systemErrorException(
                    'Request error', 'GET key is array'
                );
            }

            if (isset(self::$params[$key])) {
                throw new systemErrorException(
                    'Request error', 'Duplicate GET keys'
                );
            }

            self::$params[$key] = isset($value) ? rawurldecode($value) : true;

        }

    }


    /**
     * type of request
     */

    public static function isPost() {
        return (sizeof($_POST) > 0);
    }


    /**
     * get single $_POST data
     */

    public static function getPostParam($key) {
        return (array_key_exists($key, $_POST)) ? $_POST[$key] : null;
    }


    /**
     * get multiple required $_POST data
     * or null if not exists one or more parameters
     */

    public static function getRequiredPostParams($required = array()) {

        $data = array();
        foreach ($required as $key) {
            if (!array_key_exists($key, $_POST)) {
                return null;
            }
            $data[$key] = $_POST[$key];
        }
        return (sizeof($data) > 0) ? $data : null;

    }


    /**
     * store client information
     * IP, useragent, referer, etc.
     */

    public static function identifyClient() {

        $keys = array(
            'HTTP_USER_AGENT',
            'HTTP_REFERER',
            'HTTP_ACCEPT',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_ACCEPT_ENCODING'
        );

        foreach ($keys as $v) {
            self::$client[$v] = isset($_SERVER[$v])
                ? $_SERVER[$v] : '[no match]';
        }

        $hcip = getenv('HTTP_CLIENT_IP');
        $hxff = getenv('HTTP_X_FORWARDED_FOR');
        $radd = getenv('REMOTE_ADDR');

        if ($hcip) {
            $ip = $hcip;
        } else if ($hxff) {
            $ip = $hxff;
        } else {
            $ip = false;
        }

        self::$client['IP'] = (!$ip or $ip == 'unknown') ? $radd : $ip;

    }


    /**
     * validate referer
     */

    public static function validateReferer($referer, $useExpression = false) {

        $c = app::config();
        $status = true;

        $currentReferer = isset($_SERVER['HTTP_REFERER'])
            ? strip_tags($_SERVER['HTTP_REFERER']) : '';


        /**
         * chrome 30.0.1599.101 m always send referer without www\.
         * even when it's there..
         */

        $c->site->domain = preg_replace('/^www\./', '', $c->site->domain);
        $currentReferer = preg_replace('/(\/\/)www\./', '$1', $currentReferer);

        if (!$useExpression) {
            $ref = $c->site->protocol . '://' . $c->site->domain . $referer;
            $status = ($ref === $currentReferer);
        } else {
            $ref = '#' . $c->site->protocol . '://'
                . $c->site->domain . $referer . '#';
            $status = (preg_match($ref, $currentReferer));
        }

        if (!$status) {
            throw new memberErrorException(
                view::$language->error, view::$language->referer_invalid_or_csrf
            );
        }

    }


    /**
     * get all client info
     */

    public static function getClientInfo() {
        return self::$client;
    }


    /**
     * get client IP
     */

    public static function getClientIP() {
        return self::$client['IP'];
    }


    /**
     * returned URI without params
     */

    public static function getURI() {
        return self::$uri;
    }


    /**
     * returned $_GET param or null,
     * WARNING! use this method only for CHECK EXISTS PARAMETER!
     * for really have parameter need use shiftParam("key") method!
     * it's need for SEO optimization
     */

    public static function getParam($key) {
        return isset(self::$params[$key]) ? self::$params[$key] : null;
    }


    /**
     * set new GET parameter
     * or change value for exists GET parameter
     */

    public static function setParam($key, $value) {
        self::$params[$key] = (string) $value;
    }


    /**
     * really have parameter
     */

    public static function shiftParam($key) {

        if (!isset(self::$params[$key])) {
            return null;
        } else {
            $value = self::$params[$key];
            unset(self::$params[$key]);
            return $value;
        }

    }


    /**
     * get valid current page from request string
     */

    public static function getCurrentPage() {

        if (self::$currentPage === null) {

            $currentPage = self::getParam("page");
            if ($currentPage !== null) {
                if (!validate::isNumber($currentPage)) {
                    throw new systemErrorException(
                        'Request error', 'Current page is not number'
                    );
                }
                if ($currentPage === '0') {
                    throw new systemErrorException(
                        'Request error', "Current page is can't be zero"
                    );
                }
            }

            self::$currentPage = $currentPage ? request::shiftParam('page') : 1;

        }

        return self::$currentPage;

    }


    /**
     * send headers
     */

    public static function sendHeaders() {

        foreach (self::$headers as $item) {
            header($item);
        }

    }


    /**
     * add custom header
     */

    public static function addHeader($header) {
        array_push(self::$headers, $header);
    }


    /**
     * moved permanently
     */

    public static function redirect($destination) {

        self::$headers = array();
        self::addHeader('HTTP/1.1 301 Moved Permanently');
        self::addHeader('Location: ' . $destination);
        self::sendHeaders();
        exit();

    }


    /**
     * return origin URL
     */

    public static function getOriginURL() {
        return self::$rawURL;
    }


    /**
     * moved permanently to same origin URL
     */

    public static function sameOriginRedirect() {
        self::redirect(self::$rawURL);
    }


    /**
     * check for unused parameters
     * throw when exists unused parameters
     * WARNING! EXPERIMENTAL!
     */

    public static function checkUnusedParams() {

        $check = app::config()->site->check_unused_params;
        if ($check and (router::getParamsCount() or self::$params)) {
            throw new systemErrorException(
                'Request error', 'Request string have unused parameters'
            );
        }

    }


}


