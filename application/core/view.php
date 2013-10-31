<?php



/**
 * global view class
 */

abstract class view {


    protected static


        /**
         * memory usage of application
         */

        $memory,


        /**
         * timestamp of start application
         */

        $timer,


        /**
         * all available output contexts
         */

        $availableOutputContexts = array(),


        /**
         * current output context type
         */

        $outputContext = null,


        /**
         * default structure of XSD-schema for XML generation
         */

        $defaultXSDSchema = array("name" => "response"),


        /**
         * current XSD-schema for XML generation
         */

        $XSDSchema = null,


        /**
         * XML doctype, now supported only SYSTEM
         */

        $docType = null,


        /**
         * lockable output context status
         */

        $lockedOutputContext = false,


        /**
         * output layout
         */

        $layout = null,


        /**
         * assigned protected vars for output layout
         */

        $protectedVars = array(),


        /**
         * assigned public vars for output
         */

        $vars = array(),


        /**
         * current value of language name
         */

        $currentLanguageName = null;


    /**
     * language
     */

    public static $language = array();


    /**
     * intialization
     */

    public static function init($memory, $timestart) {


        /**
         * set memory usage and start timer
         */

        self::$memory = $memory;
        self::$timer  = $timestart;


        /**
         * get main configuration
         */

        $config = app::config();


        /**
         * set default environment
         */

        self::setOutputContext($config->system->default_output_context);
        self::setLanguage($config->site->default_language);


        /**
         * set default XSD schema
         */

        self::setXSDSchema(self::$defaultXSDSchema);


    }


    /**
     * set XDS-schema array structure for XML output context
     */

    public static function setXSDSchema($schema) {

        utils::validateXmlSchemaElement($schema);
        self::$XSDSchema = $schema;

    }


    /**
     * set output context type
     */

    public static function setOutputContext($type) {

        if (self::$lockedOutputContext) {
            throw new systemErrorException("View error", "Attempt change locked output context");
        }

        if (!in_array($type, self::$availableOutputContexts)) {
            throw new systemErrorException("View error", "Unavailable output context");
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

        return sizeof(self::$availableOutputContexts);

    }


    /**
     * set DOCTYPE of XML output
     */

    public static function setXMLDocType($qName, $systemId) {
        self::$docType = array("name" => $qName, "id" => $systemId);
    }


    /**
     * load language environment
     */

    public static function setLanguage($name) {


        $config = app::config();
        $languageDir = APPLICATION . "{$config->path->languages}{$name}";
        $cachedLang  = APPLICATION . "{$config->path->cache}{$name}_lang";

        if (file_exists($cachedLang)) {
            self::$language = unserialize(file_get_contents($cachedLang));
        } else if ($name != self::$currentLanguageName and is_dir($languageDir)) {

            foreach (utils::glob($languageDir . "/*.php") as $lang) {

                self::$language = array_merge(
                    self::$language,
                    (require_once $lang)
                );

            }

            self::$currentLanguageName = $name;
            self::$language = (object) self::$language;

            file_put_contents($cachedLang, serialize(self::$language), LOCK_EX);


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

        if (self::$outputContext == "html" and self::$layout === null) {
            throw new memberErrorException("View error", "Layout file is not set");
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
                throw new systemErrorException("View error", 'Assign method expects: view::assign("name", $var);');
            break;


        }


        if ($toPublic) {
            self::$vars = array_merge(self::$vars, $data);
        } else {
            self::$protectedVars = array_merge(self::$protectedVars, $data);
        }


    }


    /**
     * assign exception report data into extracted vars for layout,
     * choose layout to exception
     */

    public static function assignException($e) {


        try {


            if (!($e instanceof systemException)) {
                utils::takeUnexpectedException($e);
            } else {


                $report = $e->getReport();
                $config = app::config();


                /**
                 * reset XSD-schema for XML output context,
                 * delete all public vars from output
                 */

                self::setXSDSchema(self::$defaultXSDSchema);
                self::$vars = array();


                /**
                 * set refresh location
                 */

                if (isset($report['refresh_location'])) {
                    self::assignProtected("refresh_location", $report['refresh_location']);
                }


                /**
                 * select layout for exception
                 * with exception instance or mode
                 */

                if ($config->system->debug_mode) {

                    self::setLayout($config->layouts->debug);
                    self::assign("exception", $report);

                } else {


                    self::setLayout($config->layouts->exception);


                    if ($e instanceof systemErrorException) {

                        $report['code'] = 404;
                        $report['title'] = view::$language->error . " 404";
                        $report['message'] = view::$language->page_not_found;

                    }


                    /**
                     * assign based report data
                     */

                    $basedReport = array(

                        "code"       => 0,
                        "type"       => "error",
                        "title"      => "Untitled based report",
                        "message"    => "Undescription based report"

                    );

                    foreach ($basedReport as $k => $v) {
                        $basedReport[$k] = $report[$k];
                    }

                    $basedReport['page_title'] = $report['title'];
                    $basedReport['page_name']  = $report['title'];


                    self::assign("exception", $basedReport);

                    if (self::getOutputContext() == "html") {
                        self::assign($basedReport);
                    }


                }


                if ($report['code'] == 404 and self::getOutputContext() != "json") {
                    request::addHeader( $_SERVER['SERVER_PROTOCOL'] . " 404 Not Found" );
                }


            }


        } catch (Exception $e) {
            utils::takeUnexpectedException($e);
        }


    }


    /**
     * draw layout
     */

    public static function draw() {


        /**
         * get configuration environment
         */

        $config = app::config();


        /**
         * assign member data and config into extracted protected vars
         */

        self::assignProtected("_member", member::getProfile());
        self::assignProtected("_config", $config);


        /**
         * tryed draw layout for tries range
         */

        foreach (range(1, 2) as $try) {


            /**
             * exception wrapper for layout context
             */

            try {


                /**
                 * get current output context
                 */

                $outputContext = self::getOutputContext();


                /**
                 * buffered silent output
                 */

                ob_start();


                /**
                 * build output context
                 */

                switch ($outputContext) {


                    case "txt":


                        /**
                         * build text plain output
                         */

                        request::addHeader("Content-Type: text/plain");

                        $txt = textPlainOutput::buildString(self::$vars);
                        $layout = $config->layouts->txt;


                    break;


                    case "json":


                        /**
                         * build JSON output
                         */

                        request::addHeader("Content-Type: application/json");

                        $json = json_encode(self::$vars);
                        $layout = $config->layouts->json;


                    break;


                    case "xml":


                        /**
                         * build XML output
                         */

                        request::addHeader("Content-Type: application/xml");

                        $xml = xmlOutput::buildXMLString(self::$vars, self::$XSDSchema, self::$docType);
                        $layout = $config->layouts->xml;


                    break;


                    default:


                        /**
                         * add utf-8 header
                         */

                        request::addHeader("Content-Type: text/html; charset=utf-8");


                        /**
                         * normalize page variables
                         * add required environment
                         */

                        self::normalizePageVariables();


                        /**
                         * extract all vars
                         */

                        extract(self::$protectedVars);
                        extract(self::$vars);


                        /**
                         * set layout
                         */

                        $layout = self::$layout;


                        /**
                         * set include path for parts of layouts
                         */

                        $themePath = router::isAdmin()
                            ? $config->layouts->admin
                            : $config->layouts->themes . $config->site->theme . "/";

                        set_include_path(
                            get_include_path() . PATH_SEPARATOR . APPLICATION . $themePath
                        );


                    break;


                }


                /**
                 * require and draw layout
                 */

                require $layout;


                /**
                 * store member data
                 */

                member::storeData();


                /**
                 * run after autorun actions
                 */

                autorun::runAfter();


            } catch (Exception $e) {


                /**
                 * clean silent output,
                 * assign exception
                 */

                ob_clean();
                self::assignException($e);


                /**
                 * goto new draw try
                 */

                continue;


            }


            /**
             * get layout content,
             * clean silent output
             */

            $layoutContent = ob_get_clean();


            /**
             * out of tries range
             */

            break;


        }


        /**
         * cache output content
         */

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

                    APPLICATION . "{$config->path->cache}{$outputContext}---" . md5($URL),
                    $layoutContent,
                    LOCK_EX

                );
            }

        }


