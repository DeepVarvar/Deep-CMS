<?php


/**
 * slow running mode,
 * full check exists files and directories,
 * full check permissions,
 * full check fragmentation filesystem
 */

define('IS_DIR',  true);
define('IS_FILE', false);
define('IS_WRITABLE', true);

function checkPath($path, $type, $isWritable = false) {

    $target = $type == IS_DIR ? 'Directory: ' : 'File: ';
    if (!file_exists($path)) {
        exit($target . $path . ' is not exists');
    }

    if (!is_readable($path)) {
        exit($target . $path . ' is not readable');
    } else if ($type == IS_DIR and !is_dir($path)) {
        exit($target . $path . ' is not directory');
    } else if ($type == IS_FILE and !is_file($path)) {
        exit($target . $path . ' is not file');
    }

    if ($isWritable and !is_writable($path)) {
        exit($target . $path . ' don\'t have writable permission');
    }

}


/**
 * check required directories
 */

$requiredDirectories = array(
    'admin/controllers',
    'admin/in-menu',
    'autorun/after',
    'autorun/before',
    'cache',
    'config',
    'languages',
    'layouts/admin/parts',
    'layouts/admin/protected',
    'library',
    'logs',
    'metadata',
    'modules',
    'prototypes',
    'resources',
    'upload'
);

foreach ($requiredDirectories as $item) {
    $dirPath = ($item == 'upload' ? PUBLIC_HTML : APPLICATION) . $item;
    checkPath($dirPath, IS_DIR, IS_WRITABLE);
}

$requiredUnwritabeDirectories = array(
    'modules/deep_cms_info',
    'modules/search',
    'modules/sitemap',
    'modules/sitemap_xml',
    'resources/watermarks'
);

foreach ($requiredUnwritabeDirectories as $item) {
    checkPath(APPLICATION . $item, IS_DIR);
}


/**
 * check required files
 */

$requiredFiles = array(
    'admin/admin.php',
    'admin/controllers/events.php',
    'admin/controllers/groups.php',
    'admin/controllers/manage_components.php',
    'admin/controllers/menu.php',
    'admin/controllers/node_features.php',
    'admin/controllers/node_images.php',
    'admin/controllers/preferences.php',
    'admin/controllers/tree.php',
    'admin/controllers/users.php',
    'admin/in-menu/groups.php',
    'admin/in-menu/menu.php',
    'admin/in-menu/modules.php',
    'admin/in-menu/tree.php',
    'admin/in-menu/users.php',
    'autorun/before/queue50_languageTrigger.php',
    'autorun/before/queue100_globalMemberLoginAttempt.php',
    'core/adminHelper.php',
    'core/app.php',
    'core/arrayUtils.php',
    'core/autorun.php',
    'core/baseController.php',
    'core/basePrototypeModel.php',
    'core/baseTreeNode.php',
    'core/commandLine.php',
    'core/controllerUtils.php',
    'core/dataHelper.php',
    'core/db.php',
    'core/filter.php',
    'core/fsUtils.php',
    'core/helper.php',
    'core/htmlHelper.php',
    'core/install.php',
    'core/languageUtils.php',
    'core/layoutUtils.php',
    'core/member.php',
    'core/memberErrorException.php',
    'core/memberException.php',
    'core/memberRefreshErrorException.php',
    'core/memberRefreshSuccessException.php',
    'core/memberSuccessException.php',
    'core/node.php',
    'core/paginator.php',
    'core/permissionUtils.php',
    'core/protoUtils.php',
    'core/recalculatePermissions.php',
    'core/request.php',
    'core/simpleImage.php',
    //'core/slowRunning.php', - It's me, where is magic?
    'core/storage.php',
    'core/systemErrorException.php',
    'core/systemException.php',
    'core/textPlainOutput.php',
    'core/themeUtils.php',
    'core/unexpectedException.php',
    'core/validate.php',
    'core/view.php',
    'core/xmlOutput.php',
    'core/xmlValidator.php',
    'layouts/admin/parts/footer.html',
    'layouts/admin/parts/header.html',
    'layouts/admin/protected/documents-tree.html',
    'layouts/admin/protected/events.html',
    'layouts/admin/protected/exception.html',
    'layouts/admin/protected/group-edit.html',
    'layouts/admin/protected/group-new.html',
    'layouts/admin/protected/groups.html',
    'layouts/admin/protected/install.html',
    'layouts/admin/protected/install-exception.html',
    'layouts/admin/protected/login-form.html',
    'layouts/admin/protected/manage-components.html',
    'layouts/admin/protected/menu.html',
    'layouts/admin/protected/menu-edit.html',
    'layouts/admin/protected/menu-new.html',
    'layouts/admin/protected/node-edit.html',
    'layouts/admin/protected/node-features.html',
    'layouts/admin/protected/node-images.html',
    'layouts/admin/protected/node-new.html',
    'layouts/admin/protected/preferences.html',
    'layouts/admin/protected/user-edit.html',
    'layouts/admin/protected/user-new.html',
    'layouts/admin/protected/users.html',
    'layouts/system/debug.html',
    'layouts/system/raw.html',
    'layouts/themes/default/protected/search.html',
    'layouts/themes/default/protected/sitemap.html',
    'library/languageSelect.php',
    'modules/deep_cms_info/autoloaded',
    'modules/deep_cms_info/deep_cms_info.php',
    'modules/search/search.php',
    'modules/sitemap/sitemap.php',
    'modules/sitemap_xml/autoloaded',
    'modules/sitemap_xml/sitemap_xml.php',
    'prototypes/mainModule.php',
    'prototypes/mainModuleProtoModel.php',
    'prototypes/nodeGroup.php',
    'prototypes/nodeGroupProtoModel.php',
    'prototypes/simpleLink.php',
    'prototypes/simpleLinkProtoModel.php',
    'prototypes/simplePage.php',
    'prototypes/simplePageProtoModel.php',
    'resources/watermarks/preview.png',
    'resources/watermarks/sample.png'
);

