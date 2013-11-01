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

if (!array_key_exists("report", $_SESSION)) {
    $_SESSION['report'] = array();
}

if (!array_key_exists("step", $_SESSION)) {
    $_SESSION['step'] = 1;
}

if (!array_key_exists("errors", $_SESSION)) {
    $_SESSION['errors'] = false;
}


/**
 * get configuration object
 */

function getConfig() {

    if (!array_key_exists("config", $_SESSION)) {
        $_SESSION['config'] = json_decode('{"site":{"default_keywords":"","default_description":"","check_unused_params":false,"default_language":"ru","default_timezone":"+04:00","theme":"default","domain":"build.deep","protocol":"http","admin_tools_link":"\/admin","admin_resources":"\/admin-resources\/","no_image":"no-image.png","no_avatar":"no-avatar.png"},"application":{"name":"Deep-CMS","version":"2.0.0","support_email":"support@deep-cms.ru"},"system":{"debug_mode":false,"cache_enabled":true,"write_log":true,"log_file_max_size":16384,"block_prefetch_requests":true,"default_output_context":"html","cookie_expires_time":"259200","session_name":"deepcms","max_group_priority_number":"10"},"cached_pages":[],"path":{"admin":"admin\/","autorun_after":"autorun\/after\/","autorun_before":"autorun\/before\/","cache":"cache\/","languages":"languages\/","library":"library\/","logs":"logs\/","metadata":"metadata\/","modules":"modules\/","resources":"resources\/","tmp":"tmp\/","upload_dir":"upload\/"},"layouts":{"admin":"layouts\/admin\/","system":"layouts\/system\/","themes":"layouts\/themes\/","parts":"parts\/","public":"public\/","protected":"protected\/","header":"parts\/header.html","footer":"parts\/footer.html","exception":"protected\/exception.html","debug":"layouts\/system\/debug.html","txt":"layouts\/system\/txt.html","json":"layouts\/system\/json.html","xml":"layouts\/system\/xml.html"},"output_contexts":[{"name":"html","enabled":true},{"name":"json","enabled":true},{"name":"xml","enabled":true},{"name":"txt","enabled":true}],"db":{"host":"localhost","port":3306,"prefix":"","name":"","user":"","password":"","connection_charset":"utf8"}}');
    }

    return $_SESSION['config'];

}


/**
 * save configuration object into session
 */

