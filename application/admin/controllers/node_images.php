<?php



/**
 * admin submodule, manage attached node images
 */

class node_images extends baseController {


    private


        /**
         * available resize options
         */

        $availableSizes = array(

            "thumb_sizes" => array(

                array("value" => "100x100", "description" => "100x100",
                        "selected" => true),

                array("value" => "140x140", "description" => "140x140"),
                array("value" => "180x180", "description" => "180x180"),
                array("value" => "200x200", "description" => "200x200")

            ),

            "middle_sizes" => array(

                array("value" => "320x240", "description" => "320x240"),
                array("value" => "400x300", "description" => "400x300",
                        "selected" => true),

                array("value" => "520x390", "description" => "520x390"),
                array("value" => "640x480", "description" => "640x480")

            ),

            "original_sizes" => array(

                array("value" => "640x480",  "description" => "640x480"),
                array("value" => "800x600",  "description" => "800x600",
                        "selected" => true),

                array("value" => "1024x768", "description" => "1024x768"),
                array("value" => "1200x900", "description" => "1200x900")

            )

        ),


        /**
         * storage saved mode for new node
         */

        $storageMode = false,


        /**
         * storage key of working cache for images
         */

        $storageDataKey = "__stored_images",


        /**
         * target node of uploaded image
         */

        $targetNode = null,


        /**
         * ID of replaced image
         */

        $targetImage = null,


        /**
         * action type of uploaded image
         * "replace" or "add" values
         */

        $uploadActionType = null,


        /**
         * thumbnail resize dimension
         */

        $thumbnailSize = array(0, 0),


        /**
         * middle image resize dimension
         */

        $middleSize = array(0, 0),


        /**
         * original image resize dimension
         */

        $originalSize = array(0, 0),


        /**
         * thumbnail crop to square
         */

        $squareThumbnail = false,


        /**
         * middle image crop to square
         */

        $squareMiddle = false,


        /**
         * original image crop to square
         */

        $squareOriginal = false,


        /**
         * stretch small size image
         */

        $stretchImage = false,


        /**
         * add watermark mode
         */

        $addWaterMark = false,


        /**
         * name of watermark image
         */

        $waterMarkImage = "sample.png";


    /**
     * set permissions for this controller
     * this permissions repeat of tree admin controller,
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
     * default action with show list of attached images
     */

    public function index() {

        $this->view(true);
        view::assign("node_name", view::$language->images_attached);
        view::assign($this->availableSizes);
        $this->setProtectedLayout("node-images.html");

    }


    /**
     * show list of attached images
     */

    public function view($fromIndex = false) {

        if (!$fromIndex) {

            view::clearPublicVariables();
            view::setOutputContext("json");
            view::lockOutputContext();

        }

        $this->chooseMode();
        if ($e = storage::shift("admin-attached-images-exception")) {
            if ($e[0] == "success") {
                throw new memberSuccessException($e[1], $e[2]);
            } else {
                throw new memberErrorException($e[1], $e[2]);
            }
        }

        $targetNode = request::shiftParam("target");
        view::assign("target_node", $targetNode);

        if ($this->storageMode) {
            $this->getImagesListFromStorage();
        } else {
            $this->getImagesListFromDB($targetNode);
        }

    }


    /**
     * delete all images of target node
     */

    public function delete_all() {

        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();

        request::validateReferer(
            app::config()->site->admin_tools_link
                . "/node-images\?target=.+", true
        );

        $this->chooseMode();
        $targetNode = request::shiftParam("target");

        if ($this->storageMode) {

            $images = array_keys(
                member::getStorageData($this->storageDataKey)
            );

            member::setStorageData($this->storageDataKey, array());

        } else {

            $images = db::normalizeQuery(
                "SELECT name FROM images WHERE node_id = %u", $targetNode
            );

            if (!is_array($images)) $images = array($images);
            db::set("DELETE FROM images WHERE node_id = %u", $targetNode);

        }

        foreach ($images as $image) {

            @ unlink(PUBLIC_HTML . "upload/" . $image);
            @ unlink(PUBLIC_HTML . "upload/thumb_" . $image);
            @ unlink(PUBLIC_HTML . "upload/middle_" . $image);

        }

        view::assign("images", array());

    }


