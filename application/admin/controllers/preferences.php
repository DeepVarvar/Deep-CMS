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

                "action"      => null,
                "permission"  => "preferences_manage",
                "description" => view::$language->permission_preferences_manage

            ),

            array(

                "action"      => "recalculate",
                "permission"  => "preferences_recalc",
                "description" => view::$language->permission_preferences_recalc

            ),

            array(

                "action"      => "reset",
                "permission"  => "preferences_reset",
                "description" => view::$language->permission_preferences_reset

            )

        );


    }


    /**
     * view list of all groups
     */

    public function index() {


        /**
         * save preferences,
         * THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            return $this->savePreferences();
        }


        $c = app::config();


        view::assign(
            "themes",
            utils::getAvailableThemes($c->site->theme)
        );

        view::assign(
            "languages",
            utils::getAvailableLanguages($c->site->default_language)
        );


        view::assign("debug_mode_on", $c->system->debug_mode);
        view::assign("page_title", view::$language->preferences_global);

        $this->setProtectedLayout("preferences.html");


    }


    /**
     * reset preferences
     */

    public function reset() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(app::config()->site->admin_tools_link . "/preferences");


        /**
         * delete main.json.generated file
         */

        $generatedConfig = APPLICATION . "config/main.json.generated";

        if (file_exists($generatedConfig)) {
            unlink($generatedConfig);
        }

        app::loadConfig();


        /**
         * show redirect message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->preferences_global_is_reseted,
            app::config()->site->admin_tools_link . "/preferences"

        );


    }


    /**
     * recalculate permissions of all controllers
     */

    public function recalculate() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(app::config()->site->admin_tools_link . "/preferences");


        /**
         * get all permissions from controllers
         */

        $controllersPermissions = array();
        $controllers = utils::getAllControllers();

        foreach ($controllers as $controller) {


            foreach ($controller->getPermissions() as $current) {

                if (!in_array($current['permission'], $controllersPermissions)) {
                    array_push($controllersPermissions, $current['permission']);
                }

            }


        }


        /**
         * get exists permissions of groups in datatbase
         */

        $groupExistsPermissions = db::query("

            SELECT

                p.id,
                p.name,
                gp.group_id

            FROM permissions p
            INNER JOIN group_permissions gp ON gp.permission_id = p.id

            WHERE p.name IN(%s) AND gp.group_id != 0

            ",

            $controllersPermissions

        );


        /**
         * truncate tables group_permissions and permissions
         */

        db::set("TRUNCATE TABLE group_permissions");
        db::set("TRUNCATE TABLE permissions");


        /**
         * insert new list of permissions
         */

        $permissionValues = "('" . join("'), ('", $controllersPermissions) . "')";

        db::set("
            INSERT INTO permissions (name)
            VALUES {$permissionValues}
        ");


        /**
         * get new list of permissions
         */

        $newPermissions = db::query("SELECT id, name FROM permissions");


        /**
         * build new groups permissions
         */

        $newGroupsPermissions = array();
        foreach ($newPermissions as $permission) {


            /**
             * push new permission for root
             */

            array_push($newGroupsPermissions, "(0,{$permission['id']})");


            /**
             * push for other users
             */

            foreach ($groupExistsPermissions as $groupPermission) {


                if ($permission['name'] == $groupPermission['name']) {

                    array_push(
                        $newGroupsPermissions,
                        "({$groupPermission['group_id']},{$permission['id']})"
                    );

                }


            }

        }


        /**
         * insert new groups permissions
         */

        $newGroupsPermissionsValues = join(", ", $newGroupsPermissions);

        db::set("
            INSERT INTO group_permissions (group_id,permission_id)
            VALUES {$newGroupsPermissionsValues}
        ");


        /**
         * show redirect message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->permissions_is_recalculated,
            app::config()->site->admin_tools_link . "/preferences"

        );


    }


    /**
     * save preferences
     */

    private function savePreferences() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(app::config()->site->admin_tools_link . "/preferences");


        /**
         * get required data
         */

        $preferences = request::getRequiredPostParams(array("site", "system"));
        if ($preferences === null) {
            throw new memberErrorException(view::$language->error, view::$language->data_not_enough);
        }


        /**
         * check required system data
         */

        $requiredSystemData = array(
            "max_group_priority_number",
            "cookie_expires_time"
        );

        foreach ($requiredSystemData as $item) {

            if (!array_key_exists($item, $preferences['system'])) {
                throw new memberErrorException(view::$language->error, view::$language->data_not_enough);
            }

        }


        /**
         * check required site data
         */

        $requiredSiteData = array(

            "theme",
            "default_language",
            "admin_tools_link",
            "default_keywords",
            "default_description"

        );

        foreach ($requiredSiteData as $item) {

            if (!array_key_exists($item, $preferences['site'])) {
                throw new memberErrorException(view::$language->error, view::$language->data_not_enough);
            }

        }


        /**
         * set debug mode
         */

        $preferences['system']['debug_mode']
            = array_key_exists("debug_mode", $preferences['system'])
                ? true : false;


        /**
         * validate max_group_priority_number
         */

        if (!utils::isNumber($preferences['system']['max_group_priority_number'])) {
            throw new memberErrorException(view::$language->error, view::$language->priority_need_is_number);
        }

        if ($preferences['system']['max_group_priority_number'] > 1000) {
            throw new memberErrorException(view::$language->error, view::$language->priority_max_is_1000);
        }

        if ($preferences['system']['max_group_priority_number'] < 2) {
            throw new memberErrorException(view::$language->error, view::$language->priority_min_is_2);
        }


        /**
         * validate cookie_expires_time
         */

        if (!utils::isNumber($preferences['system']['cookie_expires_time'])) {
            throw new memberErrorException(view::$language->error, view::$language->cookie_expires_need_is_number);
        }

        if ($preferences['system']['cookie_expires_time'] >= 2147483646) {
            throw new systemErrorException(view::$language->error, view::$language->cookie_expires_is_too_long);
        }

        if ($preferences['system']['cookie_expires_time'] < 300) {
            throw new systemErrorException(view::$language->error, view::$language->cookie_expires_is_too_small);
        }

        $featureTime = time() + $preferences['system']['cookie_expires_time'];
        if ($featureTime >= 2147483646) {
            throw new systemErrorException(view::$language->error, view::$language->cookie_expires_is_too_long);
        }


        /**
         * validate theme (metapackage view templates)
         */

        if (!utils::likeString($preferences['site']['theme'])) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }

        $existsTheme = false;
        foreach (utils::getAvailableThemes() as $theme) {

            if ($theme['value'] == $preferences['site']['theme']) {
                $existsTheme = true;
                break;
            }

        }

        if (!$existsTheme) {
            throw new memberErrorException(view::$language->error, view::$language->theme_of_site_not_found);
        }


        /**
         * validate default_language
         */

        if (!utils::likeString($preferences['site']['default_language'])) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }

        if (!preg_match("/^[a-z-]+$/", $preferences['site']['default_language'])) {
            throw new memberErrorException(view::$language->error, view::$language->language_name_need_iso639_std);
        }

        $existsLanguage = false;
        foreach (utils::getAvailableLanguages() as $language) {

            if ($language['value'] == $preferences['site']['default_language']) {
                $existsLanguage = true;
                break;
            }

        }

        if (!$existsLanguage) {
            throw new memberErrorException(view::$language->error, view::$language->language_not_found);
        }


        /**
         * validate admin_tools_link
         */

        $adminLinkFilter = filter::input($preferences['site']['admin_tools_link']);
        if (preg_match("/[^a-z0-9\/]/u", $adminLinkFilter->getData())) {
            throw new memberErrorException(view::$language->error, view::$language->admin_tools_link_invalid_format);
        }

        $adminLinkFilter->trim("/")->expReplace(array("/\/+/", "/^([^\/])/"), array("", "/$1"));
        $preferences['site']['admin_tools_link'] = $adminLinkFilter->getData();

        if (!$preferences['site']['admin_tools_link']) {
            throw new memberErrorException(view::$language->error, view::$language->admin_tools_link_invalid_format);
        }


        /**
         * stored default site keywords
         */

        $preferences['site']['default_keywords']
            = filter::input($preferences['site']['default_keywords'])
                ->textOnly()
                ->getData();


        /**
         * stored default site description
         */

        $preferences['site']['default_description']
            = filter::input($preferences['site']['default_description'])
                ->textOnly()
                ->getData();


        /**
         * update configuration,
         * save config into generated file
         */

        app::changeConfig("main.json", $preferences);
        app::saveConfig("main.json");


        /**
         * reset view language before redirect,
         * show message for correct new language
         */

        $newConfig = app::config();
        view::setLanguage($newConfig->site->default_language);


        /**
         * show redirect message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->preferences_global_is_changed,
            $newConfig->site->admin_tools_link . "/preferences"

        );


    }


}



