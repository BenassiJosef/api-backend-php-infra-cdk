<?php

$origin = '*';
$headers = apache_request_headers();

if (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
} else if (array_key_exists('Origin', $headers)) {
    $origin = $headers['Origin'];
}

if (strpos($origin, 'nearly.online') !== false) {
    $origin = '*';
}


header("Access-Control-Allow-Origin: " . $origin);
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, Location, Cache-Control, X-Requested-With, Stmpd-Key, Stmpd-Cookie");
header("Access-Control-Expose-Headers: Location");

newrelic_set_appname(getenv('NRIA_DISPLAY_NAME'));

require __DIR__ . '/../app/app.php';
