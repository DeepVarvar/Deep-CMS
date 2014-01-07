<?php


/**
 * global view class
 */

abstract class view {


    /**
     * memory usage of application
     */

    protected static $memory;


    /**
     * timestamp of start application
     */

    protected static $timer;


    /**
     * all available output contexts
     */

    protected static $availableOutputContexts = array();


    /**
     * current output context type
     */

    protected static $outputContext = null;


    /**
     * default structure of XSD-schema for XML generation
     */

    protected static $defaultXSDSchema = array('name' => 'response');


    /**
     * current XSD-schema for XML generation
     */

    protected static $XSDSchema = array('name' => 'response');


    /**
     * XML doctype, now supported only SYSTEM
     */

    protected static $docType = null;


    /**
     * lockable output context status
     */

    protected static $lockedOutputContext = false;


    /**
     * output layout
     */

    protected static $layout = null;


    /**
     * assigned protected vars for output layout
     */

    protected static $protectedVars = array();


    /**
     * assigned public vars for output
     */

    protected static $vars = array();


    /**
     * current value of language name
     */

    protected static $currentLanguageName = null;


    /**
     * language
     */

    public static $language = array();


    /**
     * intialization
     */

    public static function init($memory, $timestart) {

        self::$memory = $memory;
        self::$timer  = $timestart;

        $config = app::config();
        self::setOutputContext($config->system->default_output_context);
        self::setLanguage($config->site->default_language);
        storage::write('nodeID', -1);

    }


    /**
     * set XDS-schema array structure for XML output context
     */

    public static function setXSDSchema($schema) {
        xmlValidator::validateXmlSchemaElement($schema);
        self::$XSDSchema = $schema;
    }


    /**
     * set output context type
     */

    public static function setOutputContext($type) {

        if (self::$lockedOutputContext) {
            throw new systemErrorException(
                'View error', 'Attempt change locked output context'
            );
        }
        if (!in_array($type, self::$availableOutputContexts)) {
            throw new systemErrorException(
                'View error', 'Unavailable output context'
            );
        }
        self::$outputContext = $type;

    }


    /**
     * get output context type
     */

    public static function getOutputContext() {
        return self::$outputContext;
    }


    /**
     * disable change output context
     */

    public static function lockOutputContext() {
        self::$lockedOutputContext = true;
    }


    /**
     * enable change output context
     */

    public static function unlockOutputContext() {
        self::$lockedOutputContext = false;
    }


    /**
     * save available output contexts array
     */

    public static function getAvailableOutputContexts() {

        foreach ((array) app::config()->output_contexts as $context) {
            if ($context->enabled === true) {
                array_push(self::$availableOutputContexts, $context->name);
            }
        }
        return self::$availableOutputContexts;

    }


    /**
     * set DOCTYPE of XML output
     */

    public static function setXMLDocType($qName, $systemId) {
        self::$docType = array('name' => $qName, 'id' => $systemId);
    }


    /**
     * load language environment
     */

    public static function setLanguage($name) {

        $config = app::config();
        $languageDir = APPLICATION . 'languages/' . $name;
        $cachedLang  = APPLICATION . 'cache/' . $name . '_lang';

        if ($config->system->cache_enabled and file_exists($cachedLang)) {

            self::$language = unserialize(
                file_get_contents($cachedLang)
            );

        } else if ($name != self::$currentLanguageName and is_dir($languageDir)) {

            self::$language = array();
            foreach (utils::glob($languageDir . '/*.php') as $lang) {
                self::$language = array_merge(
                    self::$language, (require_once $lang)
                );
            }

            self::$currentLanguageName = $name;
            self::$language = (object) self::$language;
            if ($config->system->cache_enabled) {
                file_put_contents(
                    $cachedLang, serialize(self::$language), LOCK_EX
                );
            }

        }

    }


    /**
     * set file of layout
     */

    public static function setLayout($path) {
        self::$layout = $path;
    }


    /**
     * return name of layout
     */

