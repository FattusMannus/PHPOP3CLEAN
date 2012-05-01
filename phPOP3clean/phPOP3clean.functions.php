<?php
/////////////////////////////////////////////////////////////////
/// phPOP3clean() by James Heinrich <info@silisoftware.com>    //
//  available at http://phpop3clean.sourceforge.net            //
/////////////////////////////////////////////////////////////////

error_reporting(E_ALL);
ini_set('display_errors', '1');
$phpop3clean_datestamp = '200711060735'; // current phPOP3clean version datestamp
$need_config_datestamp = '200708200827'; // minimum version of config file required to have all config settings available
define('PHPOP3CLEAN_VERSION', '0.9.17-'.$phpop3clean_datestamp);


/*********************************************************************
* DISABLE MAGIC QUOTES AT RUNTIME
*********************************************************************/

set_magic_quotes_runtime(0);


/*********************************************************************
* AVAILABILITY OF GLOBAL VARIABLES
*********************************************************************/

if (!isset($_SERVER)  || (count($_SERVER)  === 0)) { $_SERVER = $HTTP_SERVER_VARS; }
if (!isset($_ENV)     || (count($_ENV)     === 0)) { $_ENV    = $HTTP_ENV_VARS;    }
if (!isset($_GET)     || (count($_GET)     === 0)) { $_GET    = $HTTP_GET_VARS;    }
if (!isset($_POST)    || (count($_POST)    === 0)) { $_POST   = $HTTP_POST_VARS;   }
if (!isset($_FILES)   || (count($_FILES)   === 0)) { $_FILES  = $HTTP_POST_FILES;  }
if (!isset($_COOKIE)  || (count($_COOKIE)  === 0)) { $_COOKIE = $HTTP_COOKIE_VARS; }

if (!isset($_SESSION) || (count($_SESSION) === 0)) {
	// _SESSION is the only superglobal which is conditionally set
	if (isset($HTTP_SESSION_VARS)) {
		$_SESSION = $HTTP_SESSION_VARS;
	}
}

// TO DO - GET RID OF THIS ONCE ALL USE PROPER GET OR POST OR COOKIE - BETTER SECURITY
$GPCvars = array('_GET', '_POST', '_COOKIE');
foreach ($GPCvars as $GPCvar) {
	if (isset(${$GPCvar}) && (count(${$GPCvar}) > 0)) {
		foreach (${$GPCvar} as $key => $value) {
			$_REQUEST[$key] = $value;
		}
	}
}

unset($GPCvars, $HTTP_SERVER_VARS, $HTTP_ENV_VARS, $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_POST_FILES, $HTTP_COOKIE_VARS, $HTTP_SESSION_VARS);


/*********************************************************************
* PROTECT AGAINST SOME HACKS
*********************************************************************/

// Protect against GLOBALS tricks
if (isset($_POST['GLOBALS']) || isset($_FILES['GLOBALS']) || isset($_GET['GLOBALS']) || isset($_COOKIE['GLOBALS'])) {
	die('Hacking attempt');
}

// Protect against HTTP_SESSION_VARS tricks
if (isset($_SESSION) && !is_array($_SESSION)) {
	die('Hacking attempt');
}


/*********************************************************************
* UNSET GLOBALS
*********************************************************************/

if ((@ini_get('register_globals') === '1') || (strtolower(@ini_get('register_globals')) === 'on')) {

	$do_not_unset = array('_SERVER', '_ENV', '_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', 'root_path');
	// Temporary until all $_REQUEST variables have been replaced by $_GET or $_POST
	$do_not_unset[] = '_REQUEST';

	// Not only will array_merge give a warning if a parameter is not an array,
	// it will actually fail. So we check if _SESSION has been initialised.
	if (!isset($_SESSION) || !is_array($_SESSION)) {
		$_SESSION = array();
	}

	// Merge all into one extremely huge array; unset this later
	$input = array_merge($_SERVER, $_ENV, $_GET, $_POST, $_FILES, $_COOKIE, $_SESSION);

	unset($input['input']);
	unset($input['do_not_unset']);

	while (list($var) = @each($input)) {
		if (!in_array($var, $do_not_unset)) {
			unset(${$var});
		}
	}
	unset($var, $input, $do_not_unset);

}


/*********************************************************************
* ADD SLASHES AGAINST SQL INSERTS
* addslashes to vars if magic_quotes_gpc is off this is a security precaution
* to prevent someone trying to break out of a SQL statement.
*
* Even though the ADODB native function for this will be used, this is
* maintained as an added precaution just in case new hacks find a way to break
* variable treatment in php.
*********************************************************************/
/*

*** NOT USED YET AS THE DEFAULT NOW EXPECTS *NO* SLASHES (which is much less secure and might cause all sorts of undesirable effect, but that is one to fix later)  ***


if ( !get_magic_quotes_gpc() ) {
	if ( is_array( $_GET ) ) {
		while( list( $k, $v ) = each( $_GET ) ) {
			if ( is_array( $_GET[$k] ) ) {
				while( list( $k2, $v2 ) = each( $_GET[$k] ) ) {
					$_GET[$k][$k2] = addslashes( $v2 );
				}
				@reset( $_GET[$k] );
			}
			else {
				$_GET[$k] = addslashes( $v );
			}
		}
		@reset( $_GET );
	}

	if ( is_array( $_POST ) ) {
		while( list( $k, $v ) = each( $_POST ) ) {
			if ( is_array( $_POST[$k] ) ) {
				while( list( $k2, $v2 ) = each( $_POST[$k] ) ) {
					$_POST[$k][$k2] = addslashes( $v2 );
				}
				@reset( $_POST[$k] );
			}
			else {
				$_POST[$k] = addslashes( $v );
			}
		}
		@reset($_POST);
	}

	if ( is_array( $_COOKIE ) ) {
		while( list( $k, $v ) = each( $_COOKIE ) ) {
			if ( is_array($_COOKIE[$k]) ) {
				while ( list( $k2, $v2 ) = each( $_COOKIE[$k] ) ) {
					$_COOKIE[$k][$k2] = addslashes( $v2 );
				}
				@reset( $_COOKIE[$k] );
			}
			else {
				$_COOKIE[$k] = addslashes( $v );
			}
		}
		@reset( $_COOKIE );
	}
	unset( $k, $v );
}
*/

// disable the evil effects of magic_quotes_gpc
if (get_magic_quotes_gpc()) {
	$types = array('_GET', '_POST', '_COOKIE', '_REQUEST');
	foreach ($types as $type) {
		foreach (${$type} as $key => $value) {
			if (is_string($value)) {
				${$type}[$key] = stripslashes($value);
			}
		}
	}
	unset($types, $type, $key, $value);
}


require_once('phPOP3clean.config.php');
if (!defined('PHPOP3CLEAN_CONFIG_VERSION') || (PHPOP3CLEAN_CONFIG_VERSION < $need_config_datestamp)) {
	echo '<h2 style="color: red;">Config file out of date (required at least v'.$need_config_datestamp.')</h2>';
	if (basename($_SERVER['PHP_SELF']) == 'phPOP3clean.admin.php') {
		// only block if trying to access admin page, let actual scanning continue even with out-of-date config file
		exit;
	}
}
unset($phpop3clean_datestamp, $need_config_datestamp);

//define('PHPOP3CLEAN_PREG_DELIMIT', '#');
function preg_expression($expression, $options='') {
	return PHPOP3CLEAN_PREG_DELIMIT.str_replace(PHPOP3CLEAN_PREG_DELIMIT, '\\'.PHPOP3CLEAN_PREG_DELIMIT, $expression).PHPOP3CLEAN_PREG_DELIMIT.$options;
}
define('PHPOP3CLEAN_HTTPDOMAIN',  '(f|ht)tps?:[/\\\\]{1,2}(([a-z0-9]+:)?[a-z0-9]+@)?([a-z0-9'.preg_quote('_&|[]<>%*!').'\\.\\-]+)');
define('PHPOP3CLEAN_EMAILDOMAIN', '([0-9a-z_\\.]+)@(([a-z0-9\\.\\-]+\\.)+([a-z]{2,4}))[^a-z0-9]');
define('PHPOP3CLEAN_OBFUSPACE', '[-–—`\'\\+\\.,;\\*~…_\\s]');

///////////////////////////////////////////////////////////////////////////////

if (!defined('PHPOP3CLEAN_ADMINEMAIL') || !PHPOP3CLEAN_ADMINEMAIL) {
	echo 'Please define PHPOP3CLEAN_ADMINEMAIL';
	flush();
	exit;
}
if (!@mysql_connect(PHPOP3CLEAN_DBHOST, PHPOP3CLEAN_DBUSER, PHPOP3CLEAN_DBPASS)) {
	WarningEmail('FAILURE! Failed to connect to MySQL server', 'Failed to connect to SQL server in file '.@$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."\n".mysql_error());
	echo 'mysql_connect('.PHPOP3CLEAN_DBHOST.', '.PHPOP3CLEAN_DBUSER.', *****) failed';
	flush();
	exit;
}
if (!@mysql_select_db(PHPOP3CLEAN_DBNAME)) {
	WarningEmail('FAILURE! Failed to select MySQL database', 'Failed to select SQL database in file '.@$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."\n".mysql_error());
	echo 'mysql_select_db('.PHPOP3CLEAN_DBNAME.') failed';
	flush();
	exit;
}

function mysql_query_safe($SQLquery) {
	$result = mysql_query($SQLquery);
	if (mysql_error()) {
		//WarningEmail('phPOP3clean SQL error', mysql_error()."\n\n\n".$SQLquery);
		echo '<hr>';
		echo htmlentities($SQLquery).'<br>';
		echo '<b>'.htmlentities(mysql_error()).'</b><br>';
		flush();
		exit;
	}
	return $result;
}

///////////////////////////////////////////////////////////////////////////////
// http://support.microsoft.com/default.aspx?scid=kb;en-us;322826
// http://cr.yp.to/immhf/date.html
// http://antispam.yahoo.com/domainkeys
// http://crl.cs.uiuc.edu/doc/Mail/level_1.html
///////////////////////////////////////////////////////////////////////////////

function PrintHexBytes($string, $hex=true, $spaces=true, $htmlsafe=true) {
	$returnstring = '';
	for ($i = 0; $i < strlen($string); $i++) {
		if ($hex) {
			$returnstring .= str_pad(dechex(ord($string{$i})), 2, '0', STR_PAD_LEFT);
		} else {
			$returnstring .= ' '.(ereg("[\\x20-\\x7E]", $string{$i}) ? $string{$i} : '¤');
		}
		if ($spaces) {
			$returnstring .= ' ';
		}
	}
	if ($htmlsafe) {
		$returnstring = htmlentities($returnstring);
	}
	return $returnstring;
}

function EchoToScreen($text, $returnbuffer=false) {
	static $buffer = '';
	if (empty($buffer)) {
		$buffer = '<html><head><title>phPOP3clean scan: '.date('j M Y g:ia').'</title><style type="text/css">BODY,TD,TH { font-family: sans-serif; font-size: 8pt; }</style>';
		if (!@$_GET['nocache']) {
			// NOTE TO JAMES: changed the refresh to js to avoid the page reloading while the current
			// run hasn't finished yet (can easily happen if you have a lot of accounts active)
			// Currently set to refresh 2 minutes after the page has finished loading
			//
			// Best way to do this properly will probably be in php using wait at the end of the
			// script before reloading itself
			//
			// Also might be a good idea to make this optional using a $_GET variable
			// something like $_GET['autoreload'] = true
			$buffer .= '<script type="text/javascript">
				<!--
				window.onload = init;
				function init() {
					window.setTimeout("window.location.reload()", 120000);
				}
				//-->
				</script>';
		}
		$buffer .= '</head><body>';
		if (@$_GET['show']) {
			echo $buffer;
			flush();
		}
	}

	$buffer .= nl2br($text);
	if (@$_GET['show']) {
		echo nl2br($text);
		flush();
	}
	if ($returnbuffer) {
		return $buffer;
	}
	return true;
}

// NOTE TO JAMES: added account only temporarily so I can see/record which headers are received by which account
// see my remarks about the headers filter table in the mail
function POP3parseheader($header, $account='') {
	$arrayoflines = explode("\n", $header);
	$togetherlines = array();
	$currentline = '';
	foreach ($arrayoflines as $line) {
		if (!empty($line)) {
			if (($line{0} == ' ') || ($line{0} == "\t")) {
				$currentline .= ' '.trim($line);
			} else {
				$currentline = rtrim($currentline);
				if (!empty($currentline)) {
					$togetherlines[] = $currentline;
				}
				$currentline = $line;
			}
		}
	}
	$currentline = rtrim($currentline, "\r\n");
	if (!empty($currentline)) {
		$togetherlines[] = $currentline;
	}
	$ParsedHeader = array();
	foreach ($togetherlines as $headerpair) {
		if (strpos($headerpair, ':') !== false) {
			list($key, $value) = explode(':', $headerpair, 2);
			@$ParsedHeader[strtolower(trim($key))][] = trim($value);
		}
	}

	$ParsedHeader['from'][0]    = QuotedOrBinaryStringDecode(@$ParsedHeader['from'][0]);
	$ParsedHeader['subject'][0] = QuotedOrBinaryStringDecode(@$ParsedHeader['subject'][0]);
	$ParsedHeader['subject_html'][0]           = htmlentities($ParsedHeader['subject'][0], ENT_QUOTES);

	// Temporary to determine frequency and availability of various headers
	if (($account !== '') && mysql_table_exists(PHPOP3CLEAN_TABLE_PREFIX.'headers_encountered')) {
		if (!isset($_GET['nocache']) || ($_GET['nocache'] != 1)) {
			$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'headers_encountered` (`id`, `account`, `header`, `array_key`, `value`) VALUES ';
			$first = true;
			foreach ($ParsedHeader as $key => $value) {
				foreach ($value as $key2 => $value2) {
					$SQLquery  .= (($first === true) ? '' : ', ');
					$SQLquery  .= '("", ';
					$SQLquery  .= '"'.mysql_escape_string($account).'", ';
					$SQLquery  .= '"'.mysql_escape_string($key).'", ';
					$SQLquery  .= '"'.mysql_escape_string($key2).'", ';
					$SQLquery  .= '"'.mysql_escape_string($value2).'")';
					$first = false;
				}
			}
			$SQLquery .= ';';

			if (!mysql_query_safe($SQLquery)) {
				$errormessage = mysql_error();
				if (!preg_match(preg_expression('^(Can\'t open file|MySQL server has gone away)', 'i'), $errormessage)) {
					WarningEmail('NewMessageHeadersInsert SQL failed', $SQLquery."\n\n".$errormessage);
				}
			}
		}
	}
	// End of temporary code

	return $ParsedHeader;
}

function SanitizeEmailAddress($email) {
	return preg_replace('[^\x21-\x7f]', '', strtolower(trim($email)));
}

function SanitizeFilename($filename) {
	return ereg_replace('[^'.preg_quote(' !#$%^()+,-.;<>=@[]_{}').'a-zA-Z0-9]', '_', $filename);
}

function HTMLentitiesDecode(&$string) {
	if (version_compare(phpversion(), '4.3.0', '>=')) {
		// PHP v4.3.0+
		$newstring = html_entity_decode(rawurldecode($string));

	} else {
		// PHP < v4.3.0

		static $HTMLentities = array();
		if (empty($HTMLentities)) {
			//for ($i = 1; $i <= 99; $i++) {
			//	$HTMLentities['&#'.str_pad($i, 3, '0', STR_PAD_LEFT).';'] = chr($i);
			//}
			for ($i = 1; $i <= 255; $i++) {
			//	$HTMLentities['&#'.$i.';'] = chr($i);
				$hexstring = str_pad(dechex($i), 2, '0', STR_PAD_LEFT);
				$HTMLentities['%'.strtoupper($hexstring)] = chr($i);
				$HTMLentities['%'.strtolower($hexstring)] = chr($i);
			}
			$HTMLentities['&Aacute;'] = 'Á';
			$HTMLentities['&Agrave;'] = 'À';
			$HTMLentities['&Acirc;']  = 'Â';
			$HTMLentities['&Atilde;'] = 'Ã';
			$HTMLentities['&Aring;']  = 'Å';
			$HTMLentities['&Auml;']   = 'Ä';
			$HTMLentities['&AElig;']  = 'Æ';
			$HTMLentities['&Ccedil;'] = 'Ç';
			$HTMLentities['&Eacute;'] = 'É';
			$HTMLentities['&Egrave;'] = 'È';
			$HTMLentities['&Ecirc;']  = 'Ê';
			$HTMLentities['&Euml;']   = 'Ë';
			$HTMLentities['&Iacute;'] = 'Í';
			$HTMLentities['&Igrave;'] = 'Ì';
			$HTMLentities['&Icirc;']  = 'Î';
			$HTMLentities['&Iuml;']   = 'Ï';
			$HTMLentities['&ETH;']    = 'Ð';
			$HTMLentities['&Ntilde;'] = 'Ñ';
			$HTMLentities['&Oacute;'] = 'Ó';
			$HTMLentities['&Ograve;'] = 'Ò';
			$HTMLentities['&Ocirc;']  = 'Ô';
			$HTMLentities['&Otilde;'] = 'Õ';
			$HTMLentities['&Ouml;']   = 'Ö';
			$HTMLentities['&Oslash;'] = 'Ø';
			$HTMLentities['&Uacute;'] = 'Ú';
			$HTMLentities['&Ugrave;'] = 'Ù';
			$HTMLentities['&Ucirc;']  = 'Û';
			$HTMLentities['&Uuml;']   = 'Ü';
			$HTMLentities['&Yacute;'] = 'Ý';
			$HTMLentities['&THORN;']  = 'Þ';
			$HTMLentities['&szlig;']  = 'ß';
			$HTMLentities['&aacute;'] = 'á';
			$HTMLentities['&agrave;'] = 'à';
			$HTMLentities['&acirc;']  = 'â';
			$HTMLentities['&atilde;'] = 'ã';
			$HTMLentities['&auml;']   = 'ä';
			$HTMLentities['&aelig;']  = 'æ';
			$HTMLentities['&ccedil;'] = 'ç';
			$HTMLentities['&eacute;'] = 'é';
			$HTMLentities['&egrave;'] = 'è';
			$HTMLentities['&ecirc;']  = 'ê';
			$HTMLentities['&euml;']   = 'ë';
			$HTMLentities['&iacute;'] = 'í';
			$HTMLentities['&igrave;'] = 'ì';
			$HTMLentities['&icirc;']  = 'î';
			$HTMLentities['&iuml;']   = 'ï';
			$HTMLentities['&eth;']    = 'ð';
			$HTMLentities['&ntilde;'] = 'ñ';
			$HTMLentities['&oacute;'] = 'ó';
			$HTMLentities['&ograve;'] = 'ò';
			$HTMLentities['&ocirc;']  = 'ô';
			$HTMLentities['&otilde;'] = 'õ';
			$HTMLentities['&ouml;']   = 'ö';
			$HTMLentities['&oslash;'] = 'ø';
			$HTMLentities['&uacute;'] = 'ú';
			$HTMLentities['&ugrave;'] = 'ù';
			$HTMLentities['&ucirc;']  = 'û';
			$HTMLentities['&uuml;']   = 'ü';
			$HTMLentities['&yacute;'] = 'ý';
			$HTMLentities['&thorn;']  = 'þ';
			$HTMLentities['&yuml;']   = 'ÿ';
		}
		$newstring = strtr($string, $HTMLentities);

	}
	if (eregi('&#x[0-9a-f]{2,6};', $newstring)) {
		for ($i = 1; $i <= 255; $i++) {
			$newstring = eregi_replace('&#x[0]{0,5}'.dechex($i).';', chr($i), $newstring);
		}
	}
	if (eregi('&#[0-9]{1,3};', $newstring)) {
		for ($i = 100; $i <= 255; $i++) {
			$newstring = str_replace('&#'.$i.';', chr($i), $newstring);
		}
		for ($i = 10; $i <= 99; $i++) {
			$newstring = str_replace('&#'.$i.';', chr($i), $newstring);
			$newstring = str_replace('&#'.str_pad($i, 3, '0', STR_PAD_LEFT).';', chr($i), $newstring);
		}
		for ($i = 0; $i <= 9; $i++) {
			$newstring = str_replace('&#'.$i.';', chr($i), $newstring);
			$newstring = str_replace('&#'.str_pad($i, 2, '0', STR_PAD_LEFT).';', chr($i), $newstring);
			$newstring = str_replace('&#'.str_pad($i, 3, '0', STR_PAD_LEFT).';', chr($i), $newstring);
		}
	}
	$string = $newstring;
	return $newstring;
}

