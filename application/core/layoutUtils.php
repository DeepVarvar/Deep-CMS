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

        $theme = app::config()->site->theme;
        return is_file(APPLICATION . 'layouts/themes/' . $theme . '/protected/' . $name);

    }


}


