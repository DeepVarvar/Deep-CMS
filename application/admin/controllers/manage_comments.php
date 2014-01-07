<?php


/**
 * admin submodule, manage comments
 */

class manage_comments extends baseController {


    /**
     * set permissions for this controller
     */

    public function setPermissions() {

        $this->permissions = array(
            array(
                'action'      => null,
                'permission'  => 'comments_manage',
                'description' => view::$language->permission_comments_manage
            ),
            array(
                'action'      => 'delete',
                'permission'  => 'comment_delete',
                'description' => view::$language->permission_comment_delete
            ),
            array(
                'action'      => 'edit',
                'permission'  => 'comment_edit',
                'description' => view::$language->permission_comment_edit
            )
        );

    }


    public function index() {

        $paginator = new paginator(
            'SELECT c.id, c.author_ip, c.author_id, c.creation_date,
                IF(c.author_id IS NULL,c.author_name,u.login) author_name,
                IF(c.author_email IS NULL,u.email,c.author_email) author_email,
                    t.page_alias, t.node_name
                FROM comments c
                LEFT JOIN tree t ON t.id = c.node_id
                LEFT JOIN users u ON u.id = c.author_id
                ORDER BY c.creation_date DESC'
        );

        $paginator = $paginator->setCurrentPage(request::getCurrentPage())
            ->setItemsPerPage(10)->setSliceSizeByPages(10)->getResult();

        view::assign('comments', $paginator['items']);
        view::assign('pages', $paginator['pages']);
        view::assign('node_name', view::$language->comments_manage);
        $this->setProtectedLayout('comments.html');

    }


    public function delete() {

        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer($adminToolsLink . '/manage-comments');

        $commentID = request::shiftParam('id');
        if (!validate::isNumber($commentID)) {
            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid
            );
        }

        db::set('DELETE FROM comments WHERE id = %u', $commentID);
        db::set(
            'UPDATE comments SET reply_id = 0 WHERE reply_id = %u', $commentID
        );

        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->comment_is_deleted,
            $adminToolsLink . '/manage-comments'
        );

    }


    public function edit() {

        $commentID = request::shiftParam('id');
        if (!validate::isNumber($commentID)) {
            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid
            );
        }

        if (!$comment = db::normalizeQuery(
            'SELECT id, author_id, author_name,
                comment_text FROM comments WHERE id = %u', $commentID
        )) {
            throw new memberErrorException(
                view::$language->error, view::$language->comment_not_found
            );
        }

        if (request::isPost()) {
            $this->saveComment($commentID);
        }

        $comment = preg_replace('/\s*<br\s?\/?>\s*/', "\n", $comment);
        view::assign('comment', $comment);
        view::assign('page_title', view::$language->comment_edit_exists);
        $this->setProtectedLayout('comment-edit.html');

    }


    private function saveComment($id) {

        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer(
            $adminToolsLink . '/manage-comments/edit\?id=\d+', true
        );

        $comment = request::getPostParam('comment_text');
        $comment = filter::input($comment)->stripTags()->getData();

        if (!$comment) {
            throw new memberErrorException(
                view::$language->error, view::$language->data_not_enough
            );
        }

        $comment = helper::wordWrap($comment, 20);
        $comment = nl2br($comment, true);

        $author = request::getPostParam('author_name');
        if ($author !== null) {

            $author = filter::input($author)->stripTags()->getData();
            if (!$author) {
                throw new memberErrorException(
                    view::$language->error, view::$language->data_not_enough
                );
            }

            db::set(
                "UPDATE comments SET comment_text = '%s', author_name = '%s'
                    WHERE id = %u", $comment, $author, $id
            );

        } else {
            db::set(
                "UPDATE comments SET comment_text = '%s'
                    WHERE id = %u", $comment, $id
            );
        }

        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->comment_is_edited,
            $adminToolsLink . '/manage-comments'
        );

    }


}


