<?php



/**
 * admin submodule, manage site menu
 */

class menu extends baseController {


    /**
     * set permissions for this controller
     */

    public function setPermissions() {


        $this->permissions = array(

            array(

                "action"      => null,
                "permission"  => "menu_manage",
                "description" => view::$language->permission_menu_manage

            ),

            array(

                "action"      => "create",
                "permission"  => "menu_create",
                "description" => view::$language->permission_menu_create

            ),

            array(

                "action"      => "delete",
                "permission"  => "menu_delete",
                "description" => view::$language->permission_menu_delete

            ),

            array(

                "action"      => "edit",
                "permission"  => "menu_edit",
                "description" => view::$language->permission_menu_edit

            )

        );


    }


    /**
     * view list of menu
     */

    public function index() {


        $paginator = new paginator(
            "SELECT id, name FROM menu ORDER BY id ASC"
        );

        $paginator =

            $paginator->setCurrentPage(request::getCurrentPage())
                ->setItemsPerPage(20)
                ->setSliceSizeByPages(20)
                ->getResult();


        view::assign("menulist", $paginator['items']);
        view::assign("pages", $paginator['pages']);
        view::assign("page_title", view::$language->menu_of_site);

        $this->setProtectedLayout("menu.html");


    }


    /**
     * view form of new menu,
     * or save new menu if exists POST data
     */

    public function create() {


        /**
         * save new menu, THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            $this->saveMenu();
        }


        /**
         * append data into view
         */

        view::assign("page_title", view::$language->menu_create_new);
        $this->setProtectedLayout("menu-new.html");


    }


    /**
     * full delete menu
     */

    public function delete() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(app::config()->site->admin_tools_link . "/menu");


        /*
         * get menu #ID from request
         */

        $menu_id = request::shiftParam("id");
        if (!utils::isNumber($menu_id)) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }


        /**
         * delete data
         */

        db::set("
            DELETE FROM menu
            WHERE id = %u", $menu_id
        );

        db::set("
            DELETE FROM menu_items
            WHERE menu_id = %u", $menu_id
        );


        /**
         * show redirect message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->menu_is_deleted,
            app::config()->site->admin_tools_link . "/menu"

        );


    }


    /**
     * view form of exists menu,
     * or save menu if exists POST data
     */

    public function edit() {


        /*
         * get menu #ID from request
         */

        $menu_id = request::shiftParam("id");
        if (!utils::isNumber($menu_id)) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }


        /*
         * get menu with #ID
         */

        $menu = db::normalizeQuery("
            SELECT id,name FROM menu WHERE id = %u", $menu_id
        );

        if (!$menu) {
            throw new memberErrorException(view::$language->error, view::$language->menu_not_found);
        }


        /**
         * save menu, THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            $this->saveMenu($menu_id);
        }


        /**
         * append data into view
         */

        view::assign("menu", $menu);
        view::assign("page_title", view::$language->menu_edit_exists);
        $this->setProtectedLayout("menu-edit.html");


    }


    /**
     * save group data
     */

    private function saveMenu($target = null) {


        /**
         * validate referer of possible CSRF attack
         */

        $adminToolsLink = app::config()->site->admin_tools_link;
        if ($target === null) {
            request::validateReferer($adminToolsLink . "/menu/create");
        } else {
            request::validateReferer($adminToolsLink . "/menu/edit\?id=\d+", true);
        }


        /**
         * validate name of menu
         */

        $name = request::getPostParam("name");
        if ($name === null) {
            throw new memberErrorException(view::$language->error, view::$language->data_not_enough);
        }

        if (!$name = filter::input($name)->lettersOnly()->getData()) {
            throw new memberErrorException(view::$language->error, view::$language->menu_name_invalid_format);
        }


        /**
         * save data
         */

        if ($target === null) {

            db::set("
                INSERT INTO menu (id,name)
                VALUES (NULL,'%s')", $name
            );

        } else {

            db::set("
                UPDATE menu SET name = '%s'
                WHERE id = %u", $name, $target
            );

        }


        $message = ($target === null)
            ? view::$language->menu_is_created : view::$language->menu_is_edited;


        /**
         * show redirect message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
            view::$language->success,
            $message,
            app::config()->site->admin_tools_link . "/menu"

        );


    }


}



