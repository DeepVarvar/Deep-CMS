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
                'action'      => null,
                'permission'  => 'menu_manage',
                'description' => view::$language->permission_menu_manage
            ),
            array(
                'action'      => 'create',
                'permission'  => 'menu_create',
                'description' => view::$language->permission_menu_create
            ),
            array(
                'action'      => 'delete',
                'permission'  => 'menu_delete',
                'description' => view::$language->permission_menu_delete
            ),
            array(
                'action'      => 'edit',
                'permission'  => 'menu_edit',
                'description' => view::$language->permission_menu_edit
            )
        );

    }


    /**
     * view list of menu
     */

    public function index() {

        $paginator = new paginator(
            'SELECT id, mirror_id, name FROM menu ORDER BY mirror_id ASC'
        );
        $paginator = $paginator->setCurrentPage(request::getCurrentPage())
            ->setItemsPerPage(20)->setSliceSizeByPages(20)->getResult();

        view::assign('menulist', $paginator['items']);
        view::assign('pages', $paginator['pages']);
        view::assign('node_name', view::$language->menu_title);
        $this->setProtectedLayout('menu.html');

    }


    /**
     * view form of new menu,
     * or save new menu if exists POST data
     */

    public function create() {

        if (request::isPost()) {
            $this->saveMenu();
        }

        view::assign('node_name', view::$language->menu_create_title);
        $this->setProtectedLayout('menu-new.html');

    }


    /**
     * full delete menu
     */

    public function delete() {

        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer($adminToolsLink . '/menu');

        $menu_id = request::shiftParam('id');
        if (!validate::isNumber($menu_id)) {
            throw new memberErrorException(
                view::$language->menu_error,
                view::$language->menu_data_invalid
            );
        }

        db::set('DELETE FROM menu WHERE id = %u', $menu_id);
        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->menu_success,
            view::$language->menu_is_deleted,
            $adminToolsLink . '/menu'
        );

    }


    /**
     * view form of exists menu,
     * or save menu if exists POST data
     */

    public function edit() {

        $menu_id = request::shiftParam('id');
        if (!validate::isNumber($menu_id)) {
            throw new memberErrorException(
                view::$language->menu_error,
                view::$language->menu_data_invalid
            );
        }

        $menu = db::normalizeQuery(
            'SELECT id, mirror_id, name FROM menu WHERE id = %u', $menu_id
        );

        if (!$menu) {
            throw new memberErrorException(
                view::$language->menu_error,
                view::$language->menu_not_found
            );
        }

        if (request::isPost()) {
            $this->saveMenu($menu_id);
        }

        view::assign('menu', $menu);
        view::assign('node_name', view::$language->menu_edit_title);
        $this->setProtectedLayout('menu-edit.html');

    }


    /**
     * save menu data
     */

    private function saveMenu($target = null) {

        $adminToolsLink = app::config()->site->admin_tools_link;
        if ($target === null) {
            request::validateReferer($adminToolsLink . '/menu/create');
        } else {
            request::validateReferer(
                $adminToolsLink . '/menu/edit\?id=\d+', true
            );
        }

        $name = request::getPostParam('name');
        if ($name === null) {
            throw new memberErrorException(
                view::$language->menu_error,
                view::$language->menu_data_not_enough
            );
        }

        if (!$name = filter::input($name)->lettersOnly()->getData()) {
            throw new memberErrorException(
                view::$language->menu_error,
                view::$language->menu_name_invalid
            );
        }

        $mirrorID = request::getPostParam('mirror_id');
        if ($mirrorID === null) {
            throw new memberErrorException(
                view::$language->menu_error,
                view::$language->menu_data_not_enough
            );
        }

        if (!validate::isNumber($mirrorID) or $mirrorID < 1) {
            throw new memberErrorException(
                view::$language->menu_error,
                view::$language->menu_mirror_id_invalid
            );
        }

        if ($mirrorID > 10000) {
            throw new memberErrorException(
                view::$language->menu_error,
                view::$language->menu_mirror_id_less
            );
        }

        if ($target === null) {

            $exCheck = 'SELECT (1) ex FROM menu WHERE mirror_id = %u LIMIT 1';
            if (db::query($exCheck, $mirrorID)) {
                throw new memberErrorException(
                    view::$language->menu_error,
                    view::$language->menu_mirror_id_is_not_uniq
                );
            }

            db::set(
                "INSERT INTO menu (id, mirror_id, name)
                    VALUES (NULL, %u, '%s')", $mirrorID, $name
            );

            $menuID = db::lastID();

        } else {

            $menuID = $target;
            $exCheck = 'SELECT (1) ex FROM menu
                WHERE id != %u AND mirror_id = %u LIMIT 1';

            if (db::query($exCheck, $target, $mirrorID)) {
                throw new memberErrorException(
                    view::$language->menu_error,
                    view::$language->menu_mirror_id_is_not_uniq
                );
            }

            db::set(
                'UPDATE menu_items SET menu_id = %u WHERE menu_id IN(
                    SELECT mirror_id FROM menu WHERE id = %u
                )', $mirrorID, $target
            );

            db::set(
                "UPDATE menu SET mirror_id = %u, name = '%s'
                    WHERE id = %u", $mirrorID, $name, $target
            );

        }

        $location = request::getPostParam('silentsave')
            ? '/edit?id=' . $menuID : '';

        $message = ($target === null)
            ? view::$language->menu_is_created
            : view::$language->menu_is_edited;

        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->menu_success,
            $message,
            $adminToolsLink . '/menu' . $location
        );

    }


}