    public static function getCurrentLayout() {
        return self::$layout;
    }


    /**
     * check layout
     */

    public static function checkLayout() {

        if (self::$outputContext == 'html' and self::$layout === null) {
            throw new memberErrorException(
                'View error', 'Layout file is not set'
            );
        }

    }


    /**
     * assign data into extracted protected vars for layout
     */

    public static function assignProtected($item, $i = null) {
        self::assignData($item, $i);
    }


    /**
     * assign data into extracted public vars for layout
     */

    public static function assign($item, $i = null) {
        self::assignData($item, $i, true);
    }


    /**
     * choose assign data to
     */

    private static function assignData($item, $i = null, $toPublic = false) {

        $data = array();
        switch (true) {

            case (!is_array($item) and $i !== null):
                $data[$item] = $i;
            break;
            case (is_array($item) and $i === null):
                $data = $item;
            break;
            case (is_object($item) and $i === null):
                $data = array($item);
            break;
            default:
                throw new systemErrorException(
                    'View error',
                    'Assign method expects: view::assign("name", $var);'
                );
            break;

        }

        if ($toPublic) {
            self::$vars = array_merge(self::$vars, $data);
        } else {
            self::$protectedVars = array_merge(self::$protectedVars, $data);
        }

    }


    /**
     * clear all public variables from view output
     */

    public static function clearPublicVariables() {
        self::$vars = array();
    }


    /**
     * assign exception report data into extracted vars for layout,
     * choose layout to exception
     */

    public static function assignException($e, $isDebugMode = false) {

        try {

            if (!($e instanceof systemException)) {
                utils::takeUnexpectedException($e, $isDebugMode);
            } else {

                $report     = $e->getReport();
                $XSDSchema  = self::$defaultXSDSchema;
                self::$vars = array();

                if (isset($report['refresh_location'])) {
                    self::assignProtected(
                        'refresh_location', $report['refresh_location']
                    );
                }

                if ($isDebugMode) {
                    self::setLayout('layouts/system/debug.html');
                    self::assign('exception', $report);
                } else {

                    self::setLayout('protected/exception.html');
                    if ($e instanceof systemErrorException) {
                        $report['code']    = 404;
                        $report['title']   = view::$language->error . ' 404';
                        $report['message'] = view::$language->page_not_found;
                    }

                    $basedReport = array(
                        'code'    => 0,
                        'type'    => 'error',
                        'title'   => 'Untitled based report',
                        'message' => 'Undescription based report'
                    );

                    foreach ($basedReport as $k => $v) {
                        $basedReport[$k] = $report[$k];
                    }

                    $basedReport['page_title'] = $report['title'];
                    $basedReport['node_name']  = $report['title'];

                    if (isset($report['refresh_location'])) {
                        $basedReport['refresh_location'] = $report['refresh_location'];
                    }

                    self::assign('exception', $basedReport);
                    if (self::getOutputContext() == 'html') {
                        self::assign($basedReport);
                    }

                }

                if ($report['code'] == 404 and self::getOutputContext() != 'json') {
                    request::addHeader(
                        $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found'
                    );
                }

            }

        } catch (Exception $e) {
            utils::takeUnexpectedException($e, $isDebugMode);
        }

    }


    /**
     * draw layout
     */

