<?php
declare(strict_types=1);

// Only explicitly public assets bypass the application in the development server.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (in_array($path, ['/styles.css', '/shop-logo.svg'], true)) {
    return false;
}
require __DIR__ . '/index.php';