function EightBitDecode(&$string) {
	static $EightBitEntities = array();
	if (empty($EightBitEntities)) {
		for ($i = 1; $i <= 255; $i++) {
			$EightBitEntities['%'.str_pad(strtoupper(dechex($i)), 2, '0', STR_PAD_LEFT)] = chr($i);
		}
		foreach ($EightBitEntities as $key => $value) {
			// strtr() is case-sensitive
			$EightBitEntities[strtolower($key)] = $value;
		}
	}
	return strtr($string, $EightBitEntities);
}

function QuotedEntityDecode(&$string) {
	if (function_exists('quoted_printable_decode')) {
		// PHP v3.0.6+
		return quoted_printable_decode($string);
	}
	static $QuotedStringEntities = array();
	if (empty($QuotedStringEntities)) {
		for ($i = 0; $i <= 255; $i++) {
			// strtr() is case-sensitive
			$QuotedStringEntities['='.str_pad(strtoupper(dechex($i)), 2, '0', STR_PAD_LEFT)] = chr($i);
			$QuotedStringEntities['='.str_pad(strtolower(dechex($i)), 2, '0', STR_PAD_LEFT)] = chr($i);
		}
		$QuotedStringEntities['_']        = ' ';
		$QuotedStringEntities['='."\r\n"] = '';  // wrapped line
		$QuotedStringEntities['='."\n"]   = '';  // wrapped line
		//$QuotedStringEntities['='."\r"]   = '';  // wrapped line
	}
	return strtr($string, $QuotedStringEntities);
}

function QuotedOrBinaryStringDecode($string) {
	if (preg_match(preg_expression('^=\\?([a-z0-9\\-]+)\\?([qb])\\?(.*)\\?=$', 'is'), trim($string), $matches)) {
		// combine multiple lines into one
		$SubjectData = ereg_replace("\\?=[\\x0D\\x0A]*[ \\x09]*=\\?".$matches[1].'\\?'.$matches[2].'\\?', '', $matches[3]);
		switch (strtoupper($matches[2])) {
			case 'Q':
				//  Subject: =?iso-8859-1?Q?vi=E0gra!?=                   // viàgra
				//  Subject: =?iso-8859-1?Q?Test_with_=E0_special_char?=  // viàgra
				$string = QuotedEntityDecode($SubjectData);
				break;

			case 'B':
				//  Subject: =?iso-8859-1?B?4A==?=  à
				//  Subject: =?iso-8859-1?B?4OA=?=  àà
				//  Subject: =?iso-8859-1?B?4Q==?=  á
				$string = safe_base64_decode($SubjectData);
				break;
		}
	}
	return $string;
}

function safe_base64_decode($text) {
	if (version_compare(phpversion(), '4.3.5', '<')) {
	    set_time_limit(max(PHPOP3CLEAN_PHP_TIMEOUT, round(strlen($text) / 1024)));
		// http://bugs.php.net/bug.php?id=27460
		// Bug in base64_decode before PHP v4.3.5:
		// if unneeded padding '=' are at the end, base64_decode() will fail

		// remove any non-base64 characters, including whitespace
		$text = preg_replace(preg_expression('[^A-Za-z0-9\\+/=]'), '', $text);

		// no more than 2 padding chars ('', '=', or '==') are allowed at the end
		$text = rtrim($text, '=');

		switch (strlen($text) % 4) {
			case 3:
				$text .= '=';
				break;
			case 2:
				$text .= '==';
				break;
			case 1:
				// (in theory) cannot happen
				break;
			case 0:
				// no padding needed
				break;
		}
	}
	return base64_decode($text);
}

function EncodingDecode(&$text, $encoding) {
	switch (strtolower($encoding)) {
		case '7bit':
			return $text; // great, leave as-is
			// should just return text with no modifications, but quoted entities have been seen in 7bit emails
			//return QuotedEntityDecode($text);
			break;

		case '8bit':
			return $text;
			//return EightBitDecode($text);
			break;

		case 'quoted-printable':
			return QuotedEntityDecode($text);
			break;

		case 'base64':
			return safe_base64_decode($text);
			break;

		case 'binary':
		default:
			// ignore?
			break;
	}
	return $text;
}


if (!function_exists('html_entity_decode')) {
	// built-in PHP v4.3.0+
	function html_entity_decode($string)  {
	   return strtr($string, array_flip(get_html_translation_table(HTML_ENTITIES)));
	}
}

function UUdecode(&$UUencoded, &$UUdecoded, &$filename) {
	if (preg_match(preg_expression('^(begin|start) 0?[0-7]{3} ([^\\x0D\\x0A]+)[\\x0D\\x0A]+(([\\x21-\\x60]{5,61}[\\x0D\\x0A]+)+)[`\\x0D\\x0A]*end$'), trim($UUencoded), $matches)) {
		$filename = $matches[2];
		$UUdecoded = '';
		$lines = explode("\n", $matches[3]);
		foreach ($lines as $line) {
			$line = trim($line);
			//$lineLength = round((ord($line{0}) - 0x20) * 4 / 3); // "M" == chr(77) == linelength(45bytes, 60encoded)
			$line = substr($line, 1);
			while (strlen($line)) {
				$quad = substr($line, 0, 4);
				$line = substr($line, 4);
				for ($i = 0; $i < 4; $i++) {
					$Quad[$i] = ord($quad{$i}) - 0x20;
					if ($Quad[$i] == 0x40) {
						$Quad[$i] = 0x00;
					}
				}
				$triplet = (($Quad[0] & 0x3F) << 18) | (($Quad[1] & 0x3F) << 12) | (($Quad[2] & 0x3F) << 6) | ($Quad[3] & 0x3F);
				$UUdecoded .= chr(($triplet & 0xFF0000) >> 16).chr(($triplet & 0x00FF00) >> 8).chr($triplet & 0x0000FF);
			}
		}
		return true;
	}
	return false;
}

function BannedReceivedHeaderDomain(&$ParsedHeader) {
	static $BannedReceivedHeaderDomains = null;
	if (is_null($BannedReceivedHeaderDomains)) {
		$SQLquery  = 'SELECT `domain` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'received_domains`';
		$SQLquery .= ' ORDER BY `lasthit` DESC';
		$result = mysql_query_safe($SQLquery);
		while ($row = mysql_fetch_assoc($result)) {
			$BannedReceivedHeaderDomains[$row['domain']] = preg_quote($row['domain']);
		}
		@mysql_free_result($result);
	}

	if ((is_array($BannedReceivedHeaderDomains) && (count($BannedReceivedHeaderDomains) > 0)) && (isset($ParsedHeader['received']) && (is_array($ParsedHeader['received']) && (count($ParsedHeader['received']) > 0)))) {
		foreach ($ParsedHeader['received'] as $key => $ReceivedLine) {
			$ReceivedLine = strtr($ReceivedLine, "\n\r", '  ');
			if (preg_match(preg_expression('^from ([a-z0-9\\.\\-]*)', 'i'), $ReceivedLine, $matches)) {
				if (trim($matches[1]) !== '') {
					foreach ($BannedReceivedHeaderDomains as $domain => $regexdomain) {
						if (preg_match(preg_expression($regexdomain.'$', 'i'), $matches[1])) {
							$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'received_domains`';
							$SQLquery .= ' SET `lasthit` = "'.time().'"';
							$SQLquery .= ', `hitcount` = `hitcount` + 1';
							$SQLquery .= ' WHERE `domain` = "'.mysql_escape_string($domain).'"';
							mysql_query_safe($SQLquery);
							return $domain;
						}
					}
				}
			}
		}
	}
	return false;
}

function ObfuscatedWordLists() {
	static $Obfuscation = array();
	static $Obfusnumeration = array();
	if (count($Obfuscation) === 0) {
		$Obfuscation = array(
			'a' => '[aàáâãäåÀÁÂÃÄÅ@48\\^]',
			'b' => '[bß]',
			'c' => '[cçÇ¢©(]',
			'd' => '[dÐð]',
			'e' => '[eèéêëÈÉÊË€3]',
			'f' => '[fƒ]',
			'g' => '[gq]',
			'i' => '[iìíîïÌÍÎÏ!¡1l\\|]',
			'l' => '[lI1!¡\\|]',
			'n' => '[nñÑ]',
			'o' => '[oòóôõöøÒÓÔÕÖØ0]',
			'p' => '[pþ]',
			'r' => '[r®]',
			's' => '[sšzžŠŽ§\\$]',
			't' => '[t†]',
			'u' => '[uùúûüµÙÚÛÜ]',
			'x' => '[x×]',
			'y' => '[yýÿ¥ÝŸ]',
			'z' => '[zžŽ2]',
			'.' => '[.,\\*]',
			' ' => PHPOP3CLEAN_OBFUSPACE,
			'-' => PHPOP3CLEAN_OBFUSPACE,
			'_' => PHPOP3CLEAN_OBFUSPACE,
		);
	}
	if (count($Obfusnumeration) === 0) {
		$Obfusnumeration = array(
			'0' => PHPOP3CLEAN_OBFUSPACE.'*([0oòóôõöøÒÓÔÕÖØ]|zero)'.PHPOP3CLEAN_OBFUSPACE.'*',
			'1' => PHPOP3CLEAN_OBFUSPACE.'*([1l!¡\\|]|one)'.PHPOP3CLEAN_OBFUSPACE.'*',
			'2' => PHPOP3CLEAN_OBFUSPACE.'*([2Z]|two)'.PHPOP3CLEAN_OBFUSPACE.'*',
			'3' => PHPOP3CLEAN_OBFUSPACE.'*([3E]|three)'.PHPOP3CLEAN_OBFUSPACE.'*',
			'4' => PHPOP3CLEAN_OBFUSPACE.'*([4]|four)'.PHPOP3CLEAN_OBFUSPACE.'*',
			'5' => PHPOP3CLEAN_OBFUSPACE.'*([5]|five)'.PHPOP3CLEAN_OBFUSPACE.'*',
			'6' => PHPOP3CLEAN_OBFUSPACE.'*([6G]|six)'.PHPOP3CLEAN_OBFUSPACE.'*',
			'7' => PHPOP3CLEAN_OBFUSPACE.'*([7]|seven)'.PHPOP3CLEAN_OBFUSPACE.'*',
			'8' => PHPOP3CLEAN_OBFUSPACE.'*([8B]|eight)'.PHPOP3CLEAN_OBFUSPACE.'*',
			'9' => PHPOP3CLEAN_OBFUSPACE.'*([9]|nine)'.PHPOP3CLEAN_OBFUSPACE.'*',
		);
	}

	static $BlackListedWordsObfuscated = array();
	static $BlackListedWordsDirty      = array();
	if (count($BlackListedWordsObfuscated) === 0) {
		$SQLquery  = 'SELECT `word` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'words_obfuscated`';
		$SQLquery .= ' ORDER BY `lasthit` DESC, `hitcount` DESC, `added` DESC';
		$result = mysql_query_safe($SQLquery);
		while ($row = mysql_fetch_assoc($result)) {
			$BlackListedWordsObfuscated[$row['word']] = preg_quote($row['word']);
		}
		@mysql_free_result($result);

		foreach ($BlackListedWordsObfuscated as $CleanWordKey => $CleanWord) {
			$RegexWord = '';
			$wordlen = strlen($CleanWord);
			for ($i = 0; $i < $wordlen; $i++) {
				$char = strtolower($CleanWord{$i});
				if (isset($Obfusnumeration[$char])) {
					if (substr($CleanWord, $i, 3) == $char.$char.$char) {
						$RegexWord .= '('.PHPOP3CLEAN_OBFUSPACE.'*triple'.PHPOP3CLEAN_OBFUSPACE.'|('.$Obfusnumeration[$char].PHPOP3CLEAN_OBFUSPACE.'*){2})'.$Obfusnumeration[$char];
						$i += 2;
					} elseif (substr($CleanWord, $i, 2) == $char.$char) {
						$RegexWord .= '('.PHPOP3CLEAN_OBFUSPACE.'*double'.PHPOP3CLEAN_OBFUSPACE.'|('.$Obfusnumeration[$char].PHPOP3CLEAN_OBFUSPACE.'*){1})'.$Obfusnumeration[$char];
						$i += 1;
					} else {
						$RegexWord .= $Obfusnumeration[$char];
					}
				} elseif (isset($Obfuscation[$char])) {
					$RegexWord .= $Obfuscation[$char].'+';
				} else {
					$RegexWord .= $char;
				}
			}
			$BlackListedWordsDirty[$CleanWordKey] = $RegexWord;
		}
	}

	return array($BlackListedWordsObfuscated, $BlackListedWordsDirty);
}

function BlackListedWordsFound($text) {
	if ((strlen(trim($text)) === 0) || (trim($text) === 'This is a multi-part message in MIME format.')) {
		return false;
	}
	static $BlackListedWordsClean = array();
	if (count($BlackListedWordsClean) === 0) {
		//$BlackListedWordsClean = array(0=>array(), 1=>array());
		//$strtr_array[0] = array(' '=>PHPOP3CLEAN_OBFUSPACE.'*', '-'=>PHPOP3CLEAN_OBFUSPACE.'*');
		//$strtr_array[1] = array(' '=>PHPOP3CLEAN_OBFUSPACE.'*');
		//$SQLquery  = 'SELECT `word`, `isregex`, `casesensitive` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'words_clean`';
		//$SQLquery .= ' ORDER BY `isregex` DESC, `lasthit` DESC, `hitcount` DESC, `added` DESC';
		//$result = mysql_query_safe($SQLquery);
		//while ($row = mysql_fetch_assoc($result)) {
		//	$safe_clean = $row['word'];
		//	if (!$row['isregex']) {
		//		$safe_clean = preg_quote($row['word']);
		//	}
		//	$BlackListedWordsClean[$row['casesensitive']][$row['word']] = strtr($safe_clean, $strtr_array[$row['isregex']]);
		//}
		//@mysql_free_result($result);

		$SQLquery  = 'SELECT `id`, `word`, `isregex`, `casesensitive`, `spaces_quantified`, `dotall`, `onlychars`';
		$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'words_clean`';
		$SQLquery .= ' ORDER BY `lasthit` DESC, `hitcount` DESC, `added` DESC';
		$result = mysql_query_safe($SQLquery);
		while ($row = mysql_fetch_assoc($result)) {
			//$BlackListedWordsCode[$row['word']] = ($row['isregex'] ? $row['word'] : preg_quote($row['word']));
			//$BlackListedWordsCode[$row['word']] = str_replace(' ', PHPOP3CLEAN_OBFUSPACE.'*', $BlackListedWordsCode[$row['word']]);

			$quantifier = (($row['spaces_quantified'] == 1) ? '' : '*');
			if ($row['isregex'] == 1) {
				$regex = $row['word'];
				$regex = str_replace(' ', PHPOP3CLEAN_OBFUSPACE.$quantifier, $regex);
			} else {
				$regex = preg_quote($row['word']);
				$strtr_array = array(
					' '	=> PHPOP3CLEAN_OBFUSPACE.$quantifier,
					'-'	=> PHPOP3CLEAN_OBFUSPACE.$quantifier
				);
				$regex = strtr($regex, $strtr_array);
				unset($quantifier, $strtr_array);
			}

			$regex_switches = '';
			$regex_switches .= (($row['casesensitive'] == 1) ? ''  : 'i');
			$regex_switches .= (       ($row['dotall'] == 1) ? 's' : '');
			$regex = preg_expression($regex, $regex_switches);

			$BlackListedWordsClean[$row['id']] = array(
				'regex'             => $regex,
				'word'              => $row['word'],
				'casesensitive'     => $row['casesensitive'],
				'spaces_quantified' => $row['spaces_quantified'],
				'dotall'            => $row['dotall'],
				'onlychars'         => $row['onlychars'],
			);
			unset($regex);
		}
		@mysql_free_result($result);
	}

	//$text = preg_replace(preg_expression('[\s]+', 'is'), ' ', $text);

	// scan for "clean" blacklisted words
	//for ($casesensitive = 0; $casesensitive <= 1; $casesensitive++) {
	//	foreach ($BlackListedWordsClean[$casesensitive] as $CleanWord => $RegexWord) {
	foreach ($BlackListedWordsClean as $id => $array) {
		set_time_limit(PHPOP3CLEAN_PHP_TIMEOUT);
		//$regex = preg_expression($RegexWord, ($casesensitive ? '' : 'i').'s');
		// TEMPORARY EXTRA IF - to avoid the dreaded browser timeouts
		$hasdot = preg_match('/[^\\\]\.[\+\*][^\?]/', $array['regex']);
		if (($hasdot === 0) || ($hasdot === false)) {
			ob_start();
			if (!empty($array['onlychars'])) {
				$success = preg_match($array['regex'], preg_replace('/[^'.str_replace('/', '\\/', $array['onlychars']).']/', '', $text), $matches);
			} else {
				$success = preg_match($array['regex'], $text, $matches);
			}
			$errors = ob_get_contents();
			ob_end_clean();
			if ($errors) {
				WarningEmail('BlackListedWordsFound(clean) regex error in regex '.$id, $array['regex']."\n\n\n".$errors);
			}
			if ($success) {
				$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_clean`';
				$SQLquery .= ' SET `lasthit` = "'.time().'"';
				$SQLquery .= ', `hitcount` = `hitcount` + 1';
				$SQLquery .= ' WHERE `id` = "'.mysql_escape_string($id).'"';
				mysql_query_safe($SQLquery);
				if (!mysql_affected_rows()) {
					WarningEmail('BlackListedWordsFound(clean) update failed', $SQLquery."\n\n".mysql_error());
				}
				return array($matches[0], $array);
			}
		} else {
echo 'BlackListedWordsFound(clean) regex error in regex '.$id.'; this expression is NOT being checked because it contains a raw "."<br>';
WarningEmail('BlackListedWordsFound(clean) regex error in regex '.$id, 'This expression is NOT being checked because it contains a raw "." (please rewrite the expression to limit allowed characters)'."\n\n".$array['word']."\n\n\n".$array['regex']);
		}
	}

	static $BlackListedWordsObfuscated = array();
	static $BlackListedWordsDirty      = array();
	if (count($BlackListedWordsObfuscated) === 0) {
		list($BlackListedWordsObfuscated, $BlackListedWordsDirty) = ObfuscatedWordLists();
	}

	// scan for obfuscated blacklisted words
	foreach ($BlackListedWordsDirty	as $CleanWord => $RegexWord) {
		$regex = preg_expression('(^|[^a-zA-Z0-9\\xC0-\\xFF])('.$RegexWord.')([^a-zA-Z0-9\\xC0-\\xFF]|$)', 'is');
		ob_start();
		$success = preg_match($regex, $text, $matches);
		$errors = ob_get_contents();
		ob_end_clean();
		if ($errors) {
			WarningEmail('BlackListedWordsFound(clean) regex error', $regex."\n\n\n".$errors);
		}
		if ($success) {
			if (strtolower($matches[2]) !== strtolower($CleanWord)) {
				$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_obfuscated`';
				$SQLquery .= ' SET `lasthit` = "'.time().'"';
				$SQLquery .= ', `hitcount` = `hitcount` + 1';
				$SQLquery .= ' WHERE `word` = "'.mysql_escape_string($CleanWord).'"';
				mysql_query_safe($SQLquery);
				if (!mysql_affected_rows()) {
					WarningEmail('BlackListedWordsFound(obfuscated) update failed', $SQLquery."\n\n".mysql_error());
				}
				return array($matches[2], $CleanWord);
			}
		}
	}

	return false;
}


