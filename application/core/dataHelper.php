<?php



/**
 * helper data class
 */

abstract class dataHelper {


    /**
     * inner pages data data
     */

    private static $pages           = array();
    private static $number_of_items = 0;
    private static $number_of_pages = 1;
    private static $current_page    = 1;


    /**
     * inner global data
     */

    private static $expectedSortKeys = null;
    private static $perPageLimit     = 10;
    private static $innerOptions     = array(

        "filter" => "",
        "sort"   => "lk ASC",
        "limit"  => "",
        "pages"  => false

    );


    /**
     * return node data with ID
     */

    public static function getNode($id, $options = array()) {

        self::validateOptions($id, $options);
        $node = db::cachedQuery(

            "SELECT t.id, t.parent_id, t.prototype, t.lvl, t.lk, t.rk,
                t.page_alias, t.node_name FROM tree t
                    WHERE t.is_publish = 1 AND t.id = %u", $id

        );

        if (!$node) {
            throw new memberErrorException("Helper error", "Node not found");
        }

        self::joinExtendedData($node, $options['more']);
        return $node[0];

    }


    /**
     * return children array from parent node ID
     */

    public static function getNodeChildren($id, $options = array()) {

        self::validateOptions($id, $options);
        $filter = self::$innerOptions['filter'];
        $sort   = self::$innerOptions['sort'];
        $limit  = self::$innerOptions['limit'];

        $sourceQuery = db::buildQuery(array(

            "SELECT t.id, t.parent_id, t.prototype, t.lvl, t.lk, t.rk,
                t.page_alias, t.node_name FROM tree t
                    WHERE t.is_publish = 1 {$filter} AND t.parent_id = %u
                        ORDER BY {$sort} {$limit}", $id

        ));

        $items = self::$innerOptions['pages']
            ? self::splitItems($sourceQuery) : db::query($sourceQuery);

        self::joinExtendedData($items, $options['more']);
        self::resetOptions();

        return $items;

    }


    /**
     * return full all levels chain array
     * of children from parent node ID
     */

    public static function getChainChildren($id, $options = array()) {

        self::validateOptions($id, $options);
        $filter = self::$innerOptions['filter'];
        $sort   = self::$innerOptions['sort'];
        $limit  = self::$innerOptions['limit'];

        $sourceQuery = db::buildQuery(array(

            "SELECT t.id, t.parent_id, t.prototype, t.lvl, t.lk, t.rk,
                t.page_alias, t.node_name FROM tree t,
                (SELECT lk, rk FROM tree WHERE id = %u AND is_publish = 1) tk
                    WHERE t.is_publish = 1 {$filter}
                        AND t.lk > tk.lk AND t.rk < tk.rk
                            ORDER BY {$sort} {$limit}", $id

        ));

        $items = self::$innerOptions['pages']
            ? self::splitItems($sourceQuery) : db::query($sourceQuery);

        self::joinExtendedData($items, $options['more']);
        self::resetOptions();

        return $items;

    }


    /**
     * return menu items array from menu ID
     */

    public static function getMenuItems($id, $options = array()) {

        self::validateOptions($id, $options);
        $filter = self::$innerOptions['filter'];
        $sort   = self::$innerOptions['sort'];
        $limit  = self::$innerOptions['limit'];

        $sourceQuery = db::buildQuery(array(

            "SELECT t.id, t.parent_id, t.prototype, t.lvl, t.lk, t.rk,
                t.page_alias, t.node_name FROM menu_items mi
                    JOIN tree t ON t.id = mi.node_id
                        WHERE t.is_publish = 1 {$filter} AND mi.menu_id = %u
                            ORDER BY {$sort} {$limit}", $id

        ));

        $items = self::$innerOptions['pages']
            ? self::splitItems($sourceQuery) : db::cachedQuery($sourceQuery);

        self::joinExtendedData($items, $options['more']);
        self::resetOptions();

        return $items;

    }


    /**
     * return attached images array with node ID
     */

