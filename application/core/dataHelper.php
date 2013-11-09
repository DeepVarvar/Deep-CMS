<?php



/**
 * helper data class
 */

abstract class dataHelper {


    /**
     * return node data with ID
     */

    public static function getNode(
                    $id, $more = array(), $with = DATA_WITHOUT_ALL) {


        self::validateIdMoreWith($id, $more, $with);
        $noImg = app::config()->site->no_image;
        $node = db::query("

            SELECT

                d.id,
                d.parent_id,
                d.lvl,
                IF(i.name IS NOT NULL,i.name,'{$noImg}') image,
                d.page_alias,
                d.node_name,
                d.author author_id,
                u.login author_name,
                d.last_modified,
                d.creation_date

            FROM tree d
            LEFT JOIN users u ON u.id = d.author
            LEFT JOIN images i ON i.node_id = d.id AND i.is_master = 1
            WHERE d.is_publish = 1 AND d.id = %u", $id

        );


        if ($node) {
            self::joinExtendedItemsData($node, $more, $with);
            return $node[0];
        }

        return array();


    }


    /**
     * return children array from parent node ID
     */

    public static function getNodeChildren($id, $more = array(),
                    $with = DATA_WITHOUT_ALL, $limit = 0, $orderBy = null) {


        self::validateIdMoreWith($id, $more, $with);
        if (!validate::isNumber($limit)) {
            throw new systemErrorException(
                "Helper error", "Limit is not number"
            );
        }

        $limit = $limit == 0 ? "" : "LIMIT {$limit}";
        $noImg = app::config()->site->no_image;
        $items = db::query("

            SELECT

                d.id,
                d.parent_id,
                d.lvl,
                IF(i.name IS NOT NULL,i.name,'{$noImg}') image,
                d.page_alias,
                d.node_name,
                d.author author_id,
                u.login author_name,
                d.last_modified,
                d.creation_date

            FROM tree d
            LEFT JOIN users u ON u.id = d.author
            LEFT JOIN images i ON i.node_id = d.id AND i.is_master = 1

            WHERE d.is_publish = 1
                AND d.parent_id = %u {$limit}", $id

        );

        self::joinExtendedItemsData($items, $more, $with);
        return $items;


    }


    /**
     * return menu items array from menu ID
     */

    public static function getMenuItems(
                        $id, $more = array(), $with = DATA_WITHOUT_ALL) {


        self::validateIdMoreWith($id, $more, $with);
        $noImg = app::config()->site->no_image;
        $items = db::cachedQuery("

            SELECT

                d.id,
                d.parent_id,
                d.lvl,
                IF(i.name IS NOT NULL,i.name,'{$noImg}') image,
                d.page_alias,
                d.node_name,
                d.author author_id,
                u.login author_name,
                d.last_modified,
                d.creation_date

            FROM menu_items mi
            JOIN tree d ON d.id = mi.node_id
            LEFT JOIN users u ON u.id = d.author
            LEFT JOIN images i ON i.node_id = d.id AND i.is_master = 1

            WHERE d.is_publish = 1 AND mi.menu_id = %u
            ORDER BY d.lk ASC

            ", $id

        );

        self::joinExtendedItemsData($items, $more, $with);
        return $items;


    }


    /**
     * return attached images array with node ID
     */

    public static function getAttachedImages($nodeID) {

        if (!validate::isNumber($nodeID)) {
            throw new systemErrorException(
                "Helper error", "Node ID is not number"
            );
        }

        $images = array();
        foreach (self::getAttachedImagesArray(array($nodeID), false) as $img) {
            array_push($images, $img['name']);
        }

        return $images;

    }


    /**
     * return features array with node ID
     */

    public static function getNodeFeatures($nodeID) {

        if (!validate::isNumber($nodeID)) {
            throw new systemErrorException(
                "Helper error", "Node ID is not number"
            );
        }

        $features = array();
        foreach (self::getNodeFeaturesArray(array($nodeID)) as $feature) {

            array_push($features, array(
                "name"  => $feature['name'],
                "value" => $feature['value']
            ));

        }

        return $features;

    }


    /**
     * return breadcrumbs items array with current node ID
     */

