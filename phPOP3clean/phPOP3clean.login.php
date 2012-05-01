<?php
require_once('phPOP3clean.functions.php');

function IsAdminUser() {
	return ((@$_COOKIE['phPOP3cleanUSER'] === 'admin') && (@$_COOKIE['phPOP3cleanPASS'] === md5(PHPOP3CLEAN_ADMINPASS)));
}
function IsAuthenticatedUser() {
	if (IsAdminUser()) {
		return true;
	}
	if (isset($_COOKIE['phPOP3cleanUSER']) && isset($_COOKIE['phPOP3cleanPASS'])) {
		$SQLquery  = 'SELECT `password` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts`';
		$SQLquery .= ' WHERE (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
		$result = mysql_query_safe($SQLquery);
		if ($row = mysql_fetch_array($result)) {
			if ($_COOKIE['phPOP3cleanPASS'] === md5($row['password'])) {
				return true;
			}
		}
	}
	return false;
}

if (isset($_POST['admin_login_user']) && isset($_POST['admin_login_pass'])) {
	setcookie('phPOP3cleanUSER',     $_POST['admin_login_user']);
	setcookie('phPOP3cleanPASS', md5($_POST['admin_login_pass']));

	if (!isset($_POST['params']) || !is_array($_POST['params'])) {
		$_POST['params'] = array();
	}
	$paramstring = array();
	foreach ($_POST['params'] as $key => $value) {
		$paramstring[] = urlencode($key).'='.urlencode($value);
	}
	header('Location: '.$_SERVER['PHP_SELF'].'?'.implode('&', $paramstring));
	exit;
}

$loginOK = false;
if (isset($_COOKIE['phPOP3cleanUSER']) && isset($_COOKIE['phPOP3cleanPASS'])) {

	$loginOK = (IsAdminUser() || IsAuthenticatedUser());

} elseif (!defined('PHPOP3CLEAN_NONFORCEDLOGIN')) {

	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	echo '<html><head><title>phPOP3clean :: Login</title><style type="text/css">body,td,th { font-family: sans-serif; font-size: 9pt; }</style></head><body>';
	echo '<form name="loginform" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	foreach ($_GET as $key => $value) {
		if (is_string($value) || is_int($value) || is_float($value)) {
			echo '<input type="hidden" name="params['.htmlentities(urlencode($key)).']" value="'.htmlentities(urlencode($value)).'">';
		}
	}
	echo '<table border="0">';
	echo '<tr><th align="right">User:</th><td><input type="text"     size="20" name="admin_login_user"></td></tr>';
	echo '<tr><th align="right">Pass:</th><td><input type="password" size="20" name="admin_login_pass"></td></tr>';
	echo '<tr><th colspan="2"><input type="submit" value="Login"></th></tr>';
	echo '</table>';
	echo '</form>';
	echo '<script type="text/javascript">document.loginform.admin_login_user.focus();</script>';
	echo '</body></html>';
	exit;

}

if (isset($_REQUEST['logout']) || ($loginOK !== true)) {
	if (isset($_COOKIE['phPOP3cleanUSER']) || isset($_COOKIE['phPOP3cleanPASS'])) {
		setcookie('phPOP3cleanUSER', '');
		setcookie('phPOP3cleanPASS', '');
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}
}
unset($loginOK);
?>