    public static function getAttachedImages($nodeID) {

        if (!validate::isNumber($nodeID)) {
            throw new memberErrorException(
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
            throw new memberErrorException(
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

        if (!validate::isNumber($nodeID)) {
            throw new memberErrorException(
                "Helper error", "Node ID is not number"
            );
        }

        return db::query(

            "SELECT t.id, t.parent_id, t.lvl, t.node_name, t.page_alias
                FROM (SELECT lk, rk FROM tree WHERE id = %1\$u) k
                INNER JOIN tree t ON (
                    t.lk < k.lk AND t.rk > k.rk AND t.is_publish = 1
                        OR t.id = %1\$u
                        OR t.page_alias = IF(%2\$u = 0, '', '/')
                ) ORDER BY t.lvl ASC, t.lk ASC", $nodeID, ($showHome ? 1 : 0)

        );

    }


    /**
     * join extended (more) data of items
     */

    public static function joinExtendedData( & $items, $more) {


        /**
         * empty collection or empty more
         */

        if (!$items or !$more) {
            return;
        }


        /**
         * prepare collection data
         */

        $itemsIDs   = array();
        $prototypes = array();

        foreach ($items as $item) {
            array_push($itemsIDs, $item['id']);
            array_push($prototypes, $item['prototype']);
        }


        /**
         * get expected fields for this collection
         */

        $protoFields = array();
        $expectedFields = array();

        $protoNames = array_unique($prototypes);
        foreach ($protoNames as $item) {

            $prototype = new $item;
            $fields = $prototype->getPublicFields();
            $expectedFields = array_merge($expectedFields, $fields);
            $protoFields[$item] = $fields;

        }

        unset($prototype);
        unset($protoNames);

        $expectedFields = array_diff(
            array_unique($expectedFields), array("page_alias")
        );

        if (!$expectedFields) {
            return;
        }


        /**
         * join master image
         */

        $wantedFields = array("t.id");
        $masterImageQueryJoin = "";

        if (in_array("image", $more)) {

            $key = array_search("image", $more);
            if ($key !== null and $key !== false) {
                unset($more[$key]);
            }

            $noImage = app::config()->site->no_image;
            $masterImageQueryJoin
                = "LEFT JOIN images i ON i.node_id = t.id AND i.is_master = 1";

            array_push(
                $wantedFields,
                "IF(i.name IS NOT NULL,i.name,'{$noImage}') image"
            );

        }


        /**
         * prepare more fields,
         * build query string,
         * get extended data
         */

        foreach ($more as $item) {
            $pre = in_array($item, $expectedFields) ? "t." : "('') ";
            array_push($wantedFields, $pre . $item);
        }

        $wantedFields   = join(",", $wantedFields);
        $itemsIDsJoined = join(",", $itemsIDs);

        $itemsData = db::cachedQuery(
            "SELECT {$wantedFields} FROM tree t
                {$masterImageQueryJoin} WHERE t.id IN({$itemsIDsJoined})"
        );


        /**
         * get attached images and features
         */

        $withImages     = in_array("images", $more);
        $withFeatures   = in_array("features", $more);
        $attachedImages = array();
        $nodeFeatures   = array();

        if ($withImages) {
            $attachedImages = self::getAttachedImagesArray($itemsIDs);
        }

        if ($withFeatures) {
            $nodeFeatures = self::getNodeFeaturesArray($itemsIDs);
        }


        /**
         * merge extended data into items,
         * append attached images,
         * append node features
         */

        foreach ($items as $i => $item) {

            $curProto = $protoFields[$item['prototype']];
            foreach ($itemsData as $k => $data) {

                if ($data['id'] == $item['id']) {

                    foreach ($data as $edk => $none) {
                        if ($edk != "image" and !in_array($edk, $curProto)) {
                            $data[$edk] = "";
                        }
                    }

                    unset($data['id']);
                    unset($itemsData[$k]);

                    $items[$i] = array_merge($items[$i], $data);
                    break;

                }

            }

            if ($withImages) {

                $items[$i]['images'] = array();
                foreach ($attachedImages as $k => $image) {
                    if ($image['node_id'] == $item['id']) {
                        array_push($items[$i]['images'], $image['name']);
                        unset($attachedImages[$k]);
                    }
                }

            }

            if ($withFeatures) {

                $items[$i]['features'] = array();
                foreach ($nodeFeatures as $k => $f) {
                    if ($f['node_id'] == $item['id']) {
                        unset($nodeFeatures[$k]);
                        array_push($items[$i]['features'], array(
                            "name" => $f['name'], "value" => $f['value']
                        ));
                    }
                }

            }

        }


    }


    /**
     * validate helper input data
     */

    private static function validateOptions($id, & $options) {

        if (!validate::isNumber($id)) {
            throw new memberErrorException(
                "Helper error", "Target ID is not number"
            );
        }

        if (!is_array($options)) {
            throw new memberErrorException(
                "Helper error", "Options is not array"
            );
        }

        // more
        if (array_key_exists("more", $options)) {
            self::validateArrayedOption("more", $options['more']);
        } else {
            $options['more'] = array();
        }

        // filter
        if (array_key_exists("filter", $options)) {
            self::validateArrayedOption("filter", $options['filter']);
        } else {
            $options['filter'] = array();
        }

        if ($options['filter']) {
            $filter = db::escapeArray($options['filter']);
            self::$innerOptions['filter'] = " AND t.prototype IN({$filter}) ";
        }

        // sort
        if (array_key_exists("sort", $options)) {
            self::validateArrayedOption("sort", $options['sort']);
        } else {
            $options['sort'] = array();
        }

        if ($options['sort']) {

            $sortValues = array_values($options['sort']);
            $sortKeys   = array_keys($options['sort']);

            if (sizeof($sortKeys) != sizeof($sortValues)) {
                throw new memberErrorException(
                    "Helper error", "Broken sort options format"
                );
            }

            foreach ($sortValues as $k => $v) {
                $sortValues[$k] = preg_match("/^desc$/i", $v) ? "DESC" : "ASC";
            }

            if (self::$expectedSortKeys === null) {
                self::getExpectedSortKeys();
            }

            $preparedKeys = array();
            foreach ($sortKeys as $k => $key) {
                if (in_array($key, self::$expectedSortKeys)) {
                    $preKey = "t." . $key . (preg_match("/^desc$/i",
                                    $sortValues[$k]) ? " DESC" : " ASC");
                    array_push($preparedKeys, $preKey);
                }
            }

            if ($preparedKeys) {
                self::$innerOptions['sort'] = join(",", $preparedKeys);
            }

        }

        // pages
        if (array_key_exists("pages", $options)) {
            self::$innerOptions['pages'] = !!($options['pages']);

        }

        // limit
        if (array_key_exists("limit", $options)) {

            if (!validate::isNumber($options['limit'])) {
                throw new memberErrorException(
                    "Helper error", "Limit option is not number"
                );
            }

            if (!self::$innerOptions['pages']) {
                self::$innerOptions['limit'] = $options['limit'] == 0
                    ? "" : "LIMIT {$options['limit']}";
            } else if ($options['limit'] > 0) {
                self::$perPageLimit = $options['limit'];
            }

        }

    }


    /**
     * reset all options for new helper call
     */

    private static function resetOptions() {

        self::$perPageLimit = 10;
        self::$innerOptions = array(
            "filter" => "",
            "sort"   => "lk ASC",
            "limit"  => "",
            "pages"  => false
        );

    }


    /**
     * validate single arrayed option
     */

    private static function validateArrayedOption($key, $option) {

        if (!is_array($option)) {
            throw new memberErrorException(
                "Helper error", "{$key} option is not array"
            );
        }

        foreach ($option as $value) {
            if (!is_string($value)) {
                throw new memberErrorException(
                    "Helper error", "{$key} option value is not string"
                );
            }
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

        if (!$images) {
            return array();
        }

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


    /**
     * split items to pages,
     * assign pagination data into view
     */

    private static function splitItems($sourceQuery) {

        self::$innerOptions['pages'] = false;
        $paginator = new paginator($sourceQuery);
        $paginator =

            $paginator->setCurrentPage(request::getCurrentPage())
                ->setItemsPerPage(self::$perPageLimit)
                ->setSliceSizeByPages(10)
                ->getResult();

        self::$pages           = $paginator['pages'];
        self::$number_of_items = $paginator['number_of_items'];
        self::$number_of_pages = $paginator['number_of_pages'];
        self::$current_page    = $paginator['current_page'];

        return $paginator['items'];

    }


    /**
     * save all expected keys (fields) for sort
     */

    private static function getExpectedSortKeys() {

        self::$expectedSortKeys = array();
        foreach (db::query("SHOW COLUMNS FROM tree") as $item) {
            array_push(self::$expectedSortKeys, $item['Field']);
        }

    }


    /**
     * inner pages data getters
     */

    public static function getPages() {
        return self::$pages;
    }

    public static function getNumberOfItems() {
        return self::$number_of_items;
    }

    public static function getNumberOfPages() {
        return self::$number_of_pages;
    }

    public static function getCurrentPage() {
        return self::$current_page;
    }


}