function BlackListedWordsFoundCode($text) {
	if ((strlen(trim($text)) === 0) || (trim($text) === 'This is a multi-part message in MIME format.')) {
		return false;
	}
	static $BlackListedWordsCode = array();
	if (count($BlackListedWordsCode) === 0) {
		$SQLquery  = 'SELECT `id`, `word`, `isregex`, `casesensitive`, `spaces_quantified`, `dotall`';
		$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'words_code`';
		$SQLquery .= ' ORDER BY `lasthit` DESC, `hitcount` DESC, `added` DESC';
		$result = mysql_query_safe($SQLquery);
		while ($row = mysql_fetch_assoc($result)) {
			//$BlackListedWordsCode[$row['word']] = ($row['isregex'] ? $row['word'] : preg_quote($row['word']));
			//$BlackListedWordsCode[$row['word']] = str_replace(' ', PHPOP3CLEAN_OBFUSPACE.'*', $BlackListedWordsCode[$row['word']]);

			$regex = (($row['isregex'] == 1)           ? $row['word']                                    : preg_quote($row['word']));
			$regex = (($row['spaces_quantified'] == 1) ? str_replace(' ', PHPOP3CLEAN_OBFUSPACE, $regex) : str_replace(' ', PHPOP3CLEAN_OBFUSPACE.'*', $regex));

			$regex_switches = '';
			$regex_switches .= (($row['casesensitive'] == 1) ? ''  : 'i');
			$regex_switches .= (       ($row['dotall'] == 1) ? 's' : '');
			$regex = preg_expression($regex, $regex_switches);

			$BlackListedWordsCode[$row['id']] = array(
				'regex'				=> $regex,
				'word'				=> $row['word'],
				'casesensitive'		=> $row['casesensitive'],
				'spaces_quantified'	=> $row['spaces_quantified'],
				'dotall'			=> $row['dotall'],
			);
			unset($regex);

		}
		@mysql_free_result($result);
	}

	//$text = preg_replace(preg_expression('[\s]+'), ' ', $text);

	// scan for blacklisted words in source code (before decoding and/or skipping HTML)
	foreach ($BlackListedWordsCode as $id => $array) {
		$regex = $array['regex'];
		ob_start();
		$success = preg_match($regex, $text, $matches);
		$errors = ob_get_contents();
		ob_end_clean();
		if ($errors) {
			WarningEmail('BlackListedWordsFound(code) regex error', $regex."\n\n\n".$errors);
		}
		if ($success) {
			$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_code`';
			$SQLquery .= ' SET `lasthit` = "'.time().'"';
			$SQLquery .= ', `hitcount` = `hitcount` + 1';
			$SQLquery .= ' WHERE `id` = "'.mysql_escape_string($id).'"';
			mysql_query_safe($SQLquery);
			if (!mysql_affected_rows()) {
				WarningEmail('BlackListedWordsFound(code) update failed', $SQLquery."\n\n".mysql_error());
			}
/*
			$SQLquery  = 'DELETE FROM `html_patterns_for_regex`';
			$SQLquery .= ' WHERE `message` = "'.mysql_escape_string($text).'"';
			mysql_query_safe($SQLquery);
			print "<br>\n".mysql_affected_rows().' html pattern row(s) deleted'."<br>\n";
*/
			return array($matches[0], $array);
		}
	}

	// Temporary addition from jrf to detect html patterns
/*	if( preg_match( '/<[^>@]+>/', trim($text), $matches, PREG_OFFSET_CAPTURE ) === 1 ) {
		$pattern = preg_replace( "/\r\n|\n|\r/", ' ', substr( $text, $matches[0][1]) );
		$pattern = preg_replace( '/>[^<]+</', "> <", $pattern );
		$pattern = preg_replace( '/(<\\/[^>]+>)([^<]*)(<[^>\/]+>)/', "\\1\r\n\\2\\3", $pattern );
		$pattern = preg_replace( '/"[^">]+"/', '"..."', $pattern );

		$SQLquery  = 'INSERT INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'html_patterns_for_regex`';
		$SQLquery .= ' SET `id` = "", `message` = "'.mysql_escape_string($text).'"';
		$SQLquery .= ', `pattern` = "'.mysql_escape_string($pattern).'"';
		mysql_query_safe($SQLquery);
		if (!mysql_affected_rows()) {
			WarningEmail('Insert of html pattern failed (BlackListedWordCode)', $SQLquery."\n\n".mysql_error());
		}
		print "<br>\n".mysql_affected_rows().' html pattern row(s) inserted'."<br>\n";
	}
*/

	return false;
}


function SafeIP2Long($ip) {
	return (float) sprintf('%u', ip2long($ip));
}

function IPrangeMinMax($ipmask) {
if (!strpos($ipmask, '/')) {
	var_dump($ipmask);
}
	list($ip, $maskbits) = explode('/', $ipmask);
	$lowmask = bindec(str_repeat('1', $maskbits).str_repeat('0', (32 - $maskbits)));
	$decIPmin = SafeIP2Long($ip) & $lowmask;
	if ($decIPmin < 0) {
		$decIPmin += 4294967296;
	}
	$decIPmax = $decIPmin + pow(2, (32 - $maskbits)) - 1;
	return array($decIPmin, $decIPmax);
}

function SafeGetHostByNameL($hostname) {
	if (IsIP($hostname)) {

		return array($hostname);

	} elseif (!eregi('^[a-z0-9]+[a-z0-9\\.\\-]+\\.[a-z]{2,4}$', $hostname)) {

		// not a valid-format hostname/IP, fail immediately
		return false;

	} elseif (false || strtolower(substr(PHP_OS, 0, 3)) == 'win') {

		return gethostbynamel($hostname);

	} else {

		$commandline = 'host -W '.max(1, intval(PHPOP3CLEAN_DNS_TIMEOUT)).' '.escapeshellarg($hostname);
		$response = trim(`$commandline`);
		if (!$response) {
			return false;
		} elseif (preg_match_all(preg_expression('([a-z0-9\\.\\-]* is an alias for ([a-z0-9\\.\\-]*)\\.['." \r\n".']+)*[a-z0-9\\.\\-]* has address ([0-9]+\\.[0-9]+\\.[0-9]+\\.[0-9]+)'), trim($response), $matches, PREG_PATTERN_ORDER)) {
			return $matches[3];
		} elseif (preg_match(preg_expression('^Host '.$hostname.' not found', 'i'), $response, $matches)) {
			return false;
		} elseif ($response = ';; connection timed out; no servers could be reached') {
			return false;
		}
		WarningEmail('SafeGetHostByNameL() failed', 'SafeGetHostByNameL() failed to parse output from "'.$hostname.'"'."\n\n".$response);
		return false;

	}
}

function DNSBLlookup($IPs, $zone='') {
	static $DNSBLipCache = array();
	if (PHPOP3CLEAN_USE_DNSBL === true) {
		if (!is_array($IPs)) {
			$IPs = array($IPs);
		}
		$zone = ($zone ? $zone : PHPOP3CLEAN_DNSBL_ZONE);
		foreach ($IPs as $ip) {
			if (!isset($DNSBLipCache[$ip])) {
				$DNSBLipCache[$ip] = array();
				$SQLquery  = 'SELECT `ipmask`';
				$SQLquery .= ' FROM `phpop3clean_ips`';
				$SQLquery .= ' WHERE (`cmask` = "'.mysql_escape_string(CMask($ip)).'")';
				$SQLquery .= ' AND (`whitelist` = 1)';
				$result = mysql_query_safe($SQLquery);
				if ($row = mysql_fetch_assoc($result)) {
					// found that this IP is locally whitelisted, DO NOT delete message based on this IP!
					break;
				} else {
					$lookup = implode('.', array_reverse(explode('.', $ip))).'.'.$zone;
					$lookedup = SafeGetHostByNameL($lookup);
					if (is_array($lookedup)) {
						$DNSBLipCache[$ip] = $lookedup;
						foreach ($lookedup as $lookedup_ip) {
							if (IsIP($lookedup_ip) && preg_match(preg_expression('^127.0.0.[0-9]+$', 'i'), $lookedup_ip)) {
								$DNSBLipCache[$ip] = $lookedup;
								DeleteOldMessagesWithIP($ip);
							}
						}
					}
					if ($DNSBLipCache[$ip] === $lookedup) {
						break;
					}
				}
			}
		}
		return $DNSBLipCache[$ip];
	}
	return array();
}

function DNSBLlookup_dig($IPs, $zone) {
	if (PHPOP3CLEAN_USE_DNSBL === true) {
		$lookup = implode('.', array_reverse(explode('.', $ip))).'.'.$zone;
		$dig = `dig $lookup`;
		if (preg_match(preg_expression(';; ANSWER SECTION:(.+);;', 'isU'), $dig, $matches1)) {
			if (preg_match_all(preg_expression('^'.$lookup.'.+(127.0.0.[0-9]+)$', 'i'), trim($matches1[1]), $matches2, PREG_PATTERN_ORDER)) {
				return $matches2[1];
			}
		}
	}
	return array();
}

function DeleteOldMessagesWithIP($ip) {
	$SQLquery  = 'SELECT *';
	$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ip_message_recent`';
	$SQLquery .= ' WHERE (`ip` = "'.mysql_escape_string($ip).'")';
	$result = mysql_query_safe($SQLquery);
	while ($row = mysql_fetch_assoc($result)) {
		$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'delete_queue` (`account`, `messageid`) VALUES (';
		$SQLquery .= '"'.mysql_escape_string($row['account']).'", ';
		$SQLquery .= '"'.mysql_escape_string($row['messageid']).'")';
		mysql_query_safe($SQLquery);
		if (mysql_affected_rows() > 0) {
			//IncrementHistory($row['account'], 'good', -1);
			IncrementHistory($row['account'], 'spam',  1);
		}
//WarningEmail('phPOP3clean: DNSBLlookup retroactive delete ['.$ip.']', $SQLquery);
	}
	$SQLquery  = 'DELETE';
	$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ip_message_recent`';
	$SQLquery .= ' WHERE (`ip_cmask` = "'.mysql_escape_string(CMask($ip)).'")';
	mysql_query_safe($SQLquery);

	return true;
}

function RecentMessageIPid($account, $messageid, $ip) {
	$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'ip_message_recent` (`account`, `messageid`, `ip`, `ip_cmask`, `date`) VALUES (';
	$SQLquery .= '"'.mysql_escape_string($account).'", ';
	$SQLquery .= '"'.mysql_escape_string($messageid).'", ';
	$SQLquery .= '"'.mysql_escape_string($ip).'", ';
	$SQLquery .= '"'.mysql_escape_string(CMask($ip)).'", ';
	$SQLquery .= '"'.mysql_escape_string(time()).'")';
	return mysql_query_safe($SQLquery);
}

function DomainLookupIP($domain, $reverselookup=false) {
	static $DomainLookupIPcache = array();

	$domain = trim(strtolower($domain), '.-');

	if ($reverselookup) {

		$IPdomains = array();
		foreach ($DomainLookupIPcache as $possibledomain => $ip) {
			if ($ip == $domain) {
				$IPdomains[] = $possibledomain;
			}
		}
		return $IPdomains;

	} else {

		if (!isset($DomainLookupIPcache[$domain])) {
			$SQLquery  = 'SELECT `ips`';
			$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_recent`';
			$SQLquery .= ' WHERE (`domain` = "'.mysql_escape_string($domain).'")';
			$SQLquery .= ' AND (`ips` <> "")';
			$result = mysql_query_safe($SQLquery);
			$insertIPsIntoDatabase = false;
			if ($row = mysql_fetch_assoc($result)) {
				$DomainLookupIPcache[$domain] = explode(';', $row['ips']);
			} else {
				$DomainLookupIPcache[$domain] = SafeGetHostByNameL($domain);
				$insertIPsIntoDatabase = true;
			}

			if (is_array($DomainLookupIPcache[$domain])) {
				foreach ($DomainLookupIPcache[$domain] as $key => $value) {
					if (!IsIP($value)) {
						$insertIPsIntoDatabase = true;
						unset($DomainLookupIPcache[$domain][$key]);
					}
				}
			} else {
				$DomainLookupIPcache[$domain] = array();
			}
			if ($insertIPsIntoDatabase) {
				$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_recent` (`domain`, `ips`, `date`) VALUES (';
				$SQLquery .= '"'.mysql_escape_string($domain).'", ';
				$SQLquery .= '"'.mysql_escape_string(implode(';', $DomainLookupIPcache[$domain])).'", ';
				$SQLquery .= '"'.mysql_escape_string(time()).'")';
				mysql_query_safe($SQLquery);
			}

			@mysql_free_result($result);
		}
		return $DomainLookupIPcache[$domain];

	}
}

function IsIP($ip) {
	return (bool) preg_match(preg_expression('^[0-9]{1,3}(\\.[0-9]{1,3}){3}$'), $ip);
}

function CMask($ip) {
	$IPelements = explode('.', $ip);
	return $IPelements[0].'.'.@$IPelements[1].'.'.@$IPelements[2];
}

function PadIPtext($ip, $htmlspaces=false) {
	$IPelements = explode('.', $ip);
	for ($i = 0; $i <= 3; $i++) {
		$paddedIP[$i] = str_pad($IPelements[$i], 3, ' ', STR_PAD_LEFT);
	}
	$paddedIP = implode('.', $paddedIP);
	if ($htmlspaces) {
		$paddedIP = str_replace(' ', '&nbsp;', $paddedIP);
	}
	return $paddedIP;
}

function UpdateSpamBannedIPs($domain, $ip, $IPmask='') {
	if ($IPmask) {
		$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'ips`';
		$SQLquery .= ' SET `lasthit` = "'.mysql_escape_string(time()).'"';
		$SQLquery .= ', `hitcount` = `hitcount` + 1';
		$SQLquery .= ' WHERE (`ipmask` = "'.mysql_escape_string($IPmask).'")';
		mysql_query_safe($SQLquery);
	}

	$SQLquery  = 'REPLACE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'domain_hits` (`domain`, `ip`, `lasthit`) VALUES (';
	$SQLquery .= '"'.mysql_escape_string($domain).'", ';
	$SQLquery .= '"'.mysql_escape_string($ip).'", ';
	$SQLquery .= '"'.mysql_escape_string(time()).'")';
	mysql_query_safe($SQLquery);

	return true;
}

function IncrementHistory($account, $type, $quantity=1) {
	static $todaytestamp = '';
	if (!$todaytestamp) {
		$todaytestamp = date('Ymd');
	}

	switch ($type) {
		case 'good':
		case 'spam':
		case 'virus':
		case 'corrupt':
			// good
			break;
		default:
			return false;
	}
	$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'history` SET';
	$SQLquery .= '`'.$type.'` = `'.$type.'` + '.intval($quantity);
	$SQLquery .= ' WHERE (`datestamp` = "'.mysql_escape_string($todaytestamp).'")';
	$SQLquery .= ' AND (`account` = "'.mysql_escape_string($account).'")';
	mysql_query_safe($SQLquery);
	if (!mysql_affected_rows()) {
		$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'history` (`account`, `datestamp`, `'.$type.'`) VALUES (';
		$SQLquery .= '"'.mysql_escape_string($account).'", ';
		$SQLquery .= '"'.mysql_escape_string($todaytestamp).'", ';
		$SQLquery .= '"'.intval($quantity).'")';
		mysql_query_safe($SQLquery);
	}
	return true;
}

function InsertSpamScanned($account, $messageid, $from, $subject, $date) {
	$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'messages_scanned` (`account`, `messageid`, `from`, `subject`, `date`) VALUES (';
	$SQLquery .= '"'.mysql_escape_string($account).'", ';
	$SQLquery .= '"'.mysql_escape_string($messageid).'", ';
	$SQLquery .= '"'.mysql_escape_string($from).'", ';
	$SQLquery .= '"'.mysql_escape_string($subject).'", ';
	$SQLquery .= '"'.mysql_escape_string($date).'")';
	if (!mysql_query_safe($SQLquery)) {
		$errormessage = mysql_error();
		if (!preg_match(preg_expression('^(Can\'t open file|MySQL server has gone away)', 'i'), $errormessage)) {
			WarningEmail('NewMessageInsert SQL failed', $SQLquery."\n\n".$errormessage);
		}
	}
	return true;
}

function InsertRecent($account, $messageid, $header, $MessageContents, $DebugMessages) {
	global $nowtime;
	$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'messages_recent` (`id`, `date`, `account`, `headers`, `body`, `debug`) VALUES (';
	$SQLquery .= '"'.mysql_escape_string($messageid).'", ';
	$SQLquery .= '"'.mysql_escape_string($nowtime).'", ';
	$SQLquery .= '"'.mysql_escape_string($account).'", ';
	$SQLquery .= '"'.mysql_escape_string($header).'", ';
	$SQLquery .= '"'.mysql_escape_string($MessageContents).'", ';
	$SQLquery .= '"'.mysql_escape_string(implode("\n", $DebugMessages)).'")';
	if (!mysql_query_safe($SQLquery)) {
		$errormessage = mysql_error();
		if (!preg_match(preg_expression('^(Can\'t open file|MySQL server has gone away)', 'i'), $errormessage)) {
			WarningEmail('NewMessageInsert SQL failed', $SQLquery."\n\n".$errormessage);
		}
	}
	return true;
}

function ExtractDomain($domainstring) {
	$domainstring = preg_replace(preg_expression('\\s'), '', $domainstring);
	$domain = '';
	if (preg_match(preg_expression(PHPOP3CLEAN_HTTPDOMAIN, 'i'), $domainstring, $matches)) {
		$domain = $matches[4];
	} elseif (preg_match(preg_expression(PHPOP3CLEAN_EMAILDOMAIN, 'i'), $domain, $matches)) {
		$domain = $matches[2];
	}
	if ($domain) {
		$domain = eregi_replace('[^a-z0-9\\.\\-]', '', $domain);
		$domain = eregi_replace('[\\.]+', '.', $domain);
		$domain = trim($domain, '.');
		return $domain;
	}
	return false;
}

function ExtractDomainsFromText(&$text, &$noHTMLtext, $returnSubdomainsOnly=false) {
	$DomainsToLookup = array();

	$HTMLpatterns = array(
		// match anything in certain HTML tags, which may include whitespace breaking the URL itself
		//'<a[^>]+href=["\']?([^>]+)',           // <a href="*">
		'<a[^>]+href=["\']?([^<> ]+)',         // <a href="*">
		'<img[^>]+src=["\']?([^>]+)',          // <img src="*">
		'<t[dh][^>]+background=["\']?([^>]+)', // <td background="*">
	);
	foreach ($HTMLpatterns as $pattern) {
		preg_match_all(preg_expression($pattern, 'i'), $text, $URLs, PREG_PATTERN_ORDER);
		foreach ($URLs[1] as $domainstring) {
			$domainstring = urldecode($domainstring);
			$domainstring = eregi_replace('[^a-z0-9/:\\.\\-]', '', $domainstring);
			if (!preg_match(preg_expression('^(ht|f)tp://', 'i'), $domainstring)) {
				$domainstring = 'http://'.$domainstring;
			}
			if ($domain = ExtractDomain($domainstring)) {
				@$DomainsToLookup[$domain]++;
			}
		}
	}

	$sourcepatterns = array(
		PHPOP3CLEAN_HTTPDOMAIN                                                              => 4,  // match all http://(something.domain.here)/whatever?after=irrelvant
		'http:[/\\\\]{1,2}[a-z\\.]*google(\\.[a-z]{2,3}){1,2}/url\\?q=([%0-9a-z:/\\.\\-]+)' => 2,  // match all http://www.google.com/url?q=%68%74%74%70%3a%2f%2f%61%62%63%67%68...
		//'http://rds.yahoo.com/[a-z0-9\\=/]+/\\*-http://([a-z0-9\\-\\.]+)'                   => 1,  // Yahoo redirection exploit
	);
	$noHTMLpatterns = array(
		PHPOP3CLEAN_HTTPDOMAIN                => 4,  // match all http://(something.domain.here)/whatever?after=irrelvant
		'\\s(www\\.[a-z0-9\\.\\-]+)[^a-z0-9]' => 1,  // match all (www.domain.here)/whatever?after=irrelvant
		PHPOP3CLEAN_EMAILDOMAIN               => 2,  // match all user_name@(domain.spam)?subject=irrelevant
	);

	// Note: match both domains in stuff like: "http://rd.yahoo.com/guenther\extempore\redpoll\*hTtP:\\3op6nS94Q58T.mens545q.com/tp/iNdeX.ASP"
	// "&" is matched in domain name and then later stripped out before resolving
	foreach ($sourcepatterns as $pattern => $key) {
		preg_match_all(preg_expression($pattern, 'i'), $text, $URLs, PREG_PATTERN_ORDER);
		if (is_array($URLs[$key])) {
			foreach ($URLs[$key] as $domain) {
				$decoded = urldecode($domain);
				if ($decoded != $domain) {
					if (preg_match(preg_expression(PHPOP3CLEAN_HTTPDOMAIN, 'i'), $decoded, $matches)) {
						$domain = $matches[4];
					}
				}
				$domain = eregi_replace('[^a-z0-9\\.\\-]', '', $domain);
				@$DomainsToLookup[$domain]++;
			}
		}
	}
	foreach ($noHTMLpatterns as $pattern => $key) {
		preg_match_all(preg_expression($pattern, 'i'), $noHTMLtext, $URLs, PREG_PATTERN_ORDER);
		if (is_array($URLs[$key])) {
			foreach ($URLs[$key] as $domain) {
				$domain = urldecode($domain);
				$domain = ltrim(strtolower($domain), '.');  // some spam domain are like "http://.3291enhanceme.biz"
				@$DomainsToLookup[$domain]++;
			}
		}
	}
	if ($returnSubdomainsOnly) {
		$AllSubdomains = array();
		foreach ($DomainsToLookup as $domain => $count) {
			HTMLentitiesDecode($domain);
			$AllTheseSubdomains = ExtractAllSubdomains($domain);
			foreach ($AllTheseSubdomains as $key => $subdomain) {
				$AllSubdomains[] = $subdomain;
			}
		}
		return $AllSubdomains;
	}

	$ResolvedDomains = array();
	foreach ($DomainsToLookup as $domain => $count) {
		HTMLentitiesDecode($domain);
		$AllTheseSubdomains = ExtractAllSubdomains($domain);
		foreach ($AllTheseSubdomains as $key => $subdomain) {
			if (!@$ResolvedDomains[$subdomain]) {
				$ResolvedDomains[$subdomain] = DomainLookupIP($subdomain);
			}
		}
	}
	return $ResolvedDomains;
}


function ObviateObfuscatedIPs($domain) {
	if (preg_match(preg_expression('^[0-9]{8,10}$'), $domain)) {
		// integer IP: localhost = http://2130706433/
		$domain = long2ip($domain);
	} elseif (preg_match_all('#(0x0*[0-9a-f]+)#i', $domain, $matches)) {
		// 192.168.2.1 = 0xC0A80201
		// 192.168.2.1 = 0xC0.0xA80201
		// 192.168.2.1 = 0xC0.0xA8.0x0201
		// 192.168.2.1 = 0xC0.0xA8.0x02.0x01
		$intIP = 0;
		for ($i = 0; $i < count($matches[1]); $i++) {
			$shift = (($i == (count($matches[1]) - 1)) ? 1 : pow(256, 3 - $i));
			$intIP += hexdec($matches[1][$i]) * $shift;
		}
		$domain = long2ip($intIP);
	} elseif (preg_match(preg_expression('^([0-9a-fx\\.]+)$', 'i'), $domain, $matches)) {
		// mixed IP: localhost = http://0x7f.0.0000.0x0001/
		$parts = explode('.', $domain);
		if (count($parts) != 4) {
			// wrong count, abort
			return $domain;
		}
		for ($i = 0; $i <= 3; $i++) {
			$decoded[$i] = intval($parts[$i]);
			if (preg_match(preg_expression('^0x0*([0-9a-f]+)$', 'i'), $parts[$i], $matches)) {
				// hex IP: localhost = http://0x7f000001/
				$decoded[$i] = hexdec($matches[1]);
			} elseif (preg_match(preg_expression('^0+([0-7]+)$', 'i'), $parts[$i], $matches)) {
				// octal IP: localhost = http://000000177.000000000.000000000.000000001/
				$decoded[$i] = octdec(ltrim($matches[1], '0'));
			}
		}
		return implode('.', $decoded);
	}
	return $domain;
}


function ExtractAllSubdomains($domainstolookup) {
	if (!is_array($domainstolookup)) {
		$domainstolookup = array($domainstolookup);
	}
	$DomainList = array();
	foreach ($domainstolookup as $key => $domaintolookup) {
		if (is_array($domaintolookup) && IsIP(@$domaintolookup[0])) {
			continue;
		}
		$domaintolookup = ObviateObfuscatedIPs($domaintolookup);

		if (IsIP($domaintolookup)) {

			// just add the IP to the list for processing
			$DomainList[] = $domaintolookup;

		} else {

			// split subdomains out from the parent domain and look up each (since the IP may vary from subdomain to parent domain):
			// this could be www.example.com -> www.example.com + example.com
			// or some.random.subdomain.example.com -> some.random.subdomain.example.com + random.subdomain.example.com + subdomain.example.com + example.com
			//$domainpartarray = explode('.', eregi_replace('(http[s]?:[/\\\\]{1,2})?([a-z0-9]+:)?([a-z0-9]+@)?', '', $domaintolookup));
			$domainpartarray = explode('.', ExtractDomain('http://'.$domaintolookup));
			$domainpartarrayR = array_reverse($domainpartarray);
			$keeplast = 1;
			if ((strlen(@$domainpartarrayR[0]) == 2) && (strlen(@$domainpartarrayR[1]) <= 3) && @$domainpartarrayR[2]) {
				// .co.uk, .com.tw, etc
				$keeplast = 2;
			}
			$reconstructed = array();
			for ($i = 0; $i < $keeplast; $i++) {
				$reconstructed[] = $domainpartarrayR[$i];
			}
			for ($i = $keeplast; $i < count($domainpartarrayR); $i++) {
				$reconstructed[] = $domainpartarrayR[$i];
				$subdomain = implode('.', array_reverse($reconstructed));
				$DomainList[] = $subdomain;
			}

		}
	}
	return $DomainList;
}


function BaseDomain($domain) {
	if (IsIP($domain)) {
		return $domain;
	}
	if (strpos($domain, '.') === false) {
		return $domain;
	}
	$domainParts = explode('.', strtolower($domain));
	$domainParts = array_reverse($domainParts);
	if (eregi('\\.[a-z]{2,3}\\.[a-z]{2}$', $domain)) {
		// .com.au  .co.uk
		return $domainParts[2].'.'.$domainParts[1].'.'.$domainParts[0];
	}
	return $domainParts[1].'.'.$domainParts[0];
}


function BanIP($ip) {
	static $bannedIPs = array();
	if (isset($bannedIPs[$ip])) {
		return true;
	}
	$bannedIPs[$ip] = true;

	$startingMask = 32;
	$cmask = CMask($ip);

	$SQLquery  = 'SELECT `ipmask` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ips`';
	$SQLquery .= ' WHERE (`cmask` = "'.mysql_escape_string($cmask).'")';
	$SQLquery .= ' AND (`whitelist` = "0")';
	$result = mysql_query_safe($SQLquery);
	if ($row = mysql_fetch_assoc($result)) {
		list($oldIP, $oldMask) = explode('/', $row['ipmask']);
		$startingMask = min($oldMask, $startingMask);
	}
	@mysql_free_result($result);


	$IPlastPart = substr(strrchr($ip, '.'), 1);
	for ($i = $startingMask; $i >= 24; $i--) {
		list($min, $max) = IPrangeMinMax($ip.'/'.$i);
		$IPlastMin = substr(strrchr(long2ip($min), '.'), 1);
		$IPlastMax = substr(strrchr(long2ip($max), '.'), 1);
		if (($IPlastPart >= $IPlastMin) && ($IPlastPart <= $IPlastMax)) {
			break;
		}
	}
	if ($i < 24) {
		WarningEmail('BanIP('.$ip.') failed', 'BanIP('.$ip.') failed to find match in /'.$startingMask.'-/24');
		return false;
	}
	$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'ips` SET';
	$SQLquery .= ' `ipmask` = "'.mysql_escape_string($ip.'/'.$i).'", ';
	$SQLquery .= ' `lasthit` = "'.mysql_escape_string(time()).'", ';
	$SQLquery .= ' `hitcount` = `hitcount` + 1';
	$SQLquery .= ' WHERE (`cmask` = "'.mysql_escape_string($cmask).'")';
	$SQLquery .= ' AND (`whitelist` = "0")';
	mysql_query_safe($SQLquery);
	if (!mysql_affected_rows()) {
		$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'ips` (`ipmask`, `cmask`, `added`, `lasthit`, `hitcount`) VALUES (';
		$SQLquery .= '"'.mysql_escape_string($ip.'/'.$i).'", ';
		$SQLquery .= '"'.mysql_escape_string($cmask).'", ';
		$SQLquery .= '"'.mysql_escape_string(time()).'", ';
		$SQLquery .= '"'.mysql_escape_string(time()).'", ';
		$SQLquery .= '"'.mysql_escape_string('1').'")';
		mysql_query_safe($SQLquery);
	}
	return true;
}

function BlackListedDomainIP($ResolvedDomains) {
	static $FailedLookupDomainCache = array();
	static $IPblacklistCache        = array();
	static $IPcMaskCache            = array();
	static $BaseDomainAutoBan       = array();

	$BlackListedDomainIP = array();

	foreach ($ResolvedDomains as $domain => $IPs) {
		$basedomain = BaseDomain($domain);
		if (!isset($BaseDomainAutoBan[$basedomain])) {
			$BaseDomainAutoBan[$basedomain] = false;

			$SQLquery  = 'SELECT `domain`';
			$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_autoban`';
			$SQLquery .= ' WHERE (`domain` = "'.mysql_escape_string($basedomain).'")';
			$result = mysql_query_safe($SQLquery);
 			if ($row = mysql_fetch_assoc($result)) {
				$BaseDomainAutoBan[$basedomain] = true;
				foreach ($IPs as $dummy => $autoban_ip) {
					if (!IsIP($autoban_ip)) {
						continue;
					}
					$IPblacklistCache[$autoban_ip] = true;
					@$BlackListedDomainIP[$autoban_ip][] = $basedomain;
					BanIP($autoban_ip);
				}
			}
			@mysql_free_result($result);
		}
		if ($BaseDomainAutoBan[$basedomain] === true) {
			$BlackListedDomainIP[$basedomain][] = $basedomain;
			$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_autoban` SET';
			$SQLquery .= ' `hitcount` = `hitcount` + 1,';
			$SQLquery .= ' `lasthit` = "'.mysql_escape_string(time()).'"';
			$SQLquery .= ' WHERE (`domain` = "'.mysql_escape_string($basedomain).'")';
			mysql_query_safe($SQLquery);
			@mysql_free_result($result);
			continue;
		}
		foreach ($IPs as $dummy => $ip) {
			if (!IsIP($ip)) {
				if (isset($FailedLookupDomainCache[$ip])) {
					$ip = $FailedLookupDomainCache[$ip];
				} else {
					$SQLquery  = 'SELECT `ip` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domain_hits`';
					$SQLquery .= ' WHERE (`domain` = "'.mysql_escape_string($domain).'")';
					$SQLquery .= ' ORDER BY `lasthit` DESC';
					$result = mysql_query_safe($SQLquery);
					$FailedLookupDomainCache[$ip] = false;
					if ($row = mysql_fetch_assoc($result)) {
						$FailedLookupDomainCache[$ip] = $row['ip'];
						$ip = $row['ip'];
					}
					@mysql_free_result($result);
				}
			}
			if (IsIP($ip)) {
				// if the name actually resolves to an IP
				if (isset($IPblacklistCache[$ip])) {
					// this IP has been examined before
					if ($IPblacklistCache[$ip]) {
						// the IP is bad
						@$BlackListedDomainIP[$ip][] = $domain;
						UpdateSpamBannedIPs($domain, $ip);
					} else {
						// good ip, do nothing
					}
				} else {
					// this IP is new

					if (!isset($IPblacklistCache[$ip])) {
						$longIP = SafeIP2Long($ip);

						$CMask = CMask($ip);
						if (!isset($IPcMaskCache[$CMask])) {
							$SQLquery  = 'SELECT `ipmask` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ips`';
							$SQLquery .= ' WHERE (`cmask` = "'.mysql_escape_string($CMask).'")';
							$SQLquery .= ' AND (`whitelist` = "0")';
							$SQLquery .= ' ORDER BY `lasthit` DESC';
							$result = mysql_query_safe($SQLquery);
							if ($row = mysql_fetch_assoc($result)) {
								$IPcMaskCache[$CMask] = $row['ipmask'];
							} else {
								$IPcMaskCache[$CMask] = false;
							}
							@mysql_free_result($result);
						}
						if ($IPcMaskCache[$CMask]) {
							list($min, $max) = IPrangeMinMax($IPcMaskCache[$CMask]);
							if (($longIP >= $min) && ($longIP <= $max)) {
								@$BlackListedDomainIP[$ip][] = $domain;
								UpdateSpamBannedIPs($domain, $ip, $IPcMaskCache[$CMask]);
								$IPblacklistCache[$ip] = true;
								break 2;
							}
							$IPblacklistCache[$ip] = false;
						}
					}
				}
			} else {
				//WarningEmail('IP lookup failed', 'DomainLookupIP('.$domain.') failed');
			}
		}
	}
	return $BlackListedDomainIP;
}

function WarningEmail($subject, $text) {
	return mail(PHPOP3CLEAN_ADMINEMAIL, 'phPOP3clean - '.$subject, $text, 'From: phPOP3clean v'.PHPOP3CLEAN_VERSION.' <'.PHPOP3CLEAN_ADMINEMAIL.'>');
}

function getmicrotime() {
	list($usec, $sec) = explode(' ', microtime());
	return ((float) $usec + (float) $sec);
}

function DomainResolvesToTooManyVariedIPs($domain, $IPs, $IPcountThreshold=10, $AMaskThreshold=5, $AMaskRatio=2) {
	if (count($IPs) >= $IPcountThreshold) {
		$AMask = array();
		foreach ($IPs as $IP) {
			$IPparts = explode('.', $IP);
			@$AMask[$IPparts[0]]++;
		}
		if ((count($AMask) >= $AMaskThreshold) || (count($AMask) > (count($IPs) / $AMaskRatio))) {
			$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_autoban` (`domain`, `added`) VALUES (';
			$SQLquery .= ' "'.mysql_escape_string(strtolower(BaseDomain($domain))).'",';
			$SQLquery .= ' "'.mysql_escape_string(time()).'")';
			mysql_query_safe($SQLquery);
			//WarningEmail('Too many different IPs ('.$domain.')', $domain.' resolves to too many different IPs: '."\n".print_r($IPs, true));
			foreach ($IPs as $IP) {
				BanIP($IP);
				DeleteOldMessagesWithIP($IP);
			}
			return true;
		}
	}
	return false;
}

function InfectedAttachmentCheck($piece_filename, &$BinaryAttachmentData, &$ThisIsBad, &$WhyItsBad) {
	global $LoginInfoArray, $DebugMessages;

	static $IsBadInfectedAttachmentCache = array();
	$cachekey = strtolower(strlen($BinaryAttachmentData).md5($BinaryAttachmentData));
	if (isset($IsBadInfectedAttachmentCache[$cachekey])) {
		$ThisIsBad = $IsBadInfectedAttachmentCache[$cachekey]['ThisIsBad'];
		$WhyItsBad = $IsBadInfectedAttachmentCache[$cachekey]['WhyItsBad'];
		return true;
	}

	$piece_fileext = 'exe'; // assume the worst
	if (preg_match(preg_expression('\\.([a-z0-9]{2,4})$', 'i'), $piece_filename, $extension_matches)) {
		// set to specified file extension unless overridden below
		$piece_fileext = strtolower($extension_matches[1]);
		$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Setting $piece_fileext to "'.$piece_fileext.'" based on filename ('.$piece_filename.')';
	}

	// borrowed from GetFileFormatArray() in getid3.php from http://getid3.sourceforge.net
	$filetype_lookup = array(
		'exe'  => '^MZ',
		'zip'  => '^PK\\x03\\x04',
		'gif'  => '^GIF',
		'jpeg' => '^\\xFF\\xD8\\xFF',
		'uue'  => '^(begin|start) 0?[0-7]{3} ([^\\x0D\\x0A]+)[\\x0D\\x0A]+',
		'gz'   => '^\\x1F\\x8B\\x08',
		'rar'  => '^Rar\\!',
		'png'  => '^\\x89\\x50\\x4E\\x47\\x0D\\x0A\\x1A\\x0A',
		'tiff' => '^(II\\x2A\\x00|MM\\x00\\x2A)',
		'bmp'  => '^BM',
		'swf'  => '^[FC]WS',
	);
	foreach ($filetype_lookup as $ext => $pattern) {
		if (preg_match('/'.$pattern.'/s', $BinaryAttachmentData)) {
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Changing $piece_fileext from "'.$piece_fileext.'" to "'.$ext.'" because matches ('.$pattern.')';
			$piece_fileext = $ext;
			break;
		}
	}
	$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] substr($BinaryAttachmentData, 0, 4) = "'.PrintHexBytes(substr($BinaryAttachmentData, 0, 4)).'"';
	$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] $piece_fileext = "'.$piece_fileext.'"';

	switch ($piece_fileext) {
		case 'cmd':
		case 'bat':
		case 'vbs':
		case 'cpl':
		case 'hta':
		case 'pif':
		case 'scr':
		case 'com':
		case 'exe':
		case 'msi':
		case 'bhx':
		case 'hqx':
			InfectedAttachmentDatabaseCheckSave($piece_filename, $BinaryAttachmentData, $ThisIsBad, $WhyItsBad);
			break;

		case 'mim':
		case 'uue':
			if (UUdecode($BinaryAttachmentData, $UUdecoded, $filename)) {
				$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] UUdecode('.$piece_filename.') succeeded, '.strlen($BinaryAttachmentData).' bytes to '.strlen($UUdecoded).' bytes';
				InfectedAttachmentDatabaseCheckSave($filename, $UUdecoded, $ThisIsBad, $WhyItsBad);
			} else {
				$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] UUdecode('.$piece_filename.') FAILED';
				WarningEmail('failed to parse UUencoded attachment', $LoginInfoArray['email']."\n".__FILE__.' failed to parse UUencoded attachment "'.$piece_filename.'" on line '.__LINE__."\n\n\n".wordwrap(base64_encode($BinaryAttachmentData), 75, "\n", 1));
				EchoToScreen('<font color="red">failed to parse UUencoded attachment <b>'.$piece_filename.'</b></font>'."\n");
			}
			break;

		case 'taz':
		case 'zip':
			if (@include_once('phPOP3clean.getid3.lib.php')) {
				if (@include_once('phPOP3clean.getid3.module.archive.zip.php')) {
					if ($fp_ziptemp = tmpfile()) {

						fwrite($fp_ziptemp, $BinaryAttachmentData);
						$ThisFileInfo = array('fileformat'=>'', 'error'=>array(), 'filesize'=>strlen($BinaryAttachmentData));
						$getid3_zip = new getid3_zip($fp_ziptemp, $ThisFileInfo);

						if (($ThisFileInfo['fileformat'] == 'zip') && !empty($ThisFileInfo['zip']['files'])) {
							EchoToScreen('<font color="purple">zip file <b>'.$piece_filename.'</b> contains:</b></font>'."\n");
							if (!empty($ThisFileInfo['zip']['central_directory'])) {
								$ZipDirectoryToWalk = $ThisFileInfo['zip']['central_directory'];
							} elseif (!empty($ThisFileInfo['zip']['entries'])) {
								$ZipDirectoryToWalk = $ThisFileInfo['zip']['entries'];
							} else {
								EchoToScreen('<font color="orangered">failed to parse ZIP attachment "<b>'.$piece_filename.'</b>" (no central directory)</font>'."\n");
								WarningEmail('failed to parse ZIP attachment', $LoginInfoArray['email']."\n".__FILE__.' failed to parse ZIP attachment "'.$piece_filename.'" (no central directory) on line '.__LINE__);
								fclose($fp_ziptemp);
								break;
							}
							foreach ($ZipDirectoryToWalk as $key => $valuearray) {
								if (preg_match(preg_expression('\\.(cmd|bat|vbs|cpl|hta|pif|scr|com|msi|exe)$', 'i'), $valuearray['filename'])) {
									fseek($fp_ziptemp, $valuearray['entry_offset'], SEEK_SET);
									$LocalFileHeader = $getid3_zip->ZIPparseLocalFileHeader($fp_ziptemp);
									if ($LocalFileHeader['flags']['encrypted']) {
										// password-protected, use heuristics on filename only
										if (preg_match(preg_expression('\\.(cmd|bat|vbs|cpl|hta|pif|scr|com)$', 'i'), $valuearray['filename']) || preg_match(preg_expression('\\.(txt|rtf|doc|htm|html)\\s*\\.exe$', 'i'), $valuearray['filename'])) {
											EchoToScreen('<font color="red">* [password-protected] <b>'.$valuearray['filename'].'</b></font>'."\n");
											$ThisIsBad = true;
											$WhyItsBad = 'Illegal attachment ('.$valuearray['filename'].') in zip file ('.$piece_filename.')';
											fclose($fp_ziptemp);
											break 2;
										} else {
											EchoToScreen('<font color="purple">* [password-protected] <b>'.$valuearray['filename'].'</b></font>'."\n");
										}
									} else {
										fseek($fp_ziptemp, $LocalFileHeader['data_offset'], SEEK_SET);
										$compressedFileData = '';
										while ((strlen($compressedFileData) < $LocalFileHeader['compressed_size']) && !feof($fp_ziptemp)) {
											$compressedFileData .= fread($fp_ziptemp, 4096);
										}
										switch ($LocalFileHeader['raw']['compression_method']) {
											case 0:
												// store - great, do nothing at all
												$uncompressedFileData = $compressedFileData;
												break;

											case 8:
												$uncompressedFileData = gzinflate($compressedFileData);
												break;

											default:
												WarningEmail('unknown ZIP compression method ('.$LocalFileHeader['raw']['compression_method'].')', $LoginInfoArray['email']."\n".__FILE__.' failed to parse ZIP attachment "'.$piece_filename.'" due to unknown ZIP compression method ('.$LocalFileHeader['raw']['compression_method'].') on line '.__LINE__."\n\n\n".wordwrap(base64_encode($BinaryAttachmentData), 75, "\n", 1));
												continue 2;
										}
										unset($compressedFileData);
										InfectedAttachmentDatabaseCheckSave($valuearray['filename'], $uncompressedFileData, $ThisIsBad, $WhyItsBad);
										unset($uncompressedFileData);
										if ($ThisIsBad) {
											$WhyItsBad = 'Zipped '.$WhyItsBad;
											fclose($fp_ziptemp);
											break 2;
										}
									}
								} else {
									EchoToScreen('<font color="green">* <b>'.$valuearray['filename'].'</b></font>'."\n");
								}
							}

						} else {

							// could be an EXE named as .zip
							InfectedAttachmentDatabaseCheckSave($piece_filename.'.exe', $BinaryAttachmentData, $ThisIsBad, $WhyItsBad);
							if ($ThisIsBad === false) {
								//WarningEmail('failed to parse ZIP attachment', $LoginInfoArray['email']."\n".__FILE__.' failed to parse ZIP attachment "'.$piece_filename.'" on line '.__LINE__."\n\n\n".wordwrap(base64_encode($BinaryAttachmentData), 75, "\n", 1));
								EchoToScreen('<font color="red">failed to parse ZIP attachment <b>'.$piece_filename.'</b></font>'."\n");
							//} else {
								//WarningEmail('ZIP attachment masquerading', $LoginInfoArray['email']."\n".__FILE__.' failed to parse ZIP attachment "'.$piece_filename.'", but it was found to be an infected EXE ('.$WhyItsBad.'), on line '.__LINE__."\n\n\n".wordwrap(base64_encode($BinaryAttachmentData), 75, "\n", 1));
							}

						}
						fclose($fp_ziptemp);

					} else {
						WarningEmail('failed to make tmpfile()', __FILE__.' failed tmpfile() on line '.__LINE__);
						EchoToScreen('<font color="red">failed to make tmpfile() trying to parse ZIP attachment <b>'.$piece_filename.'</b></font>'."\n");
					}
				} else {
					WarningEmail('failed to include phPOP3clean.getid3.module.archive.zip.php', __FILE__.' failed to include "phPOP3clean.getid3.module.archive.zip.php" on line '.__LINE__);
					EchoToScreen('<font color="red">failed to include phPOP3clean.getid3.module.archive.zip.php</font>'."\n");
				}
			} else {
				WarningEmail('failed to include phPOP3clean.getid3.lib.php', __FILE__.' failed to include "phPOP3clean.getid3.lib.php" on line '.__LINE__);
				EchoToScreen('<font color="red">failed to include phPOP3clean.getid3.lib.php</font>'."\n");
			}
			break;

		case 'gif':
		case 'bmp':
		case 'jpg':
		case 'jpeg':
		case 'pjpeg':
		case 'png':
			BannedImageAttachmentDatabaseCheckSave($piece_filename, $BinaryAttachmentData, $ThisIsBad, $WhyItsBad);
			break;

		case 'txt':
		case 'htm':
		case 'html':
			$noHTML = strip_tags($BinaryAttachmentData);
			$ResolvedDomains = ExtractDomainsFromText($BinaryAttachmentData, $noHTML);
			foreach ($ResolvedDomains as $domain => $IPs) {
				if (DomainResolvesToTooManyVariedIPs($domain, $IPs)) {
					$ThisIsBad = true;
					$WhyItsBad = 'domain resolves to too many IPs ('.$domain.':'.implode(',', $IPs).')';
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: '.$WhyItsBad;
					break;
				}
				foreach ($IPs as $IP) {
					$DNSBL_IPs = DNSBLlookup($IP);
					if (is_array($DNSBL_IPs) && (count($DNSBL_IPs) > 0)) {
						//BanIP($ip);
						$ThisIsBad = true;
						$WhyItsBad = 'DNSBL IP in body ('.$domain.':'.$IP.')';
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: '.$WhyItsBad;
						break;
					}
				}
			}
			if (!$ThisIsBad) {
				$BannedIPdomains = BlackListedDomainIP($ResolvedDomains);
				if (is_array($BannedIPdomains) && (count($BannedIPdomains) > 0)) {
					$bad_IPs_found = array_keys($BannedIPdomains);
					foreach ($bad_IPs_found as $bad_IP_found) {
						DeleteOldMessagesWithIP($bad_IP_found);
					}
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] found: Banned IP in ".'.$piece_fileext.'" attachment ('.implode(';', $bad_IPs_found).')';

					EchoToScreen('<font color="red">found banned IP in attachment "'.htmlentities($piece_filename).'": "'.implode(';', $bad_IPs_found).'"</font>'."\n");
					$ThisIsBad = true;
					$WhyItsBad = 'Banned IP in ".'.$piece_fileext.'" attachment ('.implode(';', $bad_IPs_found).')';
				} else {
					$domainlist = array();
					foreach ($ResolvedDomains as $domain => $IPs) {
						$domainlist[] = $domain.' ['.implode(';', $IPs).']';
					}
					EchoToScreen('<font color="green">Attachment (text/html): <b>'.htmlentities($piece_filename).'</b> Domains: ( '.implode(' :: ', $domainlist).' )</font>'."\n");
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] found: no Banned IPs in ".'.$piece_fileext.'" attachment. Domains: ( '.implode(' :: ', $domainlist).' )';
				}
			}
			unset($domain, $IPs, $domainlist, $noHTML, $ResolvedDomains, $BannedIPdomains, $DNSBL_IPs);
			break;

		default:
			EchoToScreen('<font color="green">Attachment: <b>'.htmlentities($piece_filename).'</b></font>'."\n");
			break;
	}
	$IsBadInfectedAttachmentCache[$cachekey]['ThisIsBad'] = $ThisIsBad;
	$IsBadInfectedAttachmentCache[$cachekey]['WhyItsBad'] = $WhyItsBad;
	return true;
}


function FilteredBinaryDataMD5(&$filedata, $pattern) {
	return md5(FilteredBinaryData($filedata, $pattern));
}

function FilteredBinaryData(&$filedata, $pattern) {
	// $pattern looks like this (ex: Beagle.AV) "17440|144-146;204;205;480;481;488;489"
	if (strlen($filedata) == 0) {
		return false;
	}
	if (!$pattern) {
		return md5($filedata);
	}
	@list($basesize, $garbagebytelist) = explode('|', $pattern);
	$standardizeddata = substr($filedata, 0, $basesize);
	$garbagebytelist = preg_replace(preg_expression('[^0-9;\\-]'), '', $garbagebytelist);
	if ($garbagebytelist) {
		$garbagebytes = explode(';', $garbagebytelist);
		foreach ($garbagebytes as $byteaddr) {
			if ($byteaddr) {
				if (preg_match(preg_expression('^([0-9]+)\\-([0-9]+)$'), $byteaddr, $matches) && ($matches[2] > $matches[1])) {
					for ($i = $matches[1]; $i <= $matches[2]; $i++) {
						$standardizeddata{$i} = "\x00";
					}
				} else {
					$standardizeddata{$byteaddr} = "\x00";
				}
			}
		}
	}
	return $standardizeddata;
}


function InfectedAttachmentDatabaseCheckSave($piece_filename, &$BinaryAttachmentData, &$ThisIsBad, &$WhyItsBad) {
	preg_match(preg_expression('\\.([a-z0-9]{2,4})$', 'i'), $piece_filename, $extension_matches);
	$piece_fileext = strtolower(@$extension_matches[1]);

	switch ($piece_fileext) {
		case 'cmd':
		case 'bat':
		case 'vbs':
		case 'cpl':
		case 'hta':
		case 'pif':
		case 'scr':
		case 'com':
		case 'exe':
		case 'msi':
		case 'bhx':
		case 'hqx':
		case 'mim':
		case 'uue':
			$exe_filesize = strlen($BinaryAttachmentData);
			$exe_md5hash  = md5($BinaryAttachmentData);
			$VirusRecord  = false;

			$SQLquery  = 'SELECT `virus_name` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'exe`';
			$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($exe_md5hash).'")';
			$SQLquery .= ' AND ((`pattern` = "") OR (`pattern` = "'.$exe_filesize.'|"))';
			$result = mysql_query_safe($SQLquery);
			if ($row = mysql_fetch_assoc($result)) {

				$VirusRecord = $row;

			} else {

				// Many viruses, (Beagle, Netsky, et al) either have a fixed infection length (e.g. 19968 bytes)
				// after which is appended a random amount of garbage data, and/or include a few random bytes
				// thrown into the middle of the "static" infection length. Either case renders a static MD5 value useless.
				// Solution: Truncate the file data to the static length, and force all "random" bytes to null

				$SQLquery  = 'SELECT `md5`, `virus_name`, `pattern`, `filesize` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'exe`';
				$SQLquery .= ' WHERE ((`pattern` <> "") AND (`pattern` <> "'.$exe_filesize.'|"))';
				$result = mysql_query_safe($SQLquery);
				while ($row = mysql_fetch_assoc($result)) {
					if ($row['md5'] == FilteredBinaryDataMD5($BinaryAttachmentData, $row['pattern'])) {
						//WarningEmail('virus partial MD5 match', "Real:\n$exe_filesize bytes\n$exe_md5hash\n\nMatched:\n".$row['filesize']." bytes\n".$row['md5']."\n".$row['virus_name']."\nPattern: ".$row['pattern']);

						$VirusRecord = $row;
						$exe_filesize = $row['filesize'];
						$exe_md5hash  = $row['md5'];
						break;
					}
				}

			}
			@mysql_free_result($result);

			if ($VirusRecord) {

				if (empty($VirusRecord['virus_name'])) {

					switch ($piece_fileext) {
						case 'cmd':
						case 'bat':
						case 'vbs':
						case 'cpl':
						case 'hta':
						case 'pif':
						case 'scr':
						case 'msi':
						case 'com':
						case 'bhx':
						case 'hqx':
						case 'mim':
						case 'uue':
							EchoToScreen('<font color="red">has a BAD attachment: <b>'.$piece_filename.'</b></font>'."\n");
							$ThisIsBad = true;
							$WhyItsBad = 'Illegal attachment ('.$piece_filename.')';
							break;

						case 'exe':
							// unknown EXE attachment - leave it
							EchoToScreen('<font color="purple">* <b>'.$piece_filename.'</b> = unknown (benign or malicious)</font>'."\n");
							break;
					}

				} elseif (eregi('^ok', $VirusRecord['virus_name'])) {

					// known non-bad EXE attachment - leave it
					EchoToScreen('<font color="green">* <b>'.$piece_filename.'</b> = benign</font>'."\n");

				} else {

					// known bad EXE attachment - kill it
					EchoToScreen('<font color="red">* <b>'.$piece_filename.'</b> is infected with "'.$VirusRecord['virus_name'].'"</font>'."\n");
					$ThisIsBad = true;
					$WhyItsBad = 'Attachment infected with virus ('.$VirusRecord['virus_name'].')';

					$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'exe` SET';
					$SQLquery .= ' `lasthit` = "'.time().'"';
					$SQLquery .= ', `hitcount` = `hitcount` + 1';
					$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($exe_md5hash).'")';
					mysql_query_safe($SQLquery);
				}

			} else {

				$newPattern = $exe_filesize.'|';
				$newMD5     = FilteredBinaryDataMD5($BinaryAttachmentData, $newPattern);
				$SQLquery  = 'INSERT INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'exe` (`filesize`, `md5`, `pattern`, `added`, `virus_data`) VALUES (';
				$SQLquery .= '"'.mysql_escape_string($exe_filesize).'", ';
				$SQLquery .= '"'.mysql_escape_string($newMD5).'", ';
				$SQLquery .= '"'.mysql_escape_string($newPattern).'", ';
				$SQLquery .= '"'.mysql_escape_string(time()).'", ';
				$SQLquery .= '"'.mysql_escape_string($BinaryAttachmentData).'")';
				mysql_query_safe($SQLquery);

				EchoToScreen('<font color="purple">* <b>'.$piece_filename.'</b> = never-before-seen</font>'."\n");

				WarningEmail(strtoupper($piece_fileext).' attachment saved to database', 'Saved .'.$piece_fileext.' attachment in database: "'.$piece_filename.'"'."\n".'Filesize: '.$exe_filesize."\n".'md5: '.$exe_md5hash."\n\n".PHPOP3CLEAN_ADMINPAGE.'?exeadmin=edit&md5='.$exe_md5hash);

			}
			unset($BinaryAttachmentData);
			break;

		default:
			break;
	}

	return true;
}

function BannedImageAttachmentDatabaseCheckSave($piece_filename, &$BinaryAttachmentData, &$ThisIsBad, &$WhyItsBad, $tempfilename='') {
	$image_filesize = strlen($BinaryAttachmentData);
	$image_md5hash  = md5($BinaryAttachmentData);
	$IsBanned = false;
	$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] calling BannedImageAttachmentDatabaseCheckSave('.$piece_filename.', '.$image_filesize.' bytes, MD5='.$image_md5hash.')';

	$killtempfile = false;
	if (!$tempfilename) {
		$killtempfile = true;
		if ($tempfilename = @tempnam(PHPOP3CLEAN_MD5_IMAGE_CACHE, 'pP3')) {
			if ($fp_tmp = @fopen($tempfilename, 'w+b')) {
				fwrite($fp_tmp, $BinaryAttachmentData);
			}
		}
	}
	if (is_readable($tempfilename)) {
		static $GIStypes = array(1=>'gif', 2=>'jpeg', 3=>'png', 4=>'swf', 5=>'psd', 6=>'bmp', 7=>'tiff', 8=>'tiff', 9=>'jpc', 10=>'jp2', 11=>'jpx', 12=>'jb2', 13=>'swc', 14=>'iff', 15=>'wbmp', 16=>'xbm');
		$GIS = @GetImageSize($tempfilename);
		$image_x   = @$GIS[0];
		$image_y   = @$GIS[1];
		$image_ext = @$GIStypes[@$GIS[2]];
	}
	if (@$fp_tmp) {
		fclose($fp_tmp);
	} else {
		$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] failed to fopen('.@$tempfilename.')';
	}
	if ($killtempfile) {
		@unlink($tempfilename);
	}

	$SQLquery  = 'SELECT `md5` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
	$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($image_md5hash).'")';
	//$SQLquery .= ' AND (`ext` = "'.mysql_escape_string($image_ext).'")';
	//$SQLquery .= ' AND (`width` >= "'.mysql_escape_string($image_x - 10).'")';
	//$SQLquery .= ' AND (`width` <= "'.mysql_escape_string($image_x + 10).'")';
	//$SQLquery .= ' AND (`height` >= "'.mysql_escape_string($image_y - 10).'")';
	//$SQLquery .= ' AND (`height` <= "'.mysql_escape_string($image_y + 10).'")';
	//$SQLquery .= ' AND ((`pattern` = "") OR (`pattern` = "'.$image_filesize.'|"))';
	$result = mysql_query_safe($SQLquery);
	if ($row = mysql_fetch_assoc($result)) {

		$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Matched image in database (MD5='.$image_md5hash.')';
		$IsBanned = true;

	} elseif (@$image_ext && @$image_x && @$image_y) {

		$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Checking for '.$image_ext.' images close to '.$image_x.'x'.$image_y;
		$SQLquery  = 'SELECT `md5`, `size`, `pattern` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
		$SQLquery .= ' WHERE ((`pattern` <> "") AND (`pattern` <> "'.$image_filesize.'|"))';
		$SQLquery .= ' AND (`ext` = "'.mysql_escape_string($image_ext).'")';
		$SQLquery .= ' AND (`width` = "'.mysql_escape_string($image_x).'")';
		$SQLquery .= ' AND (`height` = "'.mysql_escape_string($image_y).'")';
		$result = mysql_query_safe($SQLquery);
		$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Checking for '.mysql_num_rows($result).' filtered MD5 images';
		while ($row = mysql_fetch_assoc($result)) {
			$filtered_md5 = FilteredBinaryDataMD5($BinaryAttachmentData, $row['pattern']);
			if ($row['md5'] == $filtered_md5) {
				$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Matched filtred (MD5='.$filtered_md5.')';
				//list($matchlength) = explode('|', $row['pattern']);
				//WarningEmail('image partial MD5 match', "Real:\n".strlen($BinaryAttachmentData)." bytes\n$image_md5hash\n\nMatched:\n".$matchlength." bytes\n".$row['md5']."\nPattern: ".$row['pattern']);
				$IsBanned = true;
				$PartialMatchWarning = 'Image matches partial MD5 pattern with mask ('.htmlentities($row['pattern']).') -- actual MD5 = "'.$image_md5hash.'", matched MD5 = "'.$row['md5'].'"';
				$image_md5hash = $row['md5'];
				break;
			}
		}

	} else {

		$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Failed to GetImageSize('.$tempfilename.'), skipping partial image matches';

	}
	@mysql_free_result($result);

	if ($IsBanned) {

		EchoToScreen('<font color="red">has a BANNED attached image: <b>'.$piece_filename.'</b></font>'."\n");
		if (@$PartialMatchWarning) {
			EchoToScreen($PartialMatchWarning."\n");
		}
		$ThisIsBad = true;
		$WhyItsBad = 'Illegal attached image ('.$piece_filename.' = '.$image_md5hash.')';

		$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'image` SET';
		$SQLquery .= ' `lasthit` = "'.time().'"';
		$SQLquery .= ', `hitcount` = `hitcount` + 1';
		$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($image_md5hash).'")';
		mysql_query_safe($SQLquery);

	}
	return true;
}

function ExtractActualEmailAddress($combinedNameEmail) {
	if (preg_match(preg_expression('\\<(.+\\@[a-z0-9\\-\\.]+)\\>', 'i'), $combinedNameEmail, $matches)) {
		// "User Name <user@domain.com>" or just "<user@domain.com>"
		@list($dummy, $email) = $matches;
		return $email;
	} elseif (preg_match(preg_expression('^[\x21-\x7f]+\\@[a-z0-9\\-\\.]+$', 'i'), $combinedNameEmail, $matches)) {
		// just an email address
		return $combinedNameEmail;
	}
	return '';
}

function ParseContentType($raw) {
	$clean = explode(';', $raw);
	$parsed['MIME'] = trim(eregi_replace('content-type: ', '', $clean[0]));
	@list($parsed['MIME_base'], $parsed['MIME_sub']) = @explode('/', $parsed['MIME']);
	for ($i = 1; $i < count($clean); $i++) {
		eregi('^([^ "\']+)\=["\']?([^"]*)["\']?$', trim($clean{$i}), $matches);
		$parsed[$matches[1]] = $matches[2];
	}
	return $parsed;
}

function IsWhiteListedEmail($HeaderEmail, $account='') {
	static $WhiteListedEmails = null;
	if (is_null($WhiteListedEmails)) {
		$WhiteListedEmails = array();
		$SQLquery  = 'SELECT `email`, `account`';
		$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_email`';
		$SQLquery .= ' WHERE (`account` = "'.mysql_escape_string($account).'")';
		$SQLquery .= ' OR (`account` = "")';
		$result = mysql_query_safe($SQLquery);
		while ($row = mysql_fetch_assoc($result)) {
			$WhiteListedEmails[strtolower($row['email'])] = $row['account'];
		}
		@mysql_free_result($result);
	}
	if (count($WhiteListedEmails) === 0) {
		return false;
	}
	$email = ExtractActualEmailAddress($HeaderEmail);
	if (!$email) {
		return false;
	}
	foreach ($WhiteListedEmails as $whitelisted_email => $account) {
		if (preg_match(preg_expression($whitelisted_email.'$', 'i'), $email, $matches)) {
			$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_email`';
			$SQLquery .= ' SET `lasthit` = "'.time().'"';
			$SQLquery .= ', `hitcount` = `hitcount` + 1';
			$SQLquery .= ' WHERE (`email` = "'.mysql_escape_string($whitelisted_email).'")';
			$SQLquery .= ' AND (`account` = "'.mysql_escape_string($account).'")';
			mysql_query($SQLquery);
			return strtolower($matches[0]);
		}
	}
	return false;
}

function IsWhiteListedSubject($subject) {
	static $WhiteListedWords = null;
	if (is_null($WhiteListedWords)) {
		$WhiteListedWords = array();
		$SQLquery  = 'SELECT `word` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_subject`';
		$result = mysql_query_safe($SQLquery);
		while ($row = mysql_fetch_assoc($result)) {
			$WhiteListedWords[$row['word']] = true;
		}
		@mysql_free_result($result);
	}
	if (empty($WhiteListedWords)) {
		return false;
	}
	foreach ($WhiteListedWords as $word => $dummy) {
		if (preg_match(preg_expression($word, 'i'), $subject)) {
			return $word;
		}
	}
	return false;
}

function FormatTimeInterval($seconds) {
	if ($seconds < 60) {
		return $seconds.' sec';
	} elseif ($seconds < 3600) {
		$m = floor($seconds / 60);
		$s = $seconds - ($m * 60);
		return $m.':'.str_pad($s, 2, '0', STR_PAD_LEFT).' min';
	} else {
		$h = floor($seconds / 3600);
		$m = floor(($seconds - ($h * 3600)) / 60);
		$s = $seconds - ($h * 3600) - ($m * 60);
		return $h.':'.str_pad($m, 2, '0', STR_PAD_LEFT).':'.str_pad($s, 2, '0', STR_PAD_LEFT).' ';
	}
}

function mysql_table_exists($table) {
	return (bool) mysql_query('DESCRIBE `'.$table.'`');
}

function mysql_column_exists($table, $column) {
	static $ColumnExistCache = array();
	if (!isset($ColumnExistCache[$table])) {
		$result = mysql_query('SHOW COLUMNS FROM `'.$table.'`');
		while ($row = mysql_fetch_assoc($result)) {
			$ColumnExistCache[$table][$row['Field']] = true;
		}
	}
	return (isset($ColumnExistCache[$table][$column]) ? true : false);
}

function mysql_table_indexes($tablename) {
	$TableIndexes = array();
	$result = mysql_query('SHOW INDEX FROM `'.mysql_escape_string($tablename).'`');
	while ($row = mysql_fetch_assoc($result)) {
		$TableIndexes[$row['Key_name']][$row['Seq_in_index']] = $row;
	}
	return $TableIndexes;
}

///////////////////////////////////////////////////////////////////////////////

function MessageQueueForDelete($account, $messageid) {
	$SQLquery  = 'REPLACE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'delete_queue` (`account`, `messageid`) VALUES (';
	$SQLquery .= ' "'.mysql_escape_string($account).'",';
	$SQLquery .= ' "'.mysql_escape_string($messageid).'")';
	mysql_query_safe($SQLquery);
	return true;
}

function MessageDeleteQueueCount($account) {
	$SQLquery  = 'SELECT COUNT(*) AS `queuelength` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'delete_queue`';
	$SQLquery .= ' WHERE (`account` = "'.mysql_escape_string($account).'")';
	$result = mysql_query_safe($SQLquery);
	if ($row = mysql_fetch_assoc($result)) {
		return $row['queuelength'];
	}
	@mysql_free_result($result);
	return false;
}

function MessageDeleteQueueProcess($account) {
	global $phPOP3;

	$SQLquery  = 'SELECT `messageid` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'delete_queue`';
	$SQLquery .= ' WHERE (`account` = "'.mysql_escape_string($account).'")';
	$result = mysql_query_safe($SQLquery);
	$num = mysql_num_rows($result);
	$phPOP3->OutputToScreen('<font color="blue">There are <b>'.$num.'</b> messages in the delete queue</font>'."\n\n");
	while ($row = mysql_fetch_assoc($result)) {
		if ($messagenum = $phPOP3->POP3getMessageNumFromUID($row['messageid'])) {
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] resolved "'.$account.'.'.$row['messageid'].'" to #'.$messagenum;
			$phPOP3->POP3delete($messagenum);
			$phPOP3->OutputToScreen('<font color="red"><b>Message deleted</b> ('.htmlentities($row['messageid']).')</font>'."\n\n");
		} else {
			$phPOP3->OutputToScreen('<font color="red"><b>Queued-for-delete message ID "'.htmlentities($row['messageid'], ENT_QUOTES).'" seems to be gone from server already!</b></font>'."\n\n");
		}
		$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'delete_queue`';
		$SQLquery .= ' WHERE (`messageid` = "'.mysql_escape_string($row['messageid']).'")';
		mysql_query_safe($SQLquery);
	}
	@mysql_free_result($result);

	if ($num > 0) {
		// optimize table if some messages were actually deleted
		$SQLquery  = 'OPTIMIZE TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_recent`';
		mysql_query($SQLquery);
	}
	unset($SQLquery);
	// must logout to commit deletions
	$phPOP3->POP3logout();
	return false;
}

function ExamineMessageContents(&$header, &$MessageContents, &$WhyItsBad, &$DebugMessages, $account='') {
	global $Timing, $MessageID, $LargeEmailsSkipped;

	$ThisIsBad = false;
	$WhyItsBad = '';

	$ParsedHeader = POP3parseheader($header, $account);
	$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] POP3parseheader() completed';

	// HTML in headers
	if ($ThisIsBad === false) {
		$timingstart = getmicrotime();
		if (preg_match(preg_expression('\<html\>', 'i'), $header)) {
			//$phPOP3->OutputToScreen('<font color="red">found "&lt;html&gt;" in headers</font>'."\n");
			$ThisIsBad = true;
			$WhyItsBad = 'HTML in headers';
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: HTML in headers';
		}
		@$Timing['header_html'] += (getmicrotime() - $timingstart);
	}


	// corrupt Message-ID header
	if ($ThisIsBad === false) {
		$timingstart = getmicrotime();
		if (@$ParsedHeader['message-id'][0] && !preg_match(preg_expression('^\<.+\>$', 'i'), trim($ParsedHeader['message-id'][0]))) {
			//$phPOP3->OutputToScreen('<font color="red">found corrupt [Message-ID] header</font>'."\n");
			$ThisIsBad = true;
			$WhyItsBad = 'Corrupt Message-ID header';
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: Corrupt Message-ID header';
		}
		@$Timing['header_message-id'] += (getmicrotime() - $timingstart);
	}


	// banned domain in Received header
	if ($ThisIsBad === false) {
		$timingstart = getmicrotime();
		if (($baddomain = BannedReceivedHeaderDomain($ParsedHeader)) !== false) {
			//$phPOP3->OutputToScreen('<font color="red">banned domain in Received header: "'.$baddomain.'"</font>'."\n");
			$ThisIsBad = true;
			$WhyItsBad = 'Banned domain in Received header ('.$baddomain.')';
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: Banned domain in Received header ('.$baddomain.')';
		}
		unset($baddomain);
		@$Timing['BannedReceivedHeaderDomain'] += (getmicrotime() - $timingstart);
	}


	// Added by JRF - weird things happening lately
	if ($ThisIsBad === false) {
		if (isset($ParsedHeader['to'][0]) && (((strpos($ParsedHeader['to'][0], 'thisisjusttestmessageatall@') !== false ) || ( strpos($ParsedHeader['to'][0], 'catchthismail@') !== false)) || (strpos($ParsedHeader['to'][0], 'helloitmenice@') !== false))) {
			$ThisIsBad = true;
			$WhyItsBad = 'Message send to spamtestaddress@yourdomain';
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: spamtestaddress@yourdomain';
		}
	}


	// banned phrase in subject
	if ($ThisIsBad === false) {
		$timingstart = getmicrotime();
		if ($badword = BlackListedWordsFound($ParsedHeader['subject'][0])) {
			list($matchedword, $cleaninfo) = $badword;
			$ThisIsBad = true;
			//$WhyItsBad = 'Banned phrase in subject ('."\n".$cleaninfo['word']."\n".' ::: CASE sensitive ? '.( ($cleaninfo['casesensitive'] == 1) ? 'YES' : 'NO' )."\n".' ::: SPACES quantified ? '.( ($cleaninfo['spaces_quantified'] == 1) ? 'YES' : 'NO' )."\n".' ::: DOTALL ? '.( ($cleaninfo['dotall'] == 1) ? 'YES' : 'NO' )."\n".' ::: '.$matchedword.')';
			$WhyItsBad = 'Banned phrase in subject ('."\n".$cleaninfo['word']."\n".' ::: '.$matchedword.')';
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: Banned phrase in subject ('.$cleaninfo.':::'.$matchedword.')';
			unset($matchedword, $cleaninfo);
		}
		unset($badword);
		@$Timing['BlackListedWordsFound(subject)'] += (getmicrotime() - $timingstart);
	}


//	// skip oversize messages
//	$timingstart = getmicrotime();
//	if ($messageSize > PHPOP3CLEAN_MAX_MESSAGE_SIZE) {
//		// this email is too large to scan, skip it
//		$LargeEmailsSkipped++;
//		$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Skipping message ID "'.$MessageID.'" because is larger ('.number_format($messageSize).' bytes) than PHPOP3CLEAN_MAX_MESSAGE_SIZE ('.number_format(PHPOP3CLEAN_MAX_MESSAGE_SIZE).' bytes)';
//		$phPOP3->OutputToScreen('<font color="#0000FF">skipping message #'.$i.' (id "'.$MessageID.'") - too large ('.number_format($messageSize / 1024).'kB vs '.number_format(PHPOP3CLEAN_MAX_MESSAGE_SIZE / 1024).'kB)</font><br><br>');
//		unset($MessageID, $messageSize);
//		@$Timing['SkipLarge'] += (getmicrotime() - $timingstart);
//		continue;
//	}
//	@$Timing['SkipLarge'] += (getmicrotime() - $timingstart);


	// truncated header with no message body
	if (($ThisIsBad === false) && PHPOP3CLEAN_USE_TRUNCATED_HEADER) {
		$timingstart = getmicrotime();
		if ((strlen(trim($MessageContents)) == 0) && (substr($header, -6) != "\r\n\r\n\r\n")) {
			//$phPOP3->OutputToScreen('<font color="red">truncated header with empty message body</font>'."\n");
			$ThisIsBad = true;
			$WhyItsBad = 'truncated header';
//WarningEmail('truncated header with empty message body', $LoginInfoArray['email']."\n".'Message #'.$i."\n\n".$header);
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: truncated header with empty message body';
		}
		@$Timing['header_truncated'] += (getmicrotime() - $timingstart);
	}


	if ((PHPOP3CLEAN_MAX_BODY_SIZE > 0) && (strlen($MessageContents) <= PHPOP3CLEAN_MAX_BODY_SIZE)) {
		if ($ThisIsBad === false) {
			$timingstart = getmicrotime();
			if ($badword = BlackListedWordsFoundCode($MessageContents)) {
				list($matchedword, $codeinfo) = $badword;
				$ThisIsBad = true;
				$WhyItsBad = 'Banned phrase in code ('."\n".$codeinfo['word']."\n".' ::: '.$matchedword.')';
				$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: Banned phrase in code ('.$codeinfo.':::'.$matchedword.')';
				unset($badword, $matchedword, $codeinfo);
			} else {
				$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] NOT FOUND: BlackListedWordsFoundCode($MessageContents) ('.strlen($MessageContents).' bytes)';
			}
			unset($badword);
			@$Timing['BlackListedWordsFoundCode(MessageContents)'] += (getmicrotime() - $timingstart);
		}
	} else {
		$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] skipping BlackListedWordsFoundCode($MessageContents) ($MessageContents = '.number_format(strlen($MessageContents)).' bytes, which is > PHPOP3CLEAN_MAX_BODY_SIZE ('.number_format(PHPOP3CLEAN_MAX_BODY_SIZE).'))';
	}


	// start: MULTIPART
	if (($ThisIsBad === false) && preg_match(preg_expression('Content-Type: multipart/([a-z\-]+);.*[\s\r\n]*boundary=([^\s]+)(["\s;]|$)', 'isU'), $header, $contenttype_matches)) {
		// multipart/mixed, multipart/related, multipart/report

		@list($dummy, $multiparttype, $boundary) = $contenttype_matches;
		unset($dummy);
		$boundary = str_replace('"', '', $boundary);
		$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] parsing "multipart/'.$multiparttype.'" section, boundary="'.$boundary.'"';
		if (preg_match(preg_expression('^(.+)[\r\n]+(--'.$boundary.'[\r\n]+.*)', 's'), $header, $boundary_in_header_matches)) {
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] found multipart boundary inside header, relocating to message body';
			$header = trim($boundary_in_header_matches[1])."\r\n\r\n";
			$MessageContents = $boundary_in_header_matches[2]."\r\n".$MessageContents;
		}
		$multipartpieces = explode('--'.$boundary, $MessageContents);
		$RealMultiPartPieces = array();
		foreach ($multipartpieces as $key => $piecerawdata) {
			@list($pieceheader, $piecedata) = explode("\r\n\r\n", $piecerawdata, 2);
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] $multipartpieces['.$key.'] ('.strlen($piecerawdata).'b) split into $pieceheader ('.strlen($pieceheader).'b) and $piecedata ('.strlen($piecedata).'b)';

			if ($ThisIsBad === false) {
				$timingstart = getmicrotime();
				if ($badword = BlackListedWordsFoundCode($boundary.$pieceheader)) {
					list($matchedword, $codeinfo) = $badword;
					$ThisIsBad = true;
					$WhyItsBad = 'Banned phrase in multipart piece header ('."\n".$codeinfo['word']."\n".' ::: '.$matchedword.')';
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: Banned phrase in code ('.$codeinfo.':::'.$matchedword.')';
					unset($badword, $matchedword, $codeinfo);
				} else {
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] NOT FOUND: BlackListedWordsFoundCode($pieceheader) ('.strlen($boundary.$pieceheader).' bytes)';
				}
				unset($badword);
				@$Timing['BlackListedWordsFoundCode(pieceheader)'] += (getmicrotime() - $timingstart);
			}

			if (preg_match(preg_expression('Content-Type: multipart/(.*);[\s\r\n]*boundary="?([^"]+)"?', 'is'), $pieceheader, $multipart_alternative_matches)) {

				list($dummy, $multipart_alternative_type, $multipart_alternative_boundary) = $multipart_alternative_matches;
				unset($dummy);
				if ($multipart_alternative_boundary) {

					$multipart_alternative_pieces = explode('--'.$multipart_alternative_boundary, $piecedata);
					foreach ($multipart_alternative_pieces as $multipart_alternative_piecerawdata) {
						if (strlen($multipart_alternative_piecerawdata) > 0) {
							$RealMultiPartPieces[] = $multipart_alternative_piecerawdata;
						}
					}
					unset($multipart_alternative_pieces, $multipart_alternative_piecerawdata);

				} else {
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] did not find $multipart_alternative_boundary';
				}
				unset($multipart_alternative_type, $multipart_alternative_boundary);

			} elseif (strlen($piecedata) > 0) {

				$RealMultiPartPieces[] = $piecerawdata;

			}
			unset($pieceheader, $piecedata, $multipart_alternative_matches);
		}
		unset($key, $piecerawdata);

		$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] scanning '.count($RealMultiPartPieces).' "multipart/'.$multiparttype.'" pieces';
		foreach ($RealMultiPartPieces as $key => $piecerawdata) {
			if ($ThisIsBad === true) {
				// was flagged bad in previous piece, abort out
				break;
			}
			@list($pieceheader, $piecedata) = explode("\r\n\r\n", $piecerawdata, 2);
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] $RealMultiPartPieces['.$key.'] ('.strlen($piecerawdata).'bytes) split into $pieceheader ('.strlen($pieceheader).' bytes) and $piecedata ('.strlen($piecedata).' bytes)';
			$ParsedPieceHeader = POP3parseheader($pieceheader, $account);

			if ($BinaryAttachmentData = EncodingDecode($piecedata, @$ParsedPieceHeader['content-transfer-encoding'][0])) {
				// great
				$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] ['.$key.']EncodingDecode($piecedata, '.@$ParsedPieceHeader['content-transfer-encoding'][0].') succeeded, $piecedata ('.strlen($piecedata).' bytes) decoded to $BinaryAttachmentData ('.strlen($BinaryAttachmentData).' bytes)';
			} else {
				$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] ['.$key.']EncodingDecode($piecedata, '.@$ParsedPieceHeader['content-transfer-encoding'][0].') failed on '.strlen($piecedata).' bytes input, skipping this piece';
				//if (strlen(trim($piecedata)) > 0) {
				//	WarningEmail('failed to EncodingDecode() attachment', $LoginInfoArray['email']."\n".'Message #'.$i."\n".__FILE__.' failed to ['.$key.']EncodingDecode($piecedata, '.@$ParsedPieceHeader['content-transfer-encoding'][0].') attachment ('.strlen($piecedata).' bytes) on line '.__LINE__."\n\n".$piecedata);
				//}
				unset($pieceheader, $piecedata, $ParsedPieceHeader, $BinaryAttachmentData);
				continue; // skip this multipart/piece
			}

			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] $ParsedPieceHeader[content-type][0] = "'.@$ParsedPieceHeader['content-type'][0].'"';
			$parsedContentType = ParseContentType(@$ParsedPieceHeader['content-type'][0]);
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] $parsedContentType[MIME] is "'.$parsedContentType['MIME'].'"';
			switch (strtolower(@$parsedContentType['MIME_base'])) {
				case 'multipart':
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] recursing back into ExamineMessageContents('.strlen($pieceheader).'b, '.strlen($piecedata).'b, $WhyItsBad, $DebugMessages)';
					$ThisIsBad = ExamineMessageContents($pieceheader, $piecedata, $WhyItsBad, $DebugMessages, $account);
					break;

				case 'message':
					// message/rfc822
					@list($message_header, $message_MessageContents) = explode("\r\n\r\n", $BinaryAttachmentData, 2);
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] splitting '.$parsedContentType['MIME'].' message into $message_header ('.strlen($message_header).' bytes) and $message_MessageContents ('.strlen($message_MessageContents).' bytes)';
					$ThisIsBad = ExamineMessageContents($message_header, $message_MessageContents, $WhyItsBad, $DebugMessages, $account);
					unset($message_header, $message_MessageContents);
					break;

				case '':
				case 'text':
					// text/plain, text/html
					$piecedata = $BinaryAttachmentData;
					$noHTMLpiecedata = $piecedata;
					if ($parsedContentType['MIME'] == 'text/html') {
						HTMLentitiesDecode($piecedata);
						$noHTMLpiecedata = strip_tags($piecedata);
					} else {
						$noHTMLpiecedata = $piecedata;
					}
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] $BinaryAttachmentData ('.strlen($BinaryAttachmentData).' bytes) -- $piecedata ('.strlen($piecedata).' bytes) -- $noHTMLpiecedata ('.strlen($noHTMLpiecedata).' bytes)';

					if ($ThisIsBad === false) {
						$timingstart = getmicrotime();
						if (preg_match(preg_expression('^<img src="?cid:[^>]+"?[^>]*>$'), trim($piecedata))) {
							$ThisIsBad = true;
							$WhyItsBad = 'Embedded image no message';
							$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: Embedded image no message';
							unset($noHTMLpiecedata);
							break;
						}
						@$Timing['embedded image no message'] += (getmicrotime() - $timingstart);
					}

					if ((PHPOP3CLEAN_MAX_BODY_SIZE > 0) && (strlen($noHTMLpiecedata) <= PHPOP3CLEAN_MAX_BODY_SIZE)) {
						if ($ThisIsBad === false) {
							$timingstart = getmicrotime();
							if ($badword = BlackListedWordsFound($noHTMLpiecedata)) {
								list($matchedword, $cleaninfo) = $badword;
								$ThisIsBad = true;
								//$WhyItsBad = 'Banned phrase in body ('."\n".$cleaninfo['word']."\n".' ::: CASE sensitive ? '.( ($cleaninfo['casesensitive'] == 1) ? 'YES' : 'NO' )."\n".' ::: SPACES quantified ? '.( ($cleaninfo['spaces_quantified'] == 1) ? 'YES' : 'NO' )."\n".' ::: DOTALL ? '.( ($cleaninfo['dotall'] == 1) ? 'YES' : 'NO' )."\n".' ::: '.$matchedword.')';
								$WhyItsBad = 'Banned phrase in body ('."\n".$cleaninfo['word']."\n".' ::: '.$matchedword.')';
								$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: Banned phrase in body ('.$cleaninfo.':::'.$matchedword.')';
								unset($noHTMLpiecedata, $badword, $matchedword, $cleaninfo);
								break;
							} else {
								$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] nothing found: BlackListedWordsFound($noHTMLpiecedata) ($noHTMLpiecedata = '.strlen($noHTMLpiecedata).' bytes)';
							}
							unset($badword);
							@$Timing['BlackListedWordsFound(noHTMLpiecedata)'] += (getmicrotime() - $timingstart);
						}
					} else {
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] skipping BlackListedWordsFound($noHTMLpiecedata) ($noHTMLpiecedata = '.number_format(strlen($noHTMLpiecedata)).' bytes, which is > PHPOP3CLEAN_MAX_BODY_SIZE ('.number_format(PHPOP3CLEAN_MAX_BODY_SIZE).'))';
					}

					if ((PHPOP3CLEAN_MAX_BODY_SIZE > 0) && (strlen($piecedata) <= PHPOP3CLEAN_MAX_BODY_SIZE)) {
						if ($ThisIsBad === false) {
							$timingstart = getmicrotime();
							if ($badword = BlackListedWordsFoundCode($piecedata)) {
								list($matchedword, $codeinfo) = $badword;
								$ThisIsBad = true;
								//$WhyItsBad = 'Banned phrase in code ('."\n".$codeinfo['word']."\n".' ::: CASE sensitive ? '.( ($codeinfo['casesensitive'] == 1) ? 'YES' : 'NO' )."\n".' ::: SPACES quantified ? '.( ($codeinfo['spaces_quantified'] == 1) ? 'YES' : 'NO' )."\n".' ::: DOTALL ? '.( ($codeinfo['dotall'] == 1) ? 'YES' : 'NO' )."\n".' ::: '.$matchedword.')';
								$WhyItsBad = 'Banned phrase in code ('."\n".$codeinfo['word']."\n".' ::: '.$matchedword.')';
								$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: Banned phrase in code ('.$codeinfo.':::'.$matchedword.')';
								unset($noHTMLpiecedata, $badword, $matchedword, $codeinfo);
								break;
							} else {
								$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] NOT FOUND: BlackListedWordsFoundCode($piecedata) ('.strlen($piecedata).' bytes)';
							}
							unset($badword);
							@$Timing['BlackListedWordsFoundCode(piecedata)'] += (getmicrotime() - $timingstart);
						}
					} else {
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] skipping BlackListedWordsFoundCode($piecedata) ($piecedata = '.number_format(strlen($piecedata)).' bytes, which is > PHPOP3CLEAN_MAX_BODY_SIZE ('.number_format(PHPOP3CLEAN_MAX_BODY_SIZE).'))';
					}

					if ((PHPOP3CLEAN_MAX_BODY_SIZE > 0) && (strlen($piecedata) <= PHPOP3CLEAN_MAX_BODY_SIZE)) {
						if ($ThisIsBad === false) {
							$timingstart = getmicrotime();
							$ResolvedDomains = ExtractDomainsFromText($piecedata, $noHTMLpiecedata);
							$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] ExtractDomainsFromText($piecedata, $noHTMLpiecedata) found '.count($ResolvedDomains).' domains';
							foreach ($ResolvedDomains as $domain => $IPs) {
//echo '<pre>'.__LINE__."\n";
//var_dump($domain);
//print_r($IPs);
//echo '</pre>';
								if (DomainResolvesToTooManyVariedIPs($domain, $IPs)) {
									$ThisIsBad = true;
									$WhyItsBad = 'domain resolves to too many IPs ('.$domain.':'.implode(',', $IPs).')';
									$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: '.$WhyItsBad;
									break;
								}
								foreach ($IPs as $IP) {
									RecentMessageIPid($account, $MessageID, $IP);
									$DNSBL_IPs = DNSBLlookup($IP);
//echo '<hr>';
									if (is_array($DNSBL_IPs) && (count($DNSBL_IPs) > 0)) {
//WarningEmail('BanIP('.$IP.')', 'would have called BanIP('.$domain.') on ['.$IP.']');
										//BanIP($IP);
										$ThisIsBad = true;
										$WhyItsBad = 'DNSBL IP in body ('.$domain.':'.$IP.')';
										$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: DNSBL IP in body ('.$domain.':'.$IP.')';
										break;
									}
								}
							}
							if (!$ThisIsBad) {
								$BannedIPdomains = BlackListedDomainIP($ResolvedDomains);
								if (is_array($BannedIPdomains) && (count($BannedIPdomains) > 0)) {
									$bad_IPs_found = array_keys($BannedIPdomains);
									foreach ($bad_IPs_found as $bad_IP_found) {
										DeleteOldMessagesWithIP($bad_IP_found);
									}
									$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] found: Banned IP in body ('.implode(';', $bad_IPs_found).')';
									//$phPOP3->OutputToScreen('<font color="red">found banned IP in body: "'.implode(';', $bad_IPs_found).'"</font>'."\n");
									$ThisIsBad = true;
									$WhyItsBad = 'Banned IP in body ('.implode(';', $bad_IPs_found).')';
									unset($noHTMLpiecedata, $ResolvedDomains, $BannedIPdomains);
									break;
								} else {
									$domainlist = array();
									foreach ($ResolvedDomains as $domain => $IPs) {
										$domainlist[] = $domain.' ['.implode(';', $IPs).']';
									}
									$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] found: no Banned IPs in body. Domains: ( '.implode(' :: ', $domainlist).' )';
									unset($domain, $IPs, $domainlist);
								}
							}
							unset($ResolvedDomains, $BannedIPdomains);
							@$Timing['BlackListedDomainIP'] += (getmicrotime() - $timingstart);
						}
					} else {
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] skipping BlackListedDomainIP($ResolvedDomains) ($piecedata = '.number_format(strlen($piecedata)).' bytes, which is > PHPOP3CLEAN_MAX_BODY_SIZE ('.number_format(PHPOP3CLEAN_MAX_BODY_SIZE).'))';
					}

					unset($noHTMLpiecedata);
					break;

				default:
					// probably some kind of binary attachment, don't bother scanning for words or IPs
					break;
			}
			unset($piecedata);

			if (($ThisIsBad === false) && ($parsedContentType = ParseContentType(@$ParsedPieceHeader['content-type'][0]))) {
				// e.g. Content-Type: audio/x-wav;\n     name="message.scr"
				$piece_filename = QuotedOrBinaryStringDecode(@$parsedContentType['name']);
				$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Found attachment "'.$piece_filename.'" claiming to be type ('.$parsedContentType['MIME'].')';
				if ($piece_filename) {
					$timingstart = getmicrotime();
					InfectedAttachmentCheck($piece_filename, $BinaryAttachmentData, $ThisIsBad, $WhyItsBad);
					@$Timing['InfectedAttachmentCheck'] += (getmicrotime() - $timingstart);

					//$phPOP3->OutputToScreen('<font color="'.($ThisIsBad ? 'red' : 'green').'">has an attachment: <b>'.$piece_filename.'</b> claiming to be type ('.$parsedContentType['MIME'].')</font>'."\n");
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] After InfectedAttachmentCheck('.$piece_filename.', ['.strlen($BinaryAttachmentData).' bytes]): $ThisIsBad = "'.($ThisIsBad ? 'TRUE' : 'FALSE').'", $WhyItsBad = "'.$WhyItsBad.'"';
				}
				unset($piece_filename);
			}

			if (($ThisIsBad === false) && (isset($ParsedPieceHeader['content-disposition'][0]) && ($ParsedPieceHeader['content-disposition'][0] !== ''))) {
				if (preg_match(preg_expression('attachment;\s*filename=(.*)(;|$)', 'isU'), $ParsedPieceHeader['content-disposition'][0], $piece_attachment_matches)) {
					$piece_filename = QuotedOrBinaryStringDecode(str_replace('"', '', $piece_attachment_matches[1]));
					$timingstart = getmicrotime();
					InfectedAttachmentCheck($piece_filename, $BinaryAttachmentData, $ThisIsBad, $WhyItsBad);
					@$Timing['InfectedAttachmentCheck'] += (getmicrotime() - $timingstart);
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Found attachment '.$piece_filename.' ($WhyItsBad = "'.$WhyItsBad.'")';
					unset($piece_filename);
				}
				unset($piece_attachment_matches);
			}
			unset($pieceheader, $piecedata, $ParsedPieceHeader, $BinaryAttachmentData, $parsedContentType);
		}
		unset($key, $piecerawdata);

	} else {
		// not multi-part

		if ($ThisIsBad === false) {
			$piecedata = EncodingDecode($MessageContents, @$ParsedHeader['content-transfer-encoding'][0]);
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] EncodingDecode($MessageContents, '.@$ParsedHeader['content-transfer-encoding'][0].') from '.strlen($MessageContents).' bytes to '.strlen($piecedata).' bytes';

			if (($ThisIsBad === false) && (isset($ParsedHeader['content-disposition'][0]) && ($ParsedHeader['content-disposition'][0] !== ''))) {
				if (preg_match(preg_expression('attachment;\s*filename=(.*)(;|$)', 'isU'), $ParsedHeader['content-disposition'][0], $piece_attachment_matches)) {
					$piece_filename = QuotedOrBinaryStringDecode(str_replace('"', '', $piece_attachment_matches[1]));
					$timingstart = getmicrotime();
					InfectedAttachmentCheck($piece_filename, $piecedata, $ThisIsBad, $WhyItsBad);
					@$Timing['InfectedAttachmentCheck'] += (getmicrotime() - $timingstart);
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Found attachment '.$piece_filename.' ($WhyItsBad = "'.$WhyItsBad.'")';
					unset($piece_filename);
				}
				unset($piece_attachment_matches);
			}

			if ($ThisIsBad === false) {
				//$piecedata = EncodingDecode($MessageContents, @$ParsedHeader['content-transfer-encoding'][0]);
				//$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] EncodingDecode($MessageContents, '.@$ParsedHeader['content-transfer-encoding'][0].') from '.strlen($MessageContents).' bytes to '.strlen($piecedata).' bytes';
				$parsedContentType = ParseContentType(@$ParsedHeader['content-type'][0]);
				if (@$parsedContentType['MIME'] == 'text/html') {
					HTMLentitiesDecode($piecedata);
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] HTMLentitiesDecode($piecedata) to '.strlen($piecedata).' bytes';
					$noHTMLpiecedata = strip_tags($piecedata);
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] $noHTMLpiecedata = strip_tags($piecedata) = '.strlen($noHTMLpiecedata).' bytes';
				} else {
					$noHTMLpiecedata = $piecedata;
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] skipping strip_tags(), $noHTMLpiecedata = $piecedata (= '.strlen($noHTMLpiecedata).' bytes)';
				}
				unset($parsedContentType);
			}

			if ($ThisIsBad === false) {
				$timingstart = getmicrotime();
				if ($badword = BlackListedWordsFound($noHTMLpiecedata)) {
					list($matchedword, $cleaninfo) = $badword;
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] found banned phrase in body: "'.$matchedword.'" (matches "'.$cleaninfo['word'].'")';
					$ThisIsBad = true;
					$WhyItsBad = 'Banned phrase in body ('.$cleaninfo['word'].':::'.$matchedword.')';
					unset($matchedword, $cleanword);
				} else {
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] NOT FOUND: BlackListedWordsFound($noHTMLpiecedata) ('.strlen($noHTMLpiecedata).' bytes)';
				}
				unset($badword);
				@$Timing['BlackListedWordsFound(noHTMLpiecedata)'] += (getmicrotime() - $timingstart);
			}

			if ($ThisIsBad === false) {
				$timingstart = getmicrotime();
				if ($badword = BlackListedWordsFoundCode($piecedata)) {
					list($matchedword, $codeinfo) = $badword;
					$ThisIsBad = true;
					//$WhyItsBad = 'Banned phrase in code ('."\n".$codeinfo['word']."\n".' ::: CASE sensitive ? '.( $codeinfo['casesensitive'] == 1 ? 'YES' : 'NO' )."\n".' ::: SPACES quantified ? '.( $codeinfo['spaces_quantified'] == 1 ? 'YES' : 'NO' )."\n".' ::: DOTALL ? '.( $codeinfo['dotall'] == 1 ? 'YES' : 'NO' )."\n".' ::: '.$matchedword.')';
					$WhyItsBad = 'Banned phrase in code ('."\n".$codeinfo['word']."\n".' ::: '.$matchedword.')';
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: Banned phrase in code ('.$codeinfo.':::'.$matchedword.')';
					unset($badword, $matchedword, $codeinfo);
				} else {
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] NOT FOUND: BlackListedWordsFoundCode($piecedata) ('.strlen($piecedata).' bytes)';
				}
				unset($badword);
				@$Timing['BlackListedWordsFoundCode(piecedata)'] += (getmicrotime() - $timingstart);
			}

			if ($ThisIsBad === false) {
				$timingstart = getmicrotime();
				$ResolvedDomains = ExtractDomainsFromText($piecedata, $noHTMLpiecedata);
				$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] trying BlackListedDomainIP('.count($ResolvedDomains).' domains)';
				foreach ($ResolvedDomains as $domain => $IPs) {
//echo '<pre>'.__LINE__."\n";
//var_dump($domain);
//print_r($IPs);
//echo '</pre>';
					if (DomainResolvesToTooManyVariedIPs($domain, $IPs)) {
						$ThisIsBad = true;
						$WhyItsBad = 'domain resolves to too many IPs ('.$domain.':'.implode(',', $IPs).')';
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: '.$WhyItsBad;
						break;
					}
					foreach ($IPs as $IP) {
						$DNSBL_IPs = DNSBLlookup($IP);
//echo '<hr>';
						if (is_array($DNSBL_IPs) && (count($DNSBL_IPs) > 0)) {
//WarningEmail('BanIP('.$IP.')', 'would have called BanIP('.$domain.') on ['.$IP.']');
							//BanIP($IP);
							$ThisIsBad = true;
							$WhyItsBad = 'DNSBL IP in body ('.$domain.':'.$IP.')';
							$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: DNSBL IP in body ('.$domain.':'.$IP.')';
							break;
						}
					}
				}
				if (!$ThisIsBad) {
					$BannedIPdomains = BlackListedDomainIP($ResolvedDomains);
					if (is_array($BannedIPdomains) && (count($BannedIPdomains) > 0)) {
						$bad_IPs_found = array_keys($BannedIPdomains);
						foreach ($bad_IPs_found as $bad_IP_found) {
							DeleteOldMessagesWithIP($bad_IP_found);
						}
						//$phPOP3->OutputToScreen('<font color="red">found banned IP in body: "'.implode(';', $bad_IPs_found).'"</font>'."\n");
						$ThisIsBad = true;
						$WhyItsBad = 'Banned IP in body ('.implode(';', $bad_IPs_found).')';
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: Banned IP in body ('.implode(';', $bad_IPs_found).')';
					} else {
						$domainlist = array();
						foreach ($ResolvedDomains as $domain => $IPs) {
							$domainlist[] = $domain.' ['.implode(';', $IPs).']';
						}
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] found: no Banned IPs in $noHTMLpiecedata ('.strlen($noHTMLpiecedata).' bytes). Domains: ( '.implode(' :: ', $domainlist).' )';
						unset($domain, $IPs, $domainlist);
					}
				}
				unset($ResolvedDomains, $BannedIPdomains);
				@$Timing['BlackListedDomainIP(noHTMLpiecedata)'] += (getmicrotime() - $timingstart);
			}
			unset($piecedata, $noHTMLpiecedata);

		}
	} // end of multipart/nomultipart check
	unset($contenttype_matches);

	//CASINO
	if (!$ThisIsBad) {	
		$casinoSpam =0;
		if (stripos($MessageContents,"bonus")!==FALSE){$casinoSpam++;}
		if (stripos($MessageContents,"chips")!==FALSE){$casinoSpam++;}
		if (stripos($MessageContents,"deposit")!==FALSE){$casinoSpam++;}
		if (stripos($MessageContents,"slots")!==FALSE){$casinoSpam++;}
		if (stripos($MessageContents,"casino")!==FALSE){$casinoSpam++;}
		if (stripos($MessageContents,"credits")!==FALSE){$casinoSpam++;}



		if ($casinoSpam>2){
			mail("fatman@phatman.co.uk","IS SPAM:".$ParsedHeader['subject'][0],"");
			$ThisIsBad = true;
			$WhyItsBad = "IsCasino: Score=".$casinoSpam;
			$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: IsCasino: Score='.$casinoSpam;

		}
	}



	$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] ExamineMessageContents() returning '.($ThisIsBad ? 'TRUE' : 'FALSE');
	return $ThisIsBad;
}


