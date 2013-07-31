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


        if (!class_exists($class)) {
            throw new systemErrorException("Node initialization class error", "Class $class not found");
        }

        if (!isset(self::$objects[$class])) {
            self::$objects[$class] = new $class;
        }


    }


    /**
     * call to exists object,
     * return object
     */

    public static function call($key) {


        if (!isset(self::$objects[$key])) {
            throw new systemErrorException("Node call to object error", "Object $key not found inside");
        }

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

        if (!file_exists($path)) {
            throw new systemErrorException("Node load file error", "File $path not exists");
        }

        if (is_dir($path)) {
            throw new systemErrorException("Node load file error", "File $path is directory");
        }


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
            throw new systemErrorException("Node load controller error", "Class $controllerName not instance of baseController");
        }


        /**
         * you known..
         * here you find great music: http://www.youtube.com/watch?v=0lTKErnmmoA
         *
         * good, yeah? :)
         *
         */

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



