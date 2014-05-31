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

        $priority  = member::getPriority();
        $condition = $priority > 0 ? 'WHERE g.priority > ' . $priority : '';

        $sourceQuery = db::buildQueryString("
            SELECT
                u.id,
                u.login,
                u.email,
                u.registration_date,
                u.last_ip,
                g.name groupname,
                g.priority
            FROM users u
            LEFT JOIN groups g ON g.id = u.group_id
            {$condition}
            ORDER BY priority ASC, u.login ASC
        ");

        $paginator = new paginator($sourceQuery);
        $paginator = $paginator->setCurrentPage(request::getCurrentPage())
            ->setItemsPerPage(20)->setSliceSizeByPages(20)->getResult();

        view::assign('node_name', view::$language->users_users);
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
        view::assign('node_name', view::$language->users_create_title);
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
                view::$language->users_error,
                view::$language->users_data_invalid
            );
        }

        $existsUser = db::normalizeQuery(
            'SELECT
                u.id,
                u.group_id,
                u.status,
                u.timezone,
                u.language,
                u.avatar,
                u.login,
                u.email,
                u.about,
                g.priority
            FROM users u
            LEFT JOIN groups g ON g.id = u.group_id
            WHERE u.id = %u', $userID
        );

        if (!$existsUser) {
            throw new memberErrorException(
                view::$language->users_error,
                view::$language->users_user_not_found
            );
        }

        if (!member::isRoot()) {
            if (member::getID() == $existsUser['id']) {
                throw new memberErrorException(
                    view::$language->users_error,
                    view::$language->users_cant_edit_same_profile
                );
            } else if (member::getPriority() >= $existsUser['priority']) {
                throw new memberErrorException(
                    view::$language->users_error,
                    view::$language->users_cant_edit_hoep_user
                );
            }
        }

        if (request::isPost()) {
            return $this->saveUser($existsUser['id']);
        }

        $existsUser['about'] = preg_replace(
            '/\s*<br\s?\/?>\s*/', "\n", $existsUser['about']
        );

        view::assign(array(
            'grouplist'  => $this->getAvailableGroupList($existsUser['group_id']),
            'statuslist' => $this->getUserStatusList($existsUser['status']),
            'languages'  => languageUtils::getAvailableLanguages($existsUser['language']),
            'user'       => $existsUser,
            'node_name'  => view::$language->users_edit_title
        ));

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
                view::$language->users_error,
                view::$language->users_data_invalid
            );
        }

        $existsUser = db::normalizeQuery(
            'SELECT u.id, g.priority FROM users u LEFT JOIN groups g
                ON g.id = u.group_id WHERE u.id = %u', $userID
        );

        if (!$existsUser) {
            throw new memberErrorException(
                view::$language->users_error,
                view::$language->users_user_not_found
            );
        }

        if (member::getID() == $existsUser['id']) {
            throw new memberErrorException(
                view::$language->users_ouch,
                view::$language->users_suicide_not_allowed
            );
        }

        if (!member::isRoot() and member::getPriority() >= $existsUser['priority']) {
            throw new memberErrorException(
                view::$language->users_error,
                view::$language->users_cant_delete_hoep_user
            );
        }

        db::set('DELETE FROM users WHERE id = %u', $existsUser['id']);
        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->users_success,
            view::$language->users_user_is_deleted,
            $adminToolsLink . '/users'
        );

    }


    /**
     * build and return grouplist
     */

    private function getAvailableGroupList($target = null) {

        $groups = db::query(
            'SELECT id, priority, name FROM groups
                WHERE id > 1 ORDER BY FIELD(id, 2) DESC, priority DESC, name ASC'
        );

        $grouplist = array();
        foreach ($groups as $group) {
            if (!member::isRoot() and member::getPriority() >= $group['priority']) {
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
            '3' => view::$language->users_status_email_not_confirm,
            '2' => view::$language->users_status_banned,
            '1' => view::$language->users_status_readonly,
            '0' => view::$language->users_status_free,
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
                view::$language->users_error,
                view::$language->users_data_not_enough
            );
        }

        foreach ($requiredParams as $strKey) {
            if (!validate::likeString($userData[$strKey])) {
                throw new memberErrorException(
                    view::$language->users_error,
                    view::$language->users_data_invalid
                );
            }
        }

        if (!validate::isNumber($userData['status']) or $userData['status'] > 3) {
            throw new memberErrorException(
                view::$language->users_error,
                view::$language->users_status_invalid
            );
        }

        if (!preg_match('/^[a-z-]+$/', $userData['language'])) {
            throw new memberErrorException(
                view::$language->users_error,
                view::$language->users_language_need_iso639_std
            );
        }

        $languageDir = APPLICATION . 'languages/' . $userData['language'];
        if (!is_dir($languageDir)) {
            throw new memberErrorException(
                view::$language->users_error,
                view::$language->users_language_not_found
            );
        }

        $userData['login'] = filter::input($userData['login'])
                ->htmlSpecialChars()->getData();

        if (!$userData['login']) {
            throw new memberErrorException(
                view::$language->users_error,
                view::$language->users_login_invalid
            );
        }

        if (!validate::isValidEmail($userData['email'])) {
            throw new memberErrorException(
                view::$language->users_error,
                view::$language->users_email_invalid
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
                    view::$language->users_error,
                    view::$language->users_password_confirm_req_depend
                );
            }
            if ($userData['password'] !== $userData['confirmpassword']) {
                throw new memberErrorException(
                    view::$language->users_error,
                    view::$language->users_password_confirm_dont_match
                );
            }
        }

        if (!validate::isNumber($userData['group_id'])) {
            throw new memberErrorException(
                view::$language->users_error,
                view::$language->users_group_id_invalid
            );
        } else if (!$existsGroup = db::normalizeQuery(
            'SELECT id, priority FROM groups
                WHERE id = %u', $userData['group_id']
        )) {
            throw new memberErrorException(
                view::$language->users_error,
                view::$language->users_group_not_found
            );
        }

        if (!member::isRoot() and member::getPriority() >= $existsGroup['priority']) {
            throw new memberErrorException(
                view::$language->users_error,
                view::$language->users_cant_set_hoep_group
            );
        }

        $userData['about'] = filter::input($userData['about'])->stripTags()->getData();
        $userData['about'] = helper::wordWrap($userData['about'], 20);
        $userData['about'] = nl2br($userData['about'], true);

        if ($target === null) {

            $password = md5($userData['password']);
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
            $userHash  = md5(
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

            $password = $requiredPassword ? md5($userData['password']) : $uPass;
            $userHash = md5(
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

        // TODO view::setLanguage($userData['language']);
        $message = ($target === null)
            ? view::$language->users_user_is_created
            : view::$language->users_user_is_edited;

        $location = request::getPostParam('silentsave')
            ? '/edit?id=' . ($target === null ? $newUserID : $target) : '';

        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->users_success,
            $message,
            $adminToolsLink . '/users' . $location
        );

    }


}


