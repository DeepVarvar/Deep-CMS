<?php



/**
 * exit for incorrect request on this script
 */

if (!defined("APPLICATION")) {
    exit();
}


ini_set("display_errors", "On");
ini_set("html_errors", "On");

error_reporting(E_ALL | E_STRICT);


/**
 * set path for admin installation layouts
 */

set_include_path(
    get_include_path() . PATH_SEPARATOR . APPLICATION . "layouts/admin/protected/"
);


/**
 * start session installation environment
 */

session_name("deepcms");
session_start();

if (!array_key_exists("ins", $_SESSION)) {
    $_SESSION['ins'] = array();
    $_SESSION['ins']['report'] = array();
    $_SESSION['ins']['step'] = 1;
    $_SESSION['ins']['errors'] = false;
}


/**
 * get configuration object
 */

function getConfig() {

    if (!array_key_exists("config", $_SESSION['ins'])) {
        $_SESSION['ins']['config'] = json_decode('{"site":{"default_keywords":"","default_description":"","check_unused_params":false,"default_language":"ru","default_timezone":"+04:00","theme":"default","domain":"build.deep","protocol":"http","admin_tools_link":"\/admin","admin_resources":"\/admin-resources\/","no_image":"no-image.png","no_avatar":"no-avatar.png"},"application":{"name":"Deep-CMS","version":"2.35.78","support_email":"support@deep-cms.ru"},"system":{"debug_mode":false,"cache_enabled":false,"write_log":true,"log_file_max_size":16384,"block_prefetch_requests":true,"default_output_context":"html","cookie_expires_time":"259200","session_name":"deepcms","max_group_priority_number":"10"},"cached_pages":[],"path":{"admin":"admin\/","autorun_after":"autorun\/after\/","autorun_before":"autorun\/before\/","cache":"cache\/","languages":"languages\/","library":"library\/","logs":"logs\/","metadata":"metadata\/","modules":"modules\/","resources":"resources\/","tmp":"tmp\/","upload_dir":"upload\/"},"layouts":{"admin":"layouts\/admin\/","system":"layouts\/system\/","themes":"layouts\/themes\/","parts":"parts\/","public":"public\/","protected":"protected\/","header":"parts\/header.html","footer":"parts\/footer.html","exception":"protected\/exception.html","debug":"layouts\/system\/debug.html","txt":"layouts\/system\/txt.html","json":"layouts\/system\/json.html","xml":"layouts\/system\/xml.html"},"output_contexts":[{"name":"html","enabled":true},{"name":"json","enabled":true},{"name":"xml","enabled":true},{"name":"txt","enabled":true}],"db":{"host":"localhost","port":3306,"prefix":"","name":"","user":"","password":"","connection_charset":"utf8"}}');
    }

    return $_SESSION['ins']['config'];

}


/**
 * save configuration object into session
 */

function setConfig($config) {
    $_SESSION['ins']['config'] = $config;
}


/**
 * save config string into file
 */

function saveConfigIntoFile($config) {


    /**
     * fix bool values
     */

    $config->system->debug_mode = $config->system->debug_mode ? 'true' : 'false';


    /**
     * place config values into config string example
     */

    $configString = <<<CONFIGSTRING


{


    /**
     * global application settings,
     * default language, default template theme,
     * URL of administrative tools
     */

    "site": {


        /**
         * SEO: default Keywords and Description.
         * this values always is empty
         * because you can set it on administrative tools
         */

        "default_keywords": "",
        "default_description": "",


        /**
         * SEO: check for unused GET-parameters
         * WARNING! experimental function
         */

        "check_unused_params": false,


        // default language environment
        "default_language": "ru",


        // default timezone
        "default_timezone": "+04:00",


        /**
         * default theme of site (templates collection)
         * this values always is "default"
         * because you can set it on administrative tools
         */

        "theme": "default",


        /**
         * domain of site
         * this option use for check for possible CSRF attack
         * if your use non standart port of http or https protocols
         * you need set this value like "yourdomain.tdl:portnumber"
         * note: this value is generated on installation process
         */

        "domain": "{$config->site->domain}",


        /**
         * current server protocol
         * this value is generated on installation process
         */

        "protocol": "{$config->site->protocol}",


        // URL path of administrative tools
        "admin_tools_link": "/admin",


        // relative URL path of administrative tools resources
        "admin_resources": "/admin-resources/",


        // no_image-image filename
        "no_image": "no-image.png",


        // no_avatar-image filename
        "no_avatar": "no-avatar.png"


    },


    /**
     * application identifiers
     */

    "application": {


        // name signature and version of application
        "name": "Deep-CMS",
        "version": "2.35.78",


        // email address of technical support
        "support_email": "support@deep-cms.ru"


    },


    /**
     * system settings of application
     */

    "system": {


        /**
         * debug mode for developers
         * this option show very verbosity trace of application events
         */

        "debug_mode": {$config->system->debug_mode},


        // enable or disable filesystem cache support
        "cache_enabled": false,


        // write logs of application (members) events
        "write_log": true,


        // max size of separated log file
        "log_file_max_size": 16384,


        // block prefetch requests
        "block_prefetch_requests": true,


        // default output context
        "default_output_context": "html",


        // cookie expires time (sec)
        "cookie_expires_time": "259200",


        /**
         * name of session, set for session_name() PHP function
         * note: for compatibility set only alphabetic symbols
         */

        "session_name": "deepcms",


        // member groups priority range number
        "max_group_priority_number": 10


    },


    /**
     * patterns rules of filesystem cache
     * this is standard regular expressions of PHP
     * note: need double backslash escaping
     */

    "cached_pages": [

        /*"/\\\/sitemap\\\.xml/"*/

    ],


    /**
     * inside system application path's
     */

    "path": {


        // relative path of global admin directory
        "admin": "admin/",


        // relative path of after autorun directory
        "autorun_after": "autorun/after/",


        // relative path of before autorun directory
        "autorun_before": "autorun/before/",


        // relative path of languages directory
        "cache": "cache/",


        // relative path of languages directory
        "languages": "languages/",


        // relative path of library directory
        "library": "library/",


        // relative path of log directory
        "logs": "logs/",


        // relative path of metadata directory
        "metadata": "metadata/",


        // relative path of member modules directory
        "modules": "modules/",


        // relative path of resources
        "resources": "resources/",


        // relative path of temporary files directory
        "tmp": "tmp/",


        // relative path of public uploading directory
        "upload_dir": "upload/"


    },


    /**
     * output templates
     * relative path's of templates
     */

    "layouts": {


        // relative path of admin templates directory
        "admin": "layouts/admin/",


        // relative path of system templates directory
        "system": "layouts/system/",


        // relative path of member themes directory
        "themes": "layouts/themes/",


        // relative path of parts templates directory
        "parts": "parts/",


        // relative path of public templates directory
        "public": "public/",


        // relative path of protected templates directory
        "protected": "protected/",


        // relative path of required header template
        "header": "parts/header.html",


        // relative path of required footer template
        "footer": "parts/footer.html",


        // relative path of required exception template
        "exception": "protected/exception.html",


        /**
         * relative path's of system required
         * output-context-templates and debugging template
         */

        "debug": "layouts/system/debug.html",
        "txt": "layouts/system/txt.html",
        "json": "layouts/system/json.html",
        "xml": "layouts/system/xml.html"


    },


    /**
     * available output contexts: html, json, xml, txt
     * for enabled or disabled change "enabled" properties on boolean values
     */

    "output_contexts": [

        {"name": "html", "enabled": true},
        {"name": "json", "enabled": true},
        {"name": "xml",  "enabled": true},
        {"name": "txt",  "enabled": true}

    ],


    /**
     * database connection settings
     */

    "db": {


        // host or IP of database server
        "host": "{$config->db->host}",


        // port of connection
        "port": {$config->db->port},


        // database tables prefix
        "prefix": "{$config->db->prefix}",


        // name of database
        "name": "{$config->db->name}",


        // name of database user
        "user": "{$config->db->user}",


        // database user password
        "password": "{$config->db->password}",


        // client connection charset
        "connection_charset": "utf8"


    }


}


CONFIGSTRING;


    /**
     * write content of configuration file
     */

    $configFile = APPLICATION . "config/main.json";
    file_put_contents($configFile, $configString, LOCK_EX);


}


