<?php



/**
 * admin helper class
 */

abstract class adminHelper {


    /**
     * return admin menu items array
     */

    public static function getMenuItems() {


        return array(

            array(
                "name" => "document_tree",
                "link" => "/documents"
            ),

            array(
                "name" => "menu_of_site",
                "link" => "/menu"
            ),

            array(
                "name" => "users",
                "link" => "/users"
            ),

            array(
                "name" => "groups",
                "link" => "/groups"
            )

        );


    }


}



