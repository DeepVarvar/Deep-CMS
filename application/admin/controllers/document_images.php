<?php



/**
 * admin submodule, manage attached document images
 */

class document_images extends baseController {


    private


        /**
         * allowed types of uploaded images
         */

        $allowedFileTypes = array(

            "image/gif"   => "gif",
            "image/jpeg"  => "jpg",
            "image/pjpeg" => "jpg",
            "image/png"   => "png",
            "image/x-png" => "png"

        ),


        /**
         * available resize options
         */

        $availableSizes = array(


            "thumb_sizes" => array(

                array("value" => "100x100", "description" => "100x100", "selected" => true),
                array("value" => "140x140", "description" => "140x140"),
                array("value" => "180x180", "description" => "180x180"),
                array("value" => "200x200", "description" => "200x200")

            ),

            "middle_sizes" => array(

                array("value" => "320x240", "description" => "320x240"),
                array("value" => "400x300", "description" => "400x300", "selected" => true),
                array("value" => "520x390", "description" => "520x390"),
                array("value" => "640x480", "description" => "640x480")

            ),

            "original_sizes" => array(

                array("value" => "640x480",  "description" => "640x480"),
                array("value" => "800x600",  "description" => "800x600", "selected" => true),
                array("value" => "1024x768", "description" => "1024x768"),
                array("value" => "1200x900", "description" => "1200x900")

            )


        ),


        /**
         * storage saved mode for new document
         */

        $storageMode = false,


        /**
         * storage key of working cache for images
         */

        $storageDataKey = "__stored_images",


        /**
         * extension of saved images
         */

        $fileExtension = null,


        /**
         * target document of uploaded image
         */

        $targetDocument = null,


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
     * default action with show list of attached images
     */

    public function index() {


        $this->view(true);

        view::assign("page_title", view::$language->images_attached);
        view::assign($this->availableSizes);

        $this->setProtectedLayout("document-images.html");


    }


    /**
     * show list of attached images
     */

    public function view($fromIndex = false) {


        /**
         * for AJAX request
         * set json output context
         * and disable changes
         */

        if (!$fromIndex) {
            view::setOutputContext("json");
            view::lockOutputContext();
        }


        /**
         * choose mode
         */

        $this->chooseMode();


        /**
         * check for exists attached images exceptions
         * this checkpoint throw after:
         * redirect, iframe upload, ajax action
         */

        if ($e = storage::shift("admin-attached-images-exception")) {

            if ($e[0] == "success") {
                throw new memberSuccessException($e[1], $e[2]);
            } else {
                throw new memberErrorException($e[1], $e[2]);
            }

        }


        /**
         * choose mode of view images
         */

        $targetDocument = request::shiftParam("target");
        view::assign("target_document", $targetDocument);


        if ($this->storageMode) {
            $this->getImagesListFromStorage();
        } else {
            $this->getImagesListFromDB($targetDocument);
        }


    }


    /**
     * single image upload access point
     */

    public function upload() {


        /**
         * this always expected request from iframe
         * always return json string format for exception
         * set json output context
         * and disable changes
         */

        view::setOutputContext("json");
        view::lockOutputContext();


        /**
         * set environment with validation required upload data
         */

        $this->setUploadEnvironment();


        /**
         * file validation
         */

        $this->validateFile();


        /**
         * save image
         */

        $this->saveImage();


        /**
         * this action requested from hidden iframe,
         * and not need output,
         * exit now from application
         * WARNING! need stored member cache before exit!
         */

        member::storeData();
        exit();


    }


    /**
     * set image as master image of target document
     */

