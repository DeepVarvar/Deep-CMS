<?php



/**
 * comments component helper class
 */

abstract class commentsHelper {

    public static function getEnvironment() {

        $urls = db::normalizeQuery(
            "SELECT t1.page_alias m, t2.page_alias c FROM tree t1
                LEFT JOIN tree t2
                    ON (t2.module_name = 'captcha' AND t2.is_publish = 1)
                WHERE t1.module_name = 'comments'
                    AND t1.is_publish = 1 GROUP BY t1.id LIMIT 1"

        );

        if (!$urls) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->comments_mod_is_disabled
            );
        }

        if (!$urls['c']) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->captcha_mod_is_disabled
            );
        }

        return $urls;

    }

}



