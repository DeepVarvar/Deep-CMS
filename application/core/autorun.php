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

        $autorunDirectory = ($type == self::RUN_BEFORE)
            ? 'autorun/before/' : 'autorun/after/';

        $autorunDirectory = APPLICATION . $autorunDirectory;
        set_include_path(
            get_include_path() . PATH_SEPARATOR . $autorunDirectory
        );

        $autorunItems = fsUtils::glob($autorunDirectory . '*.php');
        natsort($autorunItems);

        foreach ($autorunItems as $item) {

            $runner = basename($item, '.php');
            $action = 'run';
            if (!method_exists($runner, $action)) {
                continue;
            }

            $checkMethod = new ReflectionMethod($runner, $action);
            if (!$checkMethod->isPublic() or !$checkMethod->isStatic()) {
                continue;
            }

            call_user_func(array($runner, $action));

        }

    }


}