function IPisListedBlackWhite($ip, $whitelist=0) {
	static $IPisBannedCache = array();
	if (!isset($IPisBannedCache[$ip])) {
		$IPisBannedCache[$ip][0] = false;
		$IPisBannedCache[$ip][1] = false;

		$longIP = SafeIP2Long($ip);
		$CMask = CMask($ip);

		$SQLquery  = 'SELECT `ipmask`, `whitelist` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ips`';
		$SQLquery .= ' WHERE (`cmask` = "'.mysql_escape_string($CMask).'")';
		$SQLquery .= ' ORDER BY `lasthit` DESC';
		$result = mysql_query_safe($SQLquery);
		if ($cMaskRow = mysql_fetch_assoc($result)) {
			list($min, $max) = IPrangeMinMax($cMaskRow['ipmask']);
			if (($longIP >= $min) && ($longIP <= $max)) {
				$IPisBannedCache[$ip][$cMaskRow['whitelist']] = true;
			}
		}
	}
	return $IPisBannedCache[$ip][intval($whitelist)];
}

function IPisBanned($ip) {
	return IPisListedBlackWhite($ip, 0);
}

function IPisWhitelisted($ip) {
	return IPisListedBlackWhite($ip, 1);
}


function usort_IP($a, $b) {
	if ($a == $b) {
		return 0;
	}
	$ipA = explode('.', $a);
	$ipB = explode('.', $b);
	for ($i = 0; $i <= 3; $i++) {
		if ($ipA[$i] < $ipB[$i]) {
			return -1;
		} elseif ($ipA[$i] > $ipB[$i]) {
			return 1;
		}
	}
	// should never get here?!
	return 0;
}

