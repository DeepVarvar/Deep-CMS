<?php


/**
 * admin submodule, modules empty
 */

class modules extends baseController {


    /**
     * set permissions for this controller
     */

    public function setPermissions() {

        $this->permissions = array(
            array(
                'action'      => null,
                'permission'  => 'modules_manage',
                'description' => view::$language->permission_modules_manage
            )
        );

    }


    public function index() {

        view::setOutputContext('json');
        view::lockOutputContext();
        view::clearPublicVariables();

    }


}


