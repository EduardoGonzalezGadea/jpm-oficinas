<?php
// Router para servidor built-in de PHP con Laravel
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Servir archivos estáticos si existen
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Todo lo demás pasa por index.php (Laravel)
require_once __DIR__ . '/index.php';
