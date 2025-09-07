<?php
// router.php - Router cho PHP built-in server trên Render
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = __DIR__ . $uri;

// Nếu là file tĩnh thì trả về trực tiếp
if ($uri !== '/' && file_exists($path) && !is_dir($path)) {
    return false;
}

// Ngược lại load index.php
require __DIR__ . '/index.php';
