<?php



/**
 * admin submodule, manage node features
 */

class node_features extends baseController {


    private


        /**
         * storage key of working cache for features
         */

        $storageDataKey = "__stored_features",


        /**
         * storage saved mode for new node
         */

        $storageMode = false;


    /**
     * choose global mode from run before method
     */

    public function runBefore() {

        if (request::getParam("target") === "new") {
            $this->storageMode = true;
        }

    }


    /**
     * set permissions for this controller
     * this permissions repeat of documents tree admin controller,
     * but and you can make one permission for some other actions
     */

    public function setPermissions() {

        $this->permissions = array(

            array(

                "action"      => null,
                "permission"  => "documents_tree_manage",
                "description"
                    => view::$language->permission_documents_tree_manage

            )

        );

    }


    /**
     * show list of features
     */

    public function index() {


        /**
         * choose mode
         */

        if ($this->storageMode) {
            $this->getFeaturesFromStorage();
        } else {


            /**
             * validate target node ID
             */

            $targetNode = request::shiftParam("target");
            if (!validate::isNumber($targetNode)) {

                throw new memberErrorException(
                    view::$language->error,
                        view::$language->data_invalid
                );

            }

            $this->getFeaturesFromDB($targetNode);

        }

        view::assign("node_name", view::$language->features);
        $this->setProtectedLayout("node-features.html");


    }


    /**
     * name autocomplete
     */

