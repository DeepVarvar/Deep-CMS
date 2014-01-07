<?php


/**
 * front controller for admin environment
 */

class admin extends baseController {


    /**
     * set global permission for this controller
     */

    public function setPermissions() {

        $this->permissions = array(
            array(
                'action'      => null,
                'permission'  => 'admin_access',
                'description' => view::$language->permission_admin_access
            )
        );

    }


    /**
     * override parent preload method
     */

    public function preLoad() {

        $uri = request::getURI();
        $cnf = app::config();
        if (member::isAuth()) {

            $this->setPermissions();
            permissionUtils::initCheckPermissionAccess(
                $this->getPermissions(), null
            );

            if ($uri == $cnf->site->admin_tools_link) {
                request::redirect($cnf->site->admin_tools_link . '/tree');
            }

        } else if ($uri != $cnf->site->admin_tools_link) {
            request::redirect($cnf->site->admin_tools_link);
        }

    }


    /**
     * admin logout
     */

    public function logout() {

        storage::remove('admin-login-env');
        member::logout();
        request::redirect(app::config()->site->admin_tools_link);

    }


    /**
     * loginform action
     */

    public function index() {

        if (member::isAttemptLogin()) {

            request::validateReferer(app::config()->site->admin_tools_link);
            if (!member::logged()) {
                storage::write('admin-login-env', array(
                    'login_image'   => 'err',
                    'login_message' => view::$language->login_or_pass_bad
                ));
            }
            request::sameOriginRedirect();

        }

        if (storage::exists('admin-login-env')) {
            view::assign(storage::shift('admin-login-env'));
        } else {
            view::assign(array(
                'login_image'   => 'ok',
                'login_message' => view::$language->auth_please
            ));
        }

        $this->setProtectedLayout('login-form.html');

    }


}


