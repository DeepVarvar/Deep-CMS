<?php


/**
 * proto model types utilites
 */

abstract class protoUtils {


    /**
     * get default field element array
     */

    public static function getDefaultField($value) {

        return array(
            'top'         => 0,
            'sort'        => 0,
            'required'    => false,
            'editor'      => 0,
            'description' => 'Unnamed text field',
            'type'        => 'text',
            'selector'    => 'f' . md5(mt_rand() . microtime(true)),
            'value'       => $value
        );

    }


    /**
     * return prototypes names array
     */

    public static function getAvailableProtoTypes() {

        $prototypes = array();
        foreach (fsUtils::glob(APPLICATION . 'prototypes/*.php') as $item) {
            $protoName = basename($item, '.php');
            if (preg_match('/ProtoModel$/', $protoName)) {
                continue;
            }
            $prototypes[] = $protoName;
        }
        return $prototypes;

    }


    /**
     * return array list of available
     * frequency values for sitemap (SEO)
     */

    public static function getAvailableChangeFreq() {
        return array(
            '---','never','yearly','monthly','weekly','daily','hourly','always'
        );
    }


    /**
     * return array list of available
     * priority range values for sitemap (SEO)
     */

    public static function getAvailableSearchersPriority() {
        return array(
            '---','0.1','0.2','0.3','0.4','0.5','0.6','0.7','0.8','0.9','1.0'
        );
    }


    /**
     * return options array
     */

    public static function makeOptionsArray($inputArr, $value = null) {

        $options = array();
        foreach ($inputArr as $item) {
            $option = array('value' => $item, 'description' => $item);
            if ($value == $item) {
                $option['selected'] = true;
            }
            array_push($options, $option);
        }
        return $options;

    }


    /**
     * normalize URL string
     */

    public static function normalizeInputUrl($url, $errorMessage) {

        if ($url and $url != '/') {

            $patterns = array("/['\"\\\]+/", '/[-\s]+/');
            $replace  = array('', '-');
            $url = substr(preg_replace($patterns, $replace, $url), 0, 255);

            $domain  = '(?P<domain>(?:(?:f|ht)tps?';
            $domain .= ':\/\/[-a-z0-9]+(?:\.[-a-z0-9]+)*)?)';

            $path   = '(?P<path>(?:[^\?]*)?)';
            $params = '(?P<params>(?:\?[^=&]+=[^=&]+(?:&[^=&]+=[^=&]+)*)?)';
            $hash   = '(?P<hash>(?:#.*)?)';

            preg_match('/^' . $domain . '\/'
                . $path . $params . $hash . '$/s', $url, $m);

            if (!$m) {
                throw new memberErrorException(
                    view::$language->app_error, $errorMessage
                );
            }

            $cParts = array();
            $sParts = trim(preg_replace('/\/+/', '/', $m['path']), '/');

            foreach (explode('/', $sParts) as $part) {
                array_push($cParts, rawurlencode($part));
            }

            $m['path'] = '/' . join('/' , $cParts);

            $confDomain = app::config()->site->domain;
            if ($m['domain'] and stristr($confDomain, $m['domain'])) {
                $m['domain'] = '';
            }

            if ($m['params'] and $m['domain']) {

                $cParts = array();
                $sParts = trim(preg_replace('/&+/', '&', $m['params']), '&');
                foreach (explode('&', $sParts) as $part) {
                    array_push($cParts, rawurlencode($part));
                }

                $m['params'] = '?' . join('&' , $cParts);

            } else {
                $m['params'] = '';
            }

            if ($m['hash']) {
                $m['hash'] = rawurlencode(trim($m['hash'], '#'));
            }

            $url = $m['domain'] . $m['path'] . $m['params'] . $m['hash'];

        }

        return $url;

    }


    /**
     * return array of available public modules
     */

    public static function getAvailablePublicModules() {

        $existsTargets = fsUtils::glob(APPLICATION . 'modules/*', GLOB_ONLYDIR);
        $existsTargets = array_merge(array('---'), $existsTargets);

        $availableModules = array();
        foreach ($existsTargets as $item) {
            if (!file_exists($item . '/autoloaded')) {
                $availableModules[] = basename($item);
            }
        }
        return $availableModules;

    }


}


