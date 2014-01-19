<?php


/**
 * site themes utilites
 */

abstract class themeUtils {


    /**
     * build and return available themes
     */

     public static function getAvailableThemes($current = null) {

        $themes = array();
        $themesPath = APPLICATION . 'layouts/themes/*';
        foreach (fsUtils::glob($themesPath) as $theme) {
            $name   = basename($theme);
            $option = array(
                'description' => $name,
                'value'       => $name,
                'selected'    => ($current !== null and $current == $name)
            );
            array_push($themes, $option);
        }
        return $themes;

     }


}


