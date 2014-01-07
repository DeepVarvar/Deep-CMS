<?php


/**
 * unexpected exception wrapper class,
 * only for exit application
 */

class unexpectedException {


    public static function take($e, $isDebugMode = false) {

        if ($e instanceof systemException) {
            $report = $e->getReport();
            if ($isDebugMode) {
                dump($report);
            } else {
                echo 'Unexpected system ' . $report['type']
                        . ' exception inside catch context' . PHP_EOL;
            }
        } else {
            if ($isDebugMode) {
                dump($e->getMessage(), $e->getTrace());
            } else {
                echo 'Unexpected exception inside catch context' . PHP_EOL;
            }
        }

    }


}


