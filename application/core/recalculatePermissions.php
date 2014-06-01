<?php


/**
 * recalculate permissions helper
 */

abstract class recalculatePermissions {


    /**
     * recalculate all controllers permissions
     */

    public static function run() {

        $cPerms = array();
        foreach (controllerUtils::getAllControllers() as $c) {
            foreach ($c->getPermissions() as $curr) {
                if (!in_array($curr['permission'], $cPerms)) {
                    $cPerms[] = $curr['permission'];
                }
            }
        }

        $exPerms = db::query(
            'SELECT p.name, gp.group_id
                FROM permissions p
                INNER JOIN group_permissions gp ON gp.permission_id = p.id
                INNER JOIN groups g ON g.id = gp.group_id
                WHERE p.name IN(%s)', $cPerms
        );

        db::set('TRUNCATE TABLE permissions');
        db::set('TRUNCATE TABLE group_permissions');

        db::set(
            'INSERT INTO permissions (name)
                VALUES ' . "('" . join("'), ('", $cPerms) . "')"
        );

        $addPerms = array();
        $newPerms = array();
        foreach (db::query('SELECT id, name FROM permissions') as $perm) {

            foreach ($exPerms as $exPerm) {
                if ($perm['name'] == $exPerm['name']) {
                    $addPerms[] = $perm['id'];
                    $newPerms[] = '(' . $exPerm['group_id'] . ',' . $perm['id'] . ')';
                }
            }

            if (!in_array($perm['id'], $addPerms)) {
                $addPerms[] = $perm['id'];
                $newPerms[] = '(3,' . $perm['id'] . ')';
            }

        }

        db::set(
            'INSERT INTO group_permissions (group_id, permission_id)
                VALUES ' . join(', ', $newPerms)
        );

    }


}


