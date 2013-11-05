<?php



/**
 * admin submodule, manage groups of site users
 */

class groups extends baseController {


    /**
     * controllers for build permission list
     */

    private $controllers = array();


    /**
     * set permissions for this controller
     */

    public function setPermissions() {

        $this->permissions = array(

            array(

                "action"      => null,
                "permission"  => "groups_manage",
                "description" => view::$language->permission_groups_manage

            ),

            array(

                "action"      => "create",
                "permission"  => "groups_create",
                "description" => view::$language->permission_group_create

            ),

            array(

                "action"      => "delete",
                "permission"  => "groups_delete",
                "description" => view::$language->permission_group_delete

            ),

            array(

                "action"      => "edit",
                "permission"  => "groups_edit",
                "description" => view::$language->permission_group_edit

            )

        );

    }


    /**
     * view list of all groups
     */

    public function index() {


        $condition = (!member::isRoot() or member::getPriority() > 0)
            ? "WHERE priority > " . member::getPriority() : "";


        $sourceQuery = db::buildQueryString("
            SELECT id,priority,name FROM groups
            {$condition} ORDER BY priority ASC
        ");


        $paginator = new paginator($sourceQuery);
        $paginator =

            $paginator->setCurrentPage(request::getCurrentPage())
                ->setItemsPerPage(20)
                    ->setSliceSizeByPages(20)
                        ->getResult();


        view::assign("grouplist", $paginator['items']);
        view::assign("pages", $paginator['pages']);
        view::assign("page_title", view::$language->groups);

        $this->setProtectedLayout("groups.html");


    }


    /**
     * view form of new group,
     * or save new group if exists POST data
     */

    public function create() {


        /**
         * save new group, THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            $this->saveGroup();
        }


        /**
         * append data into view
         */

        view::assign("permissions", $this->getPermissionsList());
        view::assign("priority",    $this->getPriorityList());

        view::assign("page_title", view::$language->group_create_new);
        $this->setProtectedLayout("group-new.html");


    }


    /**
     * delete exists group
     */

    public function delete() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(
            app::config()->site->admin_tools_link . "/groups"
        );


        /*
         * get group #ID from request
         */

        $group_id = request::shiftParam("id");
        if (!validate::isNumber($group_id)) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );

        }


        /**
         * deny for delete system group
         */

        if ((string) $group_id == "0") {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->system_object_action_denied
            );

        }


        /**
         * deny for delete same origin group
         */

        if ($group_id == member::getGroupID()) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->group_cant_delete_so_group
            );

        }


        /**
         * get group with ID
         */

        $group = db::normalizeQuery(
            "SELECT priority FROM groups
                WHERE id = %u", $group_id
        );

        if (!$group) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->group_not_found
            );

        }


        /**
         * deny for delete high priority group
         */

        if (member::getPriority() >= $group) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->group_cant_delete_hoep_group
            );

        }


        /**
         * delete group
         */

        db::set(
            "DELETE FROM groups
                WHERE id = %u", $group_id
        );

        db::set(
            "DELETE FROM group_permissions
                WHERE group_id = %u", $group_id
        );


        /**
         * show redirect message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
                view::$language->success,
                    view::$language->group_is_deleted,
                        app::config()->site->admin_tools_link . "/groups"

        );


    }


    /**
     * view form of exists group,
     * or save changed group if exists POST data
     */

    public function edit() {


        /*
         * get group #ID from request
         */

        $group_id = request::shiftParam("id");
        if (!validate::isNumber($group_id)) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );

        }


        /**
         * deny for edit system group
         */

        if (!member::isRoot() and (string) $group_id === "0") {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->system_object_action_denied
            );

        }


        /**
         * deny for edit same origin group
         */

        if (!member::isRoot() and member::getGroupID() == $group_id) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->group_cant_edit_so_group
            );

        }


        /*
         * get group with #ID
         */

        $group = db::normalizeQuery(
            "SELECT id,name,priority FROM groups
                WHERE id = %u", $group_id
        );

        if (!$group) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->group_not_found
            );

        }


        /**
         * deny for edit high priority group
         */

        if (!member::isRoot()
                and member::getPriority() >= $group['priority']) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->group_cant_edit_hoep_group
            );

        }


        /**
         * save chenged group,
         * THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            $this->saveGroup($group_id);
        }


        /**
         * append data into view
         */

        view::assign("group", $group);

        view::assign(
            "permissions",
            $this->getPermissionsList($group['id'])
        );

        view::assign(
            "priority",
            $this->getPriorityList($group['priority'])
        );

        view::assign("page_title", view::$language->group_edit_exists);
        $this->setProtectedLayout("group-edit.html");


    }


    /**
     * save group data
     */

    private function saveGroup($target = null) {


        /**
         * validate referer of possible CSRF attack
         */

        $adminToolsLink = app::config()->site->admin_tools_link;
        if ($target === null) {

            request::validateReferer(
                $adminToolsLink . "/groups/create"
            );

        } else {

            request::validateReferer(
                $adminToolsLink . "/groups/edit\?id=\d+", true
            );

        }


        /**
         * get required data
         */

        $required = request::getRequiredPostParams(array("name", "priority"));
        if ($required === null) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_not_enough
            );

        }


        /**
         * check priority format
         */

        if (!validate::isNumber($required['priority'])) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->group_priority_invalid
            );

        }


        /**
         * check max allow priority level
         */

        if (!member::isRoot()
                and member::getPriority() >= $required['priority']) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->group_cant_set_hoe_priority
            );

        }

        if ($required['priority']
                > app::config()->system->max_group_priority_number) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->group_priority_invalid
            );

        }


        /**
         * filtered and check name of group
         */

        $required['name'] = filter::input(
            $required['name'])->lettersOnly()->getData();

        if (!$required['name']) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->group_name_invalid
            );

        }


        /**
         * save group data
         */

        if ($target === null) {

            db::set(
                "INSERT INTO groups (name, priority) VALUES ('%s', %u)",
                $required['name'], $required['priority']
            );

        } else {

            db::set(
                "UPDATE groups SET name = '%s', priority = %u WHERE id = %u",
                $required['name'], $required['priority'], $target
            );

        }


        /**
         * set group permissions
         */

        $permissions = request::getPostParam("permissions");
        if (!$permissions) {
            $permissions = array();
        }

        $permissions = filter::input(
            array_keys($permissions))->lettersOnly()->getData();

        if ($permissions) {

            $inDbPermissions = db::normalizeQuery("
                SELECT id FROM permissions
                WHERE name IN(%s)", $permissions
            );

            if (is_string($inDbPermissions)) {
                $inDbPermissions = array($inDbPermissions);
            }

        } else {
            $inDbPermissions = array();
        }

        if ($inDbPermissions) {


            /**
             * set permissions for group
             */

            if ($target === null) {
                $groupId = db::lastID();
            } else {

                db::set("
                    DELETE FROM group_permissions
                    WHERE group_id = %u", $target
                );

                $groupId = $target;

            }

            $insertData = "(" . $groupId . ","
                . join("), (" . $groupId . ",", $inDbPermissions) . ");";

            db::set(
                "INSERT INTO group_permissions
                    (group_id,permission_id) VALUES %s", $insertData
            );

        }

        $message = ($target === null)
            ? view::$language->group_is_created
            : view::$language->group_is_edited;


        /**
         * show redirect message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
                view::$language->success,
                    $message,
                        app::config()->site->admin_tools_link . "/groups"

        );


    }


    /**
     * return options array for priority
     */

    private function getPriorityList($current = -1) {


        $priorityList = array();
        $maxPriority = app::config()->system->max_group_priority_number;

        foreach (range($maxPriority, 0) as $value) {

            if (!member::isRoot() and member::getPriority() >= $value) {
                break;
            }

            $priority = array(

                "value"       => $value,
                "description" => $value,
                "selected"    => ($current == $value)

            );

            array_push($priorityList, $priority);

        }

        return $priorityList;


    }


    /**
     * return options array for permissions
     */

    private function getPermissionsList($existsGroup = -1) {


        $permissions = array();
        $inDbPermissions = db::query("

            SELECT

                p.name,
                gp.permission_id

            FROM permissions p

            LEFT JOIN group_permissions gp
                ON(gp.permission_id = p.id AND gp.group_id = %u)

            ORDER BY p.id

            ",

            $existsGroup

        );

        foreach ($inDbPermissions as $item) {

            $current = $this->getControllerPermissions($item['name']);
            $checkbox = array(

                "checked"     => ($item['permission_id'] !== null),
                "description" => $current['description'],
                "name"        => "permissions[{$item['name']}]"

            );

            array_push($permissions, $checkbox);

        }

        return $permissions;


    }


    /**
     * return controller permission with permission name
     */

    private function getControllerPermissions($name) {


        /**
         * get controllers for first attempt
         */

        if (!$this->controllers) {
            $this->controllers = utils::getAllControllers();
        }


        /**
         * get permission with name
         */

        foreach ($this->controllers as $controller) {

            foreach ($controller->getPermissions() as $current) {

                if ($current['permission'] == $name) {
                    return $current;
                }

            }

        }


        /**
         * don't have permission?
         */

        throw new memberErrorException(
            "Permission error",
                "Failure data. You need recalculate site permissions"
        );


    }


}



