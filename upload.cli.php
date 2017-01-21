<?php

$rootDir = __DIR__;
if(file_exists(__DIR__ . '/settings.json')) {
    $settings = file_get_contents(__DIR__ . '/settings.json');
    $settings = json_decode($settings);

    if(isset($settings->root_dir)) {
        $rootDir = $settings->root_dir;
    }
}

define('WP_PLUGIN_DIR', __DIR__ . '/../');
define('ROOT_DIR', $rootDir);
define('SCRIPT_DIR', __DIR__);

include_once 'autoloader.php';
$uploader = new \oog\uitzendingen\upload\Upload(ROOT_DIR);
$uploader->checkForFiles();
