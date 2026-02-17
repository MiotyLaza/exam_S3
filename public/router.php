<?php

$requestedPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$fullPath = __DIR__ . $requestedPath;

if ($requestedPath !== '/' && file_exists($fullPath) && !is_dir($fullPath)) {
    return false;
}

require __DIR__ . '/index.php';
