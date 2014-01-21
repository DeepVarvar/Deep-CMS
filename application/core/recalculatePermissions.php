<?php


/**
 * recalculate permissions helper
 */

abstract class recalculatePermissions {


    /**
     * recalculate all controllers permissions
     */

    public static function run() {

        $controllersPermissions = array();
        $controllers = controllerUtils::getAllControllers();
        foreach ($controllers as $controller) {
            foreach ($controller->getPermissions() as $current) {
                $check = in_array($current['permission'], $controllersPermissions);
                if (!$check) {
                    $controllersPermissions[] = $current['permission'];
                }
            }
        }

        $groupExistsPermissions = db::query(
            'SELECT p.name, gp.group_id FROM permissions p
                INNER JOIN group_permissions gp ON gp.permission_id = p.id
                INNER JOIN groups g ON g.id = gp.group_id
                WHERE p.name IN(%s) AND g.is_protected = 0', $controllersPermissions
        );

        db::set('TRUNCATE TABLE group_permissions');
        db::set('TRUNCATE TABLE permissions');

        $permissionValues = "('"
            . join("'), ('", $controllersPermissions) . "')";

        db::set('INSERT INTO permissions (name) VALUES ' . $permissionValues);
        $newPermissions = db::query('SELECT id, name FROM permissions');

        $newGroupsPermissions = array();
        $protectedGroups = db::query(
            'SELECT id FROM groups WHERE is_protected = 1'
        );

        foreach ($newPermissions as $permission) {

            foreach ($protectedGroups as $pg) {
                array_push(
                    $newGroupsPermissions,
                    '(' . $pg['id'] . ',' . $permission['id'] . ')'
                );
            }

            foreach ($groupExistsPermissions as $groupPermission) {
                if ($permission['name'] == $groupPermission['name']) {
                    array_push(
                        $newGroupsPermissions,
                        '(' . $groupPermission['group_id']
                                . ',' . $permission['id'] . ')'
                    );
                }
            }

        }

        $newGroupsPermissionsValues = join(', ', $newGroupsPermissions);
        db::set(
            'INSERT INTO group_permissions (group_id, permission_id)
                VALUES ' . $newGroupsPermissionsValues
        );

    }


}


