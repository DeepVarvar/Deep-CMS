<?php



/**
 * comments module
 */

class comments extends baseController {


    private $commentsChunkLimit = 10;


    public function index() {


        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();

        $target = request::shiftParam("target");
        $offset = request::shiftParam("offset");

        if (!validate::isNumber($target) or !validate::isNumber($offset)) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );
        }

        $paginator = new paginator(

            "SELECT c.id, c.reply_id, c.author_id, c.creation_date,
                IF(c.author_id IS NULL, c.author_name, u.login) author_name,
                    c.comment_text FROM comments c
                        LEFT JOIN users u ON u.id = c.author_id
                            WHERE c.node_id = {$target}
                                ORDER BY c.reply_id ASC, c.creation_date ASC"

        );

        $paginator

            ->setCurrentPage($offset)
            ->setItemsPerPage($this->commentsChunkLimit)
            ->setSliceSizeByPages(1);

        $paginator = $paginator->getResult();
        view::assign("comments", $paginator['items']);
        view::assign("more", ($paginator['number_of_pages'] > $offset));


    }


    public function add() {


        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();

        $required = array("target", "reply", "comment");
        if (!member::isAuth()) {
            $required = array_merge(
                $required, array("name", "email", "protection")
            );
        }

        $data = request::getRequiredPostParams($required);
        if (!$data) {
            throw new memberErrorException(
                view::$language->error, view::$language->data_not_enough
            );
        }

        $data = filter::input($data)->stripTags()->getData();
        if (!validate::isNumber($data['target'])) {
            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid
            );
        }

        if (!validate::isNumber($data['reply'])) {
            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid
            );
        }

        if (!$data['comment']) {
            throw new memberErrorException(
                view::$language->error, view::$language->comment_cant_epmty
            );
        }

        $data['comment'] = mb_substr($data['comment'], 0, 2048);
        $data['comment'] = helper::wordWrap($data['comment'], 20);
        $data['comment'] = nl2br($data['comment'], true);

        if (!member::isAuth()) {

            if (!$data['name']) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->comment_name_empty
                );
            }

            if (!$data['email']) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->email_is_empty
                );
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new memberErrorException(
                    view::$language->error, view::$language->email_invalid
                );
            }

            if (!$data['protection']) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->captcha_pcode_is_empty
                );
            }

            if ($data['protection'] !== storage::read("captcha")) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->captcha_pcode_invalid
                );
            }

            $data['author_id'] = "NULL";

        } else {

            $profile           = member::getProfile();
            $data['author_id'] = $profile['id'];
            $data['name']      = $profile['login'];
            $data['email']     = $profile['email'];

        }

        $data['author_ip'] = request::getClientIP();

        $existsNode = db::query(
            "SELECT (1) ex FROM tree WHERE id = %u
                AND is_publish = 1", $data['target']
        );

        if (!$existsNode) {
            throw new memberErrorException(
                view::$language->error, view::$language->node_not_found
            );
        }

        if ($data['reply'] > 0) {
            $existsComment = db::normalizeQuery(
                "SELECT (1) ex FROM comments WHERE id = %u", $data['reply']);
            if (!$existsComment) {
                throw new memberErrorException(
                    view::$language->error, view::$language->data_invalid);
            }
        }

        storage::shift("captcha");
        db::set(

            "INSERT INTO comments (
                id, reply_id, node_id, creation_date, author_ip, author_id,
                    author_name, author_email, comment_text) VALUES
                        (NULL, %u, %u, NOW(), '%s', %s, '%s', '%s', '%s')",

            $data['reply'],
            $data['target'],
            $data['author_ip'],
            $data['author_id'],
            $data['name'],
            $data['email'],
            $data['comment']

        );

        $newComment = db::query(

            "SELECT c.id, c.reply_id, c.author_id,
                IF(c.author_id IS NULL, c.author_name, u.login) author_name,
                    c.creation_date, c.comment_text FROM comments c
                        LEFT JOIN users u ON u.id = c.author_id
                            WHERE c.id = %u", db::lastID()

        );

        if (!$newComment) {
            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid
            );
        }

        view::assign("comments", $newComment);


    }


}