    public function master() {


        /**
         * this action always expected request from AJAX
         * set json output context
         * and disable changes
         */

        view::setOutputContext("json");
        view::lockOutputContext();


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(app::config()->site->admin_tools_link . "/document-images\?target=.+", true);


        /**
         * choose mode
         */

        $this->chooseMode();


        $targetDocument = request::shiftParam("target");
        $targetImage    = request::shiftParam("id");

        if ($this->storageMode) {


            /**
             * get images list from storage
             */

            $images = member::getStorageData($this->storageDataKey);

            if (!array_key_exists($targetImage, $images)) {
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
            }


            /**
             * set target image as master
             */

            foreach ($images as $k => $image) {

                if ($k == $targetImage) {
                    $images[$k]['is_master'] = 1;
                } else {
                    $images[$k]['is_master'] = 0;
                }

            }

            member::setStorageData($this->storageDataKey, $images);


        } else {


            /**
             * validate input image ID
             */

            if (!utils::isNumber($targetImage)) {
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
            }


            /**
             * set image as master
             */

            db::set("UPDATE images SET is_master = 0 WHERE document_id = %u", $targetDocument);
            db::set("UPDATE images SET is_master = 1 WHERE id = %u", $targetImage);


        }


        throw new memberSuccessException(view::$language->success, view::$language->changes_has_been_saved);


    }


    /**
     * delete image with ID
     */

