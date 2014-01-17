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

    if (!file_exists($path)) {
        exit('Core dependency target ' . $path . ' is not exists' . PHP_EOL);
    }

    if ($type == IS_DIR and !is_dir($path)) {
        exit('Core dependency target ' . $path . ' is not directory' . PHP_EOL);
    } else if ($type == IS_FILE and !is_file($path)) {
        exit('Core dependency target ' . $path . ' is not file' . PHP_EOL);
    }

    if ($isWritable) {
        if (!is_writable($path)) {
            exit(
                'Core dependency target ' . $path .
                ' don\'t have writable permission' . PHP_EOL
            );
        }
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
    'layouts/themes',
    'library',
    'logs',
    'metadata',
    'modules',
    'prototypes',
    'resources',
    'resources/watermarks',
    'upload'
);

foreach ($requiredDirectories as $item) {
    $dirPath = ($item == 'upload' ? PUBLIC_HTML : APPLICATION) . $item;
    checkPath($dirPath, IS_DIR, IS_WRITABLE);
}

$requiredUnwritabeDirectories = array(
    'layouts/themes/default',
    'modules/search',
    'modules/sitemap',
    'modules/sitemap_xml'
);

foreach ($requiredUnwritabeDirectories as $item) {
    checkPath(APPLICATION . $item, IS_DIR);
}

$requiredInnerWritabeDirectories = array(
    'layouts/themes/default/parts',
    'layouts/themes/default/protected',
    'layouts/themes/default/public'
);

foreach ($requiredInnerWritabeDirectories as $item) {
    checkPath(APPLICATION . $item, IS_DIR, IS_WRITABLE);
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
    'admin/controllers/variables_js.php',
    'admin/in-menu/groups.php',
    'admin/in-menu/menu.php',
    'admin/in-menu/modules.php',
    'admin/in-menu/tree.php',
    'admin/in-menu/users.php',
    'autorun/before/A_globalMemberLoginAttempt.php',
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
    'layouts/admin/protected/variables.html',
    'layouts/system/debug.html',
    'layouts/system/raw.html',
    'layouts/themes/default/parts/footer.html',
    'layouts/themes/default/parts/header.html',
    'layouts/themes/default/protected/exception.html',
    'layouts/themes/default/protected/search.html',
    'layouts/themes/default/protected/sitemap.html',
    'layouts/themes/default/public/page.html',
    'modules/search/search.php',
    'modules/sitemap/sitemap.php',
    'modules/sitemap_xml/autoloaded',
    'modules/sitemap_xml/sitemap_xml.php',
    'prototypes/mainModule.php',
    'prototypes/mainModuleProtoModel.php',
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
 * check main configuration files
 */

$mainConfigFile = APPLICATION . 'config/main.json';
checkPath($mainConfigFile, IS_FILE, IS_WRITABLE);
if (!app::loadJsonFile($mainConfigFile)) {
    exit('Configuration file ' . $mainConfigFile 
            . ' is broken or have syntax error' . PHP_EOL);
}

$generatedConfigFile = APPLICATION . 'config/main.json.generated';
if (file_exists($generatedConfigFile)) {
    checkPath($generatedConfigFile, IS_FILE, IS_WRITABLE);
    if (!app::loadJsonFile($generatedConfigFile)) {
        exit('Configuration file ' . $generatedConfigFile 
                . ' is broken or have syntax error' . PHP_EOL);
    }
}


/**
 * check current main.log file
 */

$mainLogFile = APPLICATION . 'logs/main.log';
if (file_exists($mainLogFile)) {
    checkPath($mainLogFile, IS_FILE, IS_WRITABLE);
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
    'preferences.php',
    'search.php',
    'simpleLink.php',
    'simpleLinkProtoModel.php',
    'simplePage.php',
    'simplePageProtoModel.php',
    'themeUtils.php',
    'tree.php',
    'users.php'
);

foreach (languageUtils::getLanguagePaths() as $item) {
    checkPath($item, IS_DIR, IS_WRITABLE);
    foreach ($requiredLanguageFiles as $file) {
        checkPath($item . '/' . $file, IS_FILE);
    }
}






