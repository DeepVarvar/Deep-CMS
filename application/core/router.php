<?php



/**
 * route modules and dynamic pages
 */

abstract class router {


    protected static


        /**
         * denied public actions of controllers
         */

        $deniedPublicActions = array(

            "__call",
            "setLayout",
            "getPermissions",
            "setPermissions",
            "getDenyActions",
            "preLoad",
            "runBefore",
            "runAfter"

        ),


        /**
         * main parameters
         */

        $params = array(),


        /**
         * admin mode status
         */

        $adminMode = false;


    /**
     * load admin module or dynamic page
     */

    public static function init() {


        /**
         * get module
         */

        $config = app::config();
        $module = self::shiftParam();


        /**
         * is admin mode?
         */

        if ("/" . $module == $config->site->admin_tools_link) {
            self::$adminMode = true;
            $module = "admin";
        }


        $path = APPLICATION . (!self::isAdmin()
                    ? $config->path->modules : "") . "{$module}/";


        /**
         * throw for show new messages,
         * WARNING! this throw can only after check admin mode!
         */

        if (storage::exists("__message")) {


            extract(storage::shift("__message"));

            if ($type == SUCCESS_EXCEPTION) {
                throw new memberRefreshSuccessException($title, $message, $refresh_location);
            } else {
                throw new memberRefreshErrorException($title, $message, $refresh_location);
            }


        }


        /**
         * messages not found,
         * working more,
         * load module or dynamic page
         */

        if (file_exists($path . "{$module}.php")) {
            self::loadModule($path, $module);
        } else {

            self::$params = array();
            dynamic::loadPage();

        }


    }


    /**
     * load module, load bootstrap of module,
     * and if exists, load controller of module
     */

    public static function loadModule($path, $module) {


        /**
         * too long request for exists modules,
         * expects maximum 2 parameters after module name
         */

        if (self::getParamsCount() > 2) {
            throw new systemErrorException("Load module error", "Request of parameters too long");
        }


        /**
         * load bootstrap of module
         */

        node::loadController($path . "{$module}.php", $module);
        utils::checkPermissionAccess($module, null);


        /**
         * get controller of module (submodule)
         */

        if (self::getParamsCount() > 0) {


            $subModule = self::getParam();
            $controller = "{$path}controllers/{$subModule}.php";


            /**
             * load submodule
             */

            if (file_exists($controller) and !is_dir($controller)) {


                node::loadController($controller, $subModule);
                utils::checkPermissionAccess($subModule, null);


                /**
                 * WARNING!
                 * shift parameter only after loading submodule!
                 * set/use $subModuleMode like bool
                 */

                $subModuleMode = self::shiftParam();


            }


        }


        /**
         * submodule action have high priority for bootstrap action,
         * because submodule is action of module bootstrap
         */

        if (isset($subModuleMode)) {
            $target = $subModule;
            node::call($module)->runAfter();
        } else {
            $target = $module;
        }


        /**
         * execute action of target controller
         */

        self::executeAction($target);


    }


    /**
     * execute action of controller
     */

    private static function executeAction($controller) {


        $action = router::shiftParam();


        /**
         * denied execute for custom public actions of controller
         */

        if (self::isDenyAction($action)) {
            throw new systemErrorException("Execute action error", "Public action $action of controller $controller set is denied");
        }


        /**
         * denied for index action on URI
         */

        if ($action == "index") {
            throw new systemErrorException("Execute action error", "Action index of controller $controller is denied");
        }


        /**
         * fix value of action,
         * set index action as default
         */

        if (is_null($action)) {
            $action = "index";
        }


        /**
         * check for exists and callable action, run action
         */

        $argument = null;

        utils::checkAllow($controller, $action, $args);
        utils::checkPermissionAccess($controller, $action);

        node::call($controller)->{$action}($args);
        node::call($controller)->runAfter();


    }


    /**
     * check for access to action of controller
     */

    private static function isDenyAction($action, $actionList = null) {
        return in_array($action, is_array($actionList) ? $actionList : self::$deniedPublicActions);
    }


    /**
     * prepend router parameter
     */

    public static function unshiftParam($param) {
        array_unshift(self::$params, $param);
    }


    /**
     * append router parameter
     */

    public static function pushParam($param) {
        array_push(self::$params, $param);
    }


    /**
     * return count of request parameters
     */

    public static function getParamsCount() {
        return sizeof(self::$params);
    }


    /**
     * return status of admin mode
     */

    public static function isAdmin() {
        return self::$adminMode;
    }


    /**
     * return first normalized parameter with remove inside
     */

    public static function shiftParam() {
        return (self::$params) ? self::normalize(array_shift(self::$params)) : null;
    }


    /**
     * return last normalized parameter with remove inside
     */

    public static function shiftLastParam() {
        return (self::$params) ? self::normalize(array_pop(self::$params)) : null;
    }


    /**
     * return first normalized parameter without remove
     */

    public static function getParam() {
        return (self::$params) ? self::normalize(current(self::$params)) : null;
    }


    /**
     * return last normalized parameter without remove
     */

    public static function getLastParam() {


        if (!self::$params) {
            return null;
        }

        $end = end(self::$params);
        reset(self::$params);
        return self::normalize($end);


    }


    /**
     * return normalized name
     */

    private static function normalize($name) {

        if (!$name) {
            return null;
        }

        $name = str_replace(array("_", "-", "."), array(md5(microtime(true)), "_", "_"), $name);
        return $name;

    }


}


