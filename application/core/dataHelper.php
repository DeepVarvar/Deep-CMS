<?php



/**
 * helper data class
 */

abstract class dataHelper {


    /**
     * return document data with ID
     */

    public static function getDocument($id, $more = array(), $with = DATA_WITHOUT_ALL) {


        /**
         * validate input data
         */

        if (!validate::isNumber($id)) {
            throw new systemErrorException("Helper error", "Document ID is not number");
        }

        if (!is_array($more)) {
            throw new systemErrorException("Helper error", "More data names is not array");
        }

        if (!validate::isNumber($with)) {
            throw new systemErrorException("Helper error", "Extended data type is not number");
        }


        /**
         * get document data
         */

        $noImg = app::config()->site->no_image;
        $document = db::query("

            SELECT

                d.id,
                d.parent_id,
                d.prototype,
                d.props_id,
                IF(i.name IS NOT NULL,i.name,'{$noImg}') image,
                d.page_alias,
                d.page_name,
                d.page_h1,
                d.page_title,
                d.author author_id,
                u.login author_name,
                d.last_modified,
                d.creation_date,
                p.sys_name

            FROM documents d
            JOIN prototypes p ON p.id = d.prototype
            LEFT JOIN users u ON u.id = d.author
            LEFT JOIN images i ON i.document_id = d.id AND i.is_master = 1

            WHERE d.is_publish = 1 AND d.id = %u

            ",

            $id

        );


        if ($document) {

            self::joinExtendedItemsData($document, $more, $with);
            return $document[0];

        }


    }


    /**
     * return children array from document ID
     */

    public static function getDocumentChildren($id, $more = array(), $with = DATA_WITHOUT_ALL, $limit = 0, $orderBy = null) {


        /**
         * validate input data
         */

        if (!validate::isNumber($id)) {
            throw new systemErrorException("Helper error", "Document ID is not number");
        }

        if (!is_array($more)) {
            throw new systemErrorException("Helper error", "More data names is not array");
        }

        if (!validate::isNumber($with)) {
            throw new systemErrorException("Helper error", "Extended data type is not number");
        }

        if (!validate::isNumber($limit)) {
            throw new systemErrorException("Helper error", "Limit is not number");
        }


        /**
         * get base (less) data of items
         */

        $limit = $limit == 0 ? "" : "LIMIT {$limit}";
        $orderBy = $orderBy !== null ? $orderBy : "d.sort ASC";
        $noImg = app::config()->site->no_image;

        $items = db::query("

            SELECT

                d.id,
                d.parent_id,
                d.prototype,
                d.props_id,
                IF(i.name IS NOT NULL,i.name,'{$noImg}') image,
                d.page_alias,
                d.page_name,
                d.page_h1,
                d.page_title,
                d.author author_id,
                u.login author_name,
                d.last_modified,
                d.creation_date,
                p.sys_name

            FROM documents d
            JOIN prototypes p ON p.id = d.prototype
            LEFT JOIN users u ON u.id = d.author
            LEFT JOIN images i ON i.document_id = d.id AND i.is_master = 1

            WHERE d.is_publish = 1 AND d.parent_id = %u
            ORDER BY {$orderBy}
            {$limit}

            ",

            $id

        );


        /**
         * join extendeded (more) data of items
         */

        self::joinExtendedItemsData($items, $more, $with);
        return $items;


    }


    /**
     * return menu items array from menu ID
     */

