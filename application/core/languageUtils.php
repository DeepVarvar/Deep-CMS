<?php


/**
 * language utilites
 */

abstract class languageUtils {


    /**
     * build and return available languages list
     */

    public static function getAvailableLanguages($current = null) {

        $languages = array();
        foreach (self::getLanguagePaths() as $language) {

            $language = basename($language);
            if (!preg_match('/^[a-z-]+$/', $language)) {
                throw new systemErrorException(
                    'Language error',
                    'Unexpected name ' . $name
                );
            }

            $option = array(
                'description' => $language,
                'value'       => $language,
                'selected'    => ($current == $language)
            );

            array_push($languages, $option);

        }

        return $languages;

    }


    /**
     * return available languages path's
     */

    public static function getLanguagePaths() {
        $langPath = APPLICATION . 'languages/*';
        return fsUtils::glob($langPath, GLOB_ONLYDIR | GLOB_NOSORT);
    }


}