/**
 * get full installation query string
 */

function getInstallationQueryString($prefix = "") {


    return <<<INSTALLATIONSTRING

        DROP TABLE IF EXISTS {$prefix}users;
        CREATE TABLE {$prefix}users (

            id                  BIGINT(20) NOT NULL AUTO_INCREMENT,
            group_id            BIGINT(20) DEFAULT NULL,
            status              INT(1)     NOT NULL DEFAULT '0',
            language            CHAR(16)   CHARACTER SET utf8 COLLATE utf8_bin,
            timezone            CHAR(8)    CHARACTER SET utf8 COLLATE utf8_bin,
            avatar              CHAR(128)  CHARACTER SET utf8 COLLATE utf8_bin,
            login               CHAR(128)  CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            password            CHAR(128)  CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            email               CHAR(255)  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            hash                CHAR(128)  CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            last_ip             CHAR(15)   CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '0.0.0.0',
            registration_date   DATETIME   NOT NULL,
            last_visit          DATETIME   NOT NULL,
            about               TEXT       CHARACTER SET utf8 COLLATE utf8_general_ci,
            working_cache       LONGTEXT   CHARACTER SET utf8 COLLATE utf8_bin,

            PRIMARY KEY (id),

            KEY group_id (group_id),
            KEY status   (status),
            KEY hash     (hash)

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;

        INSERT INTO {$prefix}users
        (id, group_id, status, login, password, email, hash, last_ip, registration_date, last_visit, about)
        VALUES
        (1, 0, 0, '', '', 'support@deep-cms.ru', '', '127.0.0.1', NOW(), NOW(), '');

        UPDATE {$prefix}users SET id = 0 WHERE id = 1;


        DROP TABLE IF EXISTS {$prefix}permissions;
        CREATE TABLE {$prefix}permissions (

            id     BIGINT(20) NOT NULL AUTO_INCREMENT,
            name   CHAR(255)  CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,

            PRIMARY KEY (id)

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;


        DROP TABLE IF EXISTS {$prefix}group_permissions;
        CREATE TABLE {$prefix}group_permissions (

            group_id        BIGINT(20) NOT NULL,
            permission_id   BIGINT(20) NOT NULL

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;


        DROP TABLE IF EXISTS {$prefix}groups;
        CREATE TABLE {$prefix}groups (

            id         BIGINT(20) NOT NULL AUTO_INCREMENT,
            priority   BIGINT(20) NOT NULL,
            name       CHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,

            PRIMARY KEY (id)

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;

        INSERT INTO {$prefix}groups (id, priority, name) VALUES (1, 0, 'root');
        UPDATE {$prefix}groups SET id = 0 WHERE id = 1;


        DROP TABLE IF EXISTS {$prefix}images;
        CREATE TABLE {$prefix}images (

            id         BIGINT(20)  NOT NULL AUTO_INCREMENT,
            node_id    BIGINT(20)  NOT NULL,
            is_master  TINYINT(1)  NOT NULL DEFAULT '0',
            name       CHAR(255)     CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,

            PRIMARY KEY (id),

            KEY node_id   (node_id),
            KEY is_master (is_master)

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}tree;
        CREATE TABLE {$prefix}tree (

            id                  BIGINT(20)  NOT NULL AUTO_INCREMENT,
            parent_id           BIGINT(20)  NOT NULL,
            lvl                 TINYINT(3)  NOT NULL,
            lk                  BIGINT(20)  NOT NULL,
            rk                  BIGINT(20)  NOT NULL,
            prototype           CHAR(255)   CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            children_prototype  CHAR(255)   CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            author              BIGINT(20)  NOT NULL,
            modified_author     BIGINT(20)  NOT NULL,
            last_modified       DATETIME    NOT NULL,
            creation_date       DATETIME    NOT NULL,
            is_publish          TINYINT(1)  NOT NULL,
            node_name           MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            in_sitemap          TINYINT(1)  NOT NULL,
            in_sitemap_xml      TINYINT(1)  NOT NULL,

            layout              CHAR(255)   CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            page_alias          MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            permanent_redirect  MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            change_freq         CHAR(7)     CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            searchers_priority  DOUBLE      DEFAULT NULL,
            module_name         CHAR(255)   CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            page_title          MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_general_ci,
            page_h1             MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_general_ci,
            meta_keywords       MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_general_ci,
            meta_description    MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_general_ci,
            page_text           LONGTEXT    CHARACTER SET utf8 COLLATE utf8_general_ci,

            PRIMARY KEY (id),

            KEY parent_id       (parent_id),
            KEY lvl             (lvl),
            KEY lk              (lk),
            KEY rk              (rk),
            KEY author          (author),
            KEY modified_author (modified_author),
            KEY prototype       (prototype),
            KEY is_publish      (is_publish),
            KEY in_sitemap      (in_sitemap),
            KEY in_sitemap_xml  (in_sitemap_xml)

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}tree_features;
        CREATE TABLE {$prefix}tree_features (

            node_id        BIGINT(20) NOT NULL,
            feature_id     BIGINT(20) NOT NULL,
            feature_value  MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

            KEY node_id    (node_id),
            KEY feature_id (feature_id)

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}features;
        CREATE TABLE {$prefix}features (

            id    BIGINT(20) NOT NULL AUTO_INCREMENT,
            name  CHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

            PRIMARY KEY (id)

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}menu;
        CREATE TABLE {$prefix}menu (

            id          BIGINT(20) NOT NULL AUTO_INCREMENT,
            parent_id   BIGINT(20) NOT NULL DEFAULT '0',
            name        CHAR(255)  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

            PRIMARY KEY (id),

            KEY parent_id (parent_id)

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}menu_items;
        CREATE TABLE {$prefix}menu_items (

            menu_id        BIGINT(20) NOT NULL,
            node_id        BIGINT(20) NOT NULL,

            KEY menu_id (menu_id),
            KEY node_id (node_id)

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}comments;
        CREATE TABLE {$prefix}comments (

            id             BIGINT(20)  NOT NULL AUTO_INCREMENT,
            reply_id       BIGINT(20)  NOT NULL,
            node_id        BIGINT(20)  NOT NULL,
            creation_date  DATETIME    NOT NULL,
            author_ip      CHAR(15)    CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            author_id      BIGINT(20)  DEFAULT NULL,
            author_name    MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
            author_email   char(255)   CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            comment_text   TEXT        CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

            PRIMARY KEY (id),

            KEY reply_id     (reply_id),
            KEY node_id      (node_id),
            KEY author_id    (author_id),
            KEY author_email (author_email)

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}downloads;
        CREATE TABLE {$prefix}downloads (

            id    BIGINT(20)  NOT NULL AUTO_INCREMENT,
            name  CHAR(255)   CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            cnt   BIGINT(20)  NOT NULL,

            PRIMARY KEY (id),
            KEY name (name)

        ) ENGINE = InnoDB  DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}online_guests;
        CREATE TABLE {$prefix}online_guests (

            session_id  CHAR(32)  CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            last_visit  DATETIME  NOT NULL,

            UNIQUE KEY session_id (session_id)

        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;


INSTALLATIONSTRING;


}


/**
 * get extended data installation query string
 */

function getExtendedQueryString($prefix = "") {


    return <<<EXTENDEDINSTALLATIONSTRING


        INSERT INTO {$prefix}menu (id, parent_id, name) VALUES (1, 0, 'Левое меню'), (2, 0, 'Нижнее меню');
        INSERT INTO {$prefix}menu_items (menu_id, node_id) VALUES (1, 14), (1, 1), (2, 1), (1, 4), (2, 4), (1, 7), (2, 7), (1, 11), (1, 3), (1, 2), (2, 2);

        INSERT INTO {$prefix}tree (id, parent_id, lvl, lk, rk, prototype, children_prototype, author, modified_author, last_modified, creation_date, is_publish, node_name, in_sitemap, in_sitemap_xml, layout, page_alias, permanent_redirect, change_freq, searchers_priority, module_name, page_title, page_h1, meta_keywords, meta_description, page_text) VALUES
        (1, 0, 1, 1, 2, 'simplePage', 'simplePage', 0, 0, '2013-11-15 23:18:24', '2013-11-10 12:55:46', 1, 'Главная', 1, 1, 'page-with-comments.html', '/', '', 'always', 1, NULL, 'Добро пожаловать на демонстрационный сайт Deep-CMS!', 'Добро пожаловать, друзья!', '', '', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas nec dui in&nbsp;lorem fermentum varius sit amet ac&nbsp;quam. Fusce eu&nbsp;porta nibh. Phasellus elementum vehicula est eget sollicitudin. Integer eros arcu, lacinia non vulputate sed, bibendum et&nbsp;orci. Maecenas ante felis, feugiat vitae lacus ut, faucibus pretium nisl. Aliquam non cursus mauris. Class aptent taciti sociosqu ad&nbsp;litora torquent per conubia nostra, per inceptos himenaeos. Proin bibendum convallis nisi ut&nbsp;vestibulum. Quisque dignissim libero viverra metus auctor aliquam. Sed imperdiet justo ac&nbsp;sem dapibus tincidunt ut&nbsp;at&nbsp;ligula. Mauris sagittis nec nibh non tristique. Morbi ut&nbsp;leo mollis, cursus dolor eu, rutrum lorem. Etiam cursus pellentesque velit non ultricies.</p>\r\n\r\n<p>Integer porttitor vulputate mi, non euismod nunc posuere pretium. Duis congue id&nbsp;massa eget pellentesque. In&nbsp;in&nbsp;orci elit. Nulla a&nbsp;lacinia tortor. In&nbsp;vel mollis nunc, nec tempus enim. Morbi semper sem ac&nbsp;ligula laoreet, sit amet sodales nulla ullamcorper. Mauris et&nbsp;dolor et&nbsp;odio ullamcorper condimentum. Donec purus purus, vehicula id&nbsp;interdum ut, mollis sit amet augue. Nam ultrices lobortis dapibus. In&nbsp;risus diam, interdum id&nbsp;metus ut, sollicitudin lobortis ante.</p>'),
        (2, 0, 1, 23, 24, 'mainModule', 'simplePage', 0, 0, '2013-11-15 23:23:55', '2013-11-10 12:57:18', 1, 'sitemap.xml', 1, 0, NULL, '/sitemap.xml', NULL, NULL, NULL, 'sitemap_xml', NULL, NULL, NULL, NULL, NULL),
        (3, 0, 1, 21, 22, 'mainModule', 'simplePage', 0, 0, '2013-11-15 23:23:09', '2013-11-10 12:58:05', 1, 'Карта сайта', 0, 0, NULL, '/%D0%9A%D0%B0%D1%80%D1%82%D0%B0-%D1%81%D0%B0%D0%B9%D1%82%D0%B0', NULL, NULL, NULL, 'sitemap', NULL, NULL, NULL, NULL, NULL),
        (4, 0, 1, 3, 8, 'simplePage', 'simplePage', 0, 0, '2013-11-15 23:18:48', '2013-11-10 12:59:44', 1, 'Новости', 1, 1, 'children-list.html', '/%D0%9D%D0%BE%D0%B2%D0%BE%D1%81%D1%82%D0%B8', '', 'daily', 0.7, NULL, '', '', '', '', ''),
        (5, 4, 2, 6, 7, 'simplePage', 'simplePage', 0, 0, '2013-11-15 23:21:39', '2013-11-10 13:00:31', 1, 'Владимир Путин ушел с поста президента', 1, 1, 'page-with-comments.html', '/%D0%9D%D0%BE%D0%B2%D0%BE%D1%81%D1%82%D0%B8/%D0%92%D0%BB%D0%B0%D0%B4%D0%B8%D0%BC%D0%B8%D1%80-%D0%9F%D1%83%D1%82%D0%B8%D0%BD-%D1%83%D1%88%D0%B5%D0%BB-%D1%81-%D0%BF%D0%BE%D1%81%D1%82%D0%B0-%D0%BF%D1%80%D0%B5%D0%B7%D0%B8%D0%B4%D0%B5%D0%BD%D1%82%D0%B0', '', NULL, NULL, NULL, 'Сенсация! Владимир Путин ушел с поста президента!', 'Сенсация! Владимир Путин ушел с поста президента!', '', '', '<p>Integer ipsum elit, rutrum sed ullamcorper non, dapibus ut&nbsp;leo. Vestibulum in&nbsp;lorem fringilla, fermentum elit eu, vehicula mi. Ut&nbsp;lobortis tincidunt mattis. Nullam placerat magna vitae odio imperdiet, ac&nbsp;mattis elit tincidunt. Fusce porttitor non leo a&nbsp;aliquet. Sed vel quam ut&nbsp;mi&nbsp;mattis tincidunt. Maecenas vehicula nibh non elit condimentum, sed malesuada lectus dapibus. Suspendisse sed est eget massa auctor laoreet. Nulla eu&nbsp;eleifend velit. Integer eget magna enim. Class aptent taciti sociosqu ad&nbsp;litora torquent per conubia nostra, per inceptos himenaeos. Nunc vitae tortor at&nbsp;augue volutpat auctor et&nbsp;eget arcu. Ut&nbsp;rutrum orci ac&nbsp;est bibendum aliquam. Aliquam risus purus, rutrum at&nbsp;commodo in, posuere quis neque. Proin mattis libero id&nbsp;justo sodales fringilla. Nam elementum augue ut&nbsp;mauris feugiat, ut&nbsp;elementum augue mollis.</p>\r\n\r\n<p>Integer sollicitudin, tortor a&nbsp;posuere aliquet, dolor diam dignissim magna, ac&nbsp;molestie neque eros a&nbsp;eros. Curabitur pulvinar, leo non venenatis consequat, ipsum leo suscipit sem, ut&nbsp;posuere augue nunc sed lacus. Suspendisse dictum orci vel consectetur gravida. Nullam vitae sodales libero. Duis non mauris a&nbsp;leo rutrum sagittis. Donec purus orci, suscipit a&nbsp;ante in, bibendum dapibus diam. Nam dictum ipsum quis felis tempus, ut&nbsp;lacinia orci fringilla. Morbi eget ligula justo. Phasellus semper est est, vel semper justo semper ut. Nullam varius cursus velit ut&nbsp;egestas. Nunc leo nulla, vehicula at&nbsp;commodo at, molestie nec magna. Cras non aliquet velit, id&nbsp;vehicula diam.</p>'),
        (6, 4, 2, 4, 5, 'simplePage', 'simplePage', 0, 0, '2013-11-15 23:21:25', '2013-11-10 13:01:02', 1, 'Российская сборная по футболу выиграла мировой чемпионат', 1, 1, 'page-with-comments.html', '/%D0%9D%D0%BE%D0%B2%D0%BE%D1%81%D1%82%D0%B8/%D0%A0%D0%BE%D1%81%D1%81%D0%B8%D0%B9%D1%81%D0%BA%D0%B0%D1%8F-%D1%81%D0%B1%D0%BE%D1%80%D0%BD%D0%B0%D1%8F-%D0%BF%D0%BE-%D1%84%D1%83%D1%82%D0%B1%D0%BE%D0%BB%D1%83-%D0%B2%D1%8B%D0%B8%D0%B3%D1%80%D0%B0%D0%BB%D0%B0-%D0%BC%D0%B8%D1%80%D0%BE%D0%B2%D0%BE%D0%B9-%D1%87%D0%B5%D0%BC%D0%BF%D0%B8%D0%BE%D0%BD%D0%B0%D1%82', '', NULL, NULL, NULL, '', '', '', '', '<p>Integer porttitor vulputate mi, non euismod nunc posuere pretium. Duis congue id&nbsp;massa eget pellentesque. In&nbsp;in&nbsp;orci elit. Nulla a&nbsp;lacinia tortor. In&nbsp;vel mollis nunc, nec tempus enim. Morbi semper sem ac&nbsp;ligula laoreet, sit amet sodales nulla ullamcorper. Mauris et&nbsp;dolor et&nbsp;odio ullamcorper condimentum. Donec purus purus, vehicula id&nbsp;interdum ut, mollis sit amet augue. Nam ultrices lobortis dapibus. In&nbsp;risus diam, interdum id&nbsp;metus ut, sollicitudin lobortis ante.</p>\r\n\r\n<p>Duis bibendum lectus a&nbsp;volutpat posuere. Praesent rhoncus ultrices nunc, ut&nbsp;bibendum odio aliquet interdum. Nulla condimentum augue eu&nbsp;convallis suscipit. Duis tincidunt nibh at&nbsp;eros dictum, eu&nbsp;volutpat urna pellentesque. Integer et&nbsp;quam a&nbsp;tortor mattis lobortis ut&nbsp;in&nbsp;tellus. Duis facilisis, velit vitae iaculis tempor, ligula est posuere odio, non tristique neque mi&nbsp;sed erat. Fusce in&nbsp;sodales turpis. Duis porttitor nulla vel facilisis egestas.</p>\r\n\r\n<p>Integer ipsum elit, rutrum sed ullamcorper non, dapibus ut&nbsp;leo. Vestibulum in&nbsp;lorem fringilla, fermentum elit eu, vehicula mi. Ut&nbsp;lobortis tincidunt mattis. Nullam placerat magna vitae odio imperdiet, ac&nbsp;mattis elit tincidunt. Fusce porttitor non leo a&nbsp;aliquet. Sed vel quam ut&nbsp;mi&nbsp;mattis tincidunt. Maecenas vehicula nibh non elit condimentum, sed malesuada lectus dapibus. Suspendisse sed est eget massa auctor laoreet. Nulla eu&nbsp;eleifend velit. Integer eget magna enim. Class aptent taciti sociosqu ad&nbsp;litora torquent per conubia nostra, per inceptos himenaeos. Nunc vitae tortor at&nbsp;augue volutpat auctor et&nbsp;eget arcu. Ut&nbsp;rutrum orci ac&nbsp;est bibendum aliquam. Aliquam risus purus, rutrum at&nbsp;commodo in, posuere quis neque. Proin mattis libero id&nbsp;justo sodales fringilla. Nam elementum augue ut&nbsp;mauris feugiat, ut&nbsp;elementum augue mollis.</p>'),
        (7, 0, 1, 9, 16, 'simplePage', 'simplePage', 0, 0, '2013-11-15 23:19:35', '2013-11-10 13:03:21', 1, 'Статьи', 1, 1, 'children-list.html', '/%D0%A1%D1%82%D0%B0%D1%82%D1%8C%D0%B8', '', 'weekly', 0.5, NULL, '', '', '', '', ''),
        (8, 7, 2, 14, 15, 'simplePage', 'simplePage', 0, 0, '2013-11-15 23:20:42', '2013-11-10 13:04:48', 1, 'Как трудно быть бурлаком', 1, 1, 'page-with-comments.html', '/%D0%A1%D1%82%D0%B0%D1%82%D1%8C%D0%B8/%D0%9A%D0%B0%D0%BA-%D1%82%D1%80%D1%83%D0%B4%D0%BD%D0%BE-%D0%B1%D1%8B%D1%82%D1%8C-%D0%B1%D1%83%D1%80%D0%BB%D0%B0%D0%BA%D0%BE%D0%BC', '', NULL, NULL, NULL, '', '', '', '', '<p>Cum sociis natoque penatibus et&nbsp;magnis dis parturient montes, nascetur ridiculus mus. Aliquam nec tempor ipsum, ut&nbsp;posuere lectus. Aliquam pretium gravida dolor eu&nbsp;aliquet. Pellentesque nec justo nunc. Sed tempus metus quis dolor blandit, eget tempor nisl ultricies. Integer varius porta laoreet. Etiam a&nbsp;placerat eros. Pellentesque pharetra, sem placerat pharetra laoreet, odio nibh facilisis eros, ut&nbsp;venenatis mauris enim ut&nbsp;tortor. Proin dictum ipsum mi, a&nbsp;luctus justo hendrerit a.</p>\r\n\r\n<p>Donec lacinia, eros et&nbsp;auctor placerat, tortor velit mollis metus, eu&nbsp;suscipit nibh felis nec massa. Integer lorem diam, auctor sit amet lorem at, varius scelerisque lectus. Proin porta enim at&nbsp;sem vehicula dignissim. Aliquam lobortis tincidunt venenatis. Duis eu&nbsp;tortor ac&nbsp;est sodales laoreet. Suspendisse sodales nulla facilisis, volutpat sapien ut, dictum enim. Sed tincidunt suscipit libero nec ultrices. Phasellus aliquam, lorem in&nbsp;convallis scelerisque, quam ante consectetur magna, at&nbsp;cursus orci nunc ac&nbsp;est. Fusce euismod erat a&nbsp;imperdiet viverra. Fusce lacinia porttitor laoreet. Aliquam placerat, dui ut&nbsp;bibendum adipiscing, mi&nbsp;turpis aliquet lorem, in&nbsp;tincidunt tortor mi&nbsp;et&nbsp;lorem. Vivamus sed condimentum justo. Nullam consequat tortor vel est tincidunt tincidunt. Nulla facilisi. Mauris ac&nbsp;ornare magna, vel elementum mauris.</p>\r\n\r\n<p>Fusce at&nbsp;egestas felis. Duis a&nbsp;urna vehicula sem imperdiet pellentesque. Mauris nibh metus, dictum a&nbsp;tellus eu, pretium ornare est. Fusce a&nbsp;erat vitae dui ultrices elementum eget non mi. Quisque vitae nulla dignissim, lobortis purus at, fermentum turpis. Praesent eget erat at&nbsp;mauris pharetra sodales. Cras a&nbsp;orci elementum, blandit velit eu, aliquet tellus. Phasellus tincidunt id&nbsp;risus ut&nbsp;malesuada. Nulla eget placerat augue. Donec velit purus, porta ac&nbsp;nulla sed, blandit accumsan metus. Cras tincidunt sollicitudin consequat. Donec sagittis faucibus dui, ac&nbsp;mollis dolor malesuada id. Aliquam imperdiet tellus et&nbsp;dictum ullamcorper. Curabitur ac&nbsp;convallis mauris. Vivamus sit amet ante sit amet tellus auctor molestie sed id&nbsp;nisl.</p>'),
        (9, 7, 2, 12, 13, 'simplePage', 'simplePage', 0, 0, '2013-11-15 23:20:32', '2013-11-10 13:05:32', 1, 'Методология раскрашивания листа бумаги', 1, 1, 'page-with-comments.html', '/%D0%A1%D1%82%D0%B0%D1%82%D1%8C%D0%B8/%D0%9C%D0%B5%D1%82%D0%BE%D0%B4%D0%BE%D0%BB%D0%BE%D0%B3%D0%B8%D1%8F-%D1%80%D0%B0%D1%81%D0%BA%D1%80%D0%B0%D1%88%D0%B8%D0%B2%D0%B0%D0%BD%D0%B8%D1%8F-%D0%BB%D0%B8%D1%81%D1%82%D0%B0-%D0%B1%D1%83%D0%BC%D0%B0%D0%B3%D0%B8', '', NULL, NULL, NULL, '', '', '', '', '<p>Fusce at&nbsp;egestas felis. Duis a&nbsp;urna vehicula sem imperdiet pellentesque. Mauris nibh metus, dictum a&nbsp;tellus eu, pretium ornare est. Fusce a&nbsp;erat vitae dui ultrices elementum eget non mi. Quisque vitae nulla dignissim, lobortis purus at, fermentum turpis. Praesent eget erat at&nbsp;mauris pharetra sodales. Cras a&nbsp;orci elementum, blandit velit eu, aliquet tellus. Phasellus tincidunt id&nbsp;risus ut&nbsp;malesuada. Nulla eget placerat augue. Donec velit purus, porta ac&nbsp;nulla sed, blandit accumsan metus. Cras tincidunt sollicitudin consequat. Donec sagittis faucibus dui, ac&nbsp;mollis dolor malesuada id. Aliquam imperdiet tellus et&nbsp;dictum ullamcorper. Curabitur ac&nbsp;convallis mauris. Vivamus sit amet ante sit amet tellus auctor molestie sed id&nbsp;nisl.</p>\r\n\r\n<p>Duis at&nbsp;dictum quam. Cras tempus tincidunt neque eget feugiat. Donec molestie tortor dui, ut&nbsp;porta orci rutrum a.&nbsp;Praesent convallis ante et&nbsp;magna molestie accumsan. Integer nec dui at&nbsp;mauris ultrices aliquet. Etiam rhoncus laoreet augue eu&nbsp;commodo. Aliquam id&nbsp;sodales mi, vitae cursus elit. Curabitur porttitor commodo semper. Duis porttitor venenatis libero, eu&nbsp;volutpat purus vulputate sed. Quisque ut&nbsp;neque purus. Quisque ut&nbsp;libero a&nbsp;leo egestas porttitor nec sed purus. Nulla ac&nbsp;ligula volutpat, imperdiet libero non, gravida libero.</p>'),
        (10, 7, 2, 10, 11, 'simplePage', 'simplePage', 0, 0, '2013-11-15 23:20:22', '2013-11-10 13:06:42', 1, 'Куда катится мир?', 1, 1, 'page-with-comments.html', '/%D0%A1%D1%82%D0%B0%D1%82%D1%8C%D0%B8/%D0%9A%D1%83%D0%B4%D0%B0-%D0%BA%D0%B0%D1%82%D0%B8%D1%82%D1%81%D1%8F-%D0%BC%D0%B8%D1%80', '', NULL, NULL, NULL, '', '', '', '', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean adipiscing turpis ac&nbsp;eros dictum, sit amet mollis orci porttitor. Mauris a&nbsp;neque eget erat ultrices feugiat non sed ante. Nunc metus magna, luctus eget leo sed, sodales convallis dui. Etiam molestie, nisi in&nbsp;vulputate cursus, quam tellus tempus ipsum, ac&nbsp;elementum orci magna sit amet massa. Aenean a&nbsp;leo ligula. Integer augue sem, lacinia eget urna sed, fermentum condimentum turpis. Nunc ultricies, dolor quis pellentesque tempus, justo leo facilisis turpis, congue commodo erat velit vestibulum dui. Maecenas eleifend, nulla sed eleifend laoreet, tortor velit rhoncus velit, sit amet imperdiet elit orci non sem. Morbi non nisl neque. Phasellus a&nbsp;risus eu&nbsp;justo lacinia adipiscing. Phasellus nec scelerisque mauris, in&nbsp;tristique risus. Donec nec leo imperdiet, rhoncus mauris non, pellentesque velit. Phasellus auctor egestas mi&nbsp;a&nbsp;ullamcorper. Ut&nbsp;eleifend, nisl vitae ultrices congue, metus mi&nbsp;commodo libero, et&nbsp;lobortis libero enim a&nbsp;odio.</p>\r\n\r\n<p>Cum sociis natoque penatibus et&nbsp;magnis dis parturient montes, nascetur ridiculus mus. Aliquam nec tempor ipsum, ut&nbsp;posuere lectus. Aliquam pretium gravida dolor eu&nbsp;aliquet. Pellentesque nec justo nunc. Sed tempus metus quis dolor blandit, eget tempor nisl ultricies. Integer varius porta laoreet. Etiam a&nbsp;placerat eros. Pellentesque pharetra, sem placerat pharetra laoreet, odio nibh facilisis eros, ut&nbsp;venenatis mauris enim ut&nbsp;tortor. Proin dictum ipsum mi, a&nbsp;luctus justo hendrerit a.</p>'),
        (11, 0, 1, 17, 18, 'mainModule', 'simplePage', 0, 0, '2013-11-15 23:22:50', '2013-11-10 13:14:21', 1, 'Поиск по сайту', 0, 0, NULL, '/%D0%9F%D0%BE%D0%B8%D1%81%D0%BA-%D0%BF%D0%BE-%D1%81%D0%B0%D0%B9%D1%82%D1%83', NULL, NULL, NULL, 'search', NULL, NULL, NULL, NULL, NULL),
        (14, 0, 1, 19, 20, 'mainModule', 'simplePage', 0, 0, '2013-11-15 23:08:49', '2013-11-15 23:08:39', 1, 'Обратная связь', 0, 0, NULL, '/%D0%9E%D0%B1%D1%80%D0%B0%D1%82%D0%BD%D0%B0%D1%8F-%D1%81%D0%B2%D1%8F%D0%B7%D1%8C', NULL, NULL, NULL, 'feedback', NULL, NULL, NULL, NULL, NULL),
        (15, 0, 1, 25, 26, 'mainModule', 'simplePage', 0, 0, '2013-11-15 23:24:23', '2013-11-15 23:12:05', 1, 'Капча', 0, 0, NULL, '/%D0%9A%D0%B0%D0%BF%D1%87%D0%B0', NULL, NULL, NULL, 'captcha', NULL, NULL, NULL, NULL, NULL),
        (16, 0, 1, 27, 28, 'mainModule', 'simplePage', 0, 0, '2013-11-15 23:24:37', '2013-11-15 23:14:12', 1, 'Комментарии', 0, 0, NULL, '/%D0%9A%D0%BE%D0%BC%D0%BC%D0%B5%D0%BD%D1%82%D0%B0%D1%80%D0%B8%D0%B8', NULL, NULL, NULL, 'comments', NULL, NULL, NULL, NULL, NULL);


EXTENDEDINSTALLATIONSTRING;


}


/**
 * get localozation object
 */

function getLanguage($name) {

    $lf = APPLICATION . "languages/{$name}/install.php";
    if (!file_exists($lf)) {

        throw new installException(
            "Language error",
                "Language file $lf is not exists"
        );

    }

    return (object) require $lf;

}


/**
 * chech php version
 */

function checkPhpVersion() {
    return !((float) phpversion() < 5.2);
}


/**
 * check writable permission for target path
 */

function checkPath($path, $isDir = true) {

    return (($isDir ? is_dir($path)
        : file_exists($path)) and is_writable($path));

}


/**
 * installation exception object
 */

class installException extends Exception {


    private $report = array(

        "title" => "Untitled exception",
        "message" => ""

    );

    public function __construct($title, $message) {

        $this->report['title'] = $title;
        $this->report['message'] = $message;

    }

    public function getReport() {
        return $this->report;
    }


}


/**
 * view and language imitation for controllers
 */

class pseudoLanguage {

    public function __get($key) {
        return "";
    }

}

abstract class view {
    public static $language = null;
    public static function init() {
        self::$language = new pseudoLanguage();
    }
}

view::init();


/**
 * node class for controllers
 */

abstract class node {

    protected static $objects = array();

    public static function load($class) {


        if (!class_exists($class)) {

            throw new installException(
                "Node initialization class error",
                    "Class $class not found"
            );

        }

        if (!isset(self::$objects[$class])) {
            self::$objects[$class] = new $class;
        }


    }

    public static function call($key) {


        if (!isset(self::$objects[$key])) {

            throw new installException(
                "Node call to object error",
                    "Object $key not found inside"
            );

        }

        return self::$objects[$key];


    }

    public static function loadClass($path, $className) {


        if (isset(self::$objects[$className])) {
            return;
        }

        if (!file_exists($path)) {

            throw new installException(
                "Node load file error",
                    "File $path not exists"
            );

        }

        if (is_dir($path)) {

            throw new installException(
                "Node load file error",
                    "File $path is directory"
            );

        }

        require_once $path;
        self::load($className);


    }

    public static function loadController($path, $controllerName) {


        self::loadClass($path, $controllerName);
        if (!(self::call($controllerName) instanceof baseController)) {

            throw new installException(
                "Node load controller error",
                    "Class $controllerName not instance of baseController"
            );

        }

        $controller = self::call($controllerName);
        $controller->setPermissions();


    }

}


/**
 * database wrapper
 */

abstract class db {


    private static $mysqli = null;


    public static function connect($host, $user, $password, $name, $port) {


        self::$mysqli = @ new mysqli(
            $host, $user, $password, $name, (int) $port
        );

        if (self::$mysqli->connect_errno) {

            $_SESSION['ins']['report'][] = self::$mysqli->connect_errno
                . ": " . self::$mysqli->connect_error;

            $_SESSION['ins']['errors'] = true;

        }


    }


    public static function setCharset($charset) {


        if (self::$mysqli === null) {
            return;
        }

        @ self::$mysqli->set_charset($charset);

        if (self::$mysqli->errno) {

            $_SESSION['ins']['report'][] = self::$mysqli->errno
                . ": " . self::$mysqli->error;

            $_SESSION['ins']['errors'] = true;

        }


    }


    public static function query($queryString) {


        if (self::$mysqli === null) {
            return;
        }

        @ self::$mysqli->multi_query($queryString);
        if (self::$mysqli->errno) {

            $_SESSION['ins']['report'][] = self::$mysqli->errno
                . ": " . self::$mysqli->error;

            $_SESSION['ins']['errors'] = true;

        } else {

            do {

                if ($res = self::$mysqli->store_result()) {
                    while ($row = $res->fetch_assoc()) {
                        unset($row);
                    }
                    $res->free();
                }

            } while (
                self::$mysqli->more_results()
                    && self::$mysqli->next_result()
            );

        }

    }


}


/**
 * WARNING! origin PHP function glob() maybe returned FALSE value!
 * but i'm always expected array!
 */

function mainGlob($pattern, $flags = 0) {

    if (!$result = glob($pattern, $flags)) {
        $result = array();
    }

    return $result;

}


/**
 * recursive find targets with pattern mask
 */

function globRecursive($path, $mask = "*") {


    $items = mainGlob($path . $mask);
    $dirs  = mainGlob($path . "*", GLOB_ONLYDIR | GLOB_NOSORT);

    foreach ($dirs as $dir) {
        $items = array_merge($items, globRecursive($dir . "/", $mask));
    }

    return $items;


}


/**
 * recursive find all controllers
 */

function getAllControllers() {


    $controllers = array();
    $existsTargets = array();


    /**
     * get from modules,
     * get from admin module if need get all controllers
     */

    $existsTargets = globRecursive(APPLICATION . "modules/", "*.php");

    $existsTargets = array_merge(
        $existsTargets, globRecursive(APPLICATION . "admin/", "*.php")
    );

    require_once APPLICATION . "core/baseController.php";
    foreach ($existsTargets as $item) {

        $name = basename($item, ".php");
        node::loadController($item, $name);
        array_push($controllers, node::call($name));

    }

    return $controllers;


}


/**
 * refresh reset redirection function
 */

function refresh() {

    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /");

    exit();

}


/**
 * back pre routing
 */

if (array_key_exists("errors", $_SESSION['ins'])
        and isset($_POST['prev']) and $_SESSION['ins']['step'] > 1) {

    $_SESSION['ins']['report'] = array();
    $_SESSION['ins']['step'] -= 1;

    refresh();

}


/**
 * installation process of Deep-CMS
 */

try {


    require_once APPLICATION . "core/filter.php";


    $_config  = getConfig();
    $language = getLanguage($_config->site->default_language);


    $layout = "install.html";


    switch ($_SESSION['ins']['step']) {


        case 4:


            /**
             * save configuration into file,
             * reset all session variables
             */

            saveConfigIntoFile($_config);

            $_SESSION['ins']['report'] = array();
            $_SESSION['ins']['step'] = 4;
            $_SESSION['ins']['errors'] = false;


        break;


        case 3:


            if (isset($_POST['next'])) {


                /**
                 * save configuration
                 */

                $_config->site->protocol = $_SESSION['ins']['settings']['protocol'];
                $_config->site->domain   = $_SESSION['ins']['settings']['domain'];

                $_SESSION['ins']['settings']['debugmode']
                    = array_key_exists("debugmode", $_POST);

                $_config->system->debug_mode
                    = $_SESSION['ins']['settings']['debugmode'];

                setConfig($_config);


                /**
                 * check post data fragmentation
                 */

                $required = array("rootlogin", "rootpassword");

                foreach ($required as $key) {

                    if (!array_key_exists($key, $_POST)) {

                        throw new installException(
                            $language->error,
                                $language->data_not_enough
                        );

                    }

                }


                /**
                 * prepare login
                 */

                $rootlogin = filter::input($_POST['rootlogin'])
                        ->lettersOnly()->getData();

                $_SESSION['ins']['settings']['rootlogin'] = $rootlogin;
                if (!$rootlogin) {
                    $_SESSION['ins']['report'][] = $language->user_login_invalid;
                    $_SESSION['ins']['errors'] = true;
                }


                /**
                 * prepare password
                 */

                $rootpassword = trim((string) $_POST['rootpassword']);
                $_SESSION['ins']['settings']['rootpassword'] = $rootpassword;

                if (!$rootpassword) {

                    $_SESSION['ins']['errors'] = true;
                    $_SESSION['ins']['report'][]
                        = $language->install_root_password_is_empty;

                }

                $rootpassword = md5(md5(md5($rootpassword)));
                $roothash = md5(md5(md5(
                    "0{$rootlogin}{$rootpassword}00support@deep-cms.ru"
                )));


                /**
                 * connect to DB
                 */

                if (!$_SESSION['ins']['errors']) {

                    db::connect(

                        $_config->db->host,
                        $_config->db->user,
                        $_config->db->password,
                        $_config->db->name,
                        $_config->db->port

                    );

                }

                if (!$_SESSION['ins']['errors']) {
                    db::setCharset($_config->db->connection_charset);
                }


                /**
                 * update root password and hash
                 */

                if (!$_SESSION['ins']['errors']) {

                    db::query(

                        "UPDATE {$_config->db->prefix}users
                            SET login = '{$rootlogin}',
                                password = '{$rootpassword}',
                                    hash = '{$roothash}'
                                        WHERE id = 0"

                    );

                }


                /**
                 * get permissions for root
                 */

                if (!$_SESSION['ins']['errors']) {


                    /**
                     * get all permissions from controllers
                     */

                    $controllersPermissions = array();
                    $controllers = getAllControllers();

                    foreach ($controllers as $controller) {

                        foreach ($controller->getPermissions() as $current) {

                            $check = in_array(
                                $current['permission'],
                                    $controllersPermissions
                            );

                            if (!$check) {

                                array_push(
                                    $controllersPermissions,
                                        $current['permission']
                                );

                            }

                        }

                    }

                }


                /**
                 * truncate group permissions
                 */

                if (!$_SESSION['ins']['errors']) {

                    db::query(
                        "TRUNCATE TABLE
                            {$_config->db->prefix}group_permissions"
                    );

                }


                /**
                 * truncate permissions
                 */

                if (!$_SESSION['ins']['errors']) {

                    db::query(
                        "TRUNCATE TABLE
                            {$_config->db->prefix}permissions"
                    );

                }


                /**
                 * insert new list of permissions
                 */

                if (!$_SESSION['ins']['errors']) {

                    $permissionValues = "('"
                        . join("'), ('", $controllersPermissions) . "')";

                    db::query(
                        "INSERT INTO {$_config->db->prefix}permissions
                            (name) VALUES {$permissionValues}"
                    );

                }


                /**
                 * insert permissions for root
                 */

                if (!$_SESSION['ins']['errors']) {

                    db::query(

                        "INSERT INTO {$_config->db->prefix}group_permissions
                            (group_id,permission_id)
                                SELECT (0) group_id, id
                                    FROM {$_config->db->prefix}permissions"

                    );

                }


                /**
                 * post routing
                 */

                if (!$_SESSION['ins']['errors']) {
                    $_SESSION['ins']['report'] = array();
                    $_SESSION['ins']['step'] += 1;
                }

                refresh();

            }

            $_SESSION['ins']['errors'] = false;
            $_SESSION['ins']['step'] = 3;

            if (!array_key_exists("settings", $_SESSION['ins'])) {

                $port = $_SERVER['SERVER_PORT'];
                $port = ($port != 80 and $port != 443) ? ":{$port}" : "";

                $_SESSION['ins']['settings'] = array(

                    "protocol" => stristr(
                        $_SERVER['SERVER_PROTOCOL'], "https"
                    ) ? "https" : "http",

                    "domain"        => $_SERVER['SERVER_NAME'] . $port,
                    "rootlogin"     => "root",
                    "rootpassword"  => "",
                    "debugmode"     => false

                );

            }

        break;

        case 2:

            if (isset($_POST['next'])) {

                $_SESSION['ins']['report'] = array();


                /**
                 * check post data fragmentation
                 */

                $required = array(
                    "host", "port", /*"prefix",*/ "name", "user", "password"
                );

                foreach ($required as $key) {

                    if (!array_key_exists($key, $_POST)) {

                        throw new installException(
                            $language->error,
                                $language->data_not_enough
                        );

                    }

                }


                /**
                 * get required values
                 */

                $host   = trim(strip_tags((string) $_POST['host']));
                $port   = trim(strip_tags((string) $_POST['port']));

                $prefix = ""; //preg_replace("/[^_0-9a-z]+/i", "", (string) $_POST['prefix']);

                $name   = trim(strip_tags((string) $_POST['name']));
                $user   = trim(strip_tags((string) $_POST['user']));
                $pass   = (string) $_POST['password'];


                /**
                 * check required values
                 */


                $_SESSION['ins']['db']['host'] = $host;
                if (!$host) {

                    $_SESSION['ins']['errors'] = true;
                    $_SESSION['ins']['report'][]
                        = $language->install_db_host_is_empty;

                }


                $_SESSION['ins']['db']['port'] = $port;
                if (!$port or !preg_match("/^[0-9]+$/", $port)
                        or $port > 65535) {

                    $_SESSION['ins']['errors'] = true;
                    $_SESSION['ins']['report'][]
                        = $language->install_db_port_is_broken;

                }


                $_SESSION['ins']['db']['name'] = $name;
                if (!$name) {

                    $_SESSION['ins']['errors'] = true;
                    $_SESSION['ins']['report'][]
                        = $language->install_db_name_is_empty;

                }


                $_SESSION['ins']['db']['user'] = $user;
                if (!$user) {

                    $_SESSION['ins']['errors'] = true;
                    $_SESSION['ins']['report'][]
                        = $language->install_db_user_is_empty;

                }

                $_SESSION['ins']['db']['password'] = $pass;
                $_SESSION['ins']['db']['prefix'] = $prefix;

                $_SESSION['ins']['db']['addextended']
                    = array_key_exists("addextended", $_POST);


                /**
                 * connect to DB
                 */

                if (!$_SESSION['ins']['errors']) {
                    db::connect($host, $user, $pass, $name, $port);
                }

                if (!$_SESSION['ins']['errors']) {
                    db::setCharset($_config->db->connection_charset);
                }


                /**
                 * database installation
                 */

                if (!$_SESSION['ins']['errors']) {
                    db::query(getInstallationQueryString($prefix));
                }


                /**
                 * add extended demo data
                 */

                if (!$_SESSION['ins']['errors'] and $_SESSION['ins']['db']['addextended']) {
                    db::query(getExtendedQueryString($prefix));
                }


                /**
                 * save database configuration
                 */

                $_config->db->host     = $_SESSION['ins']['db']['host'];
                $_config->db->port     = $_SESSION['ins']['db']['port'];
                $_config->db->name     = $_SESSION['ins']['db']['name'];
                $_config->db->user     = $_SESSION['ins']['db']['user'];
                $_config->db->password = $_SESSION['ins']['db']['password'];
                $_config->db->prefix   = $_SESSION['ins']['db']['prefix'];

                setConfig($_config);


                /**
                 * post routing
                 */

                if (!$_SESSION['ins']['errors']) {
                    $_SESSION['ins']['report'] = array();
                    $_SESSION['ins']['step'] += 1;
                }

                refresh();

            }


            $_SESSION['ins']['errors'] = false;
            $_SESSION['ins']['step'] = 2;


            if (!array_key_exists("db", $_SESSION['ins'])) {


                $_SESSION['ins']['db'] = array(

                    "host" => "localhost",
                    "port" => "3306",
                    "prefix" => "",
                    "name" => "",
                    "user" => "",
                    "password" => "",
                    "addextended" => true

                );


            }


        break;


        default:


            if (isset($_POST['next'])) {

                if (!$_SESSION['ins']['errors']) {
                    $_SESSION['ins']['report'] = array();
                    $_SESSION['ins']['step'] += 1;
                }

                refresh();

            }


            $_SESSION['ins']['errors'] = false;
            $_SESSION['ins']['step'] = 1;


            /**
             * check php version and php.ini settings
             */

            $currentPhpVersion = round((float) phpversion(), 2);
            if (!$checkPhpVersion = checkPhpVersion()) {
                $_SESSION['ins']['errors'] = true;
            }


            $myValue  = 8388608; // 8M
            $phpValue = ini_get("memory_limit");
            $currentMemoryLimit = $phpValue;

            $measures = substr($phpValue, 0 -1);
            switch ($measures) {

                case "G":
                    $up = pow(1024, 3);
                break;

                case "M":
                    $up = pow(1024, 2);
                break;

                case "K":
                    $up = 1024;
                break;

                default:
                    $up = 0;
                break;

            }

            if ($phpValue > 0) {

                if ($up > 0) {
                    $phpValue = substr($phpValue, 0, strlen($phpValue) - 1);
                }

                if (!$checkMemoryLimit = $myValue <= ($phpValue * $up)) {
                    $_SESSION['ins']['errors'] = true;
                }

            } else {
                $checkMemoryLimit   = true;
                $currentMemoryLimit = "unlimited";
            }


            if (!$fileUploads = !!ini_get("file_uploads")) {
                $_SESSION['ins']['errors'] = true;
            }


            $myValue  = 8388608; // 8M
            $phpValue = ini_get("upload_max_filesize");
            $currentUploadMaxFileSize = $phpValue;

            $measures = substr($phpValue, 0 -1);
            switch ($measures) {

                case "G":
                    $up = pow(1024, 3);
                break;

                case "M":
                    $up = pow(1024, 2);
                break;

                case "K":
                    $up = 1024;
                break;

                default:
                    $up = 0;
                break;

            }

            if ($phpValue > 0) {

                if ($up > 0) {
                    $phpValue = substr($phpValue, 0, strlen($phpValue) - 1);
                }

                if (!$uploadMaxFileSize = $myValue <= ($phpValue * $up)) {
                    $_SESSION['ins']['errors'] = true;
                }

            } else {
                $uploadMaxFileSize        = true;
                $currentUploadMaxFileSize = "unlimited";
            }


            $mqgpc = ini_get("magic_quotes_gpc");
            $checkMQGPCEnabled = (stristr($mqgpc, "On")
                or $mqgpc == 1 or $mqgpc === true);

            if ($checkMQGPCEnabled) {
                $_SESSION['ins']['errors'] = true;
            }


            /**
             * check php extensions and available classes
             */

            if (!$checkMysqli = class_exists("mysqli")) {
                $_SESSION['ins']['errors'] = true;
            }

            if (!$checkDOMImpl = class_exists("DOMImplementation")) {
                $_SESSION['ins']['errors'] = true;
            }

            if (!$checkDOMDoc = class_exists("DOMDocument")) {
                $_SESSION['ins']['errors'] = true;
            }

            if (!$checkGD = function_exists("imagecreatefromjpeg")) {
                $_SESSION['ins']['errors'] = true;
            }

            if (!$checkFilterVar = function_exists("filter_var")) {
                $_SESSION['ins']['errors'] = true;
            }

            // finfo and mime fix
            $checkFinfo = function_exists("finfo_open");
            $checkMime  = function_exists("mime_content_type");

            $checkFinfoOrMime = true;
            if (!$checkFinfo and !$checkMime) {

                $checkFinfoOrMime   = false;
                $_SESSION['ins']['errors'] = true;

            }


            /**
             * check writable permissions
             */

            $autorunAfterDir = APPLICATION . "autorun/after";
            if (!$checkAutorunAfterDir = checkPath($autorunAfterDir)) {
                $_SESSION['ins']['errors'] = true;
            }

            $autorunBeforeDir = APPLICATION . "autorun/before";
            if (!$checkAutorunBeforeDir = checkPath($autorunBeforeDir)) {
                $_SESSION['ins']['errors'] = true;
            }

            $cacheDir = APPLICATION . "cache";
            if (!$checkCacheDir = checkPath($cacheDir)) {
                $_SESSION['ins']['errors'] = true;
            }

            $configDir = APPLICATION . "config";
            if (!$checkConfigDir = checkPath($configDir)) {
                $_SESSION['ins']['errors'] = true;
            }

            $languagesDir = APPLICATION . "languages";
            if (!$checkLanguagesDir = checkPath($languagesDir)) {
                $_SESSION['ins']['errors'] = true;
            }

            $libraryDir = APPLICATION . "library";
            if (!$checkLibraryDir = checkPath($libraryDir)) {
                $_SESSION['ins']['errors'] = true;
            }

            $logsDir = APPLICATION . "logs";
            if (!$checkLogsDir = checkPath($logsDir)) {
                $_SESSION['ins']['errors'] = true;
            }

            $metadataDir = APPLICATION . "metadata";
            if (!$checkMetadataDir = checkPath($metadataDir)) {
                $_SESSION['ins']['errors'] = true;
            }

            $modulesDir = APPLICATION . "modules";
            if (!$checkModulesDir = checkPath($modulesDir)) {
                $_SESSION['ins']['errors'] = true;
            }

            $prototypesDir = APPLICATION . "prototypes";
            if (!$checkPrototypesDir = checkPath($prototypesDir)) {
                $_SESSION['ins']['errors'] = true;
            }

            $resourcesDir = APPLICATION . "resources";
            if (!$checkResourcesDir = checkPath($resourcesDir)) {
                $_SESSION['ins']['errors'] = true;
            }

            $uploadDir = PUBLIC_HTML . "upload";
            if (!$checkUploadDir = checkPath($uploadDir)) {
                $_SESSION['ins']['errors'] = true;
            }

        break;

    }


} catch (installException $e) {

    $exception = $e->getReport();
    $layout = "install-exception.html";

}


/**
 * show installation progress
 */

header("Content-Type: text/html; charset=utf-8");
require $layout;

if ($_SESSION['ins']['step'] == 4) {
    $_SESSION = array();
}



