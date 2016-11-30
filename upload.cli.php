<?php

define('WP_PLUGIN_DIR', __DIR__ . '/../' );
define('ROOT_DIR', __DIR__);

include_once 'autoloader.php';


$uploader = new \oog\uitzendingen\upload\Upload(__DIR__);
$uploader->checkForFiles();
