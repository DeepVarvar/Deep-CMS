<?php


/**
 * view counter component class
 */

abstract class viewCounter {


    public static function init() {

        $nodeID = storage::read("nodeID");
        if ($nodeID > 0) {
            $sid = !member::isAuth() ? session_id() : md5(member::getID());
            db::set(
                "REPLACE INTO view_count (session_id, node_id)
                    VALUES('%s', %u)", $sid, $nodeID
            );
        }

    }

    public static function getCount($id) {

        if (!validate::isNumber($id)) {
            throw new memberErrorException(
                "View counter error", "Target ID is not number"
            );
        }
        return db::cachedNormalizeQuery(
            "SELECT COUNT(1) cnt FROM view_count WHERE node_id = %u", $id
        );

    }


}