    /**
     * single image upload access point
     */

    public function upload() {

        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();

        $this->setUploadEnvironment();
        $this->validateFile();
        $this->saveImage();

        member::storeData();
        exit();

    }


    /**
     * set image as master image of target node
     */

    public function master() {


        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();

        request::validateReferer(
            app::config()->site->admin_tools_link
                . "/node-images\?target=.+", true
        );

        $this->chooseMode();
        $targetNode  = request::shiftParam("target");
        $targetImage = request::shiftParam("id");

        if ($this->storageMode) {

            $images = member::getStorageData($this->storageDataKey);
            if (!array_key_exists($targetImage, $images)) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->data_invalid
                );
            }

            foreach ($images as $k => $image) {
                if ($k == $targetImage) {
                    $images[$k]['is_master'] = 1;
                } else {
                    $images[$k]['is_master'] = 0;
                }
            }

            member::setStorageData($this->storageDataKey, $images);

        } else {

            if (!validate::isNumber($targetImage)) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->data_invalid
                );
            }

            db::set(
                "UPDATE images SET is_master = 0
                    WHERE node_id = %u", $targetNode
            );

            db::set(
                "UPDATE images SET is_master = 1
                    WHERE id = %u", $targetImage
            );

        }

        throw new memberSuccessException(
            view::$language->success,
                view::$language->changes_has_been_saved
        );


    }


    /**
     * delete image with ID
     */

    public function delete() {


        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();

        request::validateReferer(
            app::config()->site->admin_tools_link
                . "/node-images\?target=.+", true
        );

        $this->chooseMode();
        view::assign("images", array());

        $targetImage = request::shiftParam("id");
        if ($this->storageMode) {

            $images = member::getStorageData($this->storageDataKey);
            if (!array_key_exists($targetImage, $images)) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->data_invalid
                );
            }

            $changeMaster = ($images[$targetImage]['is_master'] == 1);
            unset($images[$targetImage]);

            @ unlink(PUBLIC_HTML . "upload/" . $targetImage);
            @ unlink(PUBLIC_HTML . "upload/thumb_" . $targetImage);
            @ unlink(PUBLIC_HTML . "upload/middle_" . $targetImage);

            if ($changeMaster) {
                foreach ($images as $k => $image) {
                    $images[$k]['is_master'] = 1;
                }
            }

            member::setStorageData($this->storageDataKey, $images);
            $this->getImagesListFromStorage();

        } else {

            if (!validate::isNumber($targetImage)) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->data_invalid
                );
            }

            $image = db::normalizeQuery(
                "SELECT name, node_id, is_master
                    FROM images WHERE id = %u", $targetImage
            );

            if ($image) {

                db::set("DELETE FROM images WHERE id = %u", $targetImage);

                @ unlink(PUBLIC_HTML . "upload/" . $image['name']);
                @ unlink(PUBLIC_HTML . "upload/thumb_" . $image['name']);
                @ unlink(PUBLIC_HTML . "upload/middle_" . $image['name']);

                if ($image['is_master']) {

                    $firstFindID = db::normalizeQuery(
                        "SELECT id FROM images WHERE node_id = %u
                            ORDER BY id ASC LIMIT 1", $image['node_id']
                    );

                    if ($firstFindID) {

                        db::set(
                            "UPDATE images SET is_master = 1
                                WHERE id = %u", $firstFindID
                        );

                    }

                }

                $this->getImagesListFromDB($image['node_id']);

            }

        }


    }


    private function saveImage() {


        $filePath  = PUBLIC_HTML . "upload/";
        $fileName  = md5(mt_rand() . microtime(true)) . ".jpg";
        $original  = $filePath . $fileName;
        $middle    = $filePath . "middle_" . $fileName;
        $thumbnail = $filePath . "thumb_" . $fileName;

        move_uploaded_file(
            $_FILES['uploadfile']['tmp_name'], $original
        );

        $originalImage = new simpleImage($original);
        if ($this->addWaterMark) {
            $originalImage->addWaterMark(
                APPLICATION . "resources/watermarks/" . $this->waterMarkImage
            );
        }

        $middleImage = clone $originalImage;

        if ($this->squareOriginal) {
            $originalImage->squareCrop();
        }

        $originalImage->intelligentResize(
            $this->originalSize[0], $this->originalSize[1], $this->stretchImage
        );

        $originalImage->save($original);
        $thumbnailImage = clone $middleImage;

        if ($this->squareMiddle) {
            $middleImage->squareCrop();
        }

        $middleImage->intelligentResize(
            $this->middleSize[0], $this->middleSize[1], $this->stretchImage
        );

        $middleImage->save($middle);

        if ($this->squareThumbnail) {
            $thumbnailImage->squareCrop();
        }

        $thumbnailImage->intelligentResize(
            $this->thumbnailSize[0], $this->thumbnailSize[1], $this->stretchImage
        );

        $thumbnailImage->save($thumbnail);

        switch ($this->uploadActionType) {

            case "add":

                if ($this->storageMode) {

                    $storedImages = member::getStorageData(
                        $this->storageDataKey
                    );

                    $isMasterImage = sizeof($storedImages) > 0 ? 0 : 1;
                    $storedImages[$fileName] = array();
                    $storedImages[$fileName]['is_master'] = $isMasterImage;

                    member::setStorageData(
                        $this->storageDataKey, $storedImages
                    );

                } else {

                    $isMasterImage = db::query(

                        "SELECT (1) ex FROM images WHERE
                            node_id = %u LIMIT 1", $this->targetNode

                    ) ? 0 : 1;

                    db::set(

                        "INSERT INTO images (id,node_id,is_master,name)
                            VALUES (NULL,%u,%u,'%s')",
                                $this->targetNode,
                                    $isMasterImage,
                                        $fileName

                    );

                }

            break;

            case "replace":

                if ($this->storageMode) {

                    $storedImages = member::getStorageData(
                        $this->storageDataKey
                    );

                    $storedImages[$fileName] = array();
                    $storedImages[$fileName]['is_master']
                        = $storedImages[$this->targetImage]['is_master'];

                    unset($storedImages[$this->targetImage]);

                    @ unlink(PUBLIC_HTML . "upload/" . $this->targetImage);

                    @ unlink(
                        PUBLIC_HTML . "upload/thumb_" . $this->targetImage
                    );

                    @ unlink(
                        PUBLIC_HTML . "upload/middle_" . $this->targetImage
                    );

                    member::setStorageData(
                        $this->storageDataKey, $storedImages
                    );

                } else {

                    $oldFileName = db::normalizeQuery(
                        "SELECT name FROM images
                            WHERE id = %u", $this->targetImage
                    );

                    if ($oldFileName) {

                        @ unlink(PUBLIC_HTML . "upload/" . $oldFileName);
                        @ unlink(PUBLIC_HTML . "upload/thumb_" . $oldFileName);
                        @ unlink(PUBLIC_HTML . "upload/middle_" . $oldFileName);

                    }

                    db::set(
                        "UPDATE images SET name = '%s'
                            WHERE id = %u", $fileName, $this->targetImage
                    );

                }

            break;

        }


    }


    /**
     * validate $_FILES array
     */

    private function validateFile() {

        if (is_array($_FILES['uploadfile']['tmp_name'])) {
            $this->exceptionExit(
                "error", view::$language->error,
                    view::$language->image_upload_single_only
            );
        }

        if ($_FILES['uploadfile']['error']) {
            $this->exceptionExit(
                "error", view::$language->error,
                    view::$language->image_upload_file_error
            );
        }

        if (!getimagesize($_FILES['uploadfile']['tmp_name'])) {
            @ unlink($_FILES['uploadfile']['tmp_name']);
            $this->exceptionExit(
                "error", view::$language->error,
                    view::$language->image_upload_broken_mime
            );
        }

    }


    /**
     * get required uploading data
     */

    private function setUploadEnvironment() {


        request::validateReferer(
            app::config()->site->admin_tools_link
                . "/node-images\?target=.+", true
        );

        $required = array(
            "thumbnail_size", "middle_size", "original_size",
                "target_node", "action", "image_id"
        );

        if (!$requiredData = request::getRequiredPostParams($required)) {
            $this->exceptionExit(
                "error", view::$language->error,
                    view::$language->data_not_enough
            );
        }

        if ($requiredData['action'] !== "replace"
                and $requiredData['action'] !== "add") {

            $this->exceptionExit(
                "error", view::$language->error,
                    view::$language->data_invalid
            );

        }

        $this->uploadActionType = $requiredData['action'];

        $target = $requiredData['target_node'];
        if ($target !== "new") {

            if (!validate::isNumber($target)) {
                $this->exceptionExit(
                    "error", view::$language->error,
                        view::$language->data_invalid
                );
            }

            $exists = db::query(
                "SELECT (1) ex FROM tree WHERE id = %u", $target
            );

            if (!$exists) {
                $this->exceptionExit(
                    "error", view::$language->error,
                        view::$language->node_not_found
                );
            }

            $this->targetNode  = $target;
            $this->storageMode = false;

        } else {
            $this->storageMode = true;
        }

        $target = $requiredData['image_id'];
        if ($this->storageMode and $this->uploadActionType !== "add") {

            $validate = array_key_exists(
                $target, member::getStorageData($this->storageDataKey)
            );

            if (!$validate) {
                throw new memberErrorException(
                    view::$language->error, view::$language->data_invalid
                );
            }

        } else {

            if ($this->uploadActionType !== "add") {

                if (!validate::isNumber($target)) {
                    $this->exceptionExit(
                        "error", view::$language->error,
                            view::$language->data_invalid
                    );
                }

                $exists = db::query(
                    "SELECT (1) ex FROM images WHERE id = %u", $target
                );

                if (!$exists) {
                    $this->exceptionExit(
                        "error", view::$language->error,
                            view::$language->image_not_found
                    );
                }

            }

        }

        $this->targetImage = $target;

        $this->thumbnailSize = $this->getSizeValueFromData(
            $requiredData['thumbnail_size']
        );

        $this->middleSize = $this->getSizeValueFromData(
            $requiredData['middle_size']
        );

        $this->originalSize = $this->getSizeValueFromData(
            $requiredData['original_size']
        );

        $this->squareOriginal  = request::getPostParam("square_original");
        $this->squareMiddle    = request::getPostParam("square_middle");
        $this->squareThumbnail = request::getPostParam("square_thumbnail");

        $this->stretchImage = request::getPostParam("stretch_image");
        $this->addWaterMark = request::getPostParam("add_watermark");


    }


    /**
     * check and return size values
     */

    private function getSizeValueFromData($input) {

        if (!validate::likeString($input)) {
            $this->exceptionExit(
                "error", view::$language->error,
                    view::$language->data_invalid
            );
        }

        if (!preg_match("/^([1-9]\d{0,3})x([1-9]\d{0,3})$/", $input, $m)) {
            $this->exceptionExit(
                "error", view::$language->error,
                    view::$language->data_invalid
            );
        }

        return array($m[1], $m[2]);

    }


    /**
     * get array of images for exists node from database
     */

    private function getImagesListFromDB($nodeID) {

        view::assign("images", db::query(
                "SELECT id, is_master, name FROM images WHERE
                    node_id = %u ORDER BY id ASC", $nodeID
        ));

    }


    /**
     * get array of images for new node from storage
     */

    private function getImagesListFromStorage() {

        $images = array();
        foreach (
            member::getStorageData($this->storageDataKey) as $k => $item) {

            array_push($images, array(
                "id"        => $k,
                "is_master" => $item['is_master'],
                "name"      => $k
            ));

        }

        view::assign("images", $images);

    }


    /**
     * choose working mode
     * member cache or database
     */

    private function chooseMode() {

        $targetNode = request::getParam("target");
        switch (true) {

            case ($targetNode === "new"):
                $this->storageMode = true;
            break;

            case (validate::isNumber($targetNode)):
                $this->storageMode = false;
            break;

            default:
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->data_invalid
                );
            break;

        }

    }


    /**
     * set exception and exit from application
     * exit now from application
     * WARNING! need stored member cache before exit!
     */

    private function exceptionExit() {

        $args = func_get_args();
        storage::write("admin-attached-images-exception", $args);

        member::storeData();
        exit();

    }


}



