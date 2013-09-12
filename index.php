<?php



/**
 * memory usage and timer
 */

$memory = memory_get_usage();
$timestart = microtime(true);


/**
 * defined application path
 */

define("PUBLIC_HTML", dirname(__FILE__) . "/");
define("APPLICATION", PUBLIC_HTML . "application/");


/**
 * loading bootstrap
 */

$bootstrap = APPLICATION . "bootstrap.php";
if (!file_exists($bootstrap)) {
    exit("Bootstrap file $bootstrap not found" . PHP_EOL);
}

require_once $bootstrap;



