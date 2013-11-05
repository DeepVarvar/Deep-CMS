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
     * validate theme format and collection of files
     */

    public static function validateTheme($theme) {


        /**
         * required directories and files for theme,
         * each and check required directories and files for theme
         */

        $required = array(

            "parts"     => array("header.html", "footer.html"),
            "protected" => array("exception.html"),
            "public"    => array("page.html")

        );

        foreach ($required as $dir => $files) {

            $path = $theme . $dir;
            if (!file_exists($path)) {

                throw new memberErrorException(
                    view::$language->error,
                        view::$language->required_directory_not_found
                            . ": {$path}"
                );

            }

            if (!is_dir($path)) {

                throw new memberErrorException(
                    view::$language->error,
                        view::$language->path_is_not_a_directory
                            . ": {$path}"
                );

            }

            foreach ($files as $name) {

                $file = $path . "/" . $name;
                if (!is_file($file)) {

                    throw new memberErrorException(
                        view::$language->error,
                            view::$language->required_file_not_found
                                . ": {$file}"
                    );

                }

            }

        }


    }


    /**
     * return options array
     */

    public static function makeOptionsArray($inputArr, $value = null) {

        $options = array();
        foreach ($inputArr as $item) {

            $option = array("value" => $item, "description" => $item);
            if ($value == $item) {
                $option['selected'] = true;
            }

            array_push($options, $option);

        }

        return $options;

    }


    /**
     * normalize URL string
     */

    public static function normalizeInputUrl($url, $errorMessage) {


        if ($url and $url != "/") {

            $patterns = array("/['\"\\\]+/", "/[-\s]+/");
            $replace  = array("", "-");
            $url = substr(preg_replace($patterns, $replace, $url), 0, 255);

            $domain = "(?P<domain>(?:(?:f|ht)tps?"
                            . ":\/\/[-a-z0-9]+(?:\.[-a-z0-9]+)*)?)";

            $path   = "(?P<path>(?:[^\?]*)?)";
            $params = "(?P<params>(?:\?[^=&]+=[^=&]+(?:&[^=&]+=[^=&]+)*)?)";
            $hash   = "(?P<hash>(?:#.*)?)";

            preg_match("/^{$domain}\/{$path}{$params}{$hash}$/s", $url, $m);

            if (!$m) {

                throw new memberErrorException(
                    view::$language->error, $errorMessage
                );

            }

            $cParts = array();
            $sParts = trim(preg_replace("/\/+/", "/", $m['path']), "/");

            foreach (explode("/", $sParts) as $part) {
                array_push($cParts, rawurlencode($part));
            }

            $m['path'] = "/" . join("/" , $cParts);

            $confDomain = app::config()->site->domain;
            if ($m['domain'] and stristr($confDomain, $m['domain'])) {
                $m['domain'] = "";
            }

            if ($m['params'] and $m['domain']) {

                $cParts = array();
                $sParts = trim(preg_replace("/&+/", "&", $m['params']), "&");

                foreach (explode("&", $sParts) as $part) {
                    array_push($cParts, rawurlencode($part));
                }

                $m['params'] = "?" . join("&" , $cParts);

            } else {
                $m['params'] = "";
            }

            if ($m['hash']) {
                $m['hash'] = rawurlencode(trim($m['hash'], "#"));
            }

            $url = $m['domain'] . $m['path'] . $m['params'] . $m['hash'];

        }

        return $url;


    }


    /**
     * get default field element array
     */

    public static function getDefaultField($value) {

        return array(

            "top"         => 0,
            "sort"        => 0,
            "required"    => false,
            "editor"      => 0,
            "description" => "Unnamed text field",
            "type"        => "text",
            "selector"    => "f" . md5(mt_rand() . microtime(true)),
            "value"       => $value

        );

    }


    /**
     * check for exists protected layout with layout name
     */

    public static function isExistsProtectedLayout($name) {

        $c = app::config();
        return file_exists(
            APPLICATION . $c->layouts->themes
                . $c->site->theme . "/protected/" . $name
        );

    }


    /**
     * return array list of available public layouts
     */

    public static function getAvailablePublicLayouts() {

        $c = app::config();
        $layouts = array();

        $layoutsPath = $c->layouts->themes
            . $c->site->theme . "/" . $c->layouts->public;

        $layoutsPath = APPLICATION . $layoutsPath . "*.html";

        foreach (self::glob($layoutsPath) as $item) {
            array_push($layouts, basename($item));
        }

        return $layouts;

    }


    /**
     * return array list of available
     * frequency values for sitemap (SEO)
     */

    public static function getAvailableChangeFreq() {
        return array(
            "---","never","yearly","monthly","weekly","daily","hourly","always"
        );
    }


    /**
     * return array list of available
     * priority range values for sitemap (SEO)
     */

    public static function getAvailableSearchPriority() {
        return array(
            "---","0.1","0.2","0.3","0.4","0.5","0.6","0.7","0.8","0.9","1.0"
        );
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

        $langGlobDir = self::glob($langPath, GLOB_ONLYDIR | GLOB_NOSORT);
        foreach ($langGlobDir as $language) {

            $language = basename($language);
            if (!preg_match("/^[a-z-]+$/", $language)) {

                throw new systemErrorException(
                    "Language error",
                        "Unexpected name $name"
                );

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
     * check for exists and callable action
     */

    public static function checkAllow($c, & $action, & $argument = null) {


        /**
         * check for exists action
         */

        if (!method_exists($c, $action)) {


            /**
             * check for available overloading
             */

            if (method_exists($c, "__call")) {

                $argument = $action;
                $action = "__call";

            } else {

                throw new systemErrorException(
                    "Controller error",
                        "Action $action of $c controller not found"
                );

            }

        }


        /**
         * check for callable action
         */

        $checkMethod = new ReflectionMethod($c, $action);
        if (!$checkMethod->isPublic()) {

            throw new systemErrorException(
                "Controller error",
                    "Method $action of $c controller is not public"
            );

        }


    }


    /**
     * recursive glog function
     */

    public static function globRecursive($path, $mask = "*") {

        $items = self::glob($path . $mask);
        $dirs = self::glob($path . "*", GLOB_ONLYDIR | GLOB_NOSORT);

        foreach ($dirs as $dir) {

            $items = array_merge(
                $items, self::globRecursive($dir . "/", $mask)
            );

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

        $existsTargets = self::globRecursive(
            APPLICATION . app::config()->path->modules, "*.php"
        );

        $existsTargets = array_merge(
            $existsTargets,
            self::globRecursive(
                APPLICATION . app::config()->path->admin, "*.php"
            )
        );

        foreach ($existsTargets as $item) {

            $name = basename($item, ".php");
            node::loadController($item, $name);
            array_push($controllers, node::call($name));

        }

        return $controllers;


    }


    /**
     * check permission access for action of controller,
     * auto check permissions method
     */

    public static function checkPermissionAccess($controller, $action) {

        $permissions = node::call($controller)->getPermissions();
        self::initCheckPermissionAccess($permissions, $action);

    }


    /**
     * check permission worker
     */

    public static function initCheckPermissionAccess($cp, $action) {


        $memberPermissions = member::getPermissions();
        foreach ($cp as $item) {

            if ($item['action'] == $action) {

                foreach ($memberPermissions as $allowed) {
                    if ($allowed['name'] == $item['permission']) {
                        return true;
                    }
                }

                throw new memberErrorException(
                    403,
                        view::$language->error,
                            view::$language->action_denied
                );

            }

        }


    }


    /**
     * recursive change array key case
     */

    public static function
        arrayChangeKeyCaseRecursive($arr, $type = CASE_LOWER) {

        foreach ($arr as $k => $item) {

            if (is_array($item)) {
                $arr[$k] = self::arrayChangeKeyCaseRecursive($item);
            }

        }

        return array_change_key_case($arr, $type);

    }


    /**
     * write log file,
     * fucking windows can't use ":" for timestamp
     */

    public static function writeLog($item) {


        $existsLog = false;
        $config = app::config();

        $logDir = APPLICATION . $config->path->logs;
        $logFile = $logDir . "main.log";

        if (file_exists($logFile)) {

            $existsLog = true;
            if (!is_writable($logFile)) {

                exit(
                    "Log file $logFile don't have writable permission"
                        . PHP_EOL
                );

            }

            if (filesize($logFile) > $config->system->log_file_max_size) {

                $fixedName = str_replace(
                    array(":", " "), array(".", "_"), $item['datetime']
                );

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
     * unexpected exception wrapper,
     * exit application
     */

    public static function takeUnexpectedException($e) {


        if ($e instanceof systemException) {

            $report = $e->getReport();
            $config = app::config();
            if ($config->system->debug_mode) {
                dump($report);
            } else {
                echo "Unexpected system {$report['type']} "
                        . "exception inside catch context" . PHP_EOL;
            }

        } else {

            if ($config->system->debug_mode) {
                dump($e->getMessage(), $e->getTrace());
            } else {
                echo "Unexpected exception inside catch context" . PHP_EOL;
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


    /**
     * this function exists only for autoload call
     * and compatible php version older than 5.2.3
     */

    public static function loadSortArrays() {}


}


/**
 * sort arrays callback,
 * use function because need compatible
 * for php versions older than 5.2.3
 */

function sortArrays($a, $b) {

    return $a['sort'] == $b['sort']
        ? 0 : ($a['sort'] < $b['sort'] ? -1 : 1);

}



