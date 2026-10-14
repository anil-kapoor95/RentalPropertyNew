<?php
if (!headers_sent())
{
    // A session id in the URL is only accepted for the public booking pages (embedded
    // widgets on other sites that cannot keep a cookie) - never for the back-office,
    // where it would allow session fixation.
    $pj_public = isset($_GET['controller']) && in_array($_GET['controller'], array('pjFront', 'pjListings'), true);
    if ($pj_public && isset($_GET['session_id']) && is_string($_GET['session_id']) && preg_match('/^[a-zA-Z0-9,-]{20,128}$/', trim($_GET['session_id'])))
    {
        session_id(trim($_GET['session_id']));
    }
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.use_strict_mode', '1');
    session_name('RPBCalendarV2');
    @session_start();
}
// Error output can be switched on only by a logged-in administrator.
$pj_is_admin = isset($_SESSION['admin_user']) && is_array($_SESSION['admin_user']) && isset($_SESSION['admin_user']['role_id']) && (int) $_SESSION['admin_user']['role_id'] === 1;
if ($pj_is_admin && isset($_GET["reporting"]) && $_GET["reporting"] == '0')
{
    $_SESSION["error_reporting"] = '0';
} else if ($pj_is_admin && isset($_GET["reporting"]) && $_GET["reporting"]== '1') {
    $_SESSION["error_reporting"] = '1';
}
if ($pj_is_admin && isset($_SESSION["error_reporting"]) && $_SESSION["error_reporting"]=='1')
{
    ini_set("display_errors", "On");
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}
header("Content-type: text/html; charset=utf-8");
if (!defined("ROOT_PATH"))
{
    define("ROOT_PATH", dirname(__FILE__) . '/');
}
$_SERVER['PHP_SELF'] = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES);
require ROOT_PATH . 'app/config/options.inc.php';
require_once PJ_FRAMEWORK_PATH . 'pjAutoloader.class.php';
pjAutoloader::register();
if (!isset($_GET['controller']) || empty($_GET['controller']))
{
    header("HTTP/1.1 301 Moved Permanently");
    pjUtil::redirect(PJ_INSTALL_URL . basename($_SERVER['PHP_SELF'])."?controller=pjBase&action=pjActionIndex");
}

if (isset($_GET['controller']))
{
    $pjObserver = pjObserver::factory();
    $pjObserver->init();
}
?>