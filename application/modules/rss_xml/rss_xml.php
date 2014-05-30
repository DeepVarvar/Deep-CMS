<?php


/**
 * rss_xml module
 */

class rss_xml extends baseController {


    private $mainHostUrl = null;

    public function index() {


        /**
         * clear before added public variables,
         * set main output context and disable changes
         */

        view::clearPublicVariables();
        view::setOutputContext('xml');
        view::lockOutputContext();

        $rssCnf = app::loadConfig('rss_xml.json');
        $config = app::config();
        $this->mainHostUrl = $config->site->protocol . '://' . $config->site->domain;


        /**
         * assign RSS channel into view,
         * set custom RSS XSD schema
         */

        $treeChannel = array(
            'title'       => $rssCnf->tree->title,
            'link'        => $this->mainHostUrl . '/',
            'description' => $rssCnf->tree->description,
            'generator'   => 'Deep-CMS ' . $config->application->version,
            'atom:link'   => array(
                'href' => $this->mainHostUrl . request::getURI()
            ),
            'item'        => $this->getTreeItems()
        );

        view::assign('rss', array(
            'channel'   => array($treeChannel)
        ));

        view::setXSDSchema(
            array(
                'name'       => 'rss',
                'attributes' => array(
                    array(
                        'name'  => 'version',
                        'value' => '2.0'
                    ),
                    array(
                        'name'  => 'xmlns:atom',
                        'value' => 'http://www.w3.org/2005/Atom'
                    )
                ),
                'children' => array(
                    array(
                        'name'     => 'channel',
                        'repeat'   => true,
                        'children' => array(
                            array(
                                'name' => 'atom:link',
                                'attributes' => array(
                                    array('name' => 'href', 'value' => true),
                                    array('name' => 'rel',  'value' => 'self'),
                                    array(
                                        'name'  => 'type',
                                        'value' => 'application/rss+xml'
                                    )
                                )
                            ),
                            array('name' => 'title'),
                            array('name' => 'link'),
                            array(
                                'name'   => 'item',
                                'repeat' => true
                            )
                        )
                    )
                )
            )
        );


    }


    private function getTreeItems() {

        $items = db::query("

            SET @m= (SELECT (MAX(last_modified) - INTERVAL 1 DAY) FROM tree);
            SET @n= (NOW());
            SELECT node_name title, CONCAT('%s', page_alias) link,
                page_text description,
                DATE_FORMAT(
                    last_modified,'%%a, %%d %%b %%Y %%H:%%i:%%s %s'
                ) pubDate
                FROM tree WHERE is_publish = 1 AND in_sitemap_xml = 1
                    AND last_modified BETWEEN @m AND @n
                        ORDER BY last_modified DESC LIMIT 30",

                $this->mainHostUrl,
                str_replace(':', '', app::config()->site->default_timezone)

        );

        foreach ($items as $k => $item) {

            $items[$k]['guid'] = $items[$k]['link'];
            if (!$item['description']) {
                $items[$k]['description'] = '[no description]';
                continue;
            }
            $items[$k]['description'] = helper::contentPreview($item['description'], 140);

        }

        return $items;

    }


}


