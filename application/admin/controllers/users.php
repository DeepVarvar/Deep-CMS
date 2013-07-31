<?php



/**
 * admin submodule, manage users of site
 */

class users extends baseController {


    /**
     * set permissions for this controller
     */

    public function setPermissions() {


        $this->permissions = array(

            array(

                "action"      => null,
                "permission"  => "users_manage",
                "description" => view::$language->permission_users_manage

            ),

            array(

                "action"      => "create",
                "permission"  => "users_create",
                "description" => view::$language->permission_users_create

            ),

            array(

                "action"      => "delete",
                "permission"  => "users_delete",
                "description" => view::$language->permission_users_delete

            ),

            array(

                "action"      => "edit",
                "permission"  => "users_edit",
                "description" => view::$language->permission_users_edit

            )

        );


    }


    /**
     * view list of users
     */

    public function index() {


        $priorityCondition = (!member::isRoot() or member::getPriority() > 0)
            ? "WHERE g.priority > " . member::getPriority() . " OR g.priority IS NULL" : "";


        $sourceQuery = db::buildQueryString("

            SELECT

                u.id,
                IFNULL(g.name,'---') groupname,
                IFNULL(g.priority,1001) priority,
                u.login,
                u.email,
                u.registration_date,
                u.last_ip

            FROM users u
            LEFT JOIN groups g ON g.id = u.group_id

            {$priorityCondition}
            ORDER BY priority ASC, u.login ASC

        ");


        $paginator = new paginator($sourceQuery);
        $paginator =

            $paginator->setCurrentPage(request::getCurrentPage())
                ->setItemsPerPage(20)
                ->setSliceSizeByPages(20)
                ->getResult();


        view::assign("page_title", view::$language->users);
        view::assign("users", $paginator['items']);
        view::assign("pages", $paginator['pages']);

        $this->setProtectedLayout("users.html");


    }


    /**
     * view form of new user,
     * or save new user if exists POST data
     */

    public function create() {


        /**
         * save new user action
         * THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            return $this->saveUser();
        }


        /**
         * append data into view
         */

        view::assign(
            "grouplist",
            $this->getAvailableGroupList()
        );

        view::assign(
            "statuslist",
            $this->getUserStatusList()
        );

        view::assign(
            "languages",
            utils::getAvailableLanguages()
        );

        view::assign("page_title", view::$language->user_create_new);
        $this->setProtectedLayout("user-new.html");


    }


    /**
     * view form of exists user,
     * or update if isset POST data
     */

    public function edit() {


        /**
         * get exists user,
         * check main permissions and priority
         */

        $user_id = request::shiftParam("id");
        if (!utils::isNumber($user_id)) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }

        if (!member::isRoot() and (string) $user_id === "0") {
            throw new memberErrorException(view::$language->error, view::$language->system_object_action_denied);
        }


        $existsUser = db::normalizeQuery("

            SELECT

                u.id,
                u.group_id,
                u.status,
                u.timezone,
                u.language,
                u.avatar,
                u.login,
                u.email,
                u.about,
                IFNULL(g.priority,1001) priority

            FROM users u
            LEFT JOIN groups g ON g.id = u.group_id

            WHERE u.id = %u

            ",

            $user_id

        );


        if (!$existsUser) {
            throw new memberErrorException(view::$language->error, view::$language->user_not_found);
        }

        if (!member::isRoot() and member::getID() == $existsUser['id']) {
            throw new memberErrorException(view::$language->error, view::$language->user_cant_edit_so_profile);
        }

        if (!member::isRoot() and member::getPriority() >= $existsUser['priority']) {
            throw new memberErrorException(view::$language->error, view::$language->user_cant_edit_hoep_user);
        }


        /**
         * update user action
         * THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            return $this->saveUser($existsUser['id'], $existsUser);
        }


        /**
         * append data into view
         */

        view::assign(
            "grouplist",
            $this->getAvailableGroupList($existsUser['group_id'])
        );

        view::assign(
            "statuslist",
            $this->getUserStatusList($existsUser['status'])
        );

        view::assign(
            "languages",
            utils::getAvailableLanguages($existsUser['language'])
        );

        view::assign("user", $existsUser);
        view::assign("page_title", view::$language->user_edit_exists);

        $this->setProtectedLayout("user-edit.html");


    }


    /**
     * delete exists user
     */

    public function delete() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(app::config()->site->admin_tools_link . "/users");


        /**
         * get exists user,
         * check main permissions and priority
         */

