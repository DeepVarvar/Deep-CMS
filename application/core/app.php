<?php


/**
 * application class
 */

abstract class app {


    /**
     * configs array
     */

    protected static $configs = array();


    /**
     * load config,
     * return config object
     */

    public static function loadConfig($name = null, $isArray = false) {

        if ($name === null) {
            $name = 'main.json';
        }

        return array_key_exists($name, self::$configs)
            ? self::$configs[$name] : self::reloadConfig($name, $isArray);

    }


    /**
     * reload and build config,
     * return config object
     */

    public static function reloadConfig($name = null, $isArray = false) {

        if ($name === null) {
            $name = 'main.json';
        }

        $configDir = APPLICATION . 'config/';
        $generatedConfig = $configDir . $name . '.generated';

        $config = file_exists($generatedConfig)
            ? $generatedConfig : $configDir . $name;

        if (!is_readable($config)) {
            exit("Configuration file $config don't have readable permission" . PHP_EOL);
        }

        if (!$configData = self::loadJsonFile($config, $isArray)) {
            exit('Configuration file ' . $config
                    . ' is broken or have syntax error' . PHP_EOL);
        }

        self::$configs[$name] = $configData;
        return self::$configs[$name];

    }


    /**
     * recursive merged config data
     */

    private static function mergeConfigData( & $conf, $data) {

        foreach ($data as $k => $item) {
            if (isset($conf->{$k})) {
                if (is_array($item)) {
                    self::mergeConfigData($conf->{$k}, $item);
                } else {
                    $conf->{$k} = $item;
                }
            }
        }

    }


    /**
     * update/rebuild config
     */

    public static function changeConfig($name, $newData) {

        if (!array_key_exists($name, self::$configs)) {
            exit('Application [' . $name . '] cofiguration is not loaded' . PHP_EOL);
        }
        self::mergeConfigData(self::$configs[$name], $newData);

    }


    /**
     * write config into generated file
     */

    public static function saveConfig($name) {

        if (!array_key_exists($name, self::$configs)) {
            exit('Application [' . $name . '] cofiguration is not loaded' . PHP_EOL);
        }
        $configString = json_encode(self::$configs[$name]);
        file_put_contents(
            APPLICATION . 'config/' . $name . '.generated',
            $configString, LOCK_EX
        );

    }


    /**
     * return config object or exit,
     * set default if not set custom
     */

    public static function config($name = null) {

        if ($name === null) {
            $name = 'main.json';
        }
        if (!array_key_exists($name, self::$configs)) {
            exit('Application [' . $name . '] cofiguration is not loaded' . PHP_EOL);
        }
        return self::$configs[$name];

    }


    /**
     * write log file,
     * fucking windows can't use ":" for timestamp
     */

    public static function writeLog($item) {

        $existsLog = false;
        $logDir    = APPLICATION . 'logs/';
        $logFile   = $logDir . 'main.log';

        if (file_exists($logFile)) {

            $existsLog = true;
            if (!is_writable($logFile)) {
                exit(
                    "Log file $logFile don't have writable permission" . PHP_EOL
                );
            }

            if (filesize($logFile) > self::config()->system->log_file_max_size) {
                $fixedName = str_replace(
                    array(':', ' '), array('.', '_'), $item['datetime']
                );
                rename($logFile, $logDir . 'main_' . $fixedName . '.log');
                $existsLog = false;
            }

        }

        $item = json_encode(arrayUtils::arrayChangeKeyCaseRecursive($item));
        file_put_contents(
            $logFile, ($existsLog?",\n":'') . $item, FILE_APPEND | LOCK_EX
        );

    }


    /**
     * load and parse json file
     */

    public static function loadJsonFile($filePath, $isArray = false) {

        $patterns = array('~/\*.+?\*/~s', '~\s+?//.+\r?\n~');
        $fileData = file_get_contents($filePath);
        $fileData = preg_replace($patterns, '', $fileData);

        return json_decode($fileData, $isArray);

    }


}


