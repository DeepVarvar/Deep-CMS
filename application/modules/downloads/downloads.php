<?php


/**
 * download files module
 */

class downloads extends baseController {


    public function index() {

        $name = request::shiftParam('target');
        if ($name !== null) {
            $this->sendFile($name);
        }

        $dConf = app::loadConfig('downloads.json');
        $paginator = new paginator(
            'SELECT name, description, filesize, cnt FROM downloads ORDER BY id DESC'
        );

        $paginator = $paginator
            ->setCurrentPage(request::getCurrentPage())
            ->setItemsPerPage($dConf->items_per_page)
            ->setSliceSizeByPages(10)
            ->getResult();

        view::assign('downloads', $paginator['items']);
        view::assign('pages', $paginator['pages']);
        $this->setProtectedLayout('downloads.html');

    }


    private function sendFile($name) {

        $name = filter::input($name)->stripTags()->getData();
        if (!$name) {
            throw new systemErrorException('Download error', 'Name is empty');
        }

        $file = APPLICATION . 'resources/downloads/' . rawurlencode($name);
        if (!is_file($file)) {
            throw new systemErrorException(
                'Download error', 'File ' . $name . ' is not exists');
        }

        db::set("UPDATE downloads SET cnt = cnt + 1 WHERE name = '%s'", $name);

        request::addHeader('Content-Type: application/octet-stream');
        request::addHeader('Accept-Ranges: bytes');
        request::addHeader('Content-Length: ' . filesize($file));
        request::addHeader('Content-Disposition: attachment; filename=' . $name);
        request::sendHeaders();

        readfile($file);
        exit();

    }


}


