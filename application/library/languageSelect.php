<?php


/**
 * language select helper
 */

abstract class languageSelect {


    public static function getLanguages() {
        return languageUtils::getAvailableLanguages();
    }


}


