<?php


/**
 * set main installation environment
 */

ini_set('display_errors', 'On');
ini_set('html_errors', 'On');
error_reporting(E_ALL | E_STRICT);
set_include_path('');


/**
 * installation classes and functions
 */

abstract class storage {

    protected static $storageKey = '__installation_storage';
    public static function init() {
        session_name('deepcmsinstall');
        @ session_start();
        if (!isset($_SESSION[self::$storageKey])) {
            self::clear();
        }
    }
    public static function write($key, $data) {
        $_SESSION[self::$storageKey][$key] = $data;
    }
    public static function remove($key) {
        if (isset($_SESSION[self::$storageKey][$key])) {
            unset($_SESSION[self::$storageKey][$key]);
        }
    }
    public static function read($key) {
        return self::exists($key) ? $_SESSION[self::$storageKey][$key] : null;
    }
    public static function shift($key) {
        $data = self::read($key);
        self::remove($key);
        return $data;
    }
    public static function exists($key) {
        return array_key_exists($key, $_SESSION[self::$storageKey]);
    }
    public static function clear() {
        $_SESSION = array();
        $_SESSION[self::$storageKey] = array();
    }

}

abstract class request {

    public static function isPost() {
        return (sizeof($_POST) > 0);
    }
    public static function getPostParam($key) {
        return (array_key_exists($key, $_POST)) ? $_POST[$key] : null;
    }
    public static function refresh() {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: /');
        exit();
    }

}

class installException extends Exception {

    private $report = array('title' => 'Untitled exception', 'message' => '');
    public function __construct($title, $message) {
        $this->report['title'] = $title;
        $this->report['message'] = $message;
    }
    public function getReport() {
        return $this->report;
    }

}

class pseudoLanguage {

    public function __get($key) {
        return '';
    }

}

abstract class view {

    public static $language = null;
    public static function init() {
        self::$language = new pseudoLanguage();
    }

}

abstract class reporter {

    private static $errorStatus = false;
    private static $newReport = array();
    private static $oldReport = array();

    public static function init() {
        if (!self::$oldReport = storage::read('report')) {
            self::$oldReport = array();
        }
    }
    public static function isError() {
        return self::$errorStatus;
    }
    public static function getReport() {
        storage::write('report', array());
        return self::$oldReport;
    }
    public static function setErrorStatus() {
        self::$errorStatus = true;
    }
    public static function addReportMessage($message) {
        self::$newReport[] = $message;
        storage::write('report', self::$newReport);
    }

}

abstract class db {

    private static $mysqli = null;
    public static function connect($host, $user, $password, $name, $port) {
        self::$mysqli = new mysqli($host, $user, $password, $name, (int) $port);
        if (self::$mysqli->connect_errno) {
            reporter::setErrorStatus();
            reporter::addReportMessage(
                self::$mysqli->connect_errno . ': ' . self::$mysqli->connect_error
            );
        }
    }
    public static function setCharset($charset) {
        if (self::$mysqli === null) {
            return;
        }
        self::$mysqli->set_charset($charset);
        if (self::$mysqli->errno) {
            reporter::setErrorStatus();
            reporter::addReportMessage(
                self::$mysqli->connect_errno . ': ' . self::$mysqli->connect_error
            );
        }
    }
    public static function query($queryString) {
        if (self::$mysqli === null) {
            return;
        }
        self::$mysqli->multi_query($queryString);
        if (self::$mysqli->errno) {
            reporter::setErrorStatus();
            reporter::addReportMessage(
                self::$mysqli->connect_errno . ': ' . self::$mysqli->connect_error
            );
        } else {
            do {
                if ($res = self::$mysqli->store_result()) {
                    while ($row = $res->fetch_assoc()) {
                        unset($row);
                    }
                    $res->free();
                }
            } while (
                self::$mysqli->more_results() && self::$mysqli->next_result()
            );
        }
    }
    public static function escapeString($str) {
        return self::$mysqli->real_escape_string($str);
    }

}

abstract class node {

    protected static $objects = array();
    public static function load($class) {
        if (!class_exists($class)) {
            throw new installException(
                'Node initialization class error', 'Class ' . $class . ' not found'
            );
        }
        if (!isset(self::$objects[$class])) {
            self::$objects[$class] = new $class;
        }
    }
    public static function call($key) {
        if (!isset(self::$objects[$key])) {
            throw new installException(
                'Node call to object error', 'Object ' . $key . ' not found inside'
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
                'Node load file error', 'File ' . $path . ' not exists'
            );
        }
        if (is_dir($path)) {
            throw new installException(
                'Node load file error', 'File ' . $path . ' is directory'
            );
        }
        require_once $path;
        self::load($className);
    }
    public static function loadController($path, $controllerName) {
        self::loadClass($path, $controllerName);
        if (!(self::call($controllerName) instanceof baseController)) {
            throw new installException(
                'Node load controller error',
                'Class ' . $controllerName . ' not instance of baseController'
            );
        }
        $controller = self::call($controllerName);
        $controller->setPermissions();
    }
}