        /**
         * flush page
         */

        request::sendHeaders();

        echo $layoutContent;
        exit();


    }


    /**
     * read content from cache
     */

    public static function readFromCache($fileName) {


        /**
         * store member data
         */

        member::storeData();


        /**
         * run after autorun actions
         */

        autorun::runAfter();


        /**
         * get main config settings
         */

        $config = app::config();


        /**
         * check enabled caching mode
         */

        if ($config->system->cache_enabled !== true) {
            throw new systemErrorException("View error", "Caching mode is not enabled");
        }

        $file = APPLICATION . $config->path->cache . $fileName;
        $type = preg_replace("/([a-z]+)---.+/s", "$1", $fileName);


        /**
         * set header with filetype
         */

        switch ($type) {

            case "txt":
                request::addHeader("Content-Type: text/plain");
            break;

            case "json":
                request::addHeader("Content-Type: application/json");
            break;

            case "xml":
                request::addHeader("Content-Type: application/xml");
            break;

            default:
                request::addHeader("Content-Type: text/html; charset=utf-8");
            break;

        }


        /**
         * flush output content
         */

        request::sendHeaders();

        readfile($file);
        exit();


    }


    /**
     * normalize page variables
     */

    public static function normalizePageVariables() {


        /**
         * set required page variables
         */

        $config = app::config();

        $requiredVariables = array(

            "page_text"          => "",
            "image"              => $config->site->no_image,
            "page_name"          => "[module]",
            "page_h1"            => "",
            "page_title"         => "",
            "meta_description"   => $config->site->default_description,
            "meta_keywords"      => $config->site->default_keywords,
            "permanent_redirect" => "",
            "author_id"          => 0,
            "author_name"        => "[module]",
            "page_id"            => 0,
            "parent_id"          => 0,
            "page_is_module"     => "1",

        );

        foreach ($requiredVariables as $key => $value) {

            if (!isset(self::$vars[$key]) or self::$vars[$key] === "") {
                self::$vars[$key] = $value;
            }

        }

        $currentDateTime = date("Y-m-d H:i:s");
        $unchangedVariables = array(

            "page_alias"    => request::getURI(),
            "last_modified" => $currentDateTime,
            "creation_date" => $currentDateTime,
            "layout"        => self::getCurrentLayout(),

        );

        foreach ($unchangedVariables as $key => $value) {

            if (self::$vars['page_is_module'] or !self::$vars[$key]) {
                self::$vars[$key] = $value;
            }

        }


        /**
         * dependency values for h1 and title of document
         */

        if (!self::$vars['page_h1']) {
            self::$vars['page_h1'] = self::$vars['page_name'];
        }

        if (!self::$vars['page_title']) {
            self::$vars['page_title'] = self::$vars['page_h1'];
        }


    }


    /**
     * return Refresh Metadata for current layout
     */

    public static function getRefreshMetaData() {

        if (array_key_exists("refresh_location", self::$protectedVars)) {
            return '<meta http-equiv="Refresh" content="2; url=' . self::$protectedVars['refresh_location'] . '">';
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



