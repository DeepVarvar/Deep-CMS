<?php



/**
 * components autorun class
 */

abstract class autorun {


    const RUN_BEFORE = true, RUN_AFTER = false;

    public static function runBefore() {
        self::init(self::RUN_BEFORE);
    }

    public static function runAfter() {
        self::init(self::RUN_AFTER);
    }

    private static function init($type = autorun::RUN_BEFORE) {

        $config = app::config();
        $autorunDirectory = ($type == self::RUN_BEFORE)

            ? $config->path->autorun_before
            : $config->path->autorun_after;

        $autorunDirectory = APPLICATION . $autorunDirectory;
        set_include_path(
            get_include_path() . PATH_SEPARATOR . $autorunDirectory
        );

        $autorunItems = utils::glob("{$autorunDirectory}*.php");
        natsort($autorunItems);

        foreach ($autorunItems as $item) {

            $component = basename($item, ".php");
            $action = "run";

            if (!method_exists($component, $action)) {
                throw new systemErrorException(
                    "Autoload error",
                        "Impossible execute {$component}::{$action}()"
                );
            }

            $checkMethod = new ReflectionMethod($component, $action);

            if (!$checkMethod->isPublic()) {
                throw new systemErrorException(
                    "Autoload error",
                        "Method {$action}() on $component is not public"
                );
            }

            if (!$checkMethod->isStatic()) {
                throw new systemErrorException(
                    "Autoload error",
                        "Method {$action}() on $component is not static"
                );
            }

            call_user_func(array($component, $action));

        }

    }


}



