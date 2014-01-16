<?php


/**
 * node, all objects and controllers are located inside
 * you can use any of the objects into global node
 */

abstract class node {


    /**
     * all objects here
     */

    protected static $objects = array();


    /**
     * load class
     */

    public static function load($class) {

        if (!isset(self::$objects[$class])) {
            self::$objects[$class] = new $class;
        }

    }


    /**
     * call to exists object,
     * return object
     */

    public static function call($key) {
        return self::$objects[$key];
    }


    /**
     * load class,
     * including file and call to self::load()
     */

    public static function loadClass($path, $className) {

        if (isset(self::$objects[$className])) {
            return;
        }
        view::addLoadedComponent($className);
        require_once $path;
        self::load($className);

    }


    /**
     * load controller class,
     * set permission of controller
     */

    public static function loadController($path, $controllerName) {

        self::loadClass($path, $controllerName);
        if (!(self::call($controllerName) instanceof baseController)) {
            throw new systemErrorException(
                'Node load controller error',
                'Class ' . $controllerName . ' not instance of baseController'
            );
        }

        $controller = self::call($controllerName);
        $controller->preLoad();
        $controller->runBefore();

    }


    /**
     * remove object out self,
     * unset variable
     */

    public static function remove($key) {

        if (isset(self::$objects[$key])) {
            unset(self::$objects[$key]);
        }

    }


}


