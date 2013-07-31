<?php



/**
 * admin submodule, manage document features
 */

class document_features extends baseController {


    private


        /**
         * storage key of working cache for features
         */

        $storageDataKey = "__stored_features",


        /**
         * storage saved mode for new document
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
     * this permissions repeat of documents admin controller,
     * but and you can make one permission for some other actions
     */

    public function setPermissions() {


        $this->permissions = array(

            array(

                "action"      => null,
                "permission"  => "documents_manage",
                "description" => view::$language->permission_documents_manage

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
             * validate target document ID
             */

            $targetDocument = request::shiftParam("target");

            if (!utils::isNumber($targetDocument)) {
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
            }

            $this->getFeaturesFromDB($targetDocument);


        }


        view::assign("page_title", view::$language->document_features);
        $this->setProtectedLayout("document-features.html");


    }


    /**
     * name autocomplete
     */

    public function name_autocomplete() {


        /**
         * set json output context
         * and disable changes
         */

        view::setOutputContext("json");
        view::lockOutputContext();


        /**
         * get and validate name
         */

        $name = request::getPostParam("value");
        if ($name === null) {
            throw new memberErrorException(view::$language->error, view::$language->data_not_enough);
        }


        $name = filter::input($name)
                    ->stripTags()
                    ->expReplace("/\s+/", " ")
                    ->getData();

        $items = array();

        if ($name) {

            $items = db::query("

                SELECT

                    name fvalue

                FROM features
                WHERE name LIKE '%%%s%%'
                LIMIT 0,5

                ",

                $name

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

        view::setOutputContext("json");
        view::lockOutputContext();


        /**
         * get and validate value
         */

        $value = request::getPostParam("value");
        if ($value === null) {
            throw new memberErrorException(view::$language->error, view::$language->data_not_enough);
        }


        $value = filter::input($value)
                    ->stripTags()
                    ->expReplace("/\s+/", " ")
                    ->getData();

        $items = array();

        if ($value) {

            $items = db::query("

                SELECT

                    feature_value fvalue

                FROM document_features
                WHERE feature_value LIKE '%%%s%%'
                GROUP BY feature_value
                LIMIT 0,5

                ",

                $value

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

        view::setOutputContext("json");
        view::lockOutputContext();


        /**
         * get required data
         */

        $required = array("document_id", "name", "value");
        $data = request::getRequiredPostParams($required);

        if ($data === null) {
            throw new memberErrorException(view::$language->error, view::$language->data_not_enough);
        }


        /**
         * fix mode from POST data of document ID
         */

        if ($data['document_id'] === "new") {
            $this->storageMode = true;
        }


        /**
         * validate target document ID
         */

        if (!$this->storageMode and !utils::isNumber($data['document_id'])) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }


        /**
         * validate filtered name
         */

        $data['name'] = filter::input($data['name'])
                            ->stripTags()
                            ->expReplace("/\s+/", " ")
                            ->getData();

        if (!$data['name']) {
            throw new memberErrorException(view::$language->error, view::$language->document_feature_name_invalid);
        }


        /**
         * validate filtered value
         */

        $data['value'] = filter::input($data['value'])
                            ->stripTags()
                            ->expReplace("/\s+/", " ")
                            ->getData();

        if (!$data['value']) {
            throw new memberErrorException(view::$language->error, view::$language->document_feature_value_invalid);
        }


        if ($this->storageMode) {
            $this->saveFeatureIntoStorage($data);
        } else {
            $this->saveFeatureIntoDB($data);
        }


    }


    /**
     * delete document feature
     */

    public function delete() {


        /**
         * set json output context
         * and disable changes
         */

        view::setOutputContext("json");
        view::lockOutputContext();


        $featureID  = request::shiftParam("id");
        $documentID = request::shiftParam("target");


        if ($this->storageMode) {
            $this->deleteFeatureFromStorage($featureID);
        } else {


            if (!utils::isNumber($documentID)) {
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
            }

            if (!$this->storageMode and !utils::isNumber($featureID)) {
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
            }

            $this->deleteFeatureFromDB($featureID, $documentID);


        }


    }


    /**
     * get features list from database
     */

    private function getFeaturesFromDB($documentID) {


        $features = db::query("

            SELECT

                df.feature_id,
                df.document_id,
                df.feature_value fvalue,
                f.name fname

            FROM document_features df
            INNER JOIN features f ON f.id = df.feature_id
            WHERE df.document_id = %u
            ORDER BY df.feature_id ASC

            ",

            $documentID

        );


        view::assign("target_document", $documentID);
        view::assign("features", $features);


    }


    /**
     * save feature into database
     */

    private function saveFeatureIntoDB($data) {


        /**
         * check for exists document
         */

        if (!db::query("SELECT (1) ex FROM documents WHERE id = %u", $data['document_id'])) {
            throw new memberErrorException(view::$language->error, view::$language->document_not_found);
        }


        /**
         * get ID of exists feature with name
         */

        $existsFeatureID = db::normalizeQuery(
            "SELECT id FROM features WHERE name = '%s'", $data['name']
        );


        if (!$existsFeatureID) {


            /**
             * insert new feature
             */

            db::set(
                "INSERT INTO features (id,name) VALUES (NULL,'%s')",
                $data['name']
            );

            $newFeatureID = db::lastID();
            db::set("

                INSERT INTO document_features
                (document_id,feature_id,feature_value)
                VALUES (%u,%u,'%s')

                ",

                $data['document_id'],
                $newFeatureID,
                $data['value']

            );


        } else {


            $existsValue = db::normalizeQuery(
                "SELECT (1) ex FROM document_features WHERE document_id = %u AND feature_id = %u",
                $data['document_id'], $existsFeatureID
            );


            /**
             * update exists feature
             */

            if ($existsValue) {


                db::set("

                    UPDATE document_features
                    SET feature_value = '%s'
                    WHERE document_id = %u AND feature_id = %u

                    ",

                    $data['value'],
                    $data['document_id'],
                    $existsFeatureID

                );


            /**
             * insert value for other document with exists name
             */

            } else {


                db::set("

                    INSERT INTO document_features
                    (document_id,feature_id,feature_value)
                    VALUES (%u,%u,'%s')

                    ",

                    $data['document_id'],
                    $existsFeatureID,
                    $data['value']

                );


            }


        }


        /**
         * assign into view current document features
         */

        $this->getFeaturesFromDB($data['document_id']);


    }


    /**
     * delete feature from database
     */

    private function deleteFeatureFromDB($featureID, $documentID) {


        db::set("

            DELETE
            FROM document_features
            WHERE feature_id = %u
            AND document_id = %u

            ",

            $featureID,
            $documentID

        );


        /**
         * get more values for this feature
         */

        $existsMore = db::query(
            "SELECT (1) ex FROM document_features WHERE feature_id = %u", $featureID
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

                "feature_id"  => $k,
                "document_id" => "new",
                "fvalue"      => $f['value'],
                "fname"       => $f['name']

            );

            array_push($features, $feature);

        }


        view::assign("target_document", "new");
        view::assign("features", $features);


    }


    /**
     * save feature into member storage
     */

    private function saveFeatureIntoStorage($data) {


        /**
         * set new feature value
         */


        $features = member::getStorageData($this->storageDataKey);

        $key = helper::getHash($data['name']);
        $features[$key] = array("name" => $data['name'], "value" => $data['value']);

        member::setStorageData($this->storageDataKey, $features);


        /**
         * assign into view current document features
         */

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



