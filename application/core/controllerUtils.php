<?php


/**
 * controllers utilites
 */

abstract class controllerUtils {


    /**
     * return array of controllers from all modules and submodules
     */

    public static function getAllControllers() {

        $controllers   = array();
        $existsTargets = array();
        $existsTargets = fsUtils::globRecursive(APPLICATION . 'modules/', '*.php');
        $existsTargets = array_merge(
            $existsTargets, fsUtils::globRecursive(APPLICATION . 'admin/', '*.php')
        );

        foreach ($existsTargets as $item) {
            $name = basename($item, '.php');
            node::loadController($item, $name);
            array_push($controllers, node::call($name));

        }
        return $controllers;

    }


}


