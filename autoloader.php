<?php
if(!function_exists('oog_autoloader')) {

    function oog_autoloader($classname) {
        if(strpos($classname, 'oog\\') === 0) {
            $packages = explode('\\', $classname);

            $path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'oog' . $packages[1] . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, $packages) . '.php';
            if(file_exists($path)) {
                include_once $path;
                return true;
            }
        }
        return false;
    }
    spl_autoload_register('oog_autoloader');
}

define('OOG_UITZENDINGEN_PLUGIN_FILE', __DIR__ . '/index.php');
define('OOG_UITZENDINGEN_PLUGIN_DIR', __DIR__);

if(file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else if(defined('ABSPATH') && file_exists(ABSPATH . '/vendor/autoload.php')) {
    require ABSPATH . '/vendor/autoload.php';

}
