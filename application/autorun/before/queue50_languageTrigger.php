<?php


/**
 * language trigger
 */

abstract class queue50_languageTrigger {

    public static function run() {

        $siteConfig = app::config()->site;
        $url = request::getURI();
        $adminLink = preg_quote($siteConfig->admin_tools_link, '/');
        $isAdminLink = preg_match('/^' . $adminLink . '(\/.+)?$/s', $url);

        if (!$isAdminLink) {

            if ($url == '/' . $siteConfig->default_language) {
                request::redirect('/');
            }

            $langPath  = APPLICATION . 'languages/*';
            $languages = array();
            foreach (fsUtils::glob($langPath, GLOB_ONLYDIR | GLOB_NOSORT) as $item) {
                $languages[] = basename($item);
            }

            $languages = join('|', $languages);
            if (preg_match('/^\/(' . $languages . ')(?:\/.+)?/s', $url, $m)) {
                $lang = $m[1];
            } else {
                $lang = $siteConfig->default_language;
            }

            storage::write('lang', $lang);
            view::setLanguage($lang);

        }

    }

}