function ValueKeySort($array, $reversekey=false, $reversevalue=false) {
	if ($reversevalue) {
		arsort($array);
	} else {
		asort($array);
	}
	foreach ($array as $key => $value) {
		$newarray[$value][] = $key;
	}
	foreach ($newarray as $key => $valuearray) {
		if ($reversekey) {
			arsort($valuearray);
		} else {
			asort($valuearray);
		}
		foreach ($valuearray as $value) {
			$sortedarray[$value] = $key;
		}
	}
	return $sortedarray;
}

function DomainCleanup($domain) {
	if (IsIP($domain)) {
		return $domain;
	}
	$domain = str_replace('www.', '', strtolower($domain));
	$domainparts = array_reverse(explode('.', $domain));
	if ((strlen($domainparts[0]) == 2) && @$domainparts[2]) {
		return $domainparts[2].'.'.$domainparts[1].'.'.$domainparts[0];
	}
	return $domainparts[1].'.'.$domainparts[0];
}

function SpacedIP($ip, $pad=' ') {
	$IPelements = explode('.', $ip);
	for ($i = 0; $i <= 3; $i++) {
		$PaddedIP[$i] = str_pad($IPelements[$i], 3, $pad, STR_PAD_LEFT);
	}
	return implode('.', $PaddedIP);
}

