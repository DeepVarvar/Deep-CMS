<?php


/**
 * route modules and dynamic pages
 */

abstract class router {


    /**
     * denied public actions of controllers
     */

    protected static $deniedPublicActions = array(
        '__call',
        'setLayout',
        'getPermissions',
        'setPermissions',
        'getDenyActions',
        'preLoad',
        'runBefore',
        'runAfter'
    );


    /**
     * excepted prototypes
     */

    protected static $exceptedPrototypes = array('none', 'simpleLink');


    /**
     * main parameters
     */

    protected static $params = array();


    /**
     * admin mode status
     */

    protected static $adminMode = false;


    /**
     * load dynamic page
     */

    public static function init() {

        $config = app::config();
        $requestURI = request::getURI();
        $moduleName = self::getParam();

        $adminLink = preg_quote($config->site->admin_tools_link, '/');
        if (preg_match('/^' . $adminLink . '(\/.+)?$/s', $requestURI)) {
            self::$adminMode = true;
        }


        /**
         * throw for show new messages,
         * WARNING! this throw can only after check admin mode!
         */

        if (storage::exists('__message')) {

            extract(storage::shift('__message'));
            if ($type == SUCCESS_EXCEPTION) {
                throw new memberRefreshSuccessException(
                    $title, $message, $refresh_location
                );
            } else {
                throw new memberRefreshErrorException(
                    $title, $message, $refresh_location
                );
            }

        }

        if (self::isAdmin()) {

            $loadedPage = array(
                'id'             => 0,
                'prototype'      => 'mainModule',
                'page_alias'     => $config->site->admin_tools_link,
                'page_is_module' => 1,
                'module_name'    => 'admin'
            );

        } else if (file_exists(APPLICATION
            . 'modules/' . $moduleName . '/autoloaded')) {

            $loadedPage = array(
                'id'             => 0,
                'prototype'      => 'mainModule',
                'page_alias'     => '/' . $moduleName,
                'page_is_module' => 1,
                'module_name'    => $moduleName
            );

        } else {

            $excProtos = "'" . join("','", self::$exceptedPrototypes) . "'";
            if (!$loadedPage = db::query(
                "SELECT id, prototype, page_alias, module_name,
                    IF(prototype = 'mainModule', 1, 0) page_is_module
                FROM tree WHERE prototype NOT IN({$excProtos}) AND (
                    (page_alias = '%1\$s' AND prototype != 'mainModule')
                    OR ('%1\$s' REGEXP CONCAT(
                            '^', REPLACE(page_alias, '.', '\\\.') , '(/.*)?$'
                        ) AND prototype = 'mainModule')
                ) AND is_publish = 1
                    ORDER BY page_is_module ASC,
                        LENGTH(page_alias) ASC LIMIT 2", $requestURI
            )) {

                throw new systemErrorException(
                    view::$language->app_error,
                    view::$language->app_page_not_found
                );

            }

            $siblingsKey = 0;
            if (sizeof($loadedPage) > 1) {
                foreach ($loadedPage as $siblingsKey => $item) {
                    if ($item['page_is_module']) {
                        break;
                    }
                }
            }
            $loadedPage = $loadedPage[$siblingsKey];

        }

        $rejectedLevel = substr_count($loadedPage['page_alias'], '/');
        self::$params  = array_slice(self::$params, $rejectedLevel);

        self::loadPageData($loadedPage);
        if ($loadedPage['page_is_module']) {

            if (!$loadedPage['module_name']) {
                throw new systemErrorException(
                    view::$language->app_error,
                    view::$language->app_module_not_enabled
                );
            }

            $path = self::isAdmin() ? '' : 'modules/';
            self::loadModule(
                APPLICATION . $path . $loadedPage['module_name'] . '/',
                $loadedPage['module_name']
            );

        }

        storage::write('nodeID', $loadedPage['id']);

    }


    /**
     * assign into view all page data
     */

    private static function loadPageData($loadedPage) {

        $config  = app::config();
        $pagePrototype   = new $loadedPage['prototype'];
        $prototypeFields = join(',d.', $pagePrototype->getPublicFields());

        $pageData = db::normalizeQuery(
            "SELECT d.{$prototypeFields}, u1.id author_id,
                    u1.login author_name, u2.id modifier_id,
                    u2.login modifier_name,
                    IF(i.name IS NOT NULL,i.name,'no-image.png') image
                FROM tree d
                LEFT JOIN users u1 ON u1.id = d.author
                LEFT JOIN users u2 ON u2.id = d.modified_author
                LEFT JOIN images i ON i.node_id = d.id AND i.is_master = 1
                WHERE d.id = %u", $loadedPage['id']
        );

        $pm = 'permanent_redirect';
        if (array_key_exists($pm, $pageData) and $pageData[$pm]) {
            request::redirect($pageData[$pm]);
        }

        $pageData = array_merge($pageData, $loadedPage);
        view::assign($pageData);

        if (array_key_exists('layout', $pageData)) {
            view::setLayout('public/' . $pageData['layout']);
        }

    }


    /**
     * load module, load bootstrap of module,
     * and if exists, load controller of module
     */

    public static function loadModule($path, $module) {

        if (self::getParamsCount() > 2) {
            throw new systemErrorException(
                'Load module error', 'Request of parameters too long'
            );
        }

        node::loadController($path . $module . '.php', $module);
        permissionUtils::checkPermissionAccess($module, null);

        if (self::getParamsCount() > 0) {

            $subModule = self::getParam();
            $controller = $path . 'controllers/' . $subModule . '.php';

            if (file_exists($controller) and !is_dir($controller)) {
                node::loadController($controller, $subModule);
                permissionUtils::checkPermissionAccess($subModule, null);
                $subModuleMode = self::shiftParam();
            }

        }

        if (isset($subModuleMode)) {
            $target = $subModule;
            node::call($module)->runAfter();
        } else {
            $target = $module;
        }

        self::executeAction($target);

    }


    /**
     * execute action of controller
     */

    private static function executeAction($controller) {

        $action = router::shiftParam();
        if (self::isDenyAction($action)) {
            throw new systemErrorException(
                'Execute action error',
                'Public action ' . $action
                    . ' of controller ' . $controller . ' set is denied'
            );
        }

        if ($action == 'index') {
            throw new systemErrorException(
                'Execute action error',
                'Action index of controller ' . $controller . ' is denied'
            );
        }

        if (is_null($action)) {
            $action = 'index';
        }

        $argument = null;
        permissionUtils::checkAllow($controller, $action, $args);
        permissionUtils::checkPermissionAccess($controller, $action);

        node::call($controller)->{$action}($args);
        node::call($controller)->runAfter();

    }


    /**
     * check for access to action of controller
     */

    private static function isDenyAction($action, $actionList = null) {

        $actionList = is_array($actionList)
            ? $actionList : self::$deniedPublicActions;
        return in_array($action, $actionList);

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

        if (!self::$params) return null;
        $end = end(self::$params);
        reset(self::$params);
        return self::normalize($end);

    }


    /**
     * return normalized name
     */

    private static function normalize($name) {

        return !$name ? null : str_replace(
            array('_', '-', '.'), array(md5(microtime(true)), '_', '_'), $name
        );

    }


}


