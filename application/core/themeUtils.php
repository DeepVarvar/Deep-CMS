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
            if (is_dir($theme)) {
                self::validateTheme($theme . '/');
                $name   = basename($theme);
                $option = array(
                    'description' => $name,
                    'value'       => $name,
                    'selected'    => ($current !== null and $current == $name)
                );
                array_push($themes, $option);
            }
        }
        return $themes;

     }


    /**
     * validate theme,
     * each and check required directories and files for theme
     */

    private static function validateTheme($theme) {

        $required = array(
            'parts'     => array('header.html', 'footer.html'),
            'protected' => array('exception.html'),
            'public'    => array('page.html')
        );

        foreach ($required as $dir => $files) {

            $path = $theme . $dir;
            if (!file_exists($path)) {
                throw new memberErrorException(
                    view::$language->theme_utils_error,
                    view::$language->theme_utils_req_dir_not_found . ': ' . $path
                );
            }

            if (!is_dir($path)) {
                throw new memberErrorException(
                    view::$language->theme_utils_error,
                    view::$language->theme_utils_path_is_not_a_dir . ': ' . $path
                );
            }

            foreach ($files as $name) {
                $file = $path . '/' . $name;
                if (!is_file($file)) {
                    throw new memberErrorException(
                        view::$language->theme_utils_error,
                        view::$language->theme_utils_req_file_not_found . ': ' . $file
                    );
                }
            }

        }

    }


}