    public static function draw() {

        $config = app::config();
        self::assignProtected('_member', member::getProfile());
        self::assignProtected('_config', $config);

        foreach (range(1, 2) as $try) {

            try {

                $outputContext = self::getOutputContext();
                ob_start();

                switch ($outputContext) {

                    case 'txt':
                        request::addHeader('Content-Type: text/plain');
                        $txt = textPlainOutput::buildString(self::$vars);
                        $layout = 'layouts/system/txt.html';
                    break;

                    case 'json':
                        request::addHeader('Content-Type: application/json');
                        $json = json_encode(self::$vars);
                        $layout = 'layouts/system/json.html';
                    break;

                    case 'xml':
                        request::addHeader('Content-Type: application/xml');
                        $xml = xmlOutput::buildXMLString(self::$vars, self::$XSDSchema, self::$docType);
                        $layout = 'layouts/system/xml.html';
                    break;

                    default:

                        request::addHeader('Content-Type: text/html; charset=utf-8');
                        self::normalizePageVariables();
                        extract(self::$protectedVars);
                        extract(self::$vars);

                        $layout = self::$layout;
                        $themePath = router::isAdmin()
                            ? 'layouts/admin/'
                            : 'layouts/themes/' . $config->site->theme . '/';

                        set_include_path(
                            get_include_path() . PATH_SEPARATOR . APPLICATION . $themePath
                        );

                    break;

                }

                require $layout;
                member::storeData();
                autorun::runAfter();

            } catch (Exception $e) {

                ob_clean();
                self::assignException($e, $config->system->debug_mode);
                continue;

            }

            $layoutContent = ob_get_clean();
            break;

        }

        if (!isset($layoutContent)) {
            exit('Recursive exception..' . PHP_EOL);
        }

        if ($config->system->cache_enabled === true) {

            $URL = request::getOriginURL();
            $match = false;

            foreach ($config->cached_pages as $pattern) {
                if (preg_match($pattern, $URL)) {
                    $match = true;
                    break;
                }
            }

            if ($match) {
                file_put_contents(
                    APPLICATION . 'cache/' . $outputContext . '---' . md5($URL),
                    $layoutContent,
                    LOCK_EX
                );
            }

        }

        request::sendHeaders();
        echo $layoutContent;
        exit();

    }


    /**
     * read content from cache
     */

    public static function readFromCache($fileName) {

        member::storeData();
        autorun::runAfter();

        $file = APPLICATION . 'cache/' . $fileName;
        $type = preg_replace('/([a-z]+)---.+/s', '$1', $fileName);
        switch ($type) {
            case 'txt':
                request::addHeader('Content-Type: text/plain');
            break;
            case 'json':
                request::addHeader('Content-Type: application/json');
            break;
            case 'xml':
                request::addHeader('Content-Type: application/xml');
            break;
            default:
                request::addHeader('Content-Type: text/html; charset=utf-8');
            break;
        }

        request::sendHeaders();
        readfile($file);
        exit();

    }


    /**
     * normalize page variables
     */

    public static function normalizePageVariables() {

        $config = app::config();
        $requiredVariables = array(

            'last_modified'      => db::normalizeQuery('SELECT NOW()'),
            'layout'             => self::$layout,
            'id'                 => 0,
            'node_name'          => '[This page is not a node]',
            'page_text'          => '',
            'page_h1'            => '',
            'page_title'         => '',
            'meta_description'   => $config->site->default_description,
            'meta_keywords'      => $config->site->default_keywords,

            'pages'              => array(),
            'number_of_items'    => 0,
            'number_of_pages'    => 1,
            'current_page'       => 1

        );

        foreach ($requiredVariables as $key => $value) {
            if (!isset(self::$vars[$key]) or self::$vars[$key] === '') {
                self::$vars[$key] = $value;
            }
        }

        if (!self::$vars['page_h1']) {
            self::$vars['page_h1'] = self::$vars['node_name'];
        }
        if (!self::$vars['page_title']) {
            self::$vars['page_title'] = self::$vars['page_h1'];
        }

    }


    /**
     * return Refresh Metadata for current layout
     */

    public static function getRefreshMetaData() {

        if (array_key_exists('refresh_location', self::$protectedVars)) {
            return '<meta http-equiv="Refresh" content="2; url='
                . self::$protectedVars['refresh_location'] . '">';
        }

    }


    /**
     * return initialized timer value
     */

    public static function getInitializedTimerValue() {
        return self::$timer;
    }


    /**
     * return initialized memry usage value
     */

    public static function getInitializedMemoryValue() {
        return self::$memory;
    }


}


