<?php


/**
 * admin submodule, manage site components
 */

class manage_components extends baseController {


    /**
     * uploaded file local path
     */

    private $uploadedFilePath = null;


    /**
     * main remote sources base URL
     */

    private $remoteSourcesURL = null;


    /**
     * main request params
     */

    private $params = array();


    /**
     * main remote action and remote target
     */

    private $remoteAction = null;
    private $remoteTarget = null;


    /**
     * dcm file path,
     * dcm data of current component,
     * dcm files map,
     * dcm language files map,
     * dcm directories map
     */

    private $dcmFilePath    = null;
    private $dcmData        = array();
    private $dcmFiles       = array();
    private $dcmLanguages   = array();
    private $dcmDirectories = array();


    /**
     * available languages and themes
     */

    private $availableLanguages = array();
    private $availableThemes    = array();


    /**
     * set permissions for this controller
     */

    public function setPermissions() {
        $this->permissions = array();
    }


    /**
     * check environment
     */

    public function runBefore() {

        if (!extension_loaded('curl')) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->components_curl_is_not_available
            );
        }

        $app = app::config();
        $this->remoteSourcesURL = $app->site->protocol
             . '://' . $app->application->sources_domain . '/';

        $userAgent = $app->application->name . '-' . $app->application->version;
        $appDomain = $app->site->protocol . '://' . $app->site->domain . '/';
        $this->params = array(
            'appname'    => $app->application->name,
            'appversion' => $app->application->version,
            'useragent'  => $userAgent,
            'appdomain'  => $appDomain,
            'language'   => member::getLanguage()
        );

    }


    /**
     * main components list
     */

    public function index() {

        $this->remoteAction = 'sourcelist';
        $sourcelist = $this->getRemoteData();

        $sortBy = array();
        $dcmPath = APPLICATION . 'metadata/';
        foreach ($sourcelist as $k => $item) {

            $dcmFile = $dcmPath . $item['system_name'] . '.dcm';
            $sourcelist[$k]['installed'] = file_exists($dcmFile);
            if ($sourcelist[$k]['installed']) {

                $dcmData = app::loadJsonFile($dcmFile, true);
                $sourcelist[$k]['deprecated'] = ($item['version'] != $dcmData['version']);
                $sourcelist[$k]['version'] = $dcmData['version'];
                $sourcelist[$k]['new_version'] = $item['version'];

            }

            unset($dcmData);
            array_push($sortBy, $item['type']);

        }

        array_multisort($sortBy, $sourcelist);
        view::assign('sourcelist', $sourcelist);

        view::assign('node_name', view::$language->components_manage);
        $this->setProtectedLayout('manage-components.html');

    }


    /**
     * install component
     */

    public function install() {

        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer($adminToolsLink . '/manage-components');

        $target = request::getParam('target');
        if (!$target) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->components_empty_target
            );
        }


        /**
         * receive component metadata file,
         * build and check data of component
         */

        $this->remoteAction = 'download';
        $this->remoteTarget = 'metadata/' . $target . '.dcm';
        $this->saveRemoteFile();

        $this->dcmFilePath = $this->uploadedFilePath;
        $this->dcmData = app::loadJsonFile($this->dcmFilePath, true);
        if (!$this->checkDcmDataFormat()) {
            $this->componentErrorException(
                view::$language->error, view::$language->components_invalid_dcm_format
            );
        }


        /**
         * check dependency components
         */

        if ($this->dcmData['dependency_components']) {
            $depends = join(', ', $this->dcmData['dependency_components']);
            $this->componentErrorException(
                view::$language->error,
                view::$language->components_dependency_exists . ': ' . $depends
            );
        }


        /**
         * create component directories
         */

        foreach ($this->dcmData['main_directories'] as $directory) {
            $directory = APPLICATION . $directory;
            if (!is_file($directory)) {
                $this->componentErrorException(
                    view::$language->error,
                    view::$language->components_undefined_type
                );
            }
            if (!is_dir($directory)) {
                mkdir($directory);
            }
        }


        /**
         * create/rewrite component files
         */

        foreach ($this->dcmData['main_files'] as $file) {

            $this->remoteAction = 'download';
            $this->remoteTarget = $file;
            $this->saveRemoteFile();

            if (preg_match('/^layouts\/themes\/default\//', $file)) {

                foreach ($this->getAvailableThemes() as $theme) {
                    $themeFile = preg_replace(
                        '/^(layouts\/themes\/)default(\/.+)/',
                            '$1' . $theme . '$2', $file
                    );
                    copy(APPLICATION . $file, APPLICATION . $themeFile);
                }

            }

        }


        /**
         * create/rewrite component language files
         */

        foreach ($this->getAvailableLanguages() as $language) {
            foreach ($this->dcmData['language_files'] as $file) {
                $this->remoteAction = 'download';
                $this->remoteTarget = $language . '/' . $file;
                $this->saveRemoteFile();
            }
        }


        // TODO DB install


        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->components_install_success,
            $adminToolsLink . '/manage-components'
        );

    }


    /**
     * delete component
     */

    public function delete() {

        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer($adminToolsLink . '/manage-components');

        $target = request::getParam('target');
        if (!$target) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->components_empty_target
            );
        }

        $this->dcmFilePath = APPLICATION . 'metadata/' . $target . '.dcm';
        if (!file_exists($this->dcmFilePath)) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->components_component_not_found
            );
        }

        $this->dcmData = app::loadJsonFile($this->dcmFilePath, true);
        if (!$this->checkDcmDataFormat()) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->components_invalid_dcm_format
            );
        }

        $this->deleteAllDcmItems();


        // TODO DB deinstall


        $this->redirectMessage(
            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->components_delete_success,
            $adminToolsLink . '/manage-components'
        );

    }


    /**
     * check dcm data format,
     * mapping all directories and files of component
     */

    private function checkDcmDataFormat() {

        // mapped current dcm file
        $this->dcmFiles[] = preg_replace(
            '/^' . preg_quote(APPLICATION, '/') . '/', '', $this->dcmFilePath
        );

        if (!$this->dcmData or !is_array($this->dcmData)) {
            return false;
        }


        /**
         * keys of array   - expected dcm data keys
         * values of array - expected data type of values
         *
         * values map:
         *
         *   false - string
         *   true  - array
         *
         */

        $expectedKeys = array(

            'type'                  => false,
            'system_name'           => false,
            'version'               => false,
            'author'                => false,
            'support_email'         => false,
            'main_url'              => false,
            'description'           => false,
            'dependency_components' => true,
            'main_directories'      => true,
            'main_files'            => true,
            'language_files'        => true

        );

        if (array_keys($this->dcmData) !== array_keys($expectedKeys)) {
            return false;
        }

        foreach ($expectedKeys as $key => $val) {

            // check array
            if ($val) {
                if (!is_array($this->dcmData[$key])) {
                    return false;
                }
                foreach ($this->dcmData[$key] as $string) {
                    if (!is_string($string)) {
                        return false;
                    }
                }
            // check string
            } else if (!is_string($this->dcmData[$key])) {
                return false;
            }

            // directories mapping
            if ($key == 'main_directories') {
                foreach ($this->dcmData[$key] as $dir) {
                    $this->dcmDirectories[] = $dir;
                }
            }

            // files mapping
            if ($key == 'main_files') {
                foreach ($this->dcmData[$key] as $file) {
                    $this->dcmFiles[] = $file;
                }
            }

            // language files mapping
            if ($key == 'language_files') {
                foreach ($this->dcmData[$key] as $file) {
                    $this->dcmLanguages[] = $file;
                }
            }

        }

        return true;

    }


    /**
     * return remote file contents
     */

    private function getRemoteData() {
        return $this->getRemoteResponse();
    }


    /**
     * upload remote file into temporary directory
     * return path do uploaded file
     */

    private function saveRemoteFile() {

        $this->uploadedFilePath = null;
        $this->getRemoteResponse(true);

    }


    /**
     * remote response receive wrapper
     */

    private function getRemoteResponse($save = false) {

        if (!$this->remoteAction) {
            $this->componentErrorException(
                view::$language->error,
                view::$language->components_curl_empty_action
            );
        }

        if ($save and !$this->remoteTarget) {
            $this->componentErrorException(
                view::$language->error,
                view::$language->components_curl_empty_target
            );
        }

		$ch = curl_init();
        $this->params['action'] = $this->remoteAction;
        $this->params['target'] = $this->remoteTarget;

		curl_setopt($ch, CURLOPT_URL, $this->remoteSourcesURL);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->params);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->params['useragent']);

		$content = curl_exec($ch);
        if ($content === false) {
		    curl_close($ch);
            $this->componentErrorException(
                view::$language->error, view::$language->components_curl_error
            );
        }

		curl_close($ch);
        if ($save) {
            $this->uploadedFilePath = APPLICATION . $this->remoteTarget;
            file_put_contents($this->uploadedFilePath, $content);
        } else {
            $content = @ json_decode($content, true);
            if (!$content or !$this->checkResponseFormat($content)) {
                $this->componentErrorException(
                    view::$language->error,
                    view::$language->components_curl_error
                );
            }
            return $content[$this->remoteAction];
        }

    }


    /**
     * get path's of all available writabled directories of themes
     */

    private function getAvailableThemes() {

        if (!$this->availableThemes) {
            $themesPath = APPLICATION . 'layouts/themes/*';
            $themesDirs = fsUtils::glob($themesPath, GLOB_ONLYDIR | GLOB_NOSORT);
            foreach ($themesDirs as $theme) {
                $themeName = basename($theme);
                if (is_writable($theme) and $themeName != 'default') {
                    $this->availableThemes[] = $themeName;
                }
            }
        }

        return $this->availableThemes;

    }


    /**
     * get path's of all available writabled language directories
     */

    private function getAvailableLanguages() {

        if (!$this->availableLanguages) {
            $langPath = APPLICATION . 'languages/*';
            $langDirs = fsUtils::glob($langPath, GLOB_ONLYDIR | GLOB_NOSORT);
            foreach ($langDirs as $language) {
                if (is_writable($language)) {
                    $this->availableLanguages[] = 'languages/' . basename($language);
                }
            }
        }

        return $this->availableLanguages;

    }


    /**
     * delete all component files and directories
     * availabled on dcm metadata,
     * repair CMS structure
     */

    private function deleteAllDcmItems() {

        // delete files
        foreach ($this->dcmFiles as $file) {
            unlink(APPLICATION . $file);
        }
        // delete language files
        foreach ($this->getAvailableLanguages() as $language) {
            foreach ($this->dcmLanguages as $file) {
                unlink(APPLICATION . $language . '/' . $file);
            }
        }
        // delete directories
        foreach (array_reverse($this->dcmDirectories) as $directory) {
            rmdir(APPLICATION . $directory);
        }

    }


    /**
     * check remote response format
     */

    private function checkResponseFormat($response) {

        $expextedKeys = array('status', $this->remoteAction);
        if (!is_array($response) or array_keys($response) !== $expextedKeys) {
            return false;
        }
        if (!in_array($response['status'], array(0, 1), true)) {
            return false;
        }
        if (!is_array($response[$this->remoteAction])) {
            return false;
        }
        return true;

    }


    /**
     * component error exception wrapper,
     * repair CMS structure,
     * throw exception
     */

    private function componentErrorException($title, $message) {

        $this->deleteAllDcmItems();
        throw new memberErrorException($title, $message);

    }


}


