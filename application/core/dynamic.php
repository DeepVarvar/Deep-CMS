<?php



/**
 * dynamic pages class
 */

abstract class dynamic {


    protected static


        /**
         * current dynamic document
         */

        $document = null,


        /**
         * dynamic properties of document
         */

        $properties = array();


    /**
     * load dynamic page from document tree
     */

    public static function loadPage() {


        $noImage = app::config()->site->no_image;

        self::$document = db::normalizeQuery("

            SELECT

                d.id page_id,
                d.parent_id,
                d.lvl,
                d.lk,
                d.rk,
                ('0') page_is_module,
                d.page_name,
                d.page_alias,
                d.page_h1,
                d.page_title,
                d.meta_keywords,
                d.meta_description,
                d.layout,
                d.permanent_redirect,
                d.last_modified,
                d.creation_date,
                d.props_id,
                u.id author_id,
                u.login author_name,
                pt.sys_name,
                IF(i.name IS NOT NULL,i.name,'{$noImage}') image

            FROM documents d
            INNER JOIN prototypes pt ON pt.id = d.prototype
            LEFT JOIN users u ON u.id = d.author
            LEFT JOIN images i ON i.document_id = d.id AND i.is_master = 1

            WHERE d.page_alias = '%s' AND d.is_publish = 1
            ORDER BY d.sort ASC
            LIMIT 1

            ",

            request::getURI()

        );


        /**
         * document not found
         */

        if (!self::$document) {
            throw new memberErrorException(404, view::$language->error . " 404", view::$language->page_not_found);
        }


        /**
         * moved permanently redirect
         */

        if (self::$document['permanent_redirect']) {
            request::redirect(self::$document['permanent_redirect']);
        }


        /**
         * get dynamic properties of document
         */

        $sysName = self::$document['sys_name'];
        $propsID = self::$document['props_id'];

        self::$properties = db::normalizeQuery("
            SELECT * FROM {$sysName}
            WHERE id = %u LIMIT 1", $propsID
        );


        if (!self::$properties) {
            throw new memberErrorException(404, view::$language->error . " 404", view::$language->page_not_found);
        }

        if (is_string(self::$properties)) {
            self::$properties = array("page_text" => self::$properties);
        } else {
            unset(self::$properties['id']);
        }


        /**
         * set document layout,
         * assign data into view
         */

        view::setLayout(app::config()->layouts->public . self::$document['layout']);

        view::assign(self::$properties);
        view::assign(self::$document);


    }


}