    public static function getMenuItems($id, $more = array(), $with = DATA_WITHOUT_ALL) {


        /**
         * validate input data
         */

        if (!validate::isNumber($id)) {
            throw new systemErrorException("Helper error", "Menu ID is not number");
        }

        if (!is_array($more)) {
            throw new systemErrorException("Helper error", "More data names is not array");
        }

        if (!validate::isNumber($with)) {
            throw new systemErrorException("Helper error", "Extended data type is not number");
        }


        /**
         * get base (less) data of items
         */

        $noImg = app::config()->site->no_image;
        $items = db::cachedQuery("

            SELECT

                d.id,
                d.parent_id,
                d.prototype,
                d.props_id,
                IF(i.name IS NOT NULL,i.name,'{$noImg}') image,
                d.page_alias,
                d.page_name,
                d.page_h1,
                d.page_title,
                d.author author_id,
                u.login author_name,
                d.last_modified,
                d.creation_date,
                p.sys_name

            FROM menu_items mi
            JOIN documents d ON d.id = mi.document_id
            JOIN prototypes p ON p.id = d.prototype
            LEFT JOIN users u ON u.id = d.author
            LEFT JOIN images i ON i.document_id = d.id AND i.is_master = 1

            WHERE d.is_publish = 1 AND mi.menu_id = %u
            ORDER BY d.sort ASC

            ",

            $id

        );


        /**
         * join extendeded (more) data of items
         */

        self::joinExtendedItemsData($items, $more, $with);
        return $items;


    }


    /**
     * return attached images array with document ID
     */

    public static function getAttachedImages($documentID) {


        /**
         * validate input ID
         */

        if (!validate::isNumber($documentID)) {
            throw new systemErrorException("Helper error", "Document ID is not number");
        }


        /**
         * normalize for single output
         */

        $images = array();
        foreach (self::getAttachedImagesArray(array($documentID), false) as $image) {
            array_push($images, $image['name']);
        }


        return $images;


    }


    /**
     * return features array with document ID
     */

    public static function getDocumentFeatures($documentID) {


        if (!validate::isNumber($documentID)) {
            throw new systemErrorException("Helper error", "Document ID is not number");
        }


        /**
         * normalize for single output
         */

        $features = array();
        foreach (self::getDocumentFeaturesArray(array($documentID)) as $feature) {
            $feature = array("name" => $feature['name'], "value" => $feature['value']);
            array_push($features, $feature);
        }


        return $features;


    }


    /**
     * return breadcrumbs items array with document ID
     */

    public static function getBreadcrumbs($documentID, $showHome = false) {


        return db::query("

                SELECT

                    d.id,
                    d.parent_id,
                    d.page_name,
                    d.page_alias

                FROM (
                    SELECT lk, rk, page_alias FROM documents WHERE id = %u
                ) t

                INNER JOIN documents d ON (

                    d.lk < t.lk AND d.rk > t.rk AND d.is_publish = 1
                        OR d.id = %u OR d.page_alias = IF(%u = 0, '', '/')

                )

                ORDER BY d.lvl ASC;


            ",

            $documentID,
            $documentID,
            ($showHome ? 1 : 0)

        );


    }


    /**
     * join extended (more) data of items
     */

