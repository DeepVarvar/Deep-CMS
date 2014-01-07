<?php


/**
 * download module class
 */

class download extends baseController {

    public function index() {

        $name = request::shiftParam('target');
        $name = filter::input($name)->stripTags()->getData();

        if (!$name) {
            throw new systemErrorException(
                'Download error', 'Name is empty'
            );
        }

        $file = APPLICATION . 'resources/downloads/' . $name;
        if (!file_exists($file)) {
            throw new systemErrorException(
                'Download error', 'File ' . $name . ' is not exists'
            );
        }

        db::set(
            "UPDATE downloads SET cnt = cnt + 1 WHERE name = '%s'", $name
        );

        request::addHeader('Content-Type: application/octet-stream');
        request::addHeader('Accept-Ranges: bytes');
        request::addHeader('Content-Length: ' . filesize($file));
        request::addHeader('Content-Disposition: attachment; filename=' . $name);
        request::sendHeaders();

        readfile($file);
        exit();

    }

}