    public static function getBreadcrumbs($nodeID, $showHome = false) {

        return db::query(

            "SELECT d.id, d.parent_id, d.lvl, d.node_name, d.page_alias
            FROM (SELECT lk, rk, page_alias FROM tree WHERE id = %u) t

            INNER JOIN tree d ON (

                d.lk < t.lk AND d.rk > t.rk AND d.is_publish = 1
                    OR d.id = %u OR d.page_alias = IF(%u = 0, '', '/')

            ) ORDER BY d.lvl ASC", $nodeID, $nodeID, ($showHome ? 1 : 0)

        );

    }


    /**
     * join extended (more) data of items
     */

    public static function joinExtendedItemsData(
                                & $items, $more, $with = DATA_WITHOUT_ALL) {




        /**
         * get attached images and features
         */

        $attachedImages = array();
        $nodeFeatures = array();

        $withImages = false;
        $withFeatures = false;

        switch (true) {

            case ($with == DATA_WITH_ALL):

                $withImages = true;
                $withFeatures = true;

                $nodeFeatures = self::getNodeFeaturesArray($itemsIDs);
                $attachedImages   = self::getAttachedImagesArray($itemsIDs);

            break;

            case ($with == DATA_WITH_IMAGES):

                $withImages = true;
                $attachedImages = self::getAttachedImagesArray($itemsIDs);

            break;

            case ($with == DATA_WITH_FEATURES):

                $withFeatures = true;
                $nodeFeatures = self::getNodeFeaturesArray($itemsIDs);

            break;

            default:
                // NONE
            break;

        }


        /**
         * merge extended data into items
         */

        foreach ($items as $i => $item) {


            /**
             * append attached images
             */

            if ($withImages) {
                $items[$i]['images'] = array();
            }

            foreach ($attachedImages as $k => $image) {

                if ($image['node_id'] == $item['id']) {
                    array_push($items[$i]['images'], $image['name']);
                    unset($attachedImages[$k]);
                }

            }


            /**
             * append node features
             */

            if ($withFeatures) {
                $items[$i]['features'] = array();
            }

            foreach ($nodeFeatures as $k => $feature) {

                if ($feature['node_id'] == $item['id']) {

                    $feature = array(
                        "name"  => $feature['name'],
                        "value" => $feature['value']
                    );

                    array_push($items[$i]['features'], $feature);
                    unset($nodeFeatures[$k]);

                }

            }

        }


    }


    /**
     * validate base helper input data
     */

    private static function validateIdMoreWith($id, $more, $with) {

        if (!validate::isNumber($id)) {
            throw new systemErrorException(
                "Helper error", "Menu ID is not number"
            );
        }

        if (!is_array($more)) {
            throw new systemErrorException(
                "Helper error", "More data names is not array"
            );
        }

        if (!validate::isNumber($with)) {
            throw new systemErrorException(
                "Helper error", "Extended data type is not number"
            );
        }

    }


    /**
     * return attahced images array
     */

    private static function getAttachedImagesArray($IDs, $multi = true) {


        $noImage = app::config()->site->no_image;
        $images = db::query(

            "SELECT node_id, name
                FROM images WHERE node_id IN(%s)
                    ORDER BY is_master DESC, id ASC", join(",", $IDs)

        );

        if ($multi) {

            $noImages = array();
            foreach ($images as $image) {
                if (!in_array($image['node_id'], $IDs)) {
                    $noImages[] = array(
                        "node_id" => $id,
                        "name"    => $noImage
                    );
                }
            }

            $images = array_merge($images, $noImages);
            if (!$images) {

                foreach ($IDs as $id) {
                    $images[] = array(
                        "node_id" => $id,
                        "name"    => $noImage
                    );
                }

            }

        }

        return $images;


    }


    /**
     * return node features array
     */

    private static function getNodeFeaturesArray($IDs) {

        return db::query(

            "SELECT tf.node_id, tf.feature_value value, f.name
                FROM tree_features tf
                    INNER JOIN features f ON f.id = tf.feature_id
                        WHERE tf.node_id IN(%s)
                            ORDER BY tf.feature_id ASC", join(",", $IDs)

        );

    }


}



