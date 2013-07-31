<?php



/**
 * application class,
 * now exists only for config files
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

    public static function loadConfig($name = null) {


        /**
         * set default if not set custom
         */

        if ($name === null) {
            $name = "main.json";
        }


        return array_key_exists($name, self::$configs)
            ? self::$configs[$name] : self::reloadConfig($name);


    }


    /**
     * reload and build config,
     * return config object
     */

    public static function reloadConfig($name = null) {


        /**
         * set default if not set custom
         */

        if ($name === null) {
            $name = "main.json";
        }


        /**
         * get config
         */

        $generatedConfig = CONFIG . "{$name}.generated";
        $config = file_exists($generatedConfig) ? $generatedConfig : CONFIG . $name;


        if (!file_exists($config)) {
            exit("Configuration file $config not found" . EOL);
        }

        if (!is_readable($config)) {
            exit("Configuration file $config don't have readable permission" . EOL);
        }

        $configData = file_get_contents($config);

        $patterns = array("~/\*.+?\*/~s", "~//.+\\r?\\n~");
        $configData = preg_replace($patterns, "", $configData);

        if (!$configData = @ json_decode($configData)) {
            exit("Configuration file $config is broken or have syntax error" . EOL);
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
            exit("Application [{$name}] cofiguration is not loaded" . EOL);
        }

        self::mergeConfigData(self::$configs[$name], $newData);

    }


    /**
     * write config into generated file
     */

    public static function saveConfig($name) {


        if (!array_key_exists($name, self::$configs)) {
            exit("Application [{$name}] cofiguration is not loaded" . EOL);
        }


        $configString = json_encode(self::$configs[$name]);
        file_put_contents(CONFIG . "{$name}.generated", $configString);


    }


    /**
     * return config object or exit
     */

    public static function config($name = null) {


        /**
         * set default if not set custom
         */

        if ($name === null) {
            $name = "main.json";
        }


        if (!array_key_exists($name, self::$configs)) {
            exit("Application [{$name}] cofiguration is not loaded" . EOL);
        }

        return self::$configs[$name];


    }


}