function LastHit2bgcolor($lasthit, $daysrange=90) {
	if (!$lasthit) {
		return 'ffffff';
	}
	$dayssincelasthit = round((time() - $lasthit) / 86400);
	$dayssincelasthit = min($dayssincelasthit * (256 / $daysrange), 255);
	$r = str_pad(dechex(                                      $dayssincelasthit), 2, '0', STR_PAD_LEFT);
	$g = str_pad(dechex(                                255 - $dayssincelasthit), 2, '0', STR_PAD_LEFT);
	$b = str_pad(dechex(round(255 * (1 - abs((128 - $dayssincelasthit) / 128)))), 2, '0', STR_PAD_LEFT);
	return $r.$g.$b;
}

function DateDropdown($nameprefix='', $preselecteddate=false, $ShowYear=true, $ShowMonth=true, $ShowDay=true, $YearStart=5, $YearRange=15, $ShowHour=false, $ShowMinute=false) {
	if ($preselecteddate === false) {
		$preselecteddate = time();
	}
	$datedropdown = '';
	if ($ShowMonth) {
		$datedropdown .= ' <SELECT NAME="'.$nameprefix.'Month">';
		for ($i = 1; $i <= 12; $i++) {
			$datedropdown .= '<OPTION VALUE="'.$i.'"';
			if ($i == date('n', $preselecteddate)) {
				$datedropdown .= ' SELECTED';
			}
			$datedropdown .= '>'.date('M', mktime(12, 0, 0, $i, 1, 2000)).'</OPTION>';
		}
		$datedropdown .= '</SELECT>';
	}
	if ($ShowDay) {
		$datedropdown .= ' <SELECT NAME="'.$nameprefix.'Day">';
		for ($i = 1; $i <= 31; $i++) {
			$datedropdown .= '<OPTION VALUE="'.$i.'"';
			if ($i == date('j', $preselecteddate)) {
				$datedropdown .= ' SELECTED';
			}
			$datedropdown .= '>'.$i.'</OPTION>';
		}
		$datedropdown .= '</SELECT>';
	}
	if ($ShowMonth && $ShowYear) {
		$datedropdown .= ',';
	}
	if ($ShowYear) {
		$datedropdown .= ' <SELECT NAME="'.$nameprefix.'Year">';
		for ($i = (date('Y') - $YearStart); $i <= (date('Y') + $YearRange - $YearStart); $i++) {
			$datedropdown .= '<OPTION VALUE="'.$i.'"';
			if ($i == date('Y', $preselecteddate)) {
				$datedropdown .= ' SELECTED';
			}
			$datedropdown .= '>'.$i.'</OPTION>';
		}
		$datedropdown .= '</SELECT>';
	}
	if ($ShowHour) {
		$datedropdown .= ' <SELECT NAME="'.$nameprefix.'Hour">';
		for ($i = 0; $i <= 23; $i++) {
			$datedropdown .= '<OPTION VALUE="'.$i.'"';
			if ($i == date('G', $preselecteddate)) {
				$datedropdown .= ' SELECTED';
			}
			$datedropdown .= '>'.str_pad($i, 2, '0', STR_PAD_LEFT).'h</OPTION>';
		}
		$datedropdown .= '</SELECT>';
	}
	if ($ShowMinute) {
		$datedropdown .= ' <SELECT NAME="'.$nameprefix.'Minute">';
		for ($i = 0; $i <= 59; $i++) {
			$datedropdown .= '<OPTION VALUE="'.$i.'"';
			if ($i == date('i', $preselecteddate)) {
				$datedropdown .= ' SELECTED';
			}
			$datedropdown .= '>'.str_pad($i, 2, '0', STR_PAD_LEFT).'m</OPTION>';
		}
		$datedropdown .= '</SELECT>';
	}


	return $datedropdown;
}

