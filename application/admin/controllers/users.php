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
                'action'      => null,
                'permission'  => 'users_manage',
                'description' => view::$language->permission_users_manage
            ),
            array(
                'action'      => 'create',
                'permission'  => 'user_create',
                'description' => view::$language->permission_user_create
            ),
            array(
                'action'      => 'delete',
                'permission'  => 'user_delete',
                'description' => view::$language->permission_user_delete
            ),
            array(
                'action'      => 'edit',
                'permission'  => 'user_edit',
                'description' => view::$language->permission_user_edit
            )
        );

    }


    /**
     * view list of users
     */

    public function index() {

        $pri = member::getPriority();
        $con = $pri > 0
            ? 'WHERE g.priority > ' . $pri . ' OR g.priority IS NULL' : '';

        $sourceQuery = db::buildQueryString(
            "SELECT u.id, IFNULL(g.name,'---') groupname,
                    IFNULL(g.priority,1001) priority, u.login, u.email,
                    u.registration_date, u.last_ip FROM users u
                LEFT JOIN groups g
                    ON g.id = u.group_id
                {$con}
                ORDER BY priority ASC, u.login ASC"
        );

        $paginator = new paginator($sourceQuery);
        $paginator = $paginator->setCurrentPage(request::getCurrentPage())
            ->setItemsPerPage(20)->setSliceSizeByPages(20)->getResult();

        view::assign('node_name', view::$language->users);
        view::assign('users', $paginator['items']);
        view::assign('pages', $paginator['pages']);
        $this->setProtectedLayout('users.html');

    }


    /**
     * view form of new user,
     * or save new user if exists POST data
     */

    public function create() {

        if (request::isPost()) {
            return $this->saveUser();
        }

        view::assign('grouplist', $this->getAvailableGroupList());
        view::assign('statuslist', $this->getUserStatusList());
        view::assign('languages', languageUtils::getAvailableLanguages());
        view::assign('node_name', view::$language->user_create_new);
        $this->setProtectedLayout('user-new.html');

    }


    /**
     * view form of exists user,
     * or update if isset POST data
     */

    public function edit() {

        $userID = request::shiftParam('id');
        if (!validate::isNumber($userID)) {
            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid
            );
        }

        if (!$existsUser = db::normalizeQuery(
            'SELECT u.id, u.group_id, u.status, u.timezone, u.language,
                    u.avatar, u.login, u.email, u.about,
                    IFNULL(g.priority,1001) priority,
                    IFNULL(g.is_protected,0) is_proteced
                FROM users u
                LEFT JOIN groups g
                    ON g.id = u.group_id
                WHERE u.id = %u', $userID
        )) {
            throw new memberErrorException(
                view::$language->error, view::$language->user_not_found
            );
        }

        if (!member::isProtected()) {
            if ($existsUser['is_proteced']) {
                throw new memberErrorException(
                    view::$language->error,
                    view::$language->system_object_action_denied
                );
            }
            if (member::getID() == $existsUser['id']) {
                throw new memberErrorException(
                    view::$language->error,
                    view::$language->user_cant_edit_so_profile
                );
            }
            if (member::getPriority() >= $existsUser['priority']) {
                throw new memberErrorException(
                    view::$language->error,
                    view::$language->user_cant_edit_hoep_user
                );
            }
        }

        if (request::isPost()) {
            return $this->saveUser($existsUser['id']);
        }

        view::assign(
            'grouplist', $this->getAvailableGroupList($existsUser['group_id'])
        );

        view::assign(
            'statuslist', $this->getUserStatusList($existsUser['status'])
        );

        view::assign(
            'languages', languageUtils::getAvailableLanguages($existsUser['language'])
        );

        view::assign('user', $existsUser);
        view::assign('node_name', view::$language->user_edit_exists);
        $this->setProtectedLayout('user-edit.html');

    }


    /**
     * delete exists user
     */

    public function delete() {

        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer($adminToolsLink . '/users');

        $userID = request::shiftParam('id');
        if (!validate::isNumber($userID)) {
            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid
            );
        }

        if (!$existsUser = db::normalizeQuery(
            'SELECT u.id, IFNULL(g.priority,1001) priority
                FROM users u
                LEFT JOIN groups g
                    ON g.id = u.group_id
                WHERE u.id = %u', $userID
        )) {
            throw new memberErrorException(
                view::$language->error, view::$language->user_not_found
            );
        }

        if (member::getID() == $existsUser['id']) {
            throw new memberErrorException(
                view::$language->ouch, view::$language->user_suicide_not_allowed
            );
        }

        if (!member::isProtected()
                and member::getPriority() >= $existsUser['priority']) {

            throw new memberErrorException(
                view::$language->error,
                view::$language->user_cant_delete_hoep_user
            );

        }

        db::set('DELETE FROM users WHERE id = %u', $existsUser['id']);
        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->user_is_deleted,
            $adminToolsLink . '/users'
        );

    }


    /**
     * build and return grouplist
     */

    private function getAvailableGroupList($target = null) {

        $groups = db::query(
            'SELECT id, priority, name FROM groups ORDER BY priority DESC'
        );

        $grouplist = array();
        $emptyOption = array(
            'description' => ' --- ', 'value' => 'none', 'selected' => false
        );

        array_push($grouplist, $emptyOption);
        foreach ($groups as $group) {
            if (!member::isProtected() and member::getPriority() >= $group['priority']) {
                break;
            }
            $option = array(
                'description' => $group['name'],
                'value'       => $group['id'],
                'selected'    => ($target !== null and $target == $group['id'])
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
            '3' => view::$language->user_status_email_not_confirm,
            '2' => view::$language->user_status_banned,
            '1' => view::$language->user_status_readonly,
            '0' => view::$language->user_status_free,
        );

        $statuslist = array();
        foreach ($statustypes as $k => $status) {
            $option = array(
                'description' => $status,
                'value'       => $k,
                'selected'    => ($type == $k)
            );
            array_push($statuslist, $option);
        }

        return $statuslist;

    }


    /**
     * save or update user and show message
     */

    private function saveUser($target = null) {

        $adminToolsLink = app::config()->site->admin_tools_link;
        if ($target === null) {
            request::validateReferer($adminToolsLink . '/users/create');
        } else {
            request::validateReferer(
                $adminToolsLink . '/users/edit\?id=\d+', true
            );
        }

        $requiredParams = array(
            'group_id',
            'status',
            'language',
            'login',
            'email',
            'password',
            'confirmpassword',
            'about'
        );

        $userData = request::getRequiredPostParams($requiredParams);
        if ($userData === null) {
            throw new memberErrorException(
                view::$language->error, view::$language->data_not_enough
            );
        }

        foreach ($requiredParams as $strKey) {
            if (!validate::likeString($userData[$strKey])) {
                throw new memberErrorException(
                    view::$language->error, view::$language->data_invalid
                );
            }
        }

        if (!validate::isNumber($userData['status']) or $userData['status'] > 3) {
            throw new memberErrorException(
                view::$language->error, view::$language->user_status_invalid
            );
        }

        if (!preg_match('/^[a-z-]+$/', $userData['language'])) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->language_name_need_iso639_std
            );
        }

        $languageDir = APPLICATION . 'languages/' . $userData['language'];
        if (!is_dir($languageDir)) {
            throw new memberErrorException(
                view::$language->error, view::$language->language_not_found
            );
        }

        $userData['login'] = filter::input($userData['login'])
                ->stripTags()->getData();

        if (!$userData['login']) {
            throw new memberErrorException(
                view::$language->error, view::$language->user_login_invalid
            );
        }

        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new memberErrorException(
                view::$language->error, view::$language->email_invalid
            );
        }

        $userData['confirmpassword']
            = filter::input($userData['confirmpassword'])->getData();

        $userData['password']
            = filter::input($userData['password'])->getData();

        $requiredPassword = (
            $target === null or ($userData['password']
                or $userData['confirmpassword'])
        );

        if ($requiredPassword) {
            if (!$userData['password'] or !$userData['confirmpassword']) {
                throw new memberErrorException(
                    view::$language->error,
                    view::$language->password_confirm_req_depend
                );
            }
            if ($userData['password'] !== $userData['confirmpassword']) {
                throw new memberErrorException(
                    view::$language->error,
                    view::$language->password_confirm_dont_match
                );
            }
        }

        if ($userData['group_id'] === 'none') {
            $userData['group_id'] = 'NULL';
            $existsGroup = array('priority' => null, 'id' => null);
        } else {

            if (!validate::isNumber($userData['group_id'])) {
                throw new memberErrorException(
                    view::$language->error, view::$language->group_id_invalid
                );
            }

            if (!$existsGroup = db::normalizeQuery(
                'SELECT id, priority FROM groups
                    WHERE id = %u', $userData['group_id']
            )) {
                throw new memberErrorException(
                    view::$language->error, view::$language->group_not_found
                );
            }

            if (!member::isProtected()
                    and member::getPriority() >= $existsGroup['priority']) {

                throw new memberErrorException(
                    view::$language->error,
                    view::$language->user_cant_set_hoep_group
                );

            }

        }

        $userData['about'] = filter::input($userData['about'])
                ->stripTags()->getData();

        if ($target === null) {

            $password = helper::getHash($userData['password']);
            db::set(

                "INSERT INTO users (
                    id, group_id, status, language, login,
                    password, email, hash, last_ip, registration_date,
                    last_visit, about, working_cache
                ) VALUES (
                    NULL, %s, %u, '%s', '%s', '%s', '%s', NULL,
                    '0.0.0.0', NOW(), NOW(), '%s', '[]'
                )",

                $userData['group_id'],
                $userData['status'],
                $userData['language'],
                $userData['login'],
                $password,
                $userData['email'],
                $userData['about']

            );

            $newUserID = db::lastID();
            $target    = $newUserID;
            $userHash  = helper::getHash(
                $newUserID
                    . $userData['login']
                    . $password
                    . $existsGroup['id']
                    . $existsGroup['priority']
                    . $userData['email']
            );

            db::set(
                "UPDATE users SET hash = '%s'
                    WHERE id = %u", $userHash, $newUserID
            );

        } else {

            $uPass = db::normalizeQuery(
                'SELECT password FROM users WHERE id = %u', $target
            );

            $password = $requiredPassword
                ? helper::getHash($userData['password']) : $uPass;

            $userHash = helper::getHash(
                $target
                    . $userData['login']
                    . $password
                    . $existsGroup['id']
                    . $existsGroup['priority']
                    . $userData['email']
            );

            if ($requiredPassword) {

                db::set(

                    "UPDATE users SET group_id = %s, status = %u,
                        language = '%s', login = '%s', password = '%s',
                        email = '%s', hash = '%s', about = '%s'
                      WHERE id = %u",

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

                db::set(

                    "UPDATE users SET group_id = %s, status = %u,
                        language = '%s', login = '%s', email = '%s',
                        hash = '%s', about = '%s'
                      WHERE id = %u",

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

        $location = request::getPostParam('silentsave')
            ? '/edit?id=' . $target : '';

        // TODO view::setLanguage($userData['language']);
        $message = ($target === null)
            ? view::$language->user_is_created
            : view::$language->user_is_edited;

        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->success,
            $message,
            $adminToolsLink . '/users' . $location
        );

    }


}


