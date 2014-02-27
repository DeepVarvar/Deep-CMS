<?php


/**
 * sitemap.xml module
 */

class sitemap_xml extends baseController {


    public function index() {

        //@ set_time_limit(0);
        //@ ignore_user_abort();
        //@ ini_set('memory_limit', '512M');

        view::clearPublicVariables();
        view::setOutputContext('xml');
        view::lockOutputContext();


        /**
         * available parameters on <url> item:
         *
         * <loc>        : url - REQUIRED!
         * <lastmod>    : (date) YYYY.MM.DD
         * <changefreq> : always|hourly|daily|weekly|monthly|yearly|never
         * <priority>   : (float) 0.0-1.0
         *
         * more info: http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
         */

        $config  = app::config();
        $sitemap = db::query(
            "SELECT 
                    IF(
                        page_alias LIKE '%%tp://%%',
                        page_alias,
                        CONCAT('%s', page_alias)
                    ) loc,
                    DATE_FORMAT(last_modified,'%%Y-%%m-%%d') lastmod,
                    change_freq changefreq,
                    ROUND(searchers_priority,1) priority FROM tree
                WHERE in_sitemap_xml = 1 AND is_publish = 1",
            $config->site->protocol . '://' . $config->site->domain
        );

        view::assign('urlset', $sitemap);
        view::setXSDSchema(
            array(
                'name'       => 'urlset',
                'attributes' => array(
                    array(
                        'name'  => 'xmlns',
                        'value' => 'http://www.sitemaps.org/schemas/sitemap/0.9'
                    )
                ),
                'children' => array(
                    array(
                        'name'     => 'url',
                        'children' => array(
                            array('name' => 'lastmod'),
                            array('name' => 'changefreq'),
                            array('name' => 'loc'),
                            array('name' => 'priority')
                        )
                    )
                )
            )
        );

    }


}