function getClientLanguages() {

    $acceptLangs = strtolower((string) $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $langPattern = '/([a-z]{2,}(?:-[a-z]{2,})?)((?:;q=(1|0\.[0-9]))?)/s';
    $clientLangs = array();
    preg_match_all($langPattern, $acceptLangs, $clientLangs);

    $clientLangs = array_combine($clientLangs[1], $clientLangs[3]);
    foreach ($clientLangs as $k => $v) {
        $clientLangs[$k] = $v ? $v : 1;
    }
    arsort($clientLangs, SORT_NUMERIC);
    return $clientLangs;

}

function installGlob($pattern, $flags = 0) {

    if (!$result = glob($pattern, $flags)) {
        $result = array();
    }
    return $result;

}

function installGlobRecursive($path, $mask = '*') {

    $items = installGlob($path . $mask);
    $dirs  = installGlob($path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
    foreach ($dirs as $dir) {
        $items = array_merge($items, installGlobRecursive($dir . '/', $mask));
    }
    return $items;

}

function getConfig() {

    $storedConfig = storage::read('config');
    if (!$storedConfig) {

        $storedConfig = json_decode('{
            "site":{
                "default_keywords":"",
                "default_description":"",
                "check_unused_params":false,
                "default_language":"",
                "default_timezone":"",
                "theme":"default",
                "domain":"",
                "protocol":"",
                "admin_tools_link":"\/admin",
                "admin_resources":"\/admin-resources\/"
            },
            "application":{
                "name":"Deep-CMS",
                "version":"2.203.108",
                "support_email":"support@deep-cms.ru",
                "sources_domain":"sources.deep-cms.ru"
            },
            "system":{
                "debug_mode":true,
                "cache_enabled":false,
                "write_log":true,
                "log_file_max_size":16384,
                "block_prefetch_requests":true,
                "default_output_context":"html",
                "cookie_expires_time":"259200",
                "session_name":"deepcms",
                "max_group_priority_number":"10"
            },
            "db":{
                "host":"localhost",
                "port":3306,
                "prefix":"",
                "name":"",
                "user":"",
                "password":"",
                "connection_charset":"utf8"
            }
        }');

        storage::write('config', $storedConfig);

    }

    return $storedConfig;

}

function normalizeIniValue($value) {

    if (preg_match('/^\d+/', $value)) {

        $measures = strtoupper(substr($value, 0 -1));
        switch ($measures) {
            case 'G':
                $up = pow(1024, 3);
            break;
            case 'M':
                $up = pow(1024, 2);
            break;
            case 'K':
                $up = 1024;
            break;
            default:
                $up = 1;
            break;
        }

        return substr($value, 0, strlen($value) - 1) * $up;

    } else {
        return 'unlimited';
    }

}

function checkPath($path, $type, $isWritable = false) {

    if (!file_exists($path) or !is_readable($path)) {
        return false;
    } else if ($type and !is_dir($path)) {
        return false;
    } else if (!$type and !is_file($path)) {
        return false;
    }
    if ($isWritable and !is_writable($path)) {
        return false;
    }
    return true;

}

function getInstallationQueryString($prefix = '') {

    return <<<INSTALLATIONSTRING

        DROP TABLE IF EXISTS {$prefix}users;
        CREATE TABLE {$prefix}users (
            id                  BIGINT(20) NOT NULL AUTO_INCREMENT,
            group_id            BIGINT(20) DEFAULT NULL,
            status              TINYINT(1) NOT NULL DEFAULT '0',
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
            KEY group_id     (group_id),
            KEY status       (status),
            KEY hash         (hash)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;

        INSERT INTO {$prefix}users
        (id, group_id, status, login, password, email, hash, last_ip, registration_date, last_visit, about)
            VALUES (1, 1, 0, '', '', '', '', '', NOW(), NOW(), '');

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
            id            BIGINT(20) NOT NULL AUTO_INCREMENT,
            is_protected  TINYINT(1) NOT NULL DEFAULT '0',
            priority      BIGINT(20) NOT NULL,
            name          CHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;

        INSERT INTO {$prefix}groups (id, is_protected, priority, name)
            VALUES (1, 1, 0, 'root');

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
            in_search           TINYINT(1)  NOT NULL,
            layout              CHAR(255)   CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            page_alias          MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            permanent_redirect  MEDIUMTEXT  CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            change_freq         CHAR(7)     CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            searchers_priority  DOUBLE      DEFAULT NULL,
            module_name         CHAR(255)   CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
            with_menu           TINYINT(1)  NOT NULL,
            with_images         TINYINT(1)  NOT NULL,
            with_features       TINYINT(1)  NOT NULL,
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
            KEY in_sitemap_xml  (in_sitemap_xml),
            KEY in_search       (in_search)
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
            mirror_id   BIGINT(20) NOT NULL,
            parent_id   BIGINT(20) NOT NULL DEFAULT '0',
            name        CHAR(255)  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            PRIMARY KEY (id),
            KEY mirror_id (mirror_id),
            KEY parent_id (parent_id)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;

        DROP TABLE IF EXISTS {$prefix}menu_items;
        CREATE TABLE {$prefix}menu_items (
            menu_id        BIGINT(20) NOT NULL,
            node_id        BIGINT(20) NOT NULL,
            KEY menu_id (menu_id),
            KEY node_id (node_id)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8;

INSTALLATIONSTRING;

}

function getExtendedQueryString($prefix = '') {

    return <<<EXTENDEDINSTALLATIONSTRING

        INSERT INTO {$prefix}menu (id, mirror_id, parent_id, name)
            VALUES (1, 1, 0, 'Left menu'), (2, 2, 0, 'Bottom menu');

        INSERT INTO {$prefix}menu_items (menu_id, node_id)
            VALUES (1, 1), (2, 1), (1, 2), (1, 5), (2, 5), (1, 9), (1, 10), (2, 10), (1, 11);

        INSERT INTO {$prefix}tree (id, parent_id, lvl, lk, rk, prototype, children_prototype, author, modified_author, last_modified, creation_date, is_publish, node_name, in_sitemap, in_sitemap_xml, in_search, layout, page_alias, permanent_redirect, change_freq, searchers_priority, module_name, with_menu, with_images, with_features, page_title, page_h1, meta_keywords, meta_description, page_text) VALUES
        (1, 0, 1, 1, 2, 'simplePage', 'simplePage', 1, 1, '2014-01-21 08:31:39', '2013-12-04 01:56:04', 1, 'Home', 1, 1, 1, 'page.html', '/', '', 'always', 1, NULL, 1, 1, 1, 'Welcome to Deep-CMS demo site!', 'Welcome friends!', '', '', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas nec dui in&nbsp;lorem fermentum varius sit amet ac&nbsp;quam. Fusce eu&nbsp;porta nibh. Phasellus elementum vehicula est eget sollicitudin. Integer eros arcu, lacinia non vulputate sed, bibendum et&nbsp;orci. Maecenas ante felis, feugiat vitae lacus ut, faucibus pretium nisl. Aliquam non cursus mauris. Class aptent taciti sociosqu ad&nbsp;litora torquent per conubia nostra, per inceptos himenaeos. Proin bibendum convallis nisi ut&nbsp;vestibulum. Quisque dignissim libero viverra metus auctor aliquam. Sed imperdiet justo ac&nbsp;sem dapibus tincidunt ut&nbsp;at&nbsp;ligula. Mauris sagittis nec nibh non tristique. Morbi ut&nbsp;leo mollis, cursus dolor eu, rutrum lorem. Etiam cursus pellentesque velit non ultricies.</p>\r\n\r\n<p>Integer porttitor vulputate mi, non euismod nunc posuere pretium. Duis congue id&nbsp;massa eget pellentesque. In&nbsp;in&nbsp;orci elit. Nulla a&nbsp;lacinia tortor. In&nbsp;vel mollis nunc, nec tempus enim. Morbi semper sem ac&nbsp;ligula laoreet, sit amet sodales nulla ullamcorper. Mauris et&nbsp;dolor et&nbsp;odio ullamcorper condimentum. Donec purus purus, vehicula id&nbsp;interdum ut, mollis sit amet augue. Nam ultrices lobortis dapibus. In&nbsp;risus diam, interdum id&nbsp;metus ut, sollicitudin lobortis ante.</p>'),
        (2, 0, 1, 3, 8, 'simplePage', 'simplePage', 1, 1, '2014-01-21 08:32:00', '2013-12-04 01:57:10', 1, 'News', 1, 1, 1, 'children-list.html', '/News', '', 'daily', 0.7, NULL, 1, 1, 1, '', '', '', '', ''),
        (3, 2, 2, 4, 5, 'simplePage', 'simplePage', 1, 1, '2014-01-21 08:32:08', '2013-12-04 02:00:48', 1, 'Vladimir Putin resigned', 1, 1, 1, 'page.html', '/News/Vladimir-Putin-resigned', '', NULL, NULL, NULL, 1, 1, 1, 'Sensation! Vladimir Putin resigned!', 'Sensation! Vladimir Putin resigned!', '', '', '<p>Integer ipsum elit, rutrum sed ullamcorper non, dapibus ut&nbsp;leo. Vestibulum in&nbsp;lorem fringilla, fermentum elit eu, vehicula mi. Ut&nbsp;lobortis tincidunt mattis. Nullam placerat magna vitae odio imperdiet, ac&nbsp;mattis elit tincidunt. Fusce porttitor non leo a&nbsp;aliquet. Sed vel quam ut&nbsp;mi&nbsp;mattis tincidunt. Maecenas vehicula nibh non elit condimentum, sed malesuada lectus dapibus. Suspendisse sed est eget massa auctor laoreet. Nulla eu&nbsp;eleifend velit. Integer eget magna enim. Class aptent taciti sociosqu ad&nbsp;litora torquent per conubia nostra, per inceptos himenaeos. Nunc vitae tortor at&nbsp;augue volutpat auctor et&nbsp;eget arcu. Ut&nbsp;rutrum orci ac&nbsp;est bibendum aliquam. Aliquam risus purus, rutrum at&nbsp;commodo in, posuere quis neque. Proin mattis libero id&nbsp;justo sodales fringilla. Nam elementum augue ut&nbsp;mauris feugiat, ut&nbsp;elementum augue mollis.</p>\r\n\r\n<p>Integer sollicitudin, tortor a&nbsp;posuere aliquet, dolor diam dignissim magna, ac&nbsp;molestie neque eros a&nbsp;eros. Curabitur pulvinar, leo non venenatis consequat, ipsum leo suscipit sem, ut&nbsp;posuere augue nunc sed lacus. Suspendisse dictum orci vel consectetur gravida. Nullam vitae sodales libero. Duis non mauris a&nbsp;leo rutrum sagittis. Donec purus orci, suscipit a&nbsp;ante in, bibendum dapibus diam. Nam dictum ipsum quis felis tempus, ut&nbsp;lacinia orci fringilla. Morbi eget ligula justo. Phasellus semper est est, vel semper justo semper ut. Nullam varius cursus velit ut&nbsp;egestas. Nunc leo nulla, vehicula at&nbsp;commodo at, molestie nec magna. Cras non aliquet velit, id&nbsp;vehicula diam.</p>'),
        (4, 2, 2, 6, 7, 'simplePage', 'simplePage', 1, 1, '2014-01-21 08:32:13', '2013-12-04 02:02:36', 1, 'Football team of the Antarctida won the world championship', 1, 1, 1, 'page.html', '/News/Football-team-of-the-Antarctida-won-the-world-championship', '', NULL, NULL, NULL, 1, 1, 1, '', '', '', '', '<p>Integer porttitor vulputate mi, non euismod nunc posuere pretium. Duis congue id&nbsp;massa eget pellentesque. In&nbsp;in&nbsp;orci elit. Nulla a&nbsp;lacinia tortor. In&nbsp;vel mollis nunc, nec tempus enim. Morbi semper sem ac&nbsp;ligula laoreet, sit amet sodales nulla ullamcorper. Mauris et&nbsp;dolor et&nbsp;odio ullamcorper condimentum. Donec purus purus, vehicula id&nbsp;interdum ut, mollis sit amet augue. Nam ultrices lobortis dapibus. In&nbsp;risus diam, interdum id&nbsp;metus ut, sollicitudin lobortis ante.</p>\r\n\r\n<p>Duis bibendum lectus a&nbsp;volutpat posuere. Praesent rhoncus ultrices nunc, ut&nbsp;bibendum odio aliquet interdum. Nulla condimentum augue eu&nbsp;convallis suscipit. Duis tincidunt nibh at&nbsp;eros dictum, eu&nbsp;volutpat urna pellentesque. Integer et&nbsp;quam a&nbsp;tortor mattis lobortis ut&nbsp;in&nbsp;tellus. Duis facilisis, velit vitae iaculis tempor, ligula est posuere odio, non tristique neque mi&nbsp;sed erat. Fusce in&nbsp;sodales turpis. Duis porttitor nulla vel facilisis egestas.</p>\r\n\r\n<p>Integer ipsum elit, rutrum sed ullamcorper non, dapibus ut&nbsp;leo. Vestibulum in&nbsp;lorem fringilla, fermentum elit eu, vehicula mi. Ut&nbsp;lobortis tincidunt mattis. Nullam placerat magna vitae odio imperdiet, ac&nbsp;mattis elit tincidunt. Fusce porttitor non leo a&nbsp;aliquet. Sed vel quam ut&nbsp;mi&nbsp;mattis tincidunt. Maecenas vehicula nibh non elit condimentum, sed malesuada lectus dapibus. Suspendisse sed est eget massa auctor laoreet. Nulla eu&nbsp;eleifend velit. Integer eget magna enim. Class aptent taciti sociosqu ad&nbsp;litora torquent per conubia nostra, per inceptos himenaeos. Nunc vitae tortor at&nbsp;augue volutpat auctor et&nbsp;eget arcu. Ut&nbsp;rutrum orci ac&nbsp;est bibendum aliquam. Aliquam risus purus, rutrum at&nbsp;commodo in, posuere quis neque. Proin mattis libero id&nbsp;justo sodales fringilla. Nam elementum augue ut&nbsp;mauris feugiat, ut&nbsp;elementum augue mollis.</p>'),
        (5, 0, 1, 9, 16, 'simplePage', 'simplePage', 1, 1, '2014-01-21 08:32:21', '2013-12-04 02:03:52', 1, 'Articles', 1, 1, 1, 'chain-children-list.html', '/Articles', '', NULL, NULL, NULL, 1, 1, 1, '', '', '', '', ''),
        (6, 5, 2, 10, 11, 'simplePage', 'simplePage', 1, 1, '2014-01-21 08:32:29', '2013-12-04 02:05:01', 1, 'Where did the dinosaurs', 1, 1, 1, 'page.html', '/Articles/Where-did-the-dinosaurs', '', NULL, NULL, NULL, 1, 1, 1, '', '', '', '', '<p>Cum sociis natoque penatibus et&nbsp;magnis dis parturient montes, nascetur ridiculus mus. Aliquam nec tempor ipsum, ut&nbsp;posuere lectus. Aliquam pretium gravida dolor eu&nbsp;aliquet. Pellentesque nec justo nunc. Sed tempus metus quis dolor blandit, eget tempor nisl ultricies. Integer varius porta laoreet. Etiam a&nbsp;placerat eros. Pellentesque pharetra, sem placerat pharetra laoreet, odio nibh facilisis eros, ut&nbsp;venenatis mauris enim ut&nbsp;tortor. Proin dictum ipsum mi, a&nbsp;luctus justo hendrerit a.</p>\r\n\r\n<p>Donec lacinia, eros et&nbsp;auctor placerat, tortor velit mollis metus, eu&nbsp;suscipit nibh felis nec massa. Integer lorem diam, auctor sit amet lorem at, varius scelerisque lectus. Proin porta enim at&nbsp;sem vehicula dignissim. Aliquam lobortis tincidunt venenatis. Duis eu&nbsp;tortor ac&nbsp;est sodales laoreet. Suspendisse sodales nulla facilisis, volutpat sapien ut, dictum enim. Sed tincidunt suscipit libero nec ultrices. Phasellus aliquam, lorem in&nbsp;convallis scelerisque, quam ante consectetur magna, at&nbsp;cursus orci nunc ac&nbsp;est. Fusce euismod erat a&nbsp;imperdiet viverra. Fusce lacinia porttitor laoreet. Aliquam placerat, dui ut&nbsp;bibendum adipiscing, mi&nbsp;turpis aliquet lorem, in&nbsp;tincidunt tortor mi&nbsp;et&nbsp;lorem. Vivamus sed condimentum justo. Nullam consequat tortor vel est tincidunt tincidunt. Nulla facilisi. Mauris ac&nbsp;ornare magna, vel elementum mauris.</p>\r\n\r\n<p>Fusce at&nbsp;egestas felis. Duis a&nbsp;urna vehicula sem imperdiet pellentesque. Mauris nibh metus, dictum a&nbsp;tellus eu, pretium ornare est. Fusce a&nbsp;erat vitae dui ultrices elementum eget non mi. Quisque vitae nulla dignissim, lobortis purus at, fermentum turpis. Praesent eget erat at&nbsp;mauris pharetra sodales. Cras a&nbsp;orci elementum, blandit velit eu, aliquet tellus. Phasellus tincidunt id&nbsp;risus ut&nbsp;malesuada. Nulla eget placerat augue. Donec velit purus, porta ac&nbsp;nulla sed, blandit accumsan metus. Cras tincidunt sollicitudin consequat. Donec sagittis faucibus dui, ac&nbsp;mollis dolor malesuada id. Aliquam imperdiet tellus et&nbsp;dictum ullamcorper. Curabitur ac&nbsp;convallis mauris. Vivamus sit amet ante sit amet tellus auctor molestie sed id&nbsp;nisl.</p>'),
        (7, 5, 2, 12, 13, 'simplePage', 'simplePage', 1, 1, '2014-01-21 08:32:36', '2013-12-04 02:06:05', 1, 'Slippers in Space', 1, 1, 1, 'page.html', '/Articles/Slippers-in-Space', '', NULL, NULL, NULL, 1, 1, 1, '', '', '', '', '<p>Fusce at&nbsp;egestas felis. Duis a&nbsp;urna vehicula sem imperdiet pellentesque. Mauris nibh metus, dictum a&nbsp;tellus eu, pretium ornare est. Fusce a&nbsp;erat vitae dui ultrices elementum eget non mi. Quisque vitae nulla dignissim, lobortis purus at, fermentum turpis. Praesent eget erat at&nbsp;mauris pharetra sodales. Cras a&nbsp;orci elementum, blandit velit eu, aliquet tellus. Phasellus tincidunt id&nbsp;risus ut&nbsp;malesuada. Nulla eget placerat augue. Donec velit purus, porta ac&nbsp;nulla sed, blandit accumsan metus. Cras tincidunt sollicitudin consequat. Donec sagittis faucibus dui, ac&nbsp;mollis dolor malesuada id. Aliquam imperdiet tellus et&nbsp;dictum ullamcorper. Curabitur ac&nbsp;convallis mauris. Vivamus sit amet ante sit amet tellus auctor molestie sed id&nbsp;nisl.</p>\r\n\r\n<p>Duis at&nbsp;dictum quam. Cras tempus tincidunt neque eget feugiat. Donec molestie tortor dui, ut&nbsp;porta orci rutrum a.&nbsp;Praesent convallis ante et&nbsp;magna molestie accumsan. Integer nec dui at&nbsp;mauris ultrices aliquet. Etiam rhoncus laoreet augue eu&nbsp;commodo. Aliquam id&nbsp;sodales mi, vitae cursus elit. Curabitur porttitor commodo semper. Duis porttitor venenatis libero, eu&nbsp;volutpat purus vulputate sed. Quisque ut&nbsp;neque purus. Quisque ut&nbsp;libero a&nbsp;leo egestas porttitor nec sed purus. Nulla ac&nbsp;ligula volutpat, imperdiet libero non, gravida libero.</p>'),
        (8, 5, 2, 14, 15, 'simplePage', 'simplePage', 1, 1, '2014-01-21 08:32:42', '2013-12-04 02:06:54', 1, 'Square waves on the water', 1, 1, 1, 'page.html', '/Articles/Square-waves-on-the-water', '', NULL, NULL, NULL, 1, 1, 1, '', '', '', '', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean adipiscing turpis ac&nbsp;eros dictum, sit amet mollis orci porttitor. Mauris a&nbsp;neque eget erat ultrices feugiat non sed ante. Nunc metus magna, luctus eget leo sed, sodales convallis dui. Etiam molestie, nisi in&nbsp;vulputate cursus, quam tellus tempus ipsum, ac&nbsp;elementum orci magna sit amet massa. Aenean a&nbsp;leo ligula. Integer augue sem, lacinia eget urna sed, fermentum condimentum turpis. Nunc ultricies, dolor quis pellentesque tempus, justo leo facilisis turpis, congue commodo erat velit vestibulum dui. Maecenas eleifend, nulla sed eleifend laoreet, tortor velit rhoncus velit, sit amet imperdiet elit orci non sem. Morbi non nisl neque. Phasellus a&nbsp;risus eu&nbsp;justo lacinia adipiscing. Phasellus nec scelerisque mauris, in&nbsp;tristique risus. Donec nec leo imperdiet, rhoncus mauris non, pellentesque velit. Phasellus auctor egestas mi&nbsp;a&nbsp;ullamcorper. Ut&nbsp;eleifend, nisl vitae ultrices congue, metus mi&nbsp;commodo libero, et&nbsp;lobortis libero enim a&nbsp;odio.</p>\r\n\r\n<p>Cum sociis natoque penatibus et&nbsp;magnis dis parturient montes, nascetur ridiculus mus. Aliquam nec tempor ipsum, ut&nbsp;posuere lectus. Aliquam pretium gravida dolor eu&nbsp;aliquet. Pellentesque nec justo nunc. Sed tempus metus quis dolor blandit, eget tempor nisl ultricies. Integer varius porta laoreet. Etiam a&nbsp;placerat eros. Pellentesque pharetra, sem placerat pharetra laoreet, odio nibh facilisis eros, ut&nbsp;venenatis mauris enim ut&nbsp;tortor. Proin dictum ipsum mi, a&nbsp;luctus justo hendrerit a.</p>'),
        (9, 0, 1, 27, 28, 'mainModule', 'simplePage', 1, 1, '2014-01-21 08:32:52', '2013-12-04 02:09:24', 1, 'Search on site', 0, 1, 0, NULL, '/Search-on-site', NULL, NULL, NULL, 'search', 1, 1, 0, NULL, NULL, NULL, NULL, NULL),
        (10, 0, 1, 29, 30, 'mainModule', 'simplePage', 1, 1, '2014-01-21 08:32:58', '2013-12-04 02:10:07', 1, 'Sitemap', 0, 1, 0, NULL, '/Sitemap', NULL, NULL, NULL, 'sitemap', 1, 1, 0, NULL, NULL, NULL, NULL, NULL),
        (11, 0, 1, 31, 32, 'simpleLink', 'simplePage', 1, 1, '2014-01-21 08:33:08', '2013-12-04 02:11:02', 1, 'Sitemap XML', 0, 1, 0, NULL, '/sitemap.xml', NULL, NULL, NULL, NULL, 1, 1, 0, NULL, NULL, NULL, NULL, NULL),
        (18, 0, 1, 23, 26, 'nodeGroup', 'simplePage', 1, 1, '2014-01-21 08:47:08', '2014-01-21 07:48:17', 1, 'Etc', 0, 0, 0, NULL, '/Etc', NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL),
        (19, 0, 1, 17, 22, 'nodeGroup', 'simplePage', 1, 1, '2014-01-21 08:42:28', '2014-01-21 08:42:28', 1, 'Drafts', 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL),
        (20, 19, 2, 20, 21, 'simplePage', 'simplePage', 1, 1, '2014-01-21 08:44:38', '2014-01-21 08:43:20', 0, 'How to spend all money in one day?', 0, 1, 1, 'page.html', '/How-to-spend-all-money-in-one-day', '', NULL, NULL, NULL, 1, 1, 1, '', '', '', '', '<p>Integer porttitor vulputate mi, non euismod nunc posuere pretium. Duis congue id&nbsp;massa eget pellentesque. In&nbsp;in&nbsp;orci elit. Nulla a&nbsp;lacinia tortor. In&nbsp;vel mollis nunc, nec tempus enim. Morbi semper sem ac&nbsp;ligula laoreet, sit amet sodales nulla ullamcorper. Mauris et&nbsp;dolor et&nbsp;odio ullamcorper condimentum. Donec purus purus, vehicula id&nbsp;interdum ut, mollis sit amet augue. Nam ultrices lobortis dapibus. In&nbsp;risus diam, interdum id&nbsp;metus ut, sollicitudin lobortis ante.</p>\r\n\r\n<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas nec dui in&nbsp;lorem fermentum varius sit amet ac&nbsp;quam. Fusce eu&nbsp;porta nibh. Phasellus elementum vehicula est eget sollicitudin. Integer eros arcu, lacinia non vulputate sed, bibendum et&nbsp;orci. Maecenas ante felis, feugiat vitae lacus ut, faucibus pretium nisl. Aliquam non cursus mauris. Class aptent taciti sociosqu ad&nbsp;litora torquent per conubia nostra, per inceptos himenaeos. Proin bibendum convallis nisi ut&nbsp;vestibulum. Quisque dignissim libero viverra metus auctor aliquam. Sed imperdiet justo ac&nbsp;sem dapibus tincidunt ut&nbsp;at&nbsp;ligula. Mauris sagittis nec nibh non tristique. Morbi ut&nbsp;leo mollis, cursus dolor eu, rutrum lorem. Etiam cursus pellentesque velit non ultricies.</p>'),
        (21, 19, 2, 18, 19, 'simplePage', 'simplePage', 1, 1, '2014-01-21 08:46:33', '2014-01-21 08:45:35', 0, 'Look inside yourself', 0, 1, 1, 'chain-children-list.html', '/Look-inside-yourself', '', NULL, NULL, NULL, 1, 1, 1, '', '', '', '', '<p>Duis at&nbsp;dictum quam. Cras tempus tincidunt neque eget feugiat. Donec molestie tortor dui, ut&nbsp;porta orci rutrum a.&nbsp;Praesent convallis ante et&nbsp;magna molestie accumsan. Integer nec dui at&nbsp;mauris ultrices aliquet. Etiam rhoncus laoreet augue eu&nbsp;commodo. Aliquam id&nbsp;sodales mi, vitae cursus elit. Curabitur porttitor commodo semper. Duis porttitor venenatis libero, eu&nbsp;volutpat purus vulputate sed. Quisque ut&nbsp;neque purus. Quisque ut&nbsp;libero a&nbsp;leo egestas porttitor nec sed purus. Nulla ac&nbsp;ligula volutpat, imperdiet libero non, gravida libero.</p>\r\n\r\n<p>Fusce at&nbsp;egestas felis. Duis a&nbsp;urna vehicula sem imperdiet pellentesque. Mauris nibh metus, dictum a&nbsp;tellus eu, pretium ornare est. Fusce a&nbsp;erat vitae dui ultrices elementum eget non mi. Quisque vitae nulla dignissim, lobortis purus at, fermentum turpis. Praesent eget erat at&nbsp;mauris pharetra sodales. Cras a&nbsp;orci elementum, blandit velit eu, aliquet tellus. Phasellus tincidunt id&nbsp;risus ut&nbsp;malesuada. Nulla eget placerat augue. Donec velit purus, porta ac&nbsp;nulla sed, blandit accumsan metus. Cras tincidunt sollicitudin consequat. Donec sagittis faucibus dui, ac&nbsp;mollis dolor malesuada id. Aliquam imperdiet tellus et&nbsp;dictum ullamcorper. Curabitur ac&nbsp;convallis mauris. Vivamus sit amet ante sit amet tellus auctor molestie sed id&nbsp;nisl.</p>'),
        (22, 18, 2, 24, 25, 'simplePage', 'simplePage', 1, 1, '2014-01-21 08:48:28', '2014-01-21 08:48:28', 0, 'LHGlhglg lhgl hgyoi', 0, 1, 1, 'chain-children-list.html', '/LHGlhglg-lhgl-hgyoi', '', NULL, NULL, NULL, 1, 1, 1, '', '', '', '', '<p>Donec lacinia, eros et&nbsp;auctor placerat, tortor velit mollis metus, eu&nbsp;suscipit nibh felis nec massa. Integer lorem diam, auctor sit amet lorem at, varius scelerisque lectus. Proin porta enim at&nbsp;sem vehicula dignissim. Aliquam lobortis tincidunt venenatis. Duis eu&nbsp;tortor ac&nbsp;est sodales laoreet. Suspendisse sodales nulla facilisis, volutpat sapien ut, dictum enim. Sed tincidunt suscipit libero nec ultrices. Phasellus aliquam, lorem in&nbsp;convallis scelerisque, quam ante consectetur magna, at&nbsp;cursus orci nunc ac&nbsp;est. Fusce euismod erat a&nbsp;imperdiet viverra. Fusce lacinia porttitor laoreet. Aliquam placerat, dui ut&nbsp;bibendum adipiscing, mi&nbsp;turpis aliquet lorem, in&nbsp;tincidunt tortor mi&nbsp;et&nbsp;lorem. Vivamus sed condimentum justo. Nullam consequat tortor vel est tincidunt tincidunt. Nulla facilisi. Mauris ac&nbsp;ornare magna, vel elementum mauris.</p>\r\n\r\n<p>Fusce at&nbsp;egestas felis. Duis a&nbsp;urna vehicula sem imperdiet pellentesque. Mauris nibh metus, dictum a&nbsp;tellus eu, pretium ornare est. Fusce a&nbsp;erat vitae dui ultrices elementum eget non mi. Quisque vitae nulla dignissim, lobortis purus at, fermentum turpis. Praesent eget erat at&nbsp;mauris pharetra sodales. Cras a&nbsp;orci elementum, blandit velit eu, aliquet tellus. Phasellus tincidunt id&nbsp;risus ut&nbsp;malesuada. Nulla eget placerat augue. Donec velit purus, porta ac&nbsp;nulla sed, blandit accumsan metus. Cras tincidunt sollicitudin consequat. Donec sagittis faucibus dui, ac&nbsp;mollis dolor malesuada id. Aliquam imperdiet tellus et&nbsp;dictum ullamcorper. Curabitur ac&nbsp;convallis mauris. Vivamus sit amet ante sit amet tellus auctor molestie sed id&nbsp;nisl.</p>\r\n\r\n<p>Cum sociis natoque penatibus et&nbsp;magnis dis parturient montes, nascetur ridiculus mus. Aliquam nec tempor ipsum, ut&nbsp;posuere lectus. Aliquam pretium gravida dolor eu&nbsp;aliquet. Pellentesque nec justo nunc. Sed tempus metus quis dolor blandit, eget tempor nisl ultricies. Integer varius porta laoreet. Etiam a&nbsp;placerat eros. Pellentesque pharetra, sem placerat pharetra laoreet, odio nibh facilisis eros, ut&nbsp;venenatis mauris enim ut&nbsp;tortor. Proin dictum ipsum mi, a&nbsp;luctus justo hendrerit a.</p>');

EXTENDEDINSTALLATIONSTRING;

}

function getAllControllers() {

    $controllers    = array();
    $modules        = installGlobRecursive(APPLICATION . 'modules/', '*.php');
    $adminModules   = installGlob(APPLICATION . 'admin/controllers/*.php');
    $adminBootstrap = array(APPLICATION . 'admin/admin.php');
    $existsTargets  = array_merge($adminBootstrap, $adminModules, $modules);

    require_once APPLICATION . 'core/baseController.php';
    foreach ($existsTargets as $item) {
        $name = basename($item, '.php');
        node::loadController($item, $name);
        array_push($controllers, node::call($name));
    }

    return $controllers;

}

function saveConfigIntoFile($config) {

    // fix bool values
    $config->site->check_unused_params
        = $config->site->check_unused_params ? 'true' : 'false';

    $config->system->debug_mode
        = $config->system->debug_mode ? 'true' : 'false';

    $config->system->cache_enabled
        = $config->system->cache_enabled ? 'true' : 'false';

    $config->system->write_log
        = $config->system->write_log ? 'true' : 'false';

    $config->system->block_prefetch_requests
        = $config->system->block_prefetch_requests ? 'true' : 'false';

    // place config values into config string example
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

        "check_unused_params": {$config->site->check_unused_params},

        // default language environment
        "default_language": "{$config->site->default_language}",

        // default timezone
        "default_timezone": "{$config->site->default_timezone}",


        /**
         * default theme of site (templates collection)
         * this values always is "default"
         * because you can set it on administrative tools
         */

        "theme": "{$config->site->theme}",


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
        "admin_tools_link": "{$config->site->admin_tools_link}",

        // relative URL path of administrative tools resources
        "admin_resources": "{$config->site->admin_resources}"

    },


    /**
     * application identifiers
     */

    "application": {

        // name signature and version of application
        "name": "{$config->application->name}",
        "version": "{$config->application->version}",

        // email address of technical support
        "support_email": "{$config->application->support_email}",

        // sources repository domain
        "sources_domain": "{$config->application->sources_domain}"

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
        "cache_enabled": {$config->system->cache_enabled},

        // write logs of application (members) events
        "write_log": {$config->system->write_log},

        // max size of separated log file
        "log_file_max_size": {$config->system->log_file_max_size},

        // block prefetch requests
        "block_prefetch_requests": {$config->system->block_prefetch_requests},

        // default output context
        "default_output_context": "{$config->system->default_output_context}",

        // cookie expires time (sec)
        "cookie_expires_time": {$config->system->cookie_expires_time},


        /**
         * name of session, set for session_name() PHP function
         * note: for compatibility set only alphabetic symbols
         */

        "session_name": "{$config->system->session_name}",

        // member groups priority range number
        "max_group_priority_number": {$config->system->max_group_priority_number}


    },


    /**
     * patterns rules of filesystem cache
     * this is standard regular expressions of PHP
     * note: need double backslash escaping
     */

    "cached_pages": [

        "/\\\/dir\\\/page\\\.html\\\?param1=value1&param2=value2/"

    ],


    /**
     * output templates
     * relative path's of templates
     */

    "layouts": {

        // relative path of parts templates directory
        "parts": "parts/",

        // relative path of required header template
        "header": "parts/header.html",

        // relative path of required footer template
        "footer": "parts/footer.html"

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
        "connection_charset": "{$config->db->connection_charset}"

    }


}


CONFIGSTRING;

    // write content of configuration file
    $configFile = APPLICATION . 'config/main.json';
    file_put_contents($configFile, $configString, LOCK_EX);

}


/**
 * run installation process
 */

try {


    $filterClass = APPLICATION . 'core/filter.php';
    if (!is_file($filterClass)) {
        throw new installException(
            'Installation error', 'Filter class is not exists'
        );
    }
    require_once $filterClass;


    storage::init();
    view::init();
    reporter::init();

    $_config = getConfig();
    $layout  = 'install.html';


    // get language environment
    $langFile = storage::read('langfile');
    if (!$langFile) {
        /*$clientLangs = getClientLanguages();
        foreach ($clientLangs as $k => $v) {
            $findedLang = APPLICATION . 'languages/' . $k . '/install.php';
            if (is_file($findedLang)) {
                $langFile = $findedLang;
                break;
            }
        }*/
    }
    if (!$langFile) {
        $langFile = APPLICATION . 'languages/en/install.php';
    }
    $language = (object) (require $langFile);

    // available language directories
    $langsPath = APPLICATION . 'languages/';
    $existsLangs = installGlob($langsPath . '*', GLOB_ONLYDIR | GLOB_NOSORT);

    // step by step
    $step = storage::read('step');
    $step = !$step ? 1 : $step;
    if (request::isPost() and request::getPostParam('prev')) {
        $step -= $step > 1 ? 1 : 0;
        storage::write('step', $step);
        request::refresh();
    }

    switch ($step) {


        // step 7 final
        case 7:
            saveConfigIntoFile($_config);
        break;


        // step 6 begin
        case 6:

            if (!$settings = storage::read('settings')) {

                $port = $_SERVER['SERVER_PORT'];
                $port = ($port != 80 and $port != 443) ? ':' . $port : '';
                $protocol = stristr($_SERVER['SERVER_PROTOCOL'], 'https') ? 'https' : 'http';

                $settings = array(
                    'protocol'     => $protocol,
                    'domain'       => $_SERVER['SERVER_NAME'] . $port,
                    'rootlogin'    => 'root',
                    'rootemail'    => '',
                    'rootpassword' => '',
                    'debugmode'    => true
                );

                storage::write('settings', $settings);

            }

            if (request::isPost() and request::getPostParam('next')) {

                $settings['debugmode'] = !!request::getPostParam('debugmode');
                $_config->system->debug_mode = $settings['debugmode'];
                $_config->site->protocol = $settings['protocol'];
                $_config->site->domain   = $settings['domain'];
                storage::write('config', $_config);

                $required = array('rootlogin', 'rootemail', 'rootpassword');
                foreach ($required as $key) {
                    $item = request::getPostParam($key);
                    if ($item === null) {
                        throw new installException(
                            $language->install_error,
                            $language->install_data_not_enough
                        );
                    }
                    $settings[$key] = $item;
                }

                $settings['rootlogin'] = filter::input(
                    $settings['rootlogin'])->htmlSpecialChars()->getData();

                if (!$settings['rootlogin']) {
                    reporter::setErrorStatus();
                    reporter::addReportMessage($language->install_root_login_invalid);
                }

                $settings['rootemail'] = filter::input($settings['rootemail'])->getData();
                if (!filter_var($settings['rootemail'], FILTER_VALIDATE_EMAIL)) {
                    reporter::setErrorStatus();
                    reporter::addReportMessage($language->install_email_invalid);
                }

                $settings['rootpassword'] = (string) $settings['rootpassword'];
                storage::write('settings', $settings);

                // try connect to database
                if (!reporter::isError()) {
                    db::connect(
                        $_config->db->host,
                        $_config->db->user,
                        $_config->db->password,
                        $_config->db->name,
                        $_config->db->port
                    );
                }
                if (!reporter::isError()) {
                    db::setCharset($_config->db->connection_charset);
                }
                if (!reporter::isError()) {
                    db::query("SET time_zone = '{$_config->site->default_timezone}'");
                }

                // create root
                if (!reporter::isError()) {

                    $rootpassword = md5($settings['rootpassword']);
                    $roothash = md5(
                        '1'
                        . $settings['rootlogin']
                        . $rootpassword
                        . '1'
                        . '0'
                        . $settings['rootemail']
                    );

                    $rootlogin = db::escapeString($settings['rootlogin']);
                    $rootemail = db::escapeString($settings['rootemail']);

                    db::query(
                        "UPDATE {$_config->db->prefix}users SET
                            login = '{$rootlogin}', email = '{$rootemail}',
                            password = '{$rootpassword}', hash = '{$roothash}'
                        WHERE id = 1"
                    );
                }

                // find permission
                if (!reporter::isError()) {
                    $controllersPermissions = array();
                    $controllers = getAllControllers();
                    foreach ($controllers as $controller) {
                        foreach ($controller->getPermissions() as $current) {
                            $current = db::escapeString($current['permission']);
                            $check = in_array($current, $controllersPermissions);
                            if (!$check) {
                                $controllersPermissions[] = $current;
                            }
                        }
                    }
                }

                // clear all permissions
                if (!reporter::isError()) {
                    db::query("TRUNCATE TABLE {$_config->db->prefix}group_permissions");
                }
                if (!reporter::isError()) {
                    db::query("TRUNCATE TABLE {$_config->db->prefix}permissions");
                }

                // insert new permissions
                if (!reporter::isError()) {
                    $permissionValues = "('" . join("'), ('", $controllersPermissions) . "')";
                    db::query(
                        "INSERT INTO {$_config->db->prefix}permissions (name)
                            VALUES {$permissionValues}"
                    );
                }
                if (!reporter::isError()) {
                    db::query(
                        "INSERT INTO {$_config->db->prefix}group_permissions
                            (group_id, permission_id)
                            SELECT (1) group_id, id
                            FROM {$_config->db->prefix}permissions"
                    );
                }

                if (!reporter::isError()) {
                    storage::write('step', 7);
                }
                request::refresh();

            }

        // step 6 end
        break;


        // step 5 begin
        case 5:

            if (!$db = storage::read('db')) {

                $db = array(
                    'host'        => 'localhost',
                    'port'        => '3306',
                    'prefix'      => '',
                    'name'        => '',
                    'user'        => '',
                    'password'    => '',
                    'addextended' => true
                );
                storage::write('db', $db);

            }

            if (request::isPost() and request::getPostParam('next')) {

                $required = array(
                    'host', 
                    'port',
                 // 'prefix',
                    'name',
                    'user',
                    'password'
                );

                $db = array();
                foreach ($required as $key) {
                    $item = request::getPostParam($key);
                    if ($item === null) {
                        throw new installException(
                            $language->install_error,
                            $language->install_data_not_enough
                        );
                    }
                    if ($key == 'password') {
                        $db[$key] = trim((string) $item);
                    } else {
                        $db[$key] = trim(strip_tags((string) $item));
                    }
                }

                $db['prefix'] = '';
                $db['addextended'] = !!request::getPostParam('addextended');
                storage::write('db', $db);

                // check form data
                if (!$db['host']) {
                    reporter::setErrorStatus();
                    reporter::addReportMessage($language->install_db_host_is_empty);
                }
                if (!$db['port'] or !preg_match('/^[0-9]+$/', $db['port']) or $db['port'] > 65535) {
                    reporter::setErrorStatus();
                    reporter::addReportMessage($language->install_db_port_is_broken);
                }
                if (!$db['name']) {
                    reporter::setErrorStatus();
                    reporter::addReportMessage($language->install_db_name_is_empty);
                }
                if (!$db['user']) {
                    reporter::setErrorStatus();
                    reporter::addReportMessage($language->install_db_user_is_empty);
                }

                // try connect to database
                if (!reporter::isError()) {
                    db::connect(
                        $db['host'],
                        $db['user'],
                        $db['password'],
                        $db['name'],
                        $db['port']
                    );
                }
                if (!reporter::isError()) {
                    db::setCharset($_config->db->connection_charset);
                }
                if (!reporter::isError()) {
                    db::query("SET time_zone = '{$_config->site->default_timezone}'");
                }

                // create main tables
                if (!reporter::isError()) {
                    db::query(getInstallationQueryString($db['prefix']));
                }

                if (!reporter::isError() and $db['addextended']) {
                    db::query(getExtendedQueryString($db['prefix']));
                }

                if (!reporter::isError()) {

                    $_config->db->host     = $db['host'];
                    $_config->db->port     = $db['port'];
                    $_config->db->name     = $db['name'];
                    $_config->db->user     = $db['user'];
                    $_config->db->password = $db['password'];
                    $_config->db->prefix   = $db['prefix'];

                    storage::write('config', $_config);
                    storage::write('step', 6);

                }

                request::refresh();

            }

        // step 5 end
        break;


        // step 4 begin
        case 4:

            // writable permissions for directories
            $adminControllersDir = APPLICATION . 'admin/controllers';
            if (!$checkAdminControllersDir = checkPath($adminControllersDir, true, true)) {
                reporter::setErrorStatus();
            }
            $adminInMenuDir = APPLICATION . 'admin/in-menu';
            if (!$checkAdminInMenuDir = checkPath($adminInMenuDir, true, true)) {
                reporter::setErrorStatus();
            }
            $autorunAfterDir = APPLICATION . 'autorun/after';
            if (!$checkAutorunAfterDir = checkPath($autorunAfterDir, true, true)) {
                reporter::setErrorStatus();
            }
            $autorunBeforeDir = APPLICATION . 'autorun/before';
            if (!$checkAutorunBeforeDir = checkPath($autorunBeforeDir, true, true)) {
                reporter::setErrorStatus();
            }
            $cacheDir = APPLICATION . 'cache';
            if (!$checkCacheDir = checkPath($cacheDir, true, true)) {
                reporter::setErrorStatus();
            }
            $configDir = APPLICATION . 'config';
            if (!$checkConfigDir = checkPath($configDir, true, true)) {
                reporter::setErrorStatus();
            }

            $languagesDir = APPLICATION . 'languages';
            if (!$checkLanguagesDir = checkPath($languagesDir, true, true)) {
                reporter::setErrorStatus();
            }
            foreach ($existsLangs as $item) {
                if (!checkPath($item, true, true)) {
                    reporter::setErrorStatus();
                    break;
                }
            }

            $layoutsAdminPartsDir = APPLICATION . 'layouts/admin/parts';
            if (!$checkLayoutsAdminPartsDir = checkPath($layoutsAdminPartsDir, true, true)) {
                reporter::setErrorStatus();
            }
            $layoutsAdminProtectedDir = APPLICATION . 'layouts/admin/protected';
            if (!$checkLayoutsAdminProtectedDir = checkPath($layoutsAdminProtectedDir, true, true)) {
                reporter::setErrorStatus();
            }

            $themesPath = APPLICATION . 'layouts/themes/*';
            $themeDirs = array('parts', 'protected', 'public');
            $existsThemes = installGlob($themesPath, GLOB_ONLYDIR | GLOB_NOSORT);
            foreach ($existsThemes as $item) {
                foreach ($themeDirs as $dir) {
                    if (!checkPath($item . '/' . $dir, true, true)) {
                        reporter::setErrorStatus();
                        break;
                    }
                }
            }

            $libraryDir = APPLICATION . 'library';
            if (!$checkLibraryDir = checkPath($libraryDir, true, true)) {
                reporter::setErrorStatus();
            }
            $logsDir = APPLICATION . 'logs';
            if (!$checkLogsDir = checkPath($logsDir, true, true)) {
                reporter::setErrorStatus();
            }
            $metadataDir = APPLICATION . 'metadata';
            if (!$checkMetadataDir = checkPath($metadataDir, true, true)) {
                reporter::setErrorStatus();
            }
            $modulesDir = APPLICATION . 'modules';
            if (!$checkModulesDir = checkPath($modulesDir, true, true)) {
                reporter::setErrorStatus();
            }
            $prototypesDir = APPLICATION . 'prototypes';
            if (!$checkPrototypesDir = checkPath($prototypesDir, true, true)) {
                reporter::setErrorStatus();
            }
            $resourcesDir = APPLICATION . 'resources';
            if (!$checkResourcesDir = checkPath($resourcesDir, true, true)) {
                reporter::setErrorStatus();
            }
            $uploadDir = PUBLIC_HTML . 'upload';
            if (!$checkUploadDir = checkPath($uploadDir, true, true)) {
                reporter::setErrorStatus();
            }

            if (request::isPost() and request::getPostParam('next')) {
                if (!reporter::isError()) {
                    storage::write('step', 5);
                }
                request::refresh();
            }

        // step 4 end
        break;


        // step 3 begin
        case 3:

            // check php extensions and available classes
            if (!$checkMysqli = class_exists('mysqli')) {
                reporter::setErrorStatus();
            }
            if (!$checkDOMImpl = class_exists('DOMImplementation')) {
                reporter::setErrorStatus();
            }
            if (!$checkDOMDoc = class_exists('DOMDocument')) {
                reporter::setErrorStatus();
            }
            if (!$checkGD = function_exists('imagecreatefromjpeg')) {
                reporter::setErrorStatus();
            }
            if (!$checkFilterVar = function_exists('filter_var')) {
                reporter::setErrorStatus();
            }
            if (!$checkLibCurl = extension_loaded('curl')) {
                reporter::setErrorStatus();
            }

            if (request::isPost() and request::getPostParam('next')) {
                if (!reporter::isError()) {
                    storage::write('step', 4);
                }
                request::refresh();
            }

        // step 3 end
        break;


        // step 2 begin
        case 2:

            // check php version
            $currentPhpVersion = round((float) phpversion(), 2);
            if (!$checkPhpVersion = ($currentPhpVersion > 5.2)) {
                reporter::setErrorStatus();
            }

            // memory limit
            $myMemoryLimit = '8M';
            $currentMemoryLimit = ini_get('memory_limit');
            $normalizeValue = normalizeIniValue($currentMemoryLimit);
            $checkMemoryLimit = (
                $normalizeValue == 'unlimited' or
                $normalizeValue >= normalizeIniValue($myMemoryLimit)
            );
            if (!$checkMemoryLimit) {
                reporter::setErrorStatus();
            }

            // file uploads enabled
            if (!$fileUploads = !!ini_get('file_uploads')) {
                reporter::setErrorStatus();
            }

            // upload max filesize
            $myUploadMaxFileSize = '4M';
            $currentUploadMaxFileSize = ini_get('memory_limit');
            $normalizeValue = normalizeIniValue($currentUploadMaxFileSize);
            $checkUploadMaxFileSize = (
                $normalizeValue == 'unlimited' or
                $normalizeValue >= normalizeIniValue($myUploadMaxFileSize)
            );
            if (!$checkUploadMaxFileSize) {
                reporter::setErrorStatus();
            }

            // magic qoutes
            $mqgpc = ini_get('magic_quotes_gpc');
            $checkMQGPCEnabled = (stristr($mqgpc, 'On') or $mqgpc);
            if ($checkMQGPCEnabled) {
                reporter::setErrorStatus();
            }

            if (request::isPost() and request::getPostParam('next')) {
                if (!reporter::isError()) {
                    storage::write('step', 3);
                }
                request::refresh();
            }

        // step 2 end
        break;


        // step 1 begin
        default:

            $avLangs = array();
            foreach ($existsLangs as $item) {
                $avLangs[] = basename($item);
            }

            if (request::isPost()) {

                $choosedLang = (string) request::getPostParam('language');
                if (!in_array($choosedLang, $avLangs)) {
                    reporter::setErrorStatus();
                }

                $timezone = (string) request::getPostParam('timezone');
                if (!preg_match('/^(\+|-)[0-1][0-9]:[0-5][0-9]$/', $timezone)) {
                    reporter::setErrorStatus();
                }

                if (!reporter::isError()) {

                    $langFile = $langsPath . $choosedLang . '/install.php';
                    storage::write('langfile', $langFile);

                    $_config->site->default_language = $choosedLang;
                    $_config->site->default_timezone = $timezone;
                    storage::write('config', $_config);
                    storage::write('step', 2);

                }

                request::refresh();

            }

            storage::write('step', 1);

        // step 1 end
        break;


    }


} catch (installException $e) {

    $exception = $e->getReport();
    $layout = 'install-exception.html';

}


/**
 * show installation progress
 */

header('Content-Type: text/html; charset=utf-8');
require APPLICATION . 'layouts/admin/protected/' . $layout;

if ($step == 7) {
    storage::clear();
}


