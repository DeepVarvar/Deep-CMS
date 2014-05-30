<?php


abstract class moduleHelper {


    private static $knownURLs = array();


    public static function getURL($moduleName) {

        if (!array_key_exists($moduleName, self::$knownURLs)) {

            $moduleUrl = db::normalizeQuery(
                "SELECT page_alias FROM tree
                    WHERE prototype = 'mainModule' AND module_name = '%s'
                    LIMIT 1", $moduleName
            );
            if (!$moduleUrl) {
                $message  = view::$language->module_helper_module; 
                $message .= ' ' . $moduleName . ' ';
                $message .= view::$language->module_helper_is_disabled; 
                throw new memberErrorException(
                    view::$language->module_helper_error, $message
                );
            }

            self::$knownURLs[$moduleName] = $moduleUrl;

        }

        return self::$knownURLs[$moduleName];

    }


}


