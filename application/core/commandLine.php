<?php



/**
 * commandLine class,
 * use for CLI mode
 */

abstract class commandLine {


    protected static


        /**
         * status of CLI mode
         */

        $isCLI;


    /**
     * CLI mode initialization
     */

    public static function init() {
        $config = app::config();
        exit("Sorry, {$config->application->name} {$config->application->version} don't support CLI mode" . PHP_EOL);
    }


    /**
     * return status of CLI mode
     */

    public static function isCLI() {
        return self::$isCLI;
    }


}