    public static function joinExtendedItemsData( & $items, $more, $with = DATA_WITHOUT_ALL) {


        /**
         * empty data
         */

        $moreFields = array();
        $foundedPrototypes = array();
        $queryParts = array();


        $itemsIDs = array();
        foreach ($items as $item) {
            array_push($itemsIDs, $item['id']);
        }


        /**
         * prepare data
         */

        foreach ($more as $item) {

            if (!validate::likeString($item)) {
                throw new systemErrorException("Data helper error", "Extended (more) name is not string");
            }

            $moreFields = array_merge($moreFields, array($item));

        }


        /**
         * prepare proto data
         */

        if ($moreFields) {


            /**
             * get full list of prototypes fields
             */

            $protoFields = db::cachedQuery("SELECT prototype,name FROM field_types");


            /**
             * prepare query data
             */

            foreach ($items as $item) {


                $name = $item['sys_name'];

                if (!array_key_exists($name, $foundedPrototypes)) {

                    $foundedPrototypes[$name] = array();
                    $foundedPrototypes[$name]['ids'] = array();
                    $foundedPrototypes[$name]['fields'] = array();

                }


                array_push($foundedPrototypes[$name]['ids'], $item['props_id']);


                foreach ($moreFields as $mf) {


                    /**
                     * protect founded fields
                     */

                    if (array_key_exists($mf, $item)) {
                        continue;
                    }


                    /**
                     * normalize not exists field name
                     */

                    $field = "('') {$mf}";
                    foreach ($protoFields as $pf) {

                        if ($pf['prototype'] == $item['prototype'] and $pf['name'] == $mf) {
                            $field = $mf;
                            break;
                        }

                    }


                    /**
                     * append field into query part
                     */

                    if (!in_array($field, $foundedPrototypes[$name]['fields'])) {
                        array_push($foundedPrototypes[$name]['fields'], $field);
                    }


                }


            }


        }


        /**
         * build query
         */

        foreach ($foundedPrototypes as $name => $item) {


            $fields = join(",", $item['fields']);
            $ids    = join(",", $item['ids']);

            array_push($queryParts, "SELECT ('{$name}') sys_name,id,{$fields} FROM {$name} WHERE id IN({$ids})");


        }


        /**
         * get extended data
         * run query
         */

        $itemsData = array();

        if ($queryParts) {

            $fullQuery = join(" UNION ALL ", $queryParts);
            $itemsData = db::query($fullQuery);

        }


        /**
         * get attached images and features
         */

        $attachedImages = array();
        $documentFeatures = array();

        $withImages = false;
        $withFeatures = false;


        switch (true) {


            case ($with == DATA_WITH_ALL):


                $withImages = true;
                $withFeatures = true;

                $documentFeatures = self::getDocumentFeaturesArray($itemsIDs);
                $attachedImages   = self::getAttachedImagesArray($itemsIDs);


            break;


            case ($with == DATA_WITH_IMAGES):

                $withImages = true;
                $attachedImages = self::getAttachedImagesArray($itemsIDs);

            break;


            case ($with == DATA_WITH_FEATURES):

                $withFeatures = true;
                $documentFeatures = self::getDocumentFeaturesArray($itemsIDs);

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
             * append extended data fields
             */

            foreach ($itemsData as $k => $data) {


                if ($data['sys_name'] == $item['sys_name'] and $item['props_id'] == $data['id']) {

                    unset($data['id']);
                    $items[$i] = array_merge($items[$i], $data);

                    unset($itemsData[$k]);
                    break;

                }


            }


            /**
             * append attached images
             */

            if ($withImages) {
                $items[$i]['attached_images'] = array();
            }

            foreach ($attachedImages as $k => $image) {

                if ($image['document_id'] == $item['id']) {
                    array_push($items[$i]['attached_images'], $image['name']);
                    unset($attachedImages[$k]);
                }

            }


            /**
             * append document features
             */

            if ($withFeatures) {
                $items[$i]['document_features'] = array();
            }

            foreach ($documentFeatures as $k => $feature) {

                if ($feature['document_id'] == $item['id']) {

                    $feature = array("name" => $feature['name'], "value" => $feature['value']);
                    array_push($items[$i]['document_features'], $feature);
                    unset($documentFeatures[$k]);

                }

            }


        }


    }


    /**
     * return attahced images array
     */

    private static function getAttachedImagesArray($IDs, $multiResult = true) {


        /**
         * get attached images collection
         */

        $images = db::query("

            SELECT

                document_id,
                name

            FROM images
            WHERE document_id IN(%s)
            ORDER BY is_master DESC, id ASC

            ",

            join(",", $IDs)

        );


        /**
         * this normalization only for multi results of items
         */

        if ($multiResult) {


            /**
             * normalize output for not exists images
             */

            $noImages = array();
            $noImg = app::config()->site->no_image;

            foreach ($images as $image) {

                if (!in_array($image['document_id'], $IDs)) {
                    $noImages[] = array("document_id" => $id, "name" => $noImg);
                }

            }

            $images = array_merge($images, $noImages);


            /**
             * normalize empty images list
             */

            if (!$images) {

                foreach ($IDs as $id) {
                    $images[] = array("document_id" => $id, "name" => app::config()->site->no_image);
                }

            }


        }


        return $images;


    }


    /**
     * return document features array
     */

    private static function getDocumentFeaturesArray($IDs) {


        /**
         * get features array collection
         */

        return db::query("

            SELECT

                df.document_id,
                df.feature_value value,
                f.name

            FROM document_features df
            INNER JOIN features f ON f.id = df.feature_id
            WHERE df.document_id IN(%s)
            ORDER BY df.feature_id ASC

            ",

            join(",", $IDs)

        );


    }


}