    public function delete() {


        /**
         * this action always expected request from AJAX
         * set json output context
         * and disable changes
         */

        view::setOutputContext("json");
        view::lockOutputContext();


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(app::config()->site->admin_tools_link . "/document-images\?target=.+", true);


        /**
         * choose mode,
         * set empty images array
         */

        $this->chooseMode();
        view::assign("images", array());


        $targetImage = request::shiftParam("id");

        if ($this->storageMode) {


            /**
             * get images list from storage
             */

            $images = member::getStorageData($this->storageDataKey);


            /**
             * validate target image ID
             */

            if (!array_key_exists($targetImage, $images)) {
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
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


            /**
             * WARNING!
             * this method assign data into view!
             */

            $this->getImagesListFromStorage();


        } else {


            /**
             * validate target image ID
             */

            if (!utils::isNumber($targetImage)) {
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
            }


            /**
             * get image information
             */

            $image = db::normalizeQuery("

                SELECT

                    name,
                    document_id,
                    is_master

                FROM images
                WHERE id = %u

                ",

                $targetImage

            );


            /**
             * delete image information and files
             */

            if ($image) {


                db::set("DELETE FROM images WHERE id = %u", $targetImage);

                @ unlink(PUBLIC_HTML . "upload/" . $image['name']);
                @ unlink(PUBLIC_HTML . "upload/thumb_" . $image['name']);
                @ unlink(PUBLIC_HTML . "upload/middle_" . $image['name']);


                /**
                 * set first found image as matster
                 * if deleted image has been saved is master
                 */

                if ($image['is_master']) {


                    $firstFindID = db::normalizeQuery(
                        "SELECT id FROM images WHERE document_id = %u ORDER BY id ASC LIMIT 1",
                        $image['document_id']
                    );

                    if ($firstFindID) {
                        db::set("UPDATE images SET is_master = 1 WHERE id = %u", $firstFindID);
                    }


                }


                /**
                 * WARNING!
                 * this method assign data into view!
                 */

                $this->getImagesListFromDB($image['document_id']);


            }


        }


    }


    private function saveImage() {


        /**
         * set new random name for image
         */

        $filePath = PUBLIC_HTML . "upload/";
        $fileName = md5(mt_rand() . microtime(true)) . ".{$this->fileExtension}";

        $original  = $filePath . $fileName;
        $middle    = $filePath . "middle_" . $fileName;
        $thumbnail = $filePath . "thumb_" . $fileName;


        /**
         * move uploaded file into public directory
         */

        move_uploaded_file($_FILES['uploadfile']['tmp_name'], $original);


        /**
         * create original image object
         */

        $originalImage = new simpleImage($original);


        /**
         * add watermark into image
         */

        if ($this->addWaterMark) {

            $originalImage->addWaterMark(
                APPLICATION . app::config()->path->resources
                . "watermarks/" . $this->waterMarkImage
            );

        }


        /**
         * clone middle image object from original
         */

        $middleImage = clone $originalImage;


        /**
         * resize and save original image
         */

        if ($this->squareOriginal) {
            $originalImage->squareCrop();
        }

        $originalImage->intelligentResize(

            $this->originalSize[0],
            $this->originalSize[1],
            $this->stretchImage

        );

        $originalImage->save($original);


        /**
         * clone thumbnail image object from middle
         */

        $thumbnailImage = clone $middleImage;


        /**
         * resize and save middle image
         */

        if ($this->squareMiddle) {
            $middleImage->squareCrop();
        }

        $middleImage->intelligentResize(

            $this->middleSize[0],
            $this->middleSize[1],
            $this->stretchImage

        );

        $middleImage->save($middle);


        /**
         * resize and save thumbnail image
         */

        if ($this->squareThumbnail) {
            $thumbnailImage->squareCrop();
        }

        $thumbnailImage->intelligentResize(

            $this->thumbnailSize[0],
            $this->thumbnailSize[1],
            $this->stretchImage

        );

        $thumbnailImage->save($thumbnail);


        /**
         * save or update image name
         * add new image for exists document
         */

        switch ($this->uploadActionType) {


            case "add":


                if ($this->storageMode) {


                    $storedImages = member::getStorageData($this->storageDataKey);
                    $isMasterImage = sizeof($storedImages) > 0 ? 0 : 1;

                    $storedImages[$fileName] = array();
                    $storedImages[$fileName]['is_master'] = $isMasterImage;

                    member::setStorageData($this->storageDataKey, $storedImages);


                } else {


                    $isMasterImage = db::query(

                        "SELECT (1) ex FROM images WHERE document_id = %u LIMIT 1",
                        $this->targetDocument

                    ) ? 0 : 1;

                    db::set(

                        "INSERT INTO images (id,document_id,is_master,name) VALUES (NULL,%u,%u,'%s')",
                        $this->targetDocument,
                        $isMasterImage,
                        $fileName

                    );


                }


            break;


            case "replace":


                if ($this->storageMode) {


                    $storedImages = member::getStorageData($this->storageDataKey);

                    $storedImages[$fileName] = array();
                    $storedImages[$fileName]['is_master'] = $storedImages[$this->targetImage]['is_master'];

                    unset($storedImages[$this->targetImage]);

                    @ unlink(PUBLIC_HTML . "upload/" . $this->targetImage);
                    @ unlink(PUBLIC_HTML . "upload/thumb_" . $this->targetImage);
                    @ unlink(PUBLIC_HTML . "upload/middle_" . $this->targetImage);

                    member::setStorageData($this->storageDataKey, $storedImages);


                } else {


                    $oldFileName = db::normalizeQuery(
                        "SELECT name FROM images WHERE id = %u",
                        $this->targetImage
                    );

                    if ($oldFileName) {

                        @ unlink(PUBLIC_HTML . "upload/" . $oldFileName);
                        @ unlink(PUBLIC_HTML . "upload/thumb_" . $oldFileName);
                        @ unlink(PUBLIC_HTML . "upload/middle_" . $oldFileName);

                    }

                    db::set(
                        "UPDATE images SET name = '%s' WHERE id = %u",
                        $fileName, $this->targetImage
                    );


                }


            break;


        }


    }


    /**
     * validate $_FILES array
     */

    private function validateFile() {


        /**
         * check for single upload
         */

        if (is_array($_FILES['uploadfile']['tmp_name'])) {
            $this->exceptionExit("error", view::$language->error, view::$language->upload_image_single_only);
        }


        /**
         * check for upload errors
         */

        if ($_FILES['uploadfile']['error']) {
            $this->exceptionExit("error", view::$language->error, view::$language->upload_image_file_error);
        }


        /**
         * check real mime type of uloaded file
         */

        $this->checkFileMimeType($_FILES['uploadfile']['tmp_name']);


    }


    /**
     * check real mime type of uploaded file
     */

    private function checkFileMimeType($file) {


        /**
         * open finfo if available
         */

        $finfo = null;
        if (!function_exists("mime_content_type")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
        }


        /**
         * get real mime type
         */

        $mimeType = $finfo !== null
            ? finfo_file($finfo, $file) : mime_content_type($file);


        /**
         * close before opened finfo
         */

        if ($finfo !== null) {
            finfo_close($finfo);
        }


        /**
         * check allowed mime type
         * get file extension of mime type
         */

        if (!array_key_exists($mimeType, $this->allowedFileTypes)) {
            $this->exceptionExit("error", view::$language->error, view::$language->upload_image_broken_mime);
        }

        $this->fileExtension = $this->allowedFileTypes[$mimeType];


    }


    /**
     * get required uploading data
     */

    private function setUploadEnvironment() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(app::config()->site->admin_tools_link . "/document-images\?target=.+", true);


        /**
         * check exists required data
         */

        $required = array(

            "thumbnail_size",
            "middle_size",
            "original_size",
            "target_document",
            "action",
            "image_id"

        );

        if (!$requiredData = request::getRequiredPostParams($required)) {
            $this->exceptionExit("error", view::$language->error, view::$language->data_not_enough);
        }


        /**
         * validate action type
         */

        if ($requiredData['action'] !== "replace" and $requiredData['action'] !== "add") {
            $this->exceptionExit("error", view::$language->error, view::$language->data_invalid_format);
        }

        $this->uploadActionType = $requiredData['action'];


        /**
         * validate target document
         */

        $target = $requiredData['target_document'];

        if ($target !== "new") {


            if (!utils::isNumber($target)) {
                $this->exceptionExit("error", view::$language->error, view::$language->data_invalid_format);
            }

            if (!db::query("SELECT (1) ex FROM documents WHERE id = %u", $target)) {
                $this->exceptionExit("error", view::$language->error, view::$language->document_not_found);
            }

            $this->targetDocument = $target;
            $this->storageMode = false;


        } else {
            $this->storageMode = true;
        }


        /**
         * validate target image
         */

        $target = $requiredData['image_id'];


        if ($this->storageMode and $this->uploadActionType !== "add") {


            /**
             * validate target image for exists ID
             */

            if (!array_key_exists($target, member::getStorageData($this->storageDataKey))) {
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
            }


        } else {


            if ($this->uploadActionType !== "add") {

                if (!utils::isNumber($target)) {
                    $this->exceptionExit("error", view::$language->error, view::$language->data_invalid_format);
                }

                if (!db::query("SELECT (1) ex FROM images WHERE id = %u", $target)) {
                    $this->exceptionExit("error", view::$language->error, view::$language->image_not_found);
                }

            }


        }


        $this->targetImage = $target;


        /**
         * set values of sizes
         */

        $this->thumbnailSize = $this->getSizeValueFromData($requiredData['thumbnail_size']);
        $this->middleSize    = $this->getSizeValueFromData($requiredData['middle_size']);
        $this->originalSize  = $this->getSizeValueFromData($requiredData['original_size']);


        /**
         * set custom options
         */

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


        /**
         * data is not string
         */

        if (!utils::likeString($input)) {
            $this->exceptionExit("error", view::$language->error, view::$language->data_invalid_format);
        }


        /**
         * invalid string format
         */

        if (!preg_match("/^([1-9]\d{0,3})x([1-9]\d{0,3})$/", $input, $m)) {
            $this->exceptionExit("error", view::$language->error, view::$language->data_invalid_format);
        }


        /**
         * return values of width/height
         */

        return array($m[1], $m[2]);


    }


    /**
     * get array of images for exists document from database
     */

    private function getImagesListFromDB($documentID) {


        view::assign("images", db::query("

            SELECT

                id,
                is_master,
                name

            FROM images
            WHERE document_id = %u
            ORDER BY id ASC

            ",

            $documentID

        ));


    }


    /**
     * get array of images for new document from storage
     */

    private function getImagesListFromStorage() {


        $images = array();
        foreach (member::getStorageData($this->storageDataKey) as $k => $item) {
            array_push($images, array("id" => $k, "is_master" => $item['is_master'], "name" => $k));
        }

        view::assign("images", $images);


    }


    /**
     * choose working mode
     * member cache or database
     */

    private function chooseMode() {


        $targetDocument = request::getParam("target");
        switch (true) {


            case ($targetDocument === "new"):
                $this->storageMode = true;
            break;


            case (utils::isNumber($targetDocument)):
                $this->storageMode = false;
            break;


            default:
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
            break;


        }


    }


    /**
     * set exception and exit from application
     */

    private function exceptionExit() {



        /**
         * exit now from application
         * WARNING! need stored member cache before exit!
         */

        storage::write("admin-attached-images-exception", func_get_args());
        member::storeData();

        exit();


    }


}


