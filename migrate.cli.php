<?php

$shortOpts  = "";
$shortOpts .= "h::"; // action
$longOpts = array(
    'host::',
);
$options = getopt($shortOpts, $longOpts);

$host = 'www.oog-uitzendingen.nl.dev';

if(array_key_exists('host', $options)) {
    $host = $options['host'];
}


define('OOG_UITZENDINGEN_CLI_MODE', true);
define('DOING_AJAX', true);
define('WP_USE_THEMES', false);
$_SERVER = array(
    "HTTP_HOST" => $host,
    "SERVER_NAME" => $host,
    "REQUEST_URI" => "/",
    "REQUEST_METHOD" => "GET"
);
require_once(__DIR__  . '/../../../wp-load.php');
include_once 'autoloader.php';

$migrate = new \oog\uitzendingen\Migrate();
$migrate->doMigrate();