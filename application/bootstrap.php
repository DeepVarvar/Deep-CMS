<?php



/**
 * defined main environment
 */

define("FAST_RUNNING",   false);
define("ERROR_EXCEPTION",    0);
define("SUCCESS_EXCEPTION",  1);


/**
 * set main environment path's,
 * WARNING! set path's without default value of get_include_path()
 */

set_include_path(
    APPLICATION . PATH_SEPARATOR . APPLICATION .
    join(PATH_SEPARATOR . APPLICATION, array(
        "core/", "library/", "prototypes/"
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


/**
 * install/reinstall mode
 */

if (!file_exists(APPLICATION . "config/main.json")) {
    require_once "install.php";
    exit();
}


/**
 * normal mode,
 * check PHP version like version_compare()
 */

$version = phpversion();
if ((float) $version < 5.2) {
    exit("Deep-CMS need php version 5.2 or later. "
            . "Your php version " . $version . PHP_EOL);
}


/**
 * autoload function
 */

function deepCmsSimpleAutoload($fileName) {
    $file = "{$fileName}.php";
    require_once $file;
}

spl_autoload_register("deepCmsSimpleAutoload", false);


/**
 * slow running mode,
 * check writable permissions
 */

if (!FAST_RUNNING) {

    $dirs = array(
        "admin/in-menu",
        "autorun/after",
        "autorun/before",
        "cache",
        "config",
        "languages",
        "library",
        "logs",
        "metadata",
        "modules",
        "prototypes",
        "resources",
        "upload"
    );

    foreach ($dirs as $dir) {
        $dir = ($dir == "upload" ? PUBLIC_HTML : APPLICATION) . $dir;
        if (!is_dir($dir)) {
            exit("Core dependency target $dir is not directory" . PHP_EOL);
        }
        if (!is_writable($dir)) {
            exit("Core dependency directory $dir "
                    . "don't have writable permission" . PHP_EOL);
        }
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
 * run available contexts getter,
 * check for enabled output contexts
 */

if (!$availableContexts = view::getAvailableOutputContexts()) {
    exit("Output contexts is not available" . PHP_EOL);
}


/**
 * start application
 */

try {


    /**
     * CLI mode for example:
     * ~$ php /path/do/htdocs/index.php -r[--request] /a/b/c?z=x&q=w -p[--post] r=1&s=2
     *
     * NOT WORKING NOW!
     * exit application
     */

    if (PHP_SAPI == "cli") {
        commandLine::init();
    }


    /**
     * get and stored client info - action need for member environment,
     * init session storage,
     * init view
     */

    request::identifyClient();
    storage::init();
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
     * current member environment,
     * request initialization,
     * parse and check request string, headers, etc
     */

    member::init();
    request::init();

    $timezone = member::getTimezone();
    db::set("SET time_zone = '{$timezone}'");

    autorun::runBefore();

    $pageOnCache = false;
    if ($config->system->cache_enabled) {

        $cachedPage = md5(request::getOriginURL());
        $availableContexts = join(",", $availableContexts);
        $items = utils::glob(
            APPLICATION . "cache/{{$availableContexts}}---$cachedPage",
            GLOB_BRACE
        );

        if ($items) {
            $cachedPage  = basename($items[0]);
            $pageOnCache = true;
        }

    }

    if (!$pageOnCache) {


        /**
         * run route process, execute module, controller, action,
         * check exists layout,
         * SEO: check for unused request parameters
         */

        router::init();
        view::checkLayout();
        request::checkUnusedParams();

    } else {
        view::readFromCache($cachedPage);
    }

} catch (Exception $e) {
    view::assignException($e, $config->system->debug_mode);
}

view::draw();


