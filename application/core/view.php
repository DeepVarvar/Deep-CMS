<?php


/**
 * global view class
 */

abstract class view {


    /**
     * memory usage of application
     */

    private static $memory;


    /**
     * timestamp of start application
     */

    private static $timer;


    /**
     * all available output contexts
     */

    private static $availableOutputContexts = array();


    /**
     * current output context type
     */

    private static $outputContext = null;


    /**
     * default structure of XSD-schema for XML generation
     */

    private static $defaultXSDSchema = array('name' => 'response');


    /**
     * current XSD-schema for XML generation
     */

    private static $XSDSchema = array('name' => 'response');


    /**
     * XML doctype, now supported only SYSTEM
     */

    private static $docType = null;


    /**
     * lockable output context status
     */

    private static $lockedOutputContext = false;


    /**
     * output layout
     */

    private static $layout = null;


    /**
     * assigned protected vars for output layout
     */

    private static $protectedVars = array();


    /**
     * assigned public vars for output
     */

    private static $vars = array();


    /**
     * current value of language name
     */

    private static $currentLanguageName = null;


    /**
     * language
     */

    public static $language = array();


    /**
     * loaded components list for language
     */

    private static $loadedComponents = array();


    /**
     * maybe add language file before loading component
     */

    public static function addLoadedComponent($className) {

        $newComponent = !in_array($className, self::$loadedComponents);
        $isLoadedLang = (self::$currentLanguageName !== null);
        if ($isLoadedLang and $newComponent) {

            $lang = APPLICATION . 'languages/'
                . self::$currentLanguageName . '/' . $className . '.php';

            if (is_file($lang)) {
                self::$loadedComponents[] = $className;
                self::$language = array_merge(
                    (array) self::$language, (require $lang)
                );
                self::$language = (object) self::$language;
            }

        } else if ($newComponent) {
            self::$loadedComponents[] = $className;
        }

    }


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

        if ($name != self::$currentLanguageName) {

            $languageDir = APPLICATION . 'languages/' . $name . '/';
            self::$language = array();

            $componentsList = array();
            foreach (self::$loadedComponents as $langFile) {
                $langPath = $languageDir . $langFile . '.php';
                if (is_file($langPath)) {
                    $componentsList[] = $langFile;
                    self::$language = array_merge(
                        self::$language, (require $langPath)
                    );
                }
            }

            self::$loadedComponents    = $componentsList;
            self::$currentLanguageName = $name;
            self::$language            = (object) self::$language;

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
                unexpectedException::take($e, $isDebugMode);
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
                        $report['title']   = view::$language->app_error . ' 404';
                        $report['message'] = view::$language->app_page_not_found;
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
            unexpectedException::take($e, $isDebugMode);
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
                        $raw = textPlainOutput::buildString(self::$vars);
                        $layout = 'layouts/system/raw.html';
                    break;

                    case 'json':
                        request::addHeader('Content-Type: application/json');
                        $raw = json_encode(self::$vars);
                        $layout = 'layouts/system/raw.html';
                    break;

                    case 'xml':
                        request::addHeader('Content-Type: application/xml');
                        $raw = xmlOutput::buildXMLString(self::$vars, self::$XSDSchema, self::$docType);
                        $layout = 'layouts/system/raw.html';
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
            exit('Recursive exception..');
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
                $f = APPLICATION . 'cache/' . $outputContext . '---' . md5($URL) . '.cache';
                file_put_contents($f, $layoutContent, LOCK_EX);
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
     * return javascript environment variables
     */

    public static function getJsEnvironmentVariables() {

        $c = app::config();
        return 'var variables = ' . json_encode(array(
            'language'         => member::getLanguage(),
            'admin_tools_link' => $c->site->admin_tools_link,
            'admin_resources'  => $c->site->admin_resources
            //'session_name'     => session_name(),
            //'session_id'       => session_id()
        )) . ', language = ' . json_encode(self::$language) . ';';

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