function ElapsedTimeNiceDisplay($DateRangeMin, $DateRangeMax, $decimals=0) {
	$ElapsedSeconds = $DateRangeMax - $DateRangeMin;
	if ($ElapsedSeconds > 86400) {
		return number_format($ElapsedSeconds / 86400, $decimals).' days';
	} elseif ($ElapsedSeconds > 1440) {
		return number_format($ElapsedSeconds / 3600, $decimals).' hours';
	} elseif ($ElapsedSeconds > 60) {
		return number_format($ElapsedSeconds / 60, $decimals).' minutes';
	}
	return number_format($ElapsedSeconds, $decimals).' seconds';
}

function MD5imageSRC($md5, $ext='', $fullIMGtag=false, $border=0, $constrain=0, $width=0, $height=0) {
	if (!eregi('^[0-9a-f]{32}$', $md5)) {
		return false;
	}
	if ($ext) {
		$img_filename = PHPOP3CLEAN_ADMINPAGE.'?imagepassthru='.$md5.'.'.$ext;
	} elseif (is_file(PHPOP3CLEAN_MD5_IMAGE_CACHE.$md5.'.gif')) {
		$img_filename = PHPOP3CLEAN_ADMINPAGE.'?imagepassthru='.$md5.'.gif';
	} else {
		$img_filename = PHPOP3CLEAN_ADMINPAGE.'?imagepassthru='.$md5.'.jpeg';
	}

	if (!$fullIMGtag) {
		return $img_filename;
	}
	$imgSRC  = '<img src="'.$img_filename.'"';
	$imgSRC .= ' border="'.$border.'"';
	if ($constrain > 0) {
		if (($width === 0) && ($height === 0)) {
			if ($GIS = @GetImageSize($img_filename)) {
				list($width, $height) = $GIS;
				$aspectratio = max($width, $height) / $constrain;
				$imgSRC .= ' width="'.round($width / $aspectratio).'" height="'.round($height / $aspectratio).'"';
			} else {
				$imgSRC .= ' width="'.$constrain.'" height="'.$constrain.'"';
			}
		} elseif (($width > $constrain) || ($height > $constrain)) {
			$aspectratio = max($width, $height) / $constrain;
			$imgSRC .= ' width="'.round($width / $aspectratio).'" height="'.round($height / $aspectratio).'"';
		} else {
			$imgSRC .= ' width="'.$width.'" height="'.$height.'"';
		}
	}
	$imgSRC .= ' alt="Spam image ['.$md5.']">';
	return $imgSRC;
}

