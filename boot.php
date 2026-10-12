<?php
/*
 * boot.php — DO NOT EDIT.
 * First line of every page in your module:
 *     require __DIR__ . '/boot.php';         (pages in the module folder)
 *     require __DIR__ . '/../boot.php';      (pages in api/, addon/, admin/ …)
 * It finds shared/ both on your own XAMPP and on the college portal.
 */
if (!defined('MODULE_DIR')) {
    define('MODULE_DIR', __DIR__);
}
foreach ([__DIR__ . '/shared', dirname(__DIR__, 2) . '/shared'] as $__shared) {
    if (is_file($__shared . '/bootstrap.php')) {
        require_once $__shared . '/bootstrap.php';
        unset($__shared);
        return;
    }
}
http_response_code(500);
exit('The shared/ folder is missing. Download it again from the starter template.');
