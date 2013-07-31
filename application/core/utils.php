<?php



/**
 * some utilis
 */

abstract class utils {


    /**
     * WARNING! origin PHP function glob() maybe returned FALSE value!
     * but i'm always expected array!
     */

    public static function glob($pattern, $flags = 0) {


        if (!$result = glob($pattern, $flags)) {
            $result = array();
        }

        return $result;


    }


    /**
     * check value for is number,
     * from 0 to +infinity
     */

    public static function isNumber($value) {

        $value = (string) $value;
        if ($value === "0") {
            return true;
        }

        return (substr(trim($value), 0, 1) === "0")
                    ? false : preg_match("/^\d+$/", $value);

    }


    /**
     * check value for like string
     */

    public static function likeString($v) {
        return ($v !== null and !is_array($v) and !is_object($v) and !is_resource($v) and !is_bool($v));
    }

    /**
     * validate theme format and collection of files
     */

    public static function validateTheme($theme) {


        /**
         * required directories and files for theme
         */

        $required = array(

            "parts" => array(
                "header.html",
                "footer.html"
            ),

            "protected" => array(
                "exception.html"
            ),

            "public" => array(
                "page.html"
            )

        );



        /**
         * each and check required directories and files for theme
         */

        foreach ($required as $dir => $files) {


            $path = $theme . $dir;
            if (!file_exists($path)) {
                throw new memberErrorException(view::$language->error, view::$language->required_directory_not_found . ": {$path}");
            }

            if (!is_dir($path)) {
                throw new memberErrorException(view::$language->error, view::$language->path_is_not_a_directory . ": {$path}");
            }


            foreach ($files as $name) {


                $file = $path . "/" . $name;
                if (!file_exists($file)) {
                    throw new memberErrorException(view::$language->error,  view::$language->required_file_not_found . ": {$file}");
                }

                if (!is_file($file)) {
                    throw new memberErrorException(view::$language->error, view::$language->path_is_not_a_file . ": {$file}");
                }



            }


        }


    }


    /**
     * return all available prototypes
     */