function AttachedImageDefaultPattern($filesize, $extension='gif') {
	// 3686 -> '3500|6;8;11;13;14;40-750'
	$size = (round($filesize * 0.009) * 100).'|';
	if (strtolower($extension) == 'gif') {
		$size .= '6;8;11;13;14;40-750';
	}
	return $size;
}

function UpDownSymbol($fieldname) {
	$arrowchar = array('up'=>'&#8679;', 'down'=>'&#8681;');
	if (@$_REQUEST['orderorder'] == 'ASC') {
		return ((@$_REQUEST['orderby'] == $fieldname) ? $arrowchar['down'] : $arrowchar['up']);
	} else {
		return ((@$_REQUEST['orderby'] == $fieldname) ? $arrowchar['up'] : $arrowchar['down']);
	}
}

///////////////////////////////////////////////////////////////////////////////

/*************************************************************
* LINK ENCODING ACCORDING TO THE SPECS
*
* Function by Lucas Gonze -- lucas@gonze.com
* 07-Jan-2005 07:01
* http://www.php.net/rawurlencode
*
* @input	string	(part of) url to be encoded
* @output	string	correctly encoded url
**************************************************************/
function linkencode($url) {

	$uparts		=	@parse_url($url);

	$scheme		=	array_key_exists('scheme',   $uparts) ? $uparts['scheme']   : '';
	$pass		=	array_key_exists('pass',     $uparts) ? $uparts['pass']     : '';
	$user		=	array_key_exists('user',     $uparts) ? $uparts['user']     : '';
	$port		=	array_key_exists('port',     $uparts) ? $uparts['port']     : '';
	$host		=	array_key_exists('host',     $uparts) ? $uparts['host']     : '';
	$path		=	array_key_exists('path',     $uparts) ? $uparts['path']     : '';
	$query		=	array_key_exists('query',    $uparts) ? $uparts['query']    : '';
	$fragment	=	array_key_exists('fragment', $uparts) ? $uparts['fragment'] : '';

	if (empty($scheme) === false) {
		$scheme .= '://';
	}

	if ((empty($pass) === false) && (empty($user) === false)) {
		$user = rawurlencode($user).':';
		$pass = rawurlencode($pass).'@';
	} elseif ((empty($user) === false)) {
		$user .= '@';
	}

	if ((empty($port) === false) && (empty($host) === false)) {
		$host = ''.$host.':';
	} elseif (empty($host) === false) {
		$host = $host;
	}

	if (empty($path) === false) {
		$arr = preg_split('/([\/;=])/', $path, -1, PREG_SPLIT_DELIM_CAPTURE); // needs php > 4.0.5.
		$path = '';
		foreach ($arr as $var) {
			switch($var) {
				case '/':
				case ';':
				case '=':
					$path .= $var;
					break;
				default:
					$path .= rawurlencode($var);
					break;
			}
		}
		// legacy patch for servers that need a literal /~username
		$path = str_replace('/%7E', '/~', $path);
	}

	if (empty($query) === false) {
		$arr = preg_split('/([&=])/', $query, -1, PREG_SPLIT_DELIM_CAPTURE); // needs php > 4.0.5.
		$query = '?';
		foreach ($arr as $var) {
			if (('&' === $var) || ('=' === $var)) {
				$query .= $var;
			} else {
				$query .= urlencode($var);
			}
		}
		// modified version by James Heinrich <infoØsilisoftware*com> 13-Dec-2006
/*		$query_array = array();
		$query_parts = explode('&', $query);
		$new_query = '?';
		foreach ($query_parts as $key => $query_part) {
			@list($query_key, $query_value) = explode('=', $query_part, 2);
			$new_query .= (($key > 0) ? '&amp;' : '');
			$new_query .= urlencode($query_key).'='.urlencode($query_value);
		}
		$query = $new_query;
		unset($new_query, $query_array, $query_parts, $query_key, $query_value);
*/	}

	if (empty($fragment) === false) {
		$fragment = '#'.urlencode($fragment);
	}

	return implode('', array($scheme, $user, $pass, $host, $port, $path, $query, $fragment));
}

///////////////////////////////////////////////////////////////////////////////

?>