foreach ($requiredFiles as $item) {
    checkPath(APPLICATION . $item, IS_FILE);
}


/**
 * check language directories
 */

$requiredLanguageFiles = array(
    'admin.php',
    'app.php',
    'events.php',
    'groups.php',
    'helper.php',
    'install.php',
    'mainModule.php',
    'mainModuleProtoModel.php',
    'manage_components.php',
    'menu.php',
    'node_features.php',
    'node_images.php',
    'nodeGroup.php',
    'nodeGroupProtoModel.php',
    'preferences.php',
    'search.php',
    'simpleLink.php',
    'simpleLinkProtoModel.php',
    'simplePage.php',
    'simplePageProtoModel.php',
    'sitemap.php',
    'tree.php',
    'users.php'
);

$languageDirectories = languageUtils::getLanguagePaths();
foreach ($languageDirectories as $item) {
    checkPath($item, IS_DIR, IS_WRITABLE);
    foreach ($requiredLanguageFiles as $file) {
        checkPath($item . '/' . $file, IS_FILE);
    }
}


/**
 * check themes
 */

$requiredThemeDirs = array(
    'parts'     => array('footer.html', 'header.html'),
    'protected' => array('exception.html'),
    'public'    => array('page.html')
);

$existsThemes = fsUtils::glob(APPLICATION . 'layouts/themes/*');
foreach ($existsThemes as $theme) {
    checkPath($theme, IS_DIR);
    foreach ($requiredThemeDirs as $dir => $files) {
        checkPath($theme . '/' . $dir, IS_DIR, IS_WRITABLE);
        foreach ($files as $file) {
            checkPath($theme . '/' . $dir . '/' . $file, IS_FILE);
        }
    }
}


/**
 * check main configuration files
 */

$mainConfigFile = APPLICATION . 'config/main.json';
checkPath($mainConfigFile, IS_FILE, IS_WRITABLE);
$generatedConfigFile = APPLICATION . 'config/main.json.generated';
if (file_exists($generatedConfigFile)) {
    checkPath($generatedConfigFile, IS_FILE, IS_WRITABLE);
}


/**
 * check current main.log file
 */

$mainLogFile = APPLICATION . 'logs/main.log';
if (file_exists($mainLogFile)) {
    checkPath($mainLogFile, IS_FILE, IS_WRITABLE);
}


/**
 * check installed components
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
    'language_files'        => true,
    'template_files'        => true,
    'create_db_tables'      => true,
    'drop_db_tables'        => true,
    'alter_db_tables'       => true,
    'dealter_db_tables'     => true

);

$dcmFiles = fsUtils::glob(APPLICATION . 'metadata/*.dcm');
foreach ($dcmFiles as $dcmFile) {

    checkPath($dcmFile, IS_FILE, IS_WRITABLE);
    if (!$dcmData = app::loadJsonFile($dcmFile, true) or !is_array($dcmData)) {
        exit('Metadata file ' . $dcmFile . ' is broken or have syntax error');
    }

    if (array_keys($dcmData) !== array_keys($expectedKeys)) {
        exit('Metadata file ' . $dcmFile . ' is broken syntax');
    }

    $dcmBrokenInfo = array();
    foreach ($expectedKeys as $key => $val) {

        // check array
        if ($val) {
            if (!is_array($dcmData[$key])) {
                $dcmBrokenInfo['key'] = $key;
            }
            foreach ($dcmData[$key] as $k => $string) {
                if (!is_string($string)) {
                    $dcmBrokenInfo['subkey'] = $k;
                }
            }
        // check string
        } else if (!is_string($dcmData[$key])) {
            $dcmBrokenInfo['key'] = $key;
        }

        // exit if broken
        if ($dcmBrokenInfo) {
            $subkey = array_key_exists('subkey', $dcmBrokenInfo)
                ? '[' . $dcmBrokenInfo['subkey'] . ']' : '';
            exit('Metadata value of ' . $dcmBrokenInfo['key'] . $subkey . ' is broken on ' . $dcmFile);
        }

        // check directories
        if ($key == 'main_directories') {
            foreach ($dcmData[$key] as $dir) {
                checkPath(APPLICATION . $dir, IS_DIR, IS_WRITABLE);
            }
        }

        // check files
        if ($key == 'main_files') {
            foreach ($dcmData[$key] as $file) {
                checkPath(APPLICATION . $file, IS_FILE, IS_WRITABLE);
            }
        }

        // check language files
        if ($key == 'language_files') {
            foreach ($languageDirectories as $lang) {
                foreach ($dcmData[$key] as $file) {
                    checkPath($lang . '/' . $file, IS_FILE, IS_WRITABLE);
                }
            }
        }

        // check template files
        if ($key == 'template_files') {
            foreach ($existsThemes as $theme) {
                if (is_dir($theme)) {
                    foreach ($dcmData[$key] as $file) {
                        checkPath($theme . '/' . $file, IS_FILE, IS_WRITABLE);
                    }
                }
            }
        }

    }

}