    public static function getAvailablePrototypes() {

        return db::cachedQuery("
            SELECT id, sys_name, name
            FROM prototypes ORDER BY id ASC
        ");

    }


    /**
     * check for exists protected layout with layout name
     */

    public static function isExistsProtectedLayout($name) {

        $c = app::config();
        return file_exists(
            APPLICATION . $c->layouts->themes . $c->site->theme . "/protected/" . $name
        );

    }


    /**
     * return array list of available public layouts
     */

    public static function getAvailablePublicLayouts() {


        $c = app::config();
        $layouts = array();

        $layoutsPath = $c->layouts->themes . $c->site->theme . "/" . $c->layouts->public;
        $layoutsPath = APPLICATION . $layoutsPath . "*.html";

        foreach (self::glob($layoutsPath) as $item) {
            array_push($layouts, basename($item));
        }

        return $layouts;


    }


    /**
     * build and return available themes
     */

     public static function getAvailableThemes($current = null) {


        $themes = array();
        $themesPath = APPLICATION . app::config()->layouts->themes . "*";

        foreach (self::glob($themesPath) as $theme) {


            if (is_dir($theme)) {


                self::validateTheme($theme . "/");


                $name = basename($theme);
                $option = array(

                    "description" => $name,
                    "value"       => $name,
                    "selected"    => ($current !== null and $current == $name)

                );


                array_push($themes, $option);


            }


        }


        return $themes;


     }


    /**
     * build and return available languages list
     */

    public static function getAvailableLanguages($current = null) {


        $languages = array();
        $langPath = APPLICATION . app::config()->path->languages . "/*";

        foreach (self::glob($langPath, GLOB_ONLYDIR | GLOB_NOSORT) as $language) {


            $language = basename($language);
            if (!preg_match("/^[a-z-]+$/", $language)) {
                throw new systemErrorException("Language error", "Unexpected name $name");
            }

            $option = array(

                "description" => $language,
                "value"       => $language,
                "selected"    => ($current == $language)

            );


            array_push($languages, $option);


        }


        return $languages;


    }


    /**
     * return available output contexts array
     */

    public static function getAvailableOutputContexts() {


        $contexts = array();
        foreach ((array) app::config()->output_contexts as $context) {

            if ($context->enabled === true) {
                array_push($contexts, $context->name);
            }

        }


        return $contexts;


    }


    /**
     * check for exists and callable action
     */

    public static function checkAllow($controller, & $action, & $argument = null) {


        /**
         * check for exists action
         */

        if (!method_exists($controller, $action)) {


            /**
             * check for available overloading
             */

            if (method_exists($controller, "__call")) {

                $argument = $action;
                $action = "__call";

            } else {
                throw new systemErrorException("Controller error", "Action $action of $controller controller not found");
            }


        }


        /**
         * check for callable action
         */

        $checkMethod = new ReflectionMethod($controller, $action);
        if (!$checkMethod->isPublic()) {
            throw new systemErrorException("Controller error", "Method $action of $controller controller is not public");
        }


    }


    /**
     * recursive glog function
     */

    public static function globRecursive($path, $mask = "*") {


        $items = self::glob($path . $mask);
        $dirs = self::glob($path . "*", GLOB_ONLYDIR | GLOB_NOSORT);

        foreach ($dirs as $dir) {
            $items = array_merge($items, self::globRecursive($dir . "/", $mask));
        }

        return $items;


    }


    /**
     * return array of controllers from all modules and submodules
     */

    public static function getAllControllers() {


        $controllers = array();
        $existsTargets = array();


        /**
         * get from modules,
         * get from admin module if need get all controllers
         */

        $existsTargets = self::globRecursive(APPLICATION . app::config()->path->modules, "*.php");
        $existsTargets = array_merge($existsTargets, self::globRecursive(APPLICATION . app::config()->path->admin, "*.php"));


        foreach ($existsTargets as $item) {

            $name = basename($item, ".php");
            node::loadController($item, $name);
            array_push($controllers, node::call($name));

        }


        return $controllers;


    }


    /**
     * check permission access for action of controller
     */

    public static function checkPermissionAccess($controller, $action) {


        /**
         * auto check permissions
         */

        $permissions = node::call($controller)->getPermissions();
        self::initCheckPermissionAccess($permissions, $action);


    }


    /**
     * check permission worker
     */

    public static function initCheckPermissionAccess($controllerPermissions, $action) {


        $memberPermissions = member::getPermissions();
        foreach ($controllerPermissions as $item) {

            if ($item['action'] == $action) {

                foreach ($memberPermissions as $allowed) {
                    if ($allowed['name'] == $item['permission']) {
                        return true;
                    }
                }

                throw new memberErrorException(403, view::$language->error, view::$language->action_denied);

            }

        }


    }


    /**
     * recursive change array key case
     */

    public static function arrayChangeKeyCaseRecursive($arr, $type = CASE_LOWER) {


        foreach ($arr as $k => $item) {

            if (is_array($item)) {
                $arr[$k] = self::arrayChangeKeyCaseRecursive($item);
            }

        }


        return array_change_key_case($arr, $type);


    }


    /**
     * write log file
     */

    public static function writeLog($item) {


        $existsLog = false;
        $config = app::config();

        $logDir = APPLICATION . $config->path->logs;
        $logFile = $logDir . "main.log";


        if (file_exists($logFile)) {


            $existsLog = true;

            if (!is_writable($logFile)) {
                exit("Log file $logFile don't have writable permission" . EOL);
            }


            if (filesize($logFile) > $config->system->log_file_max_size) {


                /**
                 * fucking windows can't use ":" for timestamp
                 */

                $fixedName = str_replace(array(":", " "), array(".", "_"), $item['datetime']);
                rename($logFile, $logDir . "main_" . $fixedName . ".log");

                $existsLog = false;


            }


        }


        $item = json_encode(self::arrayChangeKeyCaseRecursive($item));
        file_put_contents(
            $logFile, ($existsLog?",\n":"") . $item, FILE_APPEND | LOCK_EX
        );


    }


    /**
     * validate element schema attributes
     */

    public static function validateXmlElementSchemaAttributes($attributes) {


        if (!is_array($attributes)) {
            throw new systemErrorException("Schema XML error", "Attributes of schema element is not array");
        }

        foreach ($attributes as $attribute) {


            /**
             * check one attribute
             */

            if (!is_array($attribute)) {
                throw new systemErrorException("Schema XML error", "Attribute of element is not array");
            }

            if (!array_key_exists("name", $attribute)) {
                throw new systemErrorException("Schema XML error", "Name of attribute not found");
            }

            if (!array_key_exists("value", $attribute)) {
                throw new systemErrorException("Schema XML error", "Name of attribute not found");
            }


        }


    }


    /**
     * validate schema element structure
     */

    public static function validateXmlSchemaElement($schemaElement) {


        /**
         * check element
         */

        if (!is_array($schemaElement)) {
            throw new systemErrorException("Schema XML error", "Schema element is not array");
        }


        /**
         * check name of element
         */

        if (!array_key_exists("name", $schemaElement)) {
            throw new systemErrorException("Schema XML error", "Name of schema element not found");
        }


        /**
         * check attributes of element
         */

        if (array_key_exists("attributes", $schemaElement)) {
            self::validateXmlElementSchemaAttributes($schemaElement['attributes']);
        }


        /**
         * check children section
         * only if exists children key
         */

        $existsChildren = false;
        if (array_key_exists("children", $schemaElement)) {


            /**
             * check children
             */

            if (!is_array($schemaElement['children'])) {
                throw new systemErrorException("Schema XML error", "Children of element is not array");
            }

            foreach ($schemaElement['children'] as $element) {
                self::validateXmlSchemaElement($element);
            }


            /**
             * set exists children flag
             */

            $existsChildren = true;


        }


        /**
         * check value for element
         */

        if (array_key_exists("value", $schemaElement)) {


            if ($existsChildren) {
                throw new systemErrorException("Schema XML error", "Value of schema element can't be declared with children");
            }

            if (!self::likeString($value)) {
                throw new systemErrorException("Schema XML error", "Value of schema element is not string");
            }


        }


    }


    /**
     * unexpected exception wrapper
     */

    public static function takeUnexpectedException($e) {


        if ($e instanceof systemException) {


            /**
             * save report into log file,
             * exit application
             */

            $report = $e->getReport();
            $config = app::config();


            if ($config->system->debug_mode) {
                dump($report);
            } else {
                echo "Unexpected system {$report['type']} exception inside catch context" . EOL;
            }


        } else {


            if ($config->system->debug_mode) {
                dump($e->getMessage(), $e->getTrace());
            } else {
                echo "Unexpected exception inside catch context" . EOL;
            }


        }

    }


    /**
     * clear all cached files
     */

    public static function clearMainCache() {


        $cacheDir = APPLICATION . app::config()->path->cache . "*";
        foreach (self::glob($cacheDir) as $item) {

            if (is_file($item)) {
                unlink($item);
            }

        }


    }


}