function setConfig($config) {
    $_SESSION['config'] = $config;
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
        "version": "2.0.0",


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
        "cache_enabled": true,


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

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;

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

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;


        DROP TABLE IF EXISTS {$prefix}group_permissions;
        CREATE TABLE {$prefix}group_permissions (

            group_id        BIGINT(20) NOT NULL,
            permission_id   BIGINT(20) NOT NULL

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;


        DROP TABLE IF EXISTS {$prefix}groups;
        CREATE TABLE {$prefix}groups (

            id         BIGINT(20) NOT NULL AUTO_INCREMENT,
            priority   BIGINT(20) NOT NULL,
            name       CHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,

            PRIMARY KEY (id)

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;

        INSERT INTO {$prefix}groups (id, priority, name) VALUES (1, 0, 'root');
        UPDATE {$prefix}groups SET id = 0 WHERE id = 1;


        DROP TABLE IF EXISTS {$prefix}images;
        CREATE TABLE {$prefix}images (

            id           BIGINT(20)  NOT NULL AUTO_INCREMENT,
            document_id  BIGINT(20)  NOT NULL,
            is_master    TINYINT(1)  NOT NULL DEFAULT '0',
            name         CHAR(255)     CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,

            PRIMARY KEY (id),

            KEY document_id (document_id),
            KEY is_master   (is_master)

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}documents;
        CREATE TABLE {$prefix}documents (

            id                  BIGINT(20)  NOT NULL AUTO_INCREMENT,
            parent_id           BIGINT(20)  NOT NULL,
            lvl                 TINYINT(3)  UNSIGNED NOT NULL,
            lk                  BIGINT(20)  UNSIGNED NOT NULL,
            rk                  BIGINT(20)  UNSIGNED NOT NULL,
            prototype           BIGINT(20)  NOT NULL,
            c_prototype         BIGINT(20)  NOT NULL,
            props_id            BIGINT(20)  NOT NULL DEFAULT '0',
            is_publish          TINYINT(1)  NOT NULL DEFAULT '0',
            in_sitemap          TINYINT(1)  NOT NULL DEFAULT '0',
            sort                BIGINT(20)  NOT NULL DEFAULT '0',
            page_alias          MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            permanent_redirect  MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            page_name           CHAR(255)   CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            page_h1             MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            page_title          MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            meta_keywords       MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            meta_description    MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            layout              CHAR(255)   CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            author              BIGINT(20)  NOT NULL,
            last_modified       DATETIME    NOT NULL,
            creation_date       DATETIME    NOT NULL,
            change_freq         CHAR(7)     CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            search_priority     DOUBLE      DEFAULT NULL,

            PRIMARY KEY (id),

            KEY parent_id  (parent_id),
            KEY lvl        (lvl),
            KEY lk         (lk),
            KEY rk         (rk),
            KEY prototype  (prototype),
            KEY props_id   (props_id),
            KEY is_publish (is_publish),
            KEY in_sitemap (in_sitemap),
            KEY sort       (sort),
            KEY author     (author)

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}document_features;
        CREATE TABLE {$prefix}document_features (

            document_id    BIGINT(20) NOT NULL,
            feature_id     BIGINT(20) NOT NULL,
            feature_value  MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

            KEY document_id (document_id),
            KEY feature_id  (feature_id)

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}features;
        CREATE TABLE {$prefix}features (

            id    BIGINT(20) NOT NULL AUTO_INCREMENT,
            name  CHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

            PRIMARY KEY (id)

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}prototypes;
        CREATE TABLE {$prefix}prototypes (

            id           BIGINT(20) NOT NULL AUTO_INCREMENT,
            type         TINYINT(2) NOT NULL,
            sys_name     CHAR(255)  CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            name         CHAR(255)  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            description  MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci,

            PRIMARY KEY (id)

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8  AUTO_INCREMENT = 10;

        INSERT INTO {$prefix}prototypes (id, type, sys_name, name, description) VALUES
            (10, 10, 'props_simple_pages', 'Обычные страницы', 'Прототип свойств обычных страниц сайта');



        DROP TABLE IF EXISTS {$prefix}field_types;
        CREATE TABLE {$prefix}field_types (

            prototype    BIGINT(20) NOT NULL,
            field_type   CHAR(32)   CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            editor       TINYINT(1) NOT NULL DEFAULT '0',
            name         CHAR(255)  CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            description  CHAR(255)  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            sort         BIGINT(20) NOT NULL DEFAULT '1',

            KEY prototype (prototype),
            KEY sort      (sort)

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;

        INSERT INTO {$prefix}field_types (prototype, field_type, editor, name, description, sort) VALUES
            (10, 'textarea', '1', 'page_text', 'Содержимое страницы', 1);



        DROP TABLE IF EXISTS {$prefix}props_simple_pages;
        CREATE TABLE {$prefix}props_simple_pages (

            id         BIGINT(20) NOT NULL AUTO_INCREMENT,
            page_text  LONGTEXT   CHARACTER SET utf8 COLLATE utf8_general_ci,

            PRIMARY KEY (id)

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}menu;
        CREATE TABLE {$prefix}menu (

            id          BIGINT(20) NOT NULL AUTO_INCREMENT,
            parent_id   BIGINT(20) NOT NULL DEFAULT '0',
            name        CHAR(255)  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

            PRIMARY KEY (id),

            KEY parent_id (parent_id)

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;



        DROP TABLE IF EXISTS {$prefix}menu_items;
        CREATE TABLE {$prefix}menu_items (

            menu_id        BIGINT(20) NOT NULL,
            document_id    BIGINT(20) NOT NULL,

            KEY menu_id     (menu_id),
            KEY document_id (document_id)

        ) ENGINE = MyISAM DEFAULT CHARSET = utf8;


        DROP PROCEDURE IF EXISTS get_breadcrumbs;
        CREATE DEFINER = CURRENT_USER PROCEDURE get_breadcrumbs(IN current_id BIGINT(20), show_home TINYINT(1))

            BEGIN


                DECLARE buff_id BIGINT(20);
                DECLARE buff_parent_id BIGINT(20);

                DECLARE done_search TINYINT(1);
                DECLARE current_depth BIGINT(20);

                SET done_search = 0;
                SET current_depth = 0;
                SET buff_id = NULL;


                CREATE TEMPORARY TABLE IF NOT EXISTS breadcrumbs (id BIGINT(20), depth BIGINT(20)) ENGINE = MEMORY;
                TRUNCATE TABLE breadcrumbs;


                findloop : WHILE done_search = 0 DO


                    SELECT id, parent_id INTO buff_id, buff_parent_id FROM {$prefix}documents WHERE id = current_id;


                    IF buff_id IS NULL THEN


                        IF show_home != 0 THEN

                            SELECT id, parent_id INTO buff_id, buff_parent_id FROM {$prefix}documents WHERE page_alias = '/';
                            IF buff_id IS NOT NULL THEN
                                INSERT INTO breadcrumbs (id, depth) VALUES(buff_id, current_depth);
                            END IF;

                        END IF;


                        LEAVE findloop;


                    END IF;


                    INSERT INTO breadcrumbs (id, depth) VALUES(buff_id, current_depth);


                    SET current_id = buff_parent_id;
                    SET current_depth = current_depth + 1;
                    SET buff_id = NULL;


                END WHILE findloop;


                SELECT

                    d.id,
                    d.parent_id,
                    d.page_name,
                    d.page_alias

                FROM breadcrumbs b
                INNER JOIN {$prefix}documents d ON (d.id = b.id AND d.is_publish = 1)
                ORDER BY b.depth DESC;

            END;


INSTALLATIONSTRING;


}


/**
 * get extended data installation query string
 */

function getExtendedQueryString($prefix = "") {


    return <<<EXTENDEDINSTALLATIONSTRING

        INSERT INTO documents (

            id,
            parent_id,
            prototype,
            c_prototype,
            props_id,
            is_publish,
            in_sitemap,
            sort,
            page_alias,
            permanent_redirect,
            page_name,
            page_h1,
            page_title,
            meta_keywords,
            meta_description,
            layout, author,
            last_modified,
            creation_date,
            change_freq,
            search_priority

        ) VALUES

        (1, 0, 10, 10, 1, 1, 1, 10, '/', '', 'Главная', '', '', '', '', 'page.html', 0, '2013-07-08 00:32:46', '2013-07-07 23:38:04', NULL, NULL),
        (2, 0, 10, 10, 2, 1, 1, 20, '/news', '', 'Новости', '', '', '', '', 'page.html', 0, '2013-07-07 23:47:03', '2013-07-07 23:38:44', NULL, NULL),
        (3, 2, 10, 10, 3, 1, 1, 0, '/news/one', '', 'Первая новость', '', '', '', '', 'page.html', 0, '2013-07-08 01:57:57', '2013-07-07 23:43:08', NULL, NULL),
        (4, 2, 10, 10, 4, 1, 1, 0, '/news/two', '', 'Вторая новость', '', '', '', '', 'page.html', 0, '2013-07-08 01:57:50', '2013-07-07 23:43:38', NULL, NULL),
        (5, 0, 10, 10, 5, 1, 1, 30, '/articles', '', 'Статьи', '', '', '', '', 'page.html', 0, '2013-07-07 23:45:19', '2013-07-07 23:45:19', NULL, NULL),
        (6, 0, 10, 10, 6, 1, 0, 10000, '/sitemap', '', 'Карта сайта', '', '', '', '', 'page.html', 0, '2013-07-07 23:46:40', '2013-07-07 23:46:40', NULL, NULL),
        (7, 5, 10, 10, 7, 1, 1, 0, '/articles/one', '', 'Статья один', '', '', '', '', 'page.html', 0, '2013-07-08 01:58:18', '2013-07-07 23:47:38', NULL, NULL),
        (8, 5, 10, 10, 8, 1, 1, 0, '/articles/two', '', 'Статья два', '', '', '', '', 'page.html', 0, '2013-07-08 01:58:10', '2013-07-07 23:48:04', NULL, NULL),
        (9, 7, 10, 10, 9, 1, 1, 0, '/articles/one/inner', '', 'Вложение в статью один', '', '', '', '', 'page.html', 0, '2013-07-08 01:58:26', '2013-07-07 23:50:43', NULL, NULL),
        (10, 0, 10, 10, 10, 1, 0, 100, 'http://www.google.ru/', '', 'www.google.ru', '', '', '', '', 'page.html', 0, '2013-07-07 23:53:10', '2013-07-07 23:52:55', NULL, NULL);


        INSERT INTO menu (id, parent_id, name)
            VALUES (1, 0, 'Верхнее меню'), (2, 0, 'Нижнее меню');


        INSERT INTO menu_items (menu_id, document_id)
            VALUES (2, 1), (1, 1), (2, 2), (1, 5), (2, 6), (2, 10);


        INSERT INTO props_simple_pages (id, page_text) VALUES
        (1, '<p>Текст на главной странице..</p>'),
        (2, '<p>Это раздел новостей..</p>'),
        (3, '<p>Текст первой новости</p>'),
        (4, '<p>Текст второй новости.</p>'),
        (5, '<p>Это раздел статей.</p>'),
        (6, '<p> </p>'),
        (7, '<p>Текст статьи один.</p>'),
        (8, '<p>Текст статьи два.</p>'),
        (9, '<p>Текст вложения в статью один. Вложения могут быть любой глубины. Нет никакого ограничения вложения одних документов в других, за исключением запрета вложения в самого себя и в родителей находящихся выше в ветке дерева.</p>'),
        (10, '<p> </p>');

EXTENDEDINSTALLATIONSTRING;


}


/**
 * get localozation object
 */

function getLanguage($name) {

    $lf = APPLICATION . "languages/{$name}/install.php";

    if (!file_exists($lf)) {
        throw new installException("Language error", "Language file $lf is not exists");
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
    return (($isDir ? is_dir($path) : file_exists($path)) and is_writable($path));
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
            throw new installException("Node initialization class error", "Class $class not found");
        }

        if (!isset(self::$objects[$class])) {
            self::$objects[$class] = new $class;
        }


    }

    public static function call($key) {


        if (!isset(self::$objects[$key])) {
            throw new installException("Node call to object error", "Object $key not found inside");
        }

        return self::$objects[$key];


    }

    public static function loadClass($path, $className) {


        if (isset(self::$objects[$className])) {
            return;
        }

        if (!file_exists($path)) {
            throw new installException("Node load file error", "File $path not exists");
        }

        if (is_dir($path)) {
            throw new installException("Node load file error", "File $path is directory");
        }


        require_once $path;
        self::load($className);


    }

    public static function loadController($path, $controllerName) {


        self::loadClass($path, $controllerName);

        if (!(self::call($controllerName) instanceof baseController)) {
            throw new installException("Node load controller error", "Class $controllerName not instance of baseController");
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
            $_SESSION['report'][] = self::$mysqli->connect_errno . ": " . self::$mysqli->connect_error;
            $_SESSION['errors'] = true;
        }


    }


    public static function setCharset($charset) {


        if (self::$mysqli === null) {
            return;
        }

        @ self::$mysqli->set_charset($charset);

        if (self::$mysqli->errno) {
            $_SESSION['report'][] = self::$mysqli->errno . ": " . self::$mysqli->error;
            $_SESSION['errors'] = true;
        }


    }


    public static function query($queryString) {


        if (self::$mysqli === null) {
            return;
        }


        @ self::$mysqli->multi_query($queryString);
        if (self::$mysqli->errno) {
            $_SESSION['report'][] = self::$mysqli->errno . ": " . self::$mysqli->error;
            $_SESSION['errors'] = true;
        } else {


            do {


                if ($res = self::$mysqli->store_result()) {
                    while ($row = $res->fetch_assoc()) {
                        unset($row);
                    }
                    $res->free();
                }


            } while (self::$mysqli->more_results() && self::$mysqli->next_result());


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
    $existsTargets = array_merge($existsTargets, globRecursive(APPLICATION . "admin/", "*.php"));


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

if (array_key_exists("errors", $_SESSION) and isset($_POST['prev']) and $_SESSION['step'] > 1) {

    $_SESSION['report'] = array();
    $_SESSION['step'] -= 1;

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


    switch ($_SESSION['step']) {


        case 4:


            /**
             * save configuration into file,
             * reset all session variables
             */

            saveConfigIntoFile($_config);

            $_SESSION['report'] = array();
            $_SESSION['step'] = 4;
            $_SESSION['errors'] = false;


        break;


        case 3:


            if (isset($_POST['next'])) {


                $_SESSION['report'] = array();


                /**
                 * save configuration
                 */

                $_config->site->protocol = $_SESSION['settings']['protocol'];
                $_config->site->domain   = $_SESSION['settings']['domain'];

                $_SESSION['settings']['debugmode'] = array_key_exists("debugmode", $_POST);
                $_config->system->debug_mode = $_SESSION['settings']['debugmode'];

                setConfig($_config);


                /**
                 * check post data fragmentation
                 */

                $required = array("rootlogin", "rootpassword");

                foreach ($required as $key) {

                    if (!array_key_exists($key, $_POST)) {
                        throw new installException($language->error, $language->data_not_enough);
                    }

                }


                /**
                 * prepare login
                 */

                $rootlogin = filter::input($_POST['rootlogin'])
                        ->lettersOnly()
                        ->getData();

                $_SESSION['settings']['rootlogin'] = $rootlogin;

                if (!$rootlogin) {
                    $_SESSION['report'][] = $language->user_login_invalid_format;
                    $_SESSION['errors'] = true;
                }


                /**
                 * prepare password
                 */

                $rootpassword = trim((string) $_POST['rootpassword']);
                $_SESSION['settings']['rootpassword'] = $rootpassword;

                if (!$rootpassword) {
                    $_SESSION['report'][] = $language->install_root_password_is_empty;
                    $_SESSION['errors'] = true;
                }

                $rootpassword = md5(md5(md5($rootpassword)));
                $roothash = md5(md5(md5("0{$rootlogin}{$rootpassword}00support@deep-cms.ru")));


                /**
                 * connect to DB
                 */

                if (!$_SESSION['errors']) {

                    db::connect(

                        $_config->db->host,
                        $_config->db->user,
                        $_config->db->password,
                        $_config->db->name,
                        $_config->db->port

                    );

                }

                if (!$_SESSION['errors']) {
                    db::setCharset($_config->db->connection_charset);
                }


                /**
                 * update root password and hash
                 */

                if (!$_SESSION['errors']) {

                    db::query("

                        UPDATE {$_config->db->prefix}users
                        SET login = '{$rootlogin}', password = '{$rootpassword}', hash = '{$roothash}'
                        WHERE id = 0

                    ");

                }


                /**
                 * get permissions for root
                 */

                if (!$_SESSION['errors']) {


                    /**
                     * get all permissions from controllers
                     */

                    $controllersPermissions = array();
                    $controllers = getAllControllers();

                    foreach ($controllers as $controller) {


                        foreach ($controller->getPermissions() as $current) {

                            if (!in_array($current['permission'], $controllersPermissions)) {
                                array_push($controllersPermissions, $current['permission']);
                            }

                        }


                    }


                }


                /**
                 * truncate group permissions
                 */

                if (!$_SESSION['errors']) {
                    db::query("TRUNCATE TABLE {$_config->db->prefix}group_permissions");
                }


                /**
                 * truncate permissions
                 */

                if (!$_SESSION['errors']) {
                    db::query("TRUNCATE TABLE {$_config->db->prefix}permissions");
                }


                /**
                 * insert new list of permissions
                 */

                if (!$_SESSION['errors']) {

                    $permissionValues = "('" . join("'), ('", $controllersPermissions) . "')";
                    db::query("INSERT INTO {$_config->db->prefix}permissions (name) VALUES {$permissionValues}");

                }


                /**
                 * insert permissions for root
                 */

                if (!$_SESSION['errors']) {

                    db::query("

                        INSERT INTO {$_config->db->prefix}group_permissions (group_id,permission_id)
                            SELECT (0) group_id, id FROM {$_config->db->prefix}permissions

                    ");

                }


                /**
                 * post routing
                 */

                if (!$_SESSION['errors']) {
                    $_SESSION['report'] = array();
                    $_SESSION['step'] += 1;
                }

                refresh();

            }


            $_SESSION['errors'] = false;
            $_SESSION['step'] = 3;


            if (!array_key_exists("settings", $_SESSION)) {


                $port = $_SERVER['SERVER_PORT'];
                $port = ($port != 80 and $port != 443) ? ":{$port}" : "";

                $_SESSION['settings'] = array(

                    "protocol"      => stristr($_SERVER['SERVER_PROTOCOL'], "https") ? "https" : "http",
                    "domain"        => $_SERVER['SERVER_NAME'] . $port,
                    "rootlogin"     => "root",
                    "rootpassword"  => "",
                    "debugmode"     => false

                );


            }


        break;


        case 2:


            if (isset($_POST['next'])) {


                $_SESSION['report'] = array();


                /**
                 * check post data fragmentation
                 */

                $required = array("host", "port", /*"prefix",*/ "name", "user", "password");
                foreach ($required as $key) {

                    if (!array_key_exists($key, $_POST)) {
                        throw new installException($language->error, $language->data_not_enough);
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


                $_SESSION['db']['host'] = $host;

                if (!$host) {
                    $_SESSION['report'][] = $language->install_db_host_is_empty;
                    $_SESSION['errors'] = true;
                }


                $_SESSION['db']['port'] = $port;

                if (!$port or !preg_match("/^[0-9]+$/", $port) or $port > 65535) {
                    $_SESSION['report'][] = $language->install_db_port_is_broken;
                    $_SESSION['errors'] = true;
                }


                $_SESSION['db']['name'] = $name;

                if (!$name) {
                    $_SESSION['report'][] = $language->install_db_name_is_empty;
                    $_SESSION['errors'] = true;
                }


                $_SESSION['db']['user'] = $user;

                if (!$user) {
                    $_SESSION['report'][] = $language->install_db_user_is_empty;
                    $_SESSION['errors'] = true;
                }

                $_SESSION['db']['password'] = $pass;
                $_SESSION['db']['prefix'] = $prefix;
                $_SESSION['db']['addextended'] = array_key_exists("addextended", $_POST);


                /**
                 * connect to DB
                 */

                if (!$_SESSION['errors']) {
                    db::connect($host, $user, $pass, $name, $port);
                }

                if (!$_SESSION['errors']) {
                    db::setCharset($_config->db->connection_charset);
                }


                /**
                 * check available for create procedures
                 */

                if (!$_SESSION['errors']) {

                    db::query("

                        SET @status = 0;

                        DROP PROCEDURE IF EXISTS check_avCP;
                        CREATE DEFINER = CURRENT_USER PROCEDURE check_avCP(INOUT status TINYINT(1))
                            READS SQL DATA
                            BEGIN
                                SET status = 1;
                            END;

                        CALL check_avCP(@status);
                        SELECT @status;

                    ");

                }


                /**
                 * delete check procedure
                 */

                if (!$_SESSION['errors']) {
                    db::query("DROP PROCEDURE IF EXISTS check_avCP;");
                }


                /**
                 * full database installation
                 */

                if (!$_SESSION['errors']) {
                    db::query(getInstallationQueryString($prefix));
                }


                /**
                 * add extended demo data
                 */

                if (!$_SESSION['errors'] and $_SESSION['db']['addextended']) {
                    db::query(getExtendedQueryString($prefix));
                }


                /**
                 * save database configuration
                 */

                $_config->db->host     = $_SESSION['db']['host'];
                $_config->db->port     = $_SESSION['db']['port'];
                $_config->db->name     = $_SESSION['db']['name'];
                $_config->db->user     = $_SESSION['db']['user'];
                $_config->db->password = $_SESSION['db']['password'];
                $_config->db->prefix   = $_SESSION['db']['prefix'];

                setConfig($_config);


                /**
                 * post routing
                 */

                if (!$_SESSION['errors']) {
                    $_SESSION['report'] = array();
                    $_SESSION['step'] += 1;
                }

                refresh();

            }


            $_SESSION['errors'] = false;
            $_SESSION['step'] = 2;


            if (!array_key_exists("db", $_SESSION)) {


                $_SESSION['db'] = array(

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

                if (!$_SESSION['errors']) {
                    $_SESSION['report'] = array();
                    $_SESSION['step'] += 1;
                }

                refresh();

            }


            $_SESSION['errors'] = false;
            $_SESSION['step'] = 1;


            /**
             * check php version and php.ini settings
             */

            $currentPhpVersion = round((float) phpversion(), 2);
            if (!$checkPhpVersion = checkPhpVersion()) {
                $_SESSION['errors'] = true;
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
                    $_SESSION['errors'] = true;
                }

            } else {
                $checkMemoryLimit   = true;
                $currentMemoryLimit = "unlimited";
            }


            if (!$fileUploads = !!ini_get("file_uploads")) {
                $_SESSION['errors'] = true;
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
                    $_SESSION['errors'] = true;
                }

            } else {
                $uploadMaxFileSize        = true;
                $currentUploadMaxFileSize = "unlimited";
            }


            $mqgpc = ini_get("magic_quotes_gpc");
            $checkMQGPCEnabled = (stristr($mqgpc, "On") or $mqgpc == 1 or $mqgpc === true);

            if ($checkMQGPCEnabled) {
                $_SESSION['errors'] = true;
            }


            /**
             * check php extensions and available classes
             */

            if (!$checkMysqli = class_exists("mysqli")) {
                $_SESSION['errors'] = true;
            }

            if (!$checkDOMImpl = class_exists("DOMImplementation")) {
                $_SESSION['errors'] = true;
            }

            if (!$checkDOMDoc = class_exists("DOMDocument")) {
                $_SESSION['errors'] = true;
            }

            if (!$checkGD = function_exists("imagecreatefromjpeg")) {
                $_SESSION['errors'] = true;
            }

            if (!$checkFilterVar = function_exists("filter_var")) {
                $_SESSION['errors'] = true;
            }

            // finfo and mime fix
            $checkFinfo = function_exists("finfo_open");
            $checkMime  = function_exists("mime_content_type");

            $checkFinfoOrMime = true;
            if (!$checkFinfo and !$checkMime) {

                $checkFinfoOrMime   = false;
                $_SESSION['errors'] = true;

            }


            /**
             * check writable permissions
             */

            $autorunAfterDir = APPLICATION . "autorun/after";
            if (!$checkAutorunAfterDir = checkPath($autorunAfterDir)) {
                $_SESSION['errors'] = true;
            }

            $autorunBeforeDir = APPLICATION . "autorun/before";
            if (!$checkAutorunBeforeDir = checkPath($autorunBeforeDir)) {
                $_SESSION['errors'] = true;
            }

            $cacheDir = APPLICATION . "cache";
            if (!$checkCacheDir = checkPath($cacheDir)) {
                $_SESSION['errors'] = true;
            }

            $configDir = APPLICATION . "config";
            if (!$checkConfigDir = checkPath($configDir)) {
                $_SESSION['errors'] = true;
            }

            $languagesDir = APPLICATION . "languages";
            if (!$checkLanguagesDir = checkPath($languagesDir)) {
                $_SESSION['errors'] = true;
            }

            $libraryDir = APPLICATION . "library";
            if (!$checkLibraryDir = checkPath($libraryDir)) {
                $_SESSION['errors'] = true;
            }

            $logsDir = APPLICATION . "logs";
            if (!$checkLogsDir = checkPath($logsDir)) {
                $_SESSION['errors'] = true;
            }

            $modulesDir = APPLICATION . "modules";
            if (!$checkModulesDir = checkPath($modulesDir)) {
                $_SESSION['errors'] = true;
            }

            $resourcesDir = APPLICATION . "resources";
            if (!$checkResourcesDir = checkPath($resourcesDir)) {
                $_SESSION['errors'] = true;
            }

            $uploadDir = PUBLIC_HTML . "upload";
            if (!$checkUploadDir = checkPath($uploadDir)) {
                $_SESSION['errors'] = true;
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



