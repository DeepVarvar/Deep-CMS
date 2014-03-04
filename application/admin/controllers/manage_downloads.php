<?php


/**
 * admin submodule, manage downloads
 */

class manage_downloads extends baseController {


    private $fileSize = 0;
    private $fileName = null;


    /**
     * set permissions for this controller
     */

    public function setPermissions() {

        $this->permissions = array(
            array(
                'action'      => null,
                'permission'  => 'downloads_manage',
                'description' => view::$language->permission_downloads_manage
            )
        );

    }


    public function index() {

        $paginator = new paginator('SELECT id, name FROM downloads ORDER BY id DESC');
        $paginator = $paginator
            ->setCurrentPage(request::getCurrentPage())
            ->setItemsPerPage(20)
            ->setSliceSizeByPages(20)
            ->getResult();

        view::assign('downloads', $paginator['items']);
        view::assign('pages', $paginator['pages']);
        view::assign('node_name', view::$language->manage_downloads_title);
        $this->setProtectedLayout('manage-downloads.html');

    }


    public function delete() {

        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer($adminToolsLink . '/manage-downloads');

        $fileID = request::shiftParam('id');
        if (!validate::isNumber($fileID)) {
            throw new memberErrorException(
                view::$language->manage_downloads_error,
                view::$language->manage_downloads_data_invalid
            );
        }

        $fileName = db::normalizeQuery(
            'SELECT name FROM downloads WHERE id = %u', $fileID
        );

        if (!$fileName) {
            throw new memberErrorException(
                view::$language->manage_downloads_error,
                view::$language->manage_downloads_file_not_found
            );
        }

        db::set('DELETE FROM downloads WHERE id = %u', $fileID);
        @ unlink(APPLICATION . 'resources/downloads/' . rawurlencode($fileName));

        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->manage_downloads_success,
            view::$language->manage_downloads_file_is_deleted,
            $adminToolsLink . '/manage-downloads'
        );

    }


    public function add() {

        if (request::isPost()) {

            $adminToolsLink = app::config()->site->admin_tools_link;
            request::validateReferer($adminToolsLink . '/manage-downloads/add');

            if (!isset($_FILES['file'])) {
                throw new memberErrorException(
                    view::$language->manage_downloads_error,
                    view::$language->manage_downloads_upload_file_error
                );
            }

            $this->checkSingleUpload();
            if ($_FILES['file']['error']) {
                throw new memberErrorException(
                    view::$language->manage_downloads_error,
                    view::$language->manage_downloads_upload_file_error
                );
            }

            $this->moveUploadedFile();
            db::set(
                "INSERT INTO downloads (name, description, filesize) VALUES ('%s', '%s', %u)",
                $this->fileName, $this->getFileDescription(), $this->fileSize
            );

            $this->redirectMessage(
                SUCCESS_EXCEPTION,
                view::$language->manage_downloads_success,
                view::$language->manage_downloads_file_is_added,
                $adminToolsLink . '/manage-downloads'
            );

        }

        view::assign('node_name', view::$language->manage_downloads_add_title);
        $this->setProtectedLayout('manage-downloads-add.html');

    }


    public function edit() {

        $fileID = request::shiftParam('id');
        if (!validate::isNumber($fileID)) {
            throw new memberErrorException(
                view::$language->manage_downloads_error,
                view::$language->manage_downloads_data_invalid
            );
        }

        $fileData = db::normalizeQuery(
            'SELECT id, name, description FROM downloads WHERE id = %u', $fileID
        );

        if (!$fileData) {
            throw new memberErrorException(
                view::$language->manage_downloads_error,
                view::$language->manage_downloads_file_not_found
            );
        }

        if (request::isPost()) {

            $adminToolsLink = app::config()->site->admin_tools_link;
            request::validateReferer($adminToolsLink . '/manage-downloads/edit\?id=\d+', true);

            if (isset($_FILES['file'])) {
                $this->checkSingleUpload();
                if ($_FILES['file']['tmp_name']) {
                    @ unlink(APPLICATION . 'resources/downloads/' . rawurlencode($fileData['name']));
                    $this->moveUploadedFile();
                }
            }

            if ($this->fileName) {
                db::set(
                    "UPDATE downloads
                        SET name = '%s', description = '%s', filesize = %u
                        WHERE id = %u",
                    $this->fileName,
                    $this->getFileDescription(),
                    $this->fileSize,
                    $fileData['id']
                );
            } else {
                db::set(
                    "UPDATE downloads SET description = '%s' WHERE id = %u",
                    $this->getFileDescription(), $fileData['id']
                );
            }

            $this->redirectMessage(
                SUCCESS_EXCEPTION,
                view::$language->manage_downloads_success,
                view::$language->manage_downloads_file_is_edited,
                $adminToolsLink . '/manage-downloads'
            );

        }

        view::assign('file', $fileData);
        view::assign('node_name', view::$language->manage_downloads_edit_title);
        $this->setProtectedLayout('manage-downloads-edit.html');

    }


    private function checkSingleUpload() {

        if (is_array($_FILES['file']['tmp_name'])) {
            throw new memberErrorException(
                view::$language->manage_downloads_error,
                view::$language->manage_downloads_upload_single_only
            );
        }

    }


    private function moveUploadedFile() {

        $this->fileSize = (int) $_FILES['file']['size'];
        $this->fileName = preg_replace(
            '/\s+/', '-', (string) $_FILES['file']['name']
        );

        if (db::query("SELECT (1) ex FROM downloads WHERE name = '%s'", $this->fileName)) {
            throw new memberErrorException(
                view::$language->manage_downloads_error,
                view::$language->manage_downloads_file_is_exists
            );
        }

        $moveStatus = @ move_uploaded_file(
            $_FILES['file']['tmp_name'],
            APPLICATION . 'resources/downloads/' . rawurlencode($this->fileName)
        );

        if (!$moveStatus) {
            throw new memberErrorException(
                view::$language->manage_downloads_error,
                view::$language->manage_downloads_upload_file_error
            );
        }

    }


    private function getFileDescription() {
        return filter::input(request::getPostParam('description'))->stripTags()->getData();
    }


}


