<?php


/**
 * global member login attempt runner
 */

abstract class queue100_globalMemberLoginAttempt {


    public static function run() {

        $URI = request::getURI();
        if ($URI == '/logout') {
            member::flushLogout();
        } else if (

            $URI != app::config()->site->admin_tools_link
            and request::isPost() and member::isAttemptLogin()) {
            if (!member::logged()) {
                throw new memberErrorException(
                    view::$language->app_error,
                    view::$language->app_login_or_pass_bad
                );
            }

            request::sameOriginRedirect();

        }

    }


}


