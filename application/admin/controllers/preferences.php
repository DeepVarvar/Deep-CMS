<?php


/**
 * admin submodule, preferences of site
 */

class preferences extends baseController {


    /**
     * set permissions for this controller
     */

    public function setPermissions() {

        $this->permissions = array(
            array(
                'action'      => null,
                'permission'  => 'preferences_manage',
                'description' => view::$language->permission_preferences_manage
            ),
            array(
                'action'      => 'recalculate',
                'permission'  => 'preferences_recalc',
                'description' => view::$language->permission_preferences_recalc
            ),
            array(
                'action'      => 'reset',
                'permission'  => 'preferences_reset',
                'description' => view::$language->permission_preferences_reset
            ),
            array(
                'action'     => 'clear_cache',
                'permission' => 'preferences_clear_cache',
                'description'
                    => view::$language->permission_preferences_clear_cache
            )
        );

    }


    /**
     * view list of all groups
     */

    public function index() {

        if (request::isPost()) {
            return $this->savePreferences();
        }

        $c = app::config();
        view::assign(array(
            'themes'           => themeUtils::getAvailableThemes($c->site->theme),
            'admin_tools_link' => rawurldecode($c->site->admin_tools_link),
            'languages'        => languageUtils::getAvailableLanguages($c->site->default_language),
            'cache_enabled'    => $c->system->cache_enabled,
            'debug_mode_on'    => $c->system->debug_mode
        ));

        view::assign('node_name', view::$language->preferences_title);
        $this->setProtectedLayout('preferences.html');

    }


    /**
     * clear system cache
     */

    public function clear_cache() {

        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer($adminToolsLink . '/preferences');

        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->preferences_success,
            view::$language->preferences_cache_is_cleared,
            $adminToolsLink . '/preferences'
        );

    }


    /**
     * reset preferences
     */

    public function reset() {

        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer($adminToolsLink . '/preferences');

        $generatedConfig = APPLICATION . 'config/main.json.generated';
        if (file_exists($generatedConfig)) {
            unlink($generatedConfig);
        }

        $newConfig = app::reloadConfig();
        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->preferences_success,
            view::$language->preferences_is_reseted,
            $newConfig->site->admin_tools_link . '/preferences'
        );

    }


    /**
     * recalculate permissions of all controllers
     */

    public function recalculate() {

        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer($adminToolsLink . '/preferences');

        recalculatePermissions::run();
        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->preferences_success,
            view::$language->preferences_perms_recalculated,
            $adminToolsLink . '/preferences'
        );

    }


    /**
     * save preferences
     */

    private function savePreferences() {

        request::validateReferer(
            app::config()->site->admin_tools_link . '/preferences'
        );

        $preferences = request::getRequiredPostParams(array('site', 'system'));
        if ($preferences === null) {
            throw new memberErrorException(
                view::$language->preferences_error,
                view::$language->preferences_data_not_enough
            );
        }

        $requiredSystemData = array('cookie_expires_time');
        foreach ($requiredSystemData as $item) {
            if (!array_key_exists($item, $preferences['system'])) {
                throw new memberErrorException(
                    view::$language->preferences_error,
                    view::$language->preferences_data_not_enough
                );
            }
        }

        $requiredSiteData = array(
            'theme',
            'default_language',
            'admin_tools_link',
            'default_keywords',
            'default_description'
        );

        foreach ($requiredSiteData as $item) {
            if (!array_key_exists($item, $preferences['site'])) {
                throw new memberErrorException(
                    view::$language->preferences_error,
                    view::$language->preferences_data_not_enough
                );
            }
        }

        $preferences['system']['debug_mode'] = array_key_exists(
            'debug_mode', $preferences['system']) ? true : false;

        $preferences['system']['cache_enabled'] = array_key_exists(
            'cache_enabled', $preferences['system']) ? true : false;

        $validate = validate::isNumber(
            $preferences['system']['cookie_expires_time']
        );

        if (!$validate) {
            throw new memberErrorException(
                view::$language->preferences_error,
                view::$language->preferences_cookie_exp_need_is_number
            );
        }

        if ($preferences['system']['cookie_expires_time'] >= 2147483646) {
            throw new systemErrorException(
                view::$language->preferences_error,
                view::$language->preferences_cookie_exp_is_long
            );
        }

        if ($preferences['system']['cookie_expires_time'] < 600) {
            throw new systemErrorException(
                view::$language->preferences_error,
                view::$language->preferences_cookie_exp_is_small
            );
        }

        $futureTime = time() + $preferences['system']['cookie_expires_time'];
        if ($futureTime >= 2147483646) {
            throw new systemErrorException(
                view::$language->preferences_error,
                view::$language->preferences_cookie_exp_is_long
            );
        }

        if (!validate::likeString($preferences['site']['theme'])) {
            throw new memberErrorException(
                view::$language->preferences_error,
                view::$language->preferences_data_invalid
            );
        }

        $existsTheme = false;
        foreach (themeUtils::getAvailableThemes() as $theme) {
            if ($theme['value'] == $preferences['site']['theme']) {
                $existsTheme = true;
                break;
            }
        }

        if (!$existsTheme) {
            throw new memberErrorException(
                view::$language->preferences_error,
                view::$language->preferences_theme_not_found
            );
        }

        if (!validate::likeString($preferences['site']['default_language'])) {
            throw new memberErrorException(
                view::$language->preferences_error,
                view::$language->preferences_data_invalid
            );
        }

        $validate = preg_match(
            '/^[a-z-]+$/', $preferences['site']['default_language']
        );

        if (!$validate) {
            throw new memberErrorException(
                view::$language->preferences_error,
                view::$language->preferences_language_need_iso639_std
            );
        }

        $existsLanguage = false;
        foreach (languageUtils::getAvailableLanguages() as $language) {
            if ($language['value'] == $preferences['site']['default_language']) {
                $existsLanguage = true;
                break;
            }
        }

        if (!$existsLanguage) {
            throw new memberErrorException(
                view::$language->preferences_error,
                view::$language->preferences_language_not_found
            );
        }

        $adminLink = protoUtils::normalizeInputUrl(
            trim((string) $preferences['site']['admin_tools_link']),
            view::$language->preferences_admin_link_invalid
        );

        if (!preg_match('/^\/[^\/]/s', $adminLink)) {
            throw new memberErrorException(
                view::$language->preferences_error,
                view::$language->preferences_admin_link_invalid
            );
        }

        $preferences['site']['admin_tools_link'] = $adminLink;
        $preferences['site']['default_keywords'] = filter::input(
            $preferences['site']['default_keywords'])->textOnly()->getData();

        $preferences['site']['default_description'] = filter::input(
            $preferences['site']['default_description'])->textOnly()->getData();

        app::changeConfig('main.json', $preferences);
        app::saveConfig('main.json');

        $newConfig = app::reloadConfig();
        view::setLanguage(
            $newConfig->site->default_language
        );

        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->preferences_success,
            view::$language->preferences_is_changed,
            $newConfig->site->admin_tools_link . '/preferences'
        );

    }


}


