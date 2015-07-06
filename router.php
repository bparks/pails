<?php
/**
 * @file
 * The router.php for clean-urls when use PHP 5.4.0 built in webserver.
 *
 * Usage:
 *
 * php -S localhost:8888 .htrouter.php
 *
 */

//Change directory to the webroot
chdir($_SERVER['DOCUMENT_ROOT']);

$url = parse_url($_SERVER["REQUEST_URI"]);
if (!($url['path'] == '/') && file_exists('.' . $url['path'])) {
  // Serve the requested resource as-is.
  return FALSE;
}
include __DIR__.'/index.php';