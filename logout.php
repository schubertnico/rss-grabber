<?php
/**
 * -----------------------------------------
 * RSS Grabber free v3.0 - Abmeldung
 * -----------------------------------------
 * @version free v3.0 (PHP 8.5)
 */
require_once(__DIR__ . '/inc/auth.php');

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie((string)session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

if (headers_sent() === false) {
    header('Location: ./login.php');
}
exit;
