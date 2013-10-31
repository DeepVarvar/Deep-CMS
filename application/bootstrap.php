<?php



/**
 * defined main environment
 */

define("ERROR_EXCEPTION",    0);
define("SUCCESS_EXCEPTION",  1);

define("DATA_WITHOUT_ALL",   0);
define("DATA_WITH_IMAGES",   1);
define("DATA_WITH_FEATURES", 2);
define("DATA_WITH_ALL",      3);


/**
 * set main environment path's,
 * WARNING! set path's without default value of get_include_path()
 */

set_include_path(
    APPLICATION . join(PATH_SEPARATOR . APPLICATION, array(
        "core/", "library/"
    ))
);


/**
 * dump checkpoint data for tests
 */

function dump() {


    foreach (func_get_args() as $target) {

        echo '<hr /> <pre>';
        var_dump($target);
        echo '</pre> <hr />';

    }


    exit();


}


$install = PUBLIC_HTML . "install.php";

if (file_exists($install)) {


    /**
     * install/reinstall
     */

    require_once $install;
    exit();


}


/**
 * normal mode,
 * check PHP version like version_compare()
 */

$version = phpversion();
if ((float) $version < 5.2) {
    exit("Deep-CMS need php version 5.2 or later. Your php version " . $version . PHP_EOL);
}


/**
 * autoload function
 */

function singleAutoload($fileName) {

    $file = "{$fileName}.php";
    require_once $file;

}

spl_autoload_register("singleAutoload", false);


/**
 * check writable permissions
 */

$dirs = array(

    "autorun/after",
    "autorun/before",
    "cache",
    "config",
    "languages",
    "library",
    "logs",
    "modules",
    "resources",
    "upload"

);

foreach ($dirs as $dir) {


    $dir = ($dir == "upload" ? PUBLIC_HTML : APPLICATION) . $dir;

    if (!is_dir($dir)) {
        exit("Core dependency target $dir is not directory" . PHP_EOL);
    }

    if (!is_writable($dir)) {
        exit("Core dependency directory $dir don't have writable permission" . PHP_EOL);
    }


}


/**
 * load main config
 */

$config = app::loadConfig();


/**
 * exception details mode,
 * enable/disable errors and notices
 */

if ($config->system->debug_mode) {

    ini_set("display_errors", "On");
    ini_set("html_errors", "On");

    error_reporting(E_ALL | E_STRICT);

} else {

    ini_set("display_errors", "Off");
    ini_set("html_errors", "Off");

    error_reporting(0);

}


/**
 * check for enabled output contexts
 */

$availableContexts = utils::getAvailableOutputContexts();
if (!$availableContexts) {
    exit("Output contexts is not available" . PHP_EOL);
}


/**
 * start application
 */

try {


    /**
     * CLI mode for example:
     * ~$ php /path/do/public_html/index.php -r[--request] /a/b/c?z=x&q=w -p[--post] r=1&s=2
     *
     * NOT WORKING NOW!
     * exit application
     */

    if (PHP_SAPI == "cli") {
        commandLine::init();
    }


    /**
     * get and stored client info,
     * this action need for member environment
     */

    request::identifyClient();


    /**
     * init session storage
     */

    storage::init();


    /**
     * init view
     */

    view::init($memory, $timestart);


    /**
     * connect to database
     * working only for MySQL now
     * use mysqli wrapper
     */

    define("DB_PREFIX", $config->db->prefix);

    db::connect(

        $config->db->host,
        $config->db->user,
        $config->db->password,
        $config->db->name,
        $config->db->port

    );

    db::setCharset($config->db->connection_charset);


    /**
     * current member environment
     */

    member::init();


    /**
     * request initialization,
     * parse and check request string, headers, etc
     */

    request::init();


    /**
     * before autorun actions
     */

    autorun::runBefore();


    /**
     * set timezone on database connention
     */

    $timezone = member::getTimezone();
    db::set("SET time_zone = '{$timezone}'");


    /**
     * cached pages
     */

    $pageOnCache = false;

    if ($config->system->cache_enabled === true) {


        $cachedPage = md5(request::getOriginURL());
        $availableContexts = join(",", $availableContexts);

        if ($items = glob(APPLICATION . $config->path->cache . "{{$availableContexts}}---$cachedPage", GLOB_BRACE)) {
            $cachedPage = basename($items[0]);
            $pageOnCache = true;
        }


    }


    /**
     * if page content is not exists on cache
     */

    if (!$pageOnCache) {


        /**
         * run route process,
         * execute module, controller, action
         */

        router::init();


        /**
         * check exists layout
         */

        view::checkLayout();


        /**
         * check for unused request parameters,
         * SEO optimization
         */

        request::checkUnusedParams();


    /**
     * WARNING!
     * flush cached content and exit application
     * not working more!
     */

    } else {
        view::readFromCache($cachedPage);
    }


} catch (Exception $e) {
    view::assignException($e);
}


/**
 * flush page
 */

view::draw();