    public function name_autocomplete() {


        /**
         * set json output context
         * and disable changes
         */

        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();


        /**
         * get and validate name
         */

        $name = request::getPostParam("value");
        if ($name === null) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_not_enough
            );

        }


        $name = filter::input($name)
                    ->stripTags()
                        ->expReplace("/\s+/", " ")
                            ->getData();

        $items = array();
        if ($name) {

            $items = db::query(
                "SELECT name fvalue FROM features
                    WHERE name LIKE '%%%s%%' LIMIT 0,5", $name
            );

        }

        view::assign("items", $items);


    }


    /**
     * value autocomplete
     */

    public function value_autocomplete() {


        /**
         * set json output context
         * and disable changes
         */

        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();


        /**
         * get and validate value
         */

        $value = request::getPostParam("value");
        if ($value === null) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_not_enough
            );

        }

        $value = filter::input($value)
                    ->stripTags()
                        ->expReplace("/\s+/", " ")
                            ->getData();

        $items = array();
        if ($value) {

            $items = db::query(
                "SELECT feature_value fvalue FROM tree_features
                    WHERE feature_value LIKE '%%%s%%'
                        GROUP BY feature_value LIMIT 0,5", $value
            );

        }

        view::assign("items", $items);


    }


    /**
     * save feature
     */

    public function save() {


        /**
         * set json output context
         * and disable changes
         */

        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();


        /**
         * get required data
         */

        $required = array("node_id", "name", "value");
        $data = request::getRequiredPostParams($required);

        if ($data === null) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_not_enough
            );

        }


        /**
         * fix mode from POST data of node ID
         */

        if ($data['node_id'] === "new") {
            $this->storageMode = true;
        }


        /**
         * validate target node ID
         */

        if (!$this->storageMode
                and !validate::isNumber($data['node_id'])) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );

        }


        /**
         * validate filtered name
         */

        $data['name'] = filter::input($data['name'])
                            ->stripTags()
                                ->expReplace("/\s+/", " ")
                                    ->getData();

        if (!$data['name']) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->feature_name_invalid
            );

        }


        /**
         * validate filtered value
         */

        $data['value'] = filter::input($data['value'])
                            ->stripTags()
                                ->expReplace("/\s+/", " ")
                                    ->getData();

        if (!$data['value']) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->feature_value_invalid
            );

        }

        if ($this->storageMode) {
            $this->saveFeatureIntoStorage($data);
        } else {
            $this->saveFeatureIntoDB($data);
        }


    }


    /**
     * delete node feature
     */

    public function delete() {


        /**
         * set json output context
         * and disable changes
         */

        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();


        $featureID = request::shiftParam("id");
        $nodeID    = request::shiftParam("target");


        if ($this->storageMode) {
            $this->deleteFeatureFromStorage($featureID);
        } else {

            if (!validate::isNumber($nodeID)) {

                throw new memberErrorException(
                    view::$language->error,
                        view::$language->data_invalid
                );

            }

            if (!$this->storageMode and !validate::isNumber($featureID)) {

                throw new memberErrorException(
                    view::$language->error,
                        view::$language->data_invalid
                );

            }

            $this->deleteFeatureFromDB($featureID, $nodeID);

        }


    }


    /**
     * get features list from database
     */

    private function getFeaturesFromDB($nodeID) {


        $features = db::query("

            SELECT

                tf.feature_id,
                tf.node_id,
                tf.feature_value fvalue,
                f.name fname

            FROM tree_features tf
            INNER JOIN features f ON f.id = tf.feature_id
            WHERE tf.node_id = %u
            ORDER BY tf.feature_id ASC

            ", $nodeID

        );

        view::assign("target_node", $nodeID);
        view::assign("features", $features);


    }


    /**
     * save feature into database
     */

    private function saveFeatureIntoDB($data) {


        /**
         * check for exists node
         */

        $exists = db::query(
            "SELECT (1) ex FROM tree
                WHERE id = %u", $data['node_id']
        );

        if (!$exists) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->node_not_found
            );

        }


        /**
         * get ID of exists feature with name
         */

        $existsFeatureID = db::normalizeQuery(
            "SELECT id FROM features
                WHERE name = '%s'", $data['name']
        );

        if (!$existsFeatureID) {


            /**
             * insert new feature
             */

            db::set(
                "INSERT INTO features (id,name)
                    VALUES (NULL,'%s')", $data['name']
            );

            $newFeatureID = db::lastID();
            db::set(

                "INSERT INTO tree_features
                    (node_id,feature_id,feature_value)
                        VALUES (%u,%u,'%s')",
                            $data['node_id'],
                                $newFeatureID,
                                    $data['value']

            );

        } else {

            $existsValue = db::normalizeQuery(

                "SELECT (1) ex FROM tree_features
                    WHERE node_id = %u AND feature_id = %u",
                        $data['node_id'],
                            $existsFeatureID

            );


            /**
             * update exists feature
             */

            if ($existsValue) {

                db::set(

                    "UPDATE tree_features SET feature_value = '%s'
                        WHERE node_id = %u AND feature_id = %u",
                            $data['value'],
                                $data['node_id'],
                                    $existsFeatureID

                );


            /**
             * insert value for other node with exists name
             */

            } else {

                db::set(

                    "INSERT INTO tree_features
                        (node_id,feature_id,feature_value)
                            VALUES (%u,%u,'%s')",
                                $data['node_id'],
                                    $existsFeatureID,
                                        $data['value']

                );

            }

        }


        /**
         * assign into view current node features
         */

        $this->getFeaturesFromDB($data['node_id']);


    }


    /**
     * delete feature from database
     */

    private function deleteFeatureFromDB($featureID, $nodeID) {


        db::set(

            "DELETE FROM tree_features
                WHERE feature_id = %u AND node_id = %u",
                    $featureID, $nodeID

        );


        /**
         * get more values for this feature
         */

        $existsMore = db::query(
            "SELECT (1) ex FROM tree_features
                WHERE feature_id = %u", $featureID
        );

        if (!$existsMore) {
            db::set("DELETE FROM features WHERE id = %u", $featureID);
        }


    }


    /**
     * get features list from member storage
     */

    private function getFeaturesFromStorage() {


        $features = array();
        foreach (member::getStorageData($this->storageDataKey) as $k => $f) {

            $feature = array(

                "feature_id" => $k,
                "node_id"    => "new",
                "fvalue"     => $f['value'],
                "fname"      => $f['name']

            );

            array_push($features, $feature);

        }

        view::assign("target_node", "new");
        view::assign("features", $features);


    }


    /**
     * save feature into member storage
     */

    private function saveFeatureIntoStorage($data) {


        $features = member::getStorageData($this->storageDataKey);
        $key = helper::getHash($data['name']);

        $features[$key] = array(
            "name" => $data['name'], "value" => $data['value']
        );

        member::setStorageData($this->storageDataKey, $features);
        $this->getFeaturesFromStorage();


    }


    /**
     * delete feature from member storage
     */

    private function deleteFeatureFromStorage($featureID) {

        $features = member::getStorageData($this->storageDataKey);
        if (array_key_exists($featureID, $features)) {
            unset($features[$featureID]);
        }

        member::setStorageData($this->storageDataKey, $features);

    }


}