        $user_id = request::shiftParam("id");
        if (!utils::isNumber($user_id)) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }

        if ((string) $user_id === "0") {
            throw new memberErrorException(view::$language->error, view::$language->system_object_action_denied);
        }


        $existsUser = db::normalizeQuery("

            SELECT

                u.id,
                IFNULL(g.priority,1001) priority

            FROM users u
            LEFT JOIN groups g ON g.id = u.group_id

            WHERE u.id = %u

            ",

            $user_id

        );


        if (!$existsUser) {
            throw new memberErrorException(view::$language->error, view::$language->user_not_found);
        }

        if (member::getID() == $existsUser['id']) {
            throw new memberErrorException(view::$language->ouch, view::$language->user_suicide_not_allowed);
        }

        if (!member::isRoot() and member::getPriority() >= $existsUser['priority']) {
            throw new memberErrorException(view::$language->error, view::$language->user_cant_delete_hoep_user);
        }


        /**
         * now delete user
         */

        db::set("
            DELETE FROM users
            WHERE id = %u", $existsUser['id']
        );


        /**
         * show redirect message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->user_is_deleted,
            app::config()->site->admin_tools_link . "/users"

        );


    }


    /**
     * build and return grouplist
     */

    private function getAvailableGroupList($target = null) {


        /**
         * get available groups
         */

        $groups = db::query("
            SELECT id, priority, name FROM groups
            ORDER BY priority DESC
        ");


        /**
         * build grouplist
         */

        $grouplist = array();
        $emptyOption = array(

            "description" => " --- ",
            "value"       => "none",
            "selected"    => false

        );


        array_push($grouplist, $emptyOption);


        foreach ($groups as $group) {


            /**
             * check group priority for available items
             */

            if (!member::isRoot() and member::getPriority() >= $group['priority']) {
                break;
            }


            $option = array(

                "description" => $group['name'],
                "value"       => $group['id'],
                "selected"    => ($target !== null and $target == $group['id'])

            );

            array_push($grouplist, $option);


        }


        return $grouplist;


    }


    /**
     * build and return statuslist
     */

    private function getUserStatusList($type = 0) {


        $statustypes = array(

            "3" => view::$language->user_status_email_not_confirm,
            "2" => view::$language->user_status_banned,
            "1" => view::$language->user_status_readonly,
            "0" => view::$language->user_status_free,

        );


        /**
         * build statuslist
         */

        $statuslist = array();
        foreach ($statustypes as $k => $status) {


            $option = array(

                "description" => $status,
                "value"       => $k,
                "selected"    => ($type == $k)

            );


            array_push($statuslist, $option);


        }


        return $statuslist;


    }


    /**
     * save or update user and show message
     */

    private function saveUser($target = null) {


        /**
         * validate referer of possible CSRF attack
         */

        $adminToolsLink = app::config()->site->admin_tools_link;
        if ($target === null) {
            request::validateReferer($adminToolsLink . "/users/create");
        } else {
            request::validateReferer($adminToolsLink . "/users/edit\?id=\d+", true);
        }


        /**
         * get required user data
         */

        $requiredParams = array(

            "group_id",
            "status",
            "language",
            "login",
            "email",
            "password",
            "confirmpassword",
            "about"

        );

        $userData = request::getRequiredPostParams($requiredParams);
        if ($userData === null) {
            throw new memberErrorException(view::$language->error, view::$language->data_not_enough);
        }


        /**
         * check like string params
         */

        foreach ($requiredParams as $strKey) {

            if (!utils::likeString($userData[$strKey])) {
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
            }

        }


        /**
         * check user status type
         */

        if (!utils::isNumber($userData['status']) or $userData['status'] > 3) {
            throw new memberErrorException(view::$language->error, view::$language->user_status_invalid_format);
        }


        /**
         * check user language
         */

        if (!preg_match("/^[a-z-]+$/", $userData['language'])) {
            throw new memberErrorException(view::$language->error, view::$language->language_name_need_iso639_std);
        }

        $languageDir = APPLICATION . app::config()->path->languages . $userData['language'];

        if (!is_dir($languageDir)) {
            throw new memberErrorException(view::$language->error, view::$language->language_not_found);
        }


        /**
         * check user login
         */

        $userData['login']
            = filter::input($userData['login'])
                ->lettersOnly()
                ->getData();

        if (!$userData['login']) {
            throw new memberErrorException(view::$language->error, view::$language->user_login_invalid_format);
        }


        /**
         * check user email
         */

        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new memberErrorException(view::$language->error, view::$language->email_invalid_format);
        }


        /**
         * check password,
         * password for new user is required,
         * password for exists user is required only if isset
         */

        $userData['confirmpassword'] = filter::input($userData['confirmpassword'])->getData();
        $userData['password'] = filter::input($userData['password'])->getData();


        $requiredPassword = (
            $target === null or ($userData['password']
                or $userData['confirmpassword'])
        );


        if ($requiredPassword) {

            if (!$userData['password'] or !$userData['confirmpassword']) {
                throw new memberErrorException(view::$language->error, view::$language->password_confirm_req_depend);
            }

            if ($userData['password'] !== $userData['confirmpassword']) {
                throw new memberErrorException(view::$language->error, view::$language->password_confirm_dont_match);
            }

        }


        /**
         * check group of user,
         * allow set only lower priority
         */

        if ($userData['group_id'] === "none") {


            $userData['group_id'] = "NULL";
            $existsGroup = array(
                "priority" => null,
                "id" => null
            );


        } else {


            if (!utils::isNumber($userData['group_id'])) {
                throw new memberErrorException(view::$language->error, view::$language->group_id_invalid_format);
            }

            $existsGroup = db::normalizeQuery("
                SELECT id,priority FROM groups
                WHERE id = %u", $userData['group_id']
            );

            if (!$existsGroup) {
                throw new memberErrorException(view::$language->error, view::$language->group_not_found);
            }

            if (!member::isRoot() and member::getPriority() >= $existsGroup['priority']) {
                throw new memberErrorException(view::$language->error, view::$language->user_cant_set_hoep_group);
            }


        }


        /**
         * lead user about info
         */

        $userData['about']
            = filter::input($userData['about'])
                ->stripTags()
                ->getData();


        /**
         * save user data
         */

        if ($target === null) {


            /**
             * get pasword hash value
             */

            $password = helper::getHash($userData['password']);


            /**
             * create new user
             */

            db::set("

                INSERT INTO users (

                    id,
                    group_id,
                    status,
                    language,
                    login,
                    password,
                    email,
                    hash,
                    last_ip,
                    registration_date,
                    last_visit,
                    about,
                    working_cache

                ) VALUES (

                    NULL,
                    %s,
                    %u,
                   '%s',
                   '%s',
                   '%s',
                   '%s',
                    NULL,
                   '0.0.0.0',
                    NOW(),
                    NOW(),
                   '%s',
                   '[]'

                )

                ",

                $userData['group_id'],
                $userData['status'],
                $userData['language'],
                $userData['login'],
                $password,
                $userData['email'],
                $userData['about']

            );


            /**
             * set valid hash value
             */

            $newUserID = db::lastID();
            $userHash = helper::getHash(

                $newUserID . $userData['login'] . $password . $existsGroup['id']
                . $existsGroup['priority'] . $userData['email']

            );


            db::set(
                "UPDATE users SET hash = '%s' WHERE id = %u",
                $userHash, $newUserID
            );



        } else {


            /**
             * get user password hash
             */

            $uPass = db::normalizeQuery("SELECT password FROM users WHERE id = %u", $target);


            /**
             * build user hash
             */

            $password = $requiredPassword ? helper::getHash($userData['password']) : $uPass;
            $userHash = helper::getHash(

                $target . $userData['login'] . $password . $existsGroup['id']
                . $existsGroup['priority'] . $userData['email']

            );


            /**
             * update user now
             */

            if ($requiredPassword) {


                db::set("

                    UPDATE users SET

                        group_id = %s,
                        status = %u,
                        language = '%s',
                        login = '%s',
                        password = '%s',
                        email = '%s',
                        hash = '%s',
                        about = '%s'

                    WHERE id = %u

                    ",

                    $userData['group_id'],
                    $userData['status'],
                    $userData['language'],
                    $userData['login'],
                    $password,
                    $userData['email'],
                    $userHash,
                    $userData['about'],
                    $target

                );


            } else {


                db::set("

                    UPDATE users SET

                        group_id = %s,
                        status = %u,
                        language = '%s',
                        login = '%s',
                        email = '%s',
                        hash = '%s',
                        about = '%s'

                    WHERE id = %u

                    ",

                    $userData['group_id'],
                    $userData['status'],
                    $userData['language'],
                    $userData['login'],
                    $userData['email'],
                    $userHash,
                    $userData['about'],
                    $target

                );


            }


        }


        /* TODO view::setLanguage($userData['language']);*/
        $message = ($target === null)
            ? view::$language->user_is_created
            : view::$language->user_is_edited;


        /**
         * show redirect message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
            view::$language->success,
            $message,
            app::config()->site->admin_tools_link . "/users"

        );


    }


}



