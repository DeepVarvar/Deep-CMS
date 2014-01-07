<?php


/**
 * layouts utilites
 */

abstract class layoutUtils {


    /**
     * return array list of available public layouts
     */

    public static function getAvailablePublicLayouts() {

        $layouts = array();
        $layoutsPath = APPLICATION . 'layouts/themes/'
            . app::config()->site->theme . '/public/*.html';

        foreach (fsUtils::glob($layoutsPath) as $item) {
            array_push($layouts, basename($item));
        }
        return $layouts;

    }


    /**
     * check for exists protected layout with layout name
     */

    public static function isExistsProtectedLayout($name) {

        return file_exists(
            APPLICATION . 'layouts/themes/'
                . app::config()->site->theme . '/protected/' . $name
        );

    }


}


