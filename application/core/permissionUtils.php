<?php


/**
 * controller permissions utilites
 */

abstract class permissionUtils {


    /**
     * check for exists and callable action
     */

    public static function checkAllow($c, & $action, & $argument = null) {

        if (!method_exists($c, $action)) {
            // check for available overloading
            if (method_exists($c, '__call')) {
                $argument = $action;
                $action = '__call';
            } else {
                throw new systemErrorException(
                    'Controller error',
                    'Action ' . $action . ' of ' . $c . ' controller not found'
                );
            }
        }

        $checkMethod = new ReflectionMethod($c, $action);
        if (!$checkMethod->isPublic()) {
            throw new systemErrorException(
                'Controller error',
                'Method ' . $action . ' of ' . $c . ' controller is not public'
            );
        }

    }


    /**
     * check permission access for action of controller,
     * auto check permissions method
     */

    public static function checkPermissionAccess($controller, $action) {
        $permissions = node::call($controller)->getPermissions();
        self::initCheckPermissionAccess($permissions, $action);
    }


    /**
     * check permission worker
     */

    public static function initCheckPermissionAccess($cp, $action) {

        $memberPermissions = member::getPermissions();
        foreach ($cp as $item) {

            if ($item['action'] == $action) {

                foreach ($memberPermissions as $allowed) {
                    if ($allowed['name'] == $item['permission']) {
                        return true;
                    }
                }

                throw new memberErrorException(
                    403,
                    view::$language->error,
                    view::$language->action_denied
                );

            }

        }

    }


}


