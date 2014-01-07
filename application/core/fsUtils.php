<?php


/**
 * filesystem utilites
 */

abstract class fsUtils {


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
     * recursive glog function
     */

    public static function globRecursive($path, $mask = "*") {

        $items = self::glob($path . $mask);
        $dirs = self::glob($path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        foreach ($dirs as $dir) {
            $items = array_merge(
                $items, self::globRecursive($dir . '/', $mask)
            );
        }
        return $items;

    }


    /**
     * clear all cached files
     */

    public static function clearMainCache() {

        foreach (self::glob(APPLICATION . 'cache/*') as $item) {
            if (is_file($item)) {
                unlink($item);
            }
        }

    }


}


