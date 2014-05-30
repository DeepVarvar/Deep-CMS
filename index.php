<?php


/**
 * memory usage and timer
 */

$memory = memory_get_usage();
$timestart = microtime(true);


/**
 * defined application environment
 */

define('PUBLIC_HTML', dirname(__FILE__) . '/');
define('APPLICATION', PUBLIC_HTML . 'application/');

mb_internal_encoding('UTF-8');


/**
 * loading bootstrap
 */

$boot = APPLICATION . 'bootstrap.php';
if (!is_file($boot)) {
    exit('Bootstrap file ' . $boot . ' not found or don\'t have read permission');
}

require_once $boot;


