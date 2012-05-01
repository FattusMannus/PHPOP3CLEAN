<?php
/////////////////////////////////////////////////////////////////
/// phPOP3clean() by James Heinrich <info@silisoftware.com>    //
//  available at http://phpop3clean.sourceforge.net            //
/////////////////////////////////////////////////////////////////


// THIS IS THE ONLY LINE IN THIS FILE YOU MAY NEED TO MODIFY:
define('PHPOP3CLEAN_DIRECTORY', './phPOP3clean/');  // relative to current directory, must have trailing slash. If you modify this value, please modify PHPOP3CLEAN_DIRECTORY in phPOP3clean.config.php to the same value
if (!is_dir(PHPOP3CLEAN_DIRECTORY)) {
	echo 'please define PHPOP3CLEAN_DIRECTORY to a valid directory ("'.htmlentities(PHPOP3CLEAN_DIRECTORY, ENT_QUOTES).'" does not exist)';
	exit;
}


///////////////////////////////////////////////////////////////////////////////
// VARIABLE VALIDATION:
$_GET['pixel'] = ((isset($_GET['pixel']) && eregi('^[0-9a-f]{6}$', $_GET['pixel'])) ? $_GET['pixel'] : null);
///////////////////////////////////////////////////////////////////////////////

require_once(PHPOP3CLEAN_DIRECTORY.'phPOP3clean.login.php');
//include( '../debug/mydebug.inc');

if (!headers_sent() && isset($_GET['orderby'])) {
	setcookie('orderby',   $_GET['orderby']);
	$_COOKIE['orderby']  = $_GET['orderby'];
	$_REQUEST['orderby'] = $_GET['orderby'];
}

if (isset($_GET['pixel']) && eregi('^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$', @$_GET['pixel'], $matches)) {

	// output a single-pixel, 2-color GIF
	header('Content-type: image/gif');
	echo "\x47\x49\x46\x38\x39\x61";                                                 // version (GIF89a)
	echo "\x01\x00";                                                                 // width (1px)
	echo "\x01\x00";                                                                 // height (1px)
	echo "\x80";                                                                     // flags
	echo "\x00";                                                                     // background color index
	echo "\x00";                                                                     // aspect ratio
	echo chr(hexdec($matches[1])).chr(hexdec($matches[2])).chr(hexdec($matches[3])); // Color-0
	echo "\xFF\xFF\xFF";                                                             // Color-1
	echo "\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x01\x44\x00\x3B";             //
	exit;

} elseif (isset($_GET['imagepassthru']) && eregi('^([0-9a-f]{32})\.([a-z]{3,4})$', $_GET['imagepassthru'], $matches)) {

	header('Last-Modified: '.gmdate('D, d M Y H:i:s', 987654321).' GMT'); // date in the past
	if (@file_exists(PHPOP3CLEAN_MD5_IMAGE_CACHE.$_GET['imagepassthru'])) {
		header('Content-type: image/'.$matches[2]);
		readfile(PHPOP3CLEAN_MD5_IMAGE_CACHE.$_GET['imagepassthru']);
	} else {
		$errorPNG = 'iVBORw0KGgoAAAANSUhEUgAAAGQAAABkCAYAAABw4pVUAAAALHRFWHRDcmVhdGlvbiBUaW1lAFRodSAzMCBOb3YgMjAwNiAwNjoyNTo0NSAtMDUwMNMZGTEAAAAHdElNRQfWCx4LHCLw57d3AAAACXBIWXMAAAsSAAALEgHS3X78AAAABGdBTUEAALGPC/xhBQAABs5JREFUeNrtXU1oXUUUHn8wYMAssqigULCLgBVcqHRhIUIXWURwEUHcmEgWAasUURCpEiGFLgKmNCq6CviDSJVKi6S4UEFFXamIoviTWNu0NRH/Sk1rY53DvVdvvje/987cN++988GhvZkz587Md2fuzLwz9wjBSAqXGdIueegyAuHydheAsRlMSGJgQhIDE5IYmJDEwIQkhrqEXAIZkLJfymrpb/9IOSllr5S+PB/pPS3l55LehpTjUh6WcoXlvlvy+yxLOQ9l2Mjv/6qUmx3qQGWZkbKS5y3s/C1lScq0op4mW6T/o5SLJf2L+d+mc50gje2ic0bxt7J8IOVGKacsescMpIxLuWDJX5ZZQx1vl/Kbhy1TW4xK+d0hL+mMNEUI9YZzUp4Q2VO8Q2RPcVnnQq63LuWpXO8WkT2NZb1xTQOWnzx6op+Vsj1PJ1sPKRr5LoWtHXlZC51yeUgeUJRd1xYjinK9IGVrLgfF5t5HuruaIOR7KUOgM1pR713F/Y6Czm5NuXaB3iKk90FjUwMNa2zttbQF2Tot7A/TPaBzQvw/hEcjZJtCZ0ChN+Sgt6rQ+RN0+jXlstkah/SXarTFbmF/kAocBt3J2ITUseWjV7f82DCjNWwtQtq4wc4Y6B4OVaFYDe2qRz3jPpG9/GlmRu+CDUV+na2fIG1LjbbACcqQwc4g6H7rwUeShBARB0Q2Ja0zM/LtiT62QszYOoIQIuNLSD8r5Q0p90u5QWyeKjMhkQmZg7SPhHmRZbK1CmmDNdoCh6x+EQmpEeIzVttsvQ9pwzVsLXraMqKT9rKuhetvDLq2p/QYXN9t0LVtv7wO1xMGXXypn/JpgNR6yBKk6Va610n5xGKLZlXrpTT6/3aFLdpBWLPYwkUmyZimbDOgN9fJhDwCabRIfCwngHrEHVJehoY23XOPwt6DInsvETkvCv1UGoHbMJTvNSm3Sbk6L9s7YIP2/WzvrqQJoRnU85oGqjqbmRb29csZR1vUm447luUrYX8HJk9IAXppvi1at1HO5w3yXF5Z13vSO4Kmzr+CPu1P0RAz4GGLhi/aDvlUtPZU6kEfS7lX2H9eYBhA75vKawdfdNIsq124Hq5Px7wZE2LHrXD9Xcyb9TIhuB+m+23iTrh+r90F71bgal21dhgBHSJxa7sL3q3AxqZZ0aMiW9fQ+uBJ0TpT2t/uQnc7aIrq6jDxjODpaiOgaS25JJEbUNlRgYScJd4SmXMFg8FgMBgMBoPhAJzXz9bMz6gJbFCa8/vM75mQwFCtfpeFu9sMExIYui2J+Yr521HmroJpn8jFl4kJiVy5snvNsrAPXUxI5Mqha75t6GJCGqjcIfjbiGd+HejXvykpn4vW3zTW879PCf2vhFXdijoKqgrRD0HloYtO6w545FeBnNZWhFujruT6tnv1DCEEHLoWPPOXgR6EJEdE5kF4Vf7vEUg/J9SkuN6zY2Gq3AKkjXrmJ9DwcwJ09mjKgm6kugOYPUsIDVMnS2mqocvWOFOQftRSHjzRO+VZ5o6HrXJ4LHrBMz+e09B5oBfAoXJRodPThBBw6BrzyI8HemwHOdFFVHVWo+cJwaGLZmCDjvmrNF4Mmx0D18qhv9Qhx/xMiCd8KjcPumMO+XnI8oRP5Whfa7mkaztWRuCXuid8KzesyMPT3oCoUrl54U5IjIXhX6BX+eNjKaIKITh0Nb118gXoPi7lGhHx4wBNomr3x4+W2fKH2FwsMCPce2jHoU6FZj3z191+L0De7vuk/AI2vA78MxgMBoPBYDAYDAaDwWAwGAwGo9egit4ZYq+fo4KagW38X3v18gfMksSVDjr8tDcI7iGJgQlJDExIYmBCEkPThITwBvHxPvF1pqb70kf/l8Tmb8KTwxwFkLEFDyPoIodS/T4U6piKtSpRFaH8pWIS4vIxfVOIiZCRQ6MSEtKjMCYhJEWoCYp2UESDOws6qp4SMnKotZB1mA7tcxuTkLWcCMQk6MWMHBqdkNBe6TEJ0cX0wFBFMSOH1lN2QOhzGzEJqWorZOTQesoOCH2yKUVCgkUOddntrbuXVcWeKY+PPZtuKFu+ddTq88IwMTRBCEakcRmyTPlTxBpce0VfK6MJQj6D650W/Z2W/Cnia7i+qaqhJgh5E64nLPoTlvyuT6N3WLoaCBk5tAWhZ1mhF4Y4jZ5U2KHAkBiAOOYsK2Tk0FqFdEXIrRP8SM0fItve6M8bgYJP6uKtxyKEEDJyaHRCClJCHcacc7DxQ8OEEKaFPXLohqOt6IQQQh3GJFBPoS3x8llyquySyLbQ+9pACEEXOZR2gV+Rsi1yGzMYXYx/AQ4EtreX/Z9MAAAAAElFTkSuQmCC';
		header('Content-type: image/png');
		echo base64_decode($errorPNG);
	}
	exit;

}
if (isset($matches)) {
	unset($matches);
}

session_start();

///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
if (IsAdminUser() && (@$_GET['imgadmin'] == 'file') && eregi('^[0-9a-f]{32}$', @$_GET['md5'])) {

	$SQLquery  = 'SELECT `image_data`, `ext` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
	$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($_GET['md5']).'")';
	$result = mysql_query_safe($SQLquery);
	if ($row = mysql_fetch_assoc($result)) {
		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.$_REQUEST['md5'].'.'.($row['ext'] ? $row['ext'] : 'jpg'));
		echo $row['image_data'];
		exit;
	} else {
		die('MD5['.htmlentities(@$_GET['md5'], ENT_QUOTES).'] not found in database');
	}

} elseif (IsAdminUser() && (@$_GET['exeadmin'] == 'file')) {

	$SQLquery  = 'SELECT `virus_data`, `pattern` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'exe`';
	$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($_REQUEST['md5']).'")';
	$result = mysql_query_safe($SQLquery);
	if ($row = mysql_fetch_assoc($result)) {
		//ob_end_clean();
		header('Content-type: application/octet-stream');
		if (@$_GET['filtered']) {
			$filtered = FilteredBinaryData($row['virus_data'], $row['pattern']);
			header('Content-Disposition: attachment; filename='.md5($filtered).'.dat');
			echo $filtered;
		} else {
			header('Content-Disposition: attachment; filename='.$_REQUEST['md5'].'.dat');
			echo $row['virus_data'];
		}
		exit;
	}

}
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////

if (!mysql_table_exists(PHPOP3CLEAN_TABLE_PREFIX.'accounts')) {
	die('Table `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` does not exist. Please run <a href="'.PHPOP3CLEAN_DIRECTORY.'phPOP3clean.install.php">phPOP3clean.install.php</a> first.');
}

if (@$_REQUEST['DateRangeMinYear']) {
	$DateRangeMin = mktime($_REQUEST['DateRangeMinHour'], $_REQUEST['DateRangeMinMinute'], 0, $_REQUEST['DateRangeMinMonth'], $_REQUEST['DateRangeMinDay'], $_REQUEST['DateRangeMinYear']);
	$DateRangeMax = mktime($_REQUEST['DateRangeMaxHour'], $_REQUEST['DateRangeMaxMinute'], 0, $_REQUEST['DateRangeMaxMonth'], $_REQUEST['DateRangeMaxDay'], $_REQUEST['DateRangeMaxYear']);
} elseif (empty($_REQUEST['daterange'])) {
	$DateRangeMin = time() - 86400; // last day
	$DateRangeMax = time();
} else {
	list($DateRangeMin, $DateRangeMax) = explode('|', $_REQUEST['daterange']);
}

///////////////////////////////////////////////////////////

if (@$_GET['messages_recent']) {
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'messages_recent`';
	$SQLquery .= ' WHERE (`id` = "'.mysql_escape_string($_REQUEST['messages_recent']).'")';
	if (!IsAdminUser()) {
		$SQLquery .= ' AND (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
	}
	$result = mysql_query_safe($SQLquery);
	if ($row = mysql_fetch_assoc($result)) {
		$ParsedHeader = POP3parseheader($row['headers']);

		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
		echo '<html><head><style type="text/css">body,td,th { font-family: sans-serif; font-size: 9pt; }</style></head><body>';
		echo '<table border="0">';
		echo '<tr><td><b>Account</b></td><td>'.htmlentities($row['account'], ENT_QUOTES).'</td></tr>';
		echo '<tr><td><b>Scanned</b></td><td>'.htmlentities(date('j M Y g:i:sa', $row['date']), ENT_QUOTES).'</td></tr>';
		echo '<tr><td><b>Message ID</b></td><td>'.htmlentities($row['id'], ENT_QUOTES).'</td></tr>';
		echo '<tr><td><b>From</b></td><td>'.htmlentities(@$ParsedHeader['from'][0], ENT_QUOTES).'</td></tr>';
		echo '<tr><td><b>Subject</b></td><td>'.htmlentities(@$ParsedHeader['subject'][0], ENT_QUOTES).'</td></tr>';

		echo '<tr><td valign="top"><b>Domains</b></td><td><ul>';

		$noHTMLtext = strip_tags(QuotedEntityDecode($row['body']));
		$ResolvedDomains = ExtractDomainsFromText($row['body'], $noHTMLtext);
		foreach ($ResolvedDomains as $domain => $IPs) {
			$iplist = '';
			foreach ($IPs as $ip) {
				$iplist .= ($iplist ? ';' : '').'<span style="background-color: #'.(IPisBanned($ip) ? PHPOP3CLEAN_COL_BLIST : (IPisWhitelisted($ip) ? PHPOP3CLEAN_COL_WLIST : PHPOP3CLEAN_COL_OK)).';">'.$ip.'</span>';
			}
			echo '<li>'.htmlentities($domain, ENT_QUOTES).' ['.$iplist.']</li>';
		}
		echo '</ul></td></tr>';

		echo '<tr><td><b>Debug</b></td><td>'.nl2br(htmlentities($row['debug'], ENT_QUOTES)).'</td></tr>';

		echo '</table><br>';
		echo '<textarea cols="100" rows="10" wrap="off">'.htmlentities($row['headers'], ENT_QUOTES).'</textarea>';
		echo '<textarea cols="100" rows="30" wrap="off">'.htmlentities($row['body'], ENT_QUOTES).'</textarea>';
		echo '</body></html>';
	} else {
		echo 'failed to select message (`id` = "'.htmlentities(@$_GET['messages_recent'], ENT_QUOTES).'")';
	}
	exit;
}

///////////////////////////////////////////////////////////

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
echo '<html><head><title>phPOP3clean :: admin</title><style type="text/css">BODY, TH, TD { font-family: sans-serif; font-size: 8pt; }</style></head><body>';
echo '<div style="float: right;">Logged in as: <b>'.htmlentities((IsAdminUser() ? 'ADMIN' : $_COOKIE['phPOP3cleanUSER']), ENT_QUOTES).'</b> <a href="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?logout'), ENT_QUOTES).'">logout</a></div>';

echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE), ENT_QUOTES).'"><b>Filter summary</b></a><br>';
echo 'Edit:<ul style="margin-top: 0px; margin-bottom: 0px;">';
if (IsAdminUser()) {
	echo '<li><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?exeadmin='.__LINE__), ENT_QUOTES).'">Infected Attachments (worms/viruses)</a></li>';
	echo '<li><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?imgadmin='.__LINE__), ENT_QUOTES).'">Attached Images</a></li>';
	echo '<li><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?ipadmin='.__LINE__.'&bulkadd='.__LINE__), ENT_QUOTES).'">IPs Blacklist</a></li>';
	echo '<li><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?autobandomains='.__LINE__), ENT_QUOTES).'">Auto-ban Domains</a></li>';
	echo '<li><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?receivedadmin='.__LINE__), ENT_QUOTES).'">"Received" header domain blacklist</a></li>';
}
echo '<li><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.__LINE__), ENT_QUOTES).'">Words/Phrases</a> (<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.__LINE__.'&action=list&db=phpop3clean_words_clean&orderby=lasthit'), ENT_QUOTES).'">clean</a>, <a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.__LINE__.'&action=list&db=phpop3clean_words_obfuscated&orderby=lasthit'), ENT_QUOTES).'">obfuscated</a>, <a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.__LINE__.'&action=list&db=phpop3clean_words_code&orderby=lasthit'), ENT_QUOTES).'">source</a>)</li>';
echo '<li><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?emailwhitelistadmin='.__LINE__), ENT_QUOTES).'">"From" email whitelist</a></li>';
echo '<li><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?subjectwhitelistadmin='.__LINE__), ENT_QUOTES).'">Subject whitelist</a></li>';
echo '</ul>';
echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?useradmin='.__LINE__), ENT_QUOTES).'">User admin</a><br>';
if (IsAdminUser()) {
	//echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?recentdomains='.__LINE__), ENT_QUOTES).'">List recently-seen domains</a><br>';
	echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?databaseupdate='.__LINE__), ENT_QUOTES).'">Update Database</a><br>';
	echo '<a href="'.PHPOP3CLEAN_DIRECTORY.'phPOP3clean.emptycache.php">Purge/Backup quarantined emails</a><br>';
	echo '<a href="'.PHPOP3CLEAN_DIRECTORY.'phPOP3clean.datadump.php">Export Database</a><br>';
}
echo '<hr><br clear="all">';

///////////////////////////////////////////////////////////////////////////////

if (@$_REQUEST['wordadmin']) {

	if (@$_REQUEST['db']) {

		echo '<b>'.ucfirst(str_replace(PHPOP3CLEAN_TABLE_PREFIX.'words_', '', htmlentities($_REQUEST['db']))).' Words Admin</b><br>';
		echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post">';
		//$hiddenvars = array('wordadmin', 'action', 'db', 'orderby');
		$hiddenvars = array('wordadmin', 'action', 'db');
		foreach ($hiddenvars as $var) {
			echo '<input type="hidden" name="'.$var.'" value="'.htmlentities(@$_REQUEST[$var], ENT_QUOTES).'">';
		}
		echo 'Banned Phrase test: <textarea cols="40" rows="2" name="testword">'.htmlentities(@$_REQUEST['testword'], ENT_QUOTES).'</textarea> ';
		echo '<input type="submit" value="Test Phrase">';
		echo '</form>';
		if (@$_REQUEST['testword']) {
			echo '<div style="background-color: #EEEEEE; border: 2px #000000 inset;" align="center">';
			$badword1 = BlackListedWordsFound($_REQUEST['testword']);
			$badword2 = BlackListedWordsFoundCode($_REQUEST['testword']);
			list($matchedword1, $cleaninfo1) = $badword1;
			list($matchedword2, $cleaninfo2) = $badword2;
			$actual_regex = '';
			if ($badword1 !== false) {
				echo '<span style="color: red;">Banned phrase:<br><b>'.htmlentities($matchedword1, ENT_QUOTES).'</b><br>matches:<br>'.htmlentities($cleaninfo1['word'], ENT_QUOTES).'</span>';
				$actual_regex = 'Actual regex used:<br><textarea rows="3" cols="80">'.htmlentities($cleaninfo1['regex']).'</textarea><br>';
			} elseif ($badword2 !== false) {
				echo '<span style="color: red;">Banned phrase (code):<br><b>'.htmlentities($matchedword2, ENT_QUOTES).'</b><br>matches:<br>'.htmlentities($cleaninfo2['word'], ENT_QUOTES).'</span>';
				$actual_regex = 'Actual regex used:<br><textarea rows="3" cols="80">'.htmlentities($cleaninfo2['regex']).'</textarea><br>';
			} else {
				echo '<span style="color: darkgreen;">Phrase is OK:<br><b>'.htmlentities($_REQUEST['testword'], ENT_QUOTES).'</b></span>';
			}
			echo '</div><br>';
			echo $actual_regex;
		}
		echo '<br>';

	} else {

		echo '<b>Blacklisted Words admin</b><ul>';
		echo '<li><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.$_REQUEST['wordadmin'].'&action=list&db='.PHPOP3CLEAN_TABLE_PREFIX.'words_clean&orderby=lasthit'), ENT_QUOTES).'">"Clean" Words admin</a></li>';
		echo '<li><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.$_REQUEST['wordadmin'].'&action=list&db='.PHPOP3CLEAN_TABLE_PREFIX.'words_obfuscated&orderby=lasthit'), ENT_QUOTES).'">Obfuscated Words admin</a></li>';
		echo '<li><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.$_REQUEST['wordadmin'].'&action=list&db='.PHPOP3CLEAN_TABLE_PREFIX.'words_code&orderby=lasthit'), ENT_QUOTES).'">Source Code Words admin</a></li>';
		echo '</ul>';

	}

	switch (@$_REQUEST['action']) {
		case 'delete':
			$SQLquery  = 'DELETE FROM `'.mysql_escape_string($_REQUEST['db']).'`';
			$SQLquery .= ' WHERE `id` = "'.mysql_escape_string($_REQUEST['id']).'"';
			$SQLquery .= ' AND (`account` LIKE "'.mysql_escape_string(IsAdminUser() ? '%' : $_COOKIE['phPOP3cleanUSER']).'")';
			mysql_query_safe($SQLquery);
			$redirect = PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.__LINE__.'&action=list&db='.$_REQUEST['db'];
			echo 'Record deleted<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
			echo '<script type="text/javascript">location = "'.$redirect.'";</script>';
			break;

		case 'save':
			$strtr_array[0] = array(' '=>PHPOP3CLEAN_OBFUSPACE.'*', '-'=>PHPOP3CLEAN_OBFUSPACE.'*');
			$strtr_array[1] = array(' '=>PHPOP3CLEAN_OBFUSPACE.'*');
			ob_start();
			$regex = preg_expression(strtr(intval(@$_POST['isregex']) ? $_POST['word'] : preg_quote($_POST['word']), $strtr_array[intval(@$_POST['isregex'])]));
			preg_match($regex, '');
			$warnings = ob_get_contents();
			ob_end_clean();
			if ($warnings === '') {

				if ($_POST['id'] == 'new') {
					$SQLquery  = 'INSERT IGNORE INTO `'.mysql_escape_string($_POST['db']).'` (`word`, `account`, `isregex`, `casesensitive`, `added`, `onlychars`, `description`) VALUES (';
					$SQLquery .= '"'.mysql_escape_string($_POST['word']).'", ';
					$SQLquery .= '"'.mysql_escape_string(IsAdminUser() ? '' : $_COOKIE['phPOP3cleanUSER']).'", ';
					$SQLquery .= '"'.mysql_escape_string(intval(@$_POST['isregex'])).'", ';
					$SQLquery .= '"'.mysql_escape_string(intval(@$_POST['casesensitive'])).'", ';
					$SQLquery .= '"'.mysql_escape_string(time()).'", ';
					$SQLquery .= '"'.mysql_escape_string($_POST['onlychars']).'", ';
					$SQLquery .= '"'.mysql_escape_string($_POST['description']).'")';
				} else {
					$SQLquery  = 'UPDATE `'.mysql_escape_string($_POST['db']).'` SET';
					$SQLquery .= ' `word` = "'.mysql_escape_string($_POST['word']).'",';
					if (IsAdminUser()) {
						$SQLquery .= ' `isregex` = "'.mysql_escape_string(intval(@$_POST['isregex'])).'",';
						$SQLquery .= ' `dotall` = "'.mysql_escape_string(intval(@$_POST['dotall'])).'",';
						$SQLquery .= ' `onlychars` = "'.mysql_escape_string(@$_POST['onlychars']).'",';
					}
					$SQLquery .= ' `casesensitive` = "'.mysql_escape_string(intval(@$_POST['casesensitive'])).'",';
					$SQLquery .= ' `description` = "'.mysql_escape_string($_POST['description']).'"';
					$SQLquery .= ' WHERE `id` = "'.mysql_escape_string($_POST['id']).'"';
					$SQLquery .= ' AND (`account` LIKE "'.mysql_escape_string(IsAdminUser() ? '%' : $_COOKIE['phPOP3cleanUSER']).'")';
				}
				mysql_query_safe($SQLquery);
				$redirect = PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.__LINE__.'&action=list&db='.$_REQUEST['db'];
				echo 'Record updated<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
				echo '<script type="text/javascript">location = "'.$redirect.'";</script>';

			} else {

				echo 'There is a problem with your regular expression, please go back and fix it before saving';
				echo '<blockquote style="background-color: #FF9999; padding: 5px;">'.trim(strip_tags($warnings)).'</blockquote>';

			}
			break;

		case 'edit':
			if (@$_REQUEST['id'] == 'new') {

				$row = array('id'=>'new', 'word'=>'', 'description'=>'', 'isregex'=>'0', 'casesensitive'=>'0', 'dotall'=>'1', 'onlychars'=>'');

			} else {

				$SQLquery  = 'SELECT * FROM `'.mysql_escape_string($_REQUEST['db']).'`';
				$SQLquery .= ' WHERE `id` = "'.mysql_escape_string($_REQUEST['id']).'"';
				$SQLquery .= ' AND (`account` LIKE "'.mysql_escape_string(IsAdminUser() ? '%' : $_COOKIE['phPOP3cleanUSER']).'")';
				$result = mysql_query_safe($SQLquery);
				$row = mysql_fetch_assoc($result);
				unset($word);

			}
			if (!empty($row)) {
				echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post">';
				echo '<b>Word:</b> <input type="text" name="word" value="'.htmlentities($row['word'], ENT_QUOTES).'" size="40"> ';
				if (strpos($_REQUEST['db'], 'obfuscated') === false) {
					// regular expressions cannot be used for obfuscated words
					if (IsAdminUser()) {
						// disable user-level regex words until a good validation method is in place
						echo '<br>Containing only these characers: <input type="text" name="onlychars" value="'.htmlentities($row['onlychars'], ENT_QUOTES).'" size="20"> (regex, eg "a-zA-Z0-9")<br>';
						echo '<input type="checkbox" name="isregex"   value="1"'.($row['isregex']       ? ' CHECKED' : '').'>Regular Expression | ';
						echo '<input type="checkbox" name="dotall"    value="1"'.($row['dotall']        ? ' CHECKED' : '').'>dot matches linebreaks | ';
					} elseif ($row['onlychars']) {
						echo '<br>Containing only these characers: "<b>'.htmlentities($row['onlychars'], ENT_QUOTES).'</b>"<br>';
					}
					echo '<input type="checkbox" name="casesensitive" value="1"'.($row['casesensitive'] ? ' CHECKED' : '').'>Case-Sensitive<br>';
					echo '<ul style="font-style: italic;">';
					echo '<li>Use hex characters for HTML entities in regular expressions, for example "\xA0" instead of "&amp;nbsp;"</li>';
					echo '<li>Use <b>\s</b> instead of a normal space inside bracketed expressions in regex mode (good: [\sa-z]+; bad: [ a-z]+)</li>';
					echo '</ul>';
				}
				echo '<br><b>Description (optional):</b><br><textarea name="description" cols="40" rows="3">'.htmlentities($row['description'], ENT_QUOTES).'</textarea><br><br>';
				echo '<input type="hidden" name="wordadmin" value="'.htmlentities($_REQUEST['wordadmin'], ENT_QUOTES).'">';
				echo '<input type="hidden" name="action" value="save">';
				//echo '<input type="hidden" name="orderby" value="'.htmlentities(@$_REQUEST['orderby'], ENT_QUOTES).'">';
				echo '<input type="hidden" name="db" value="'.htmlentities($_REQUEST['db'], ENT_QUOTES).'">';
				echo '<input type="hidden" name="id" value="'.htmlentities($row['id'], ENT_QUOTES).'">';
				echo '<input type="submit" value="Save">';
				echo '</form>';
			} else {
				echo 'Cannot find record for word #'.htmlentities(@$_REQUEST['id'], ENT_QUOTES);
			}
			break;

		case 'list':
			$SQLquery  = 'SELECT * FROM `'.mysql_escape_string($_REQUEST['db']).'`';
			$SQLquery .= ' WHERE (`account` LIKE "'.mysql_escape_string(IsAdminUser() ? '%' : $_COOKIE['phPOP3cleanUSER']).'")';
			$SQLquery .= ' ORDER BY `'.mysql_escape_string(@$_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'lasthit').'` '.mysql_escape_string((@$_GET['orderorder'] == 'ASC') ? 'ASC' : 'DESC');
			$result = mysql_query_safe($SQLquery);
			echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.$_REQUEST['wordadmin'].'&db='.$_REQUEST['db'].'&action=edit&id=new&orderby='.@$_REQUEST['orderby']), ENT_QUOTES).'">Add new word</a></br>';
			echo '<table border="1" cellspacing="0" cellpadding="3">';

			$fields = array('word', 'hitcount', 'lasthit', 'added', 'category', 'description', 'onlychars');
			$invAscDesc = array(''=>'ASC', 'ASC'=>'DESC', 'ASC'=>'');
			echo '<tr><th></th>';
			foreach ($fields as $field) {
				echo '<th nowrap><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.$_REQUEST['wordadmin'].'&action=list&db='.$_REQUEST['db'].'&orderby='.$field.((@$_REQUEST['orderby'] == $field) ? '&orderorder='.@$invAscDesc[@$_REQUEST['orderorder']] : '')), ENT_QUOTES).'">'.htmlentities($field, ENT_QUOTES).'</a> <span style="font-size: 18pt;">'.UpDownSymbol($field).'</span></th>';
			}
			echo '<th nowrap>Popularity</th><th>&nbsp;</th></tr>';

			while ($row = mysql_fetch_assoc($result)) {

				echo "\n".'<tr>';
				echo "\n\t".'<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.$_REQUEST['wordadmin'].'&db='.$_REQUEST['db'].'&action=edit&id='.$row['id']), ENT_QUOTES).'">edit</a></td>';
				echo "\n\t".'<td>'.(@$row['isregex'] ? '<b>' : '').(@$row['casesensitive'] ? '<i>' : '').preg_replace('#\(([^\(]+)\)#U', ' ($1) ', htmlentities($row['word'], ENT_QUOTES)).(@$row['casesensitive'] ? '</i>' : '').(@$row['isregex'] ? '</b>' : '').'</td>';
				echo "\n\t".'<td align="right">'.number_format($row['hitcount']).'</td>';
				if ($row['lasthit']) {
					echo "\n\t".'<td align="right" bgcolor="#'.LastHit2bgcolor($row['lasthit']).'" nowrap>'.date('M-d-Y', $row['lasthit']).'</td>';
					if ($row['added']) {
						echo "\n\t".'<td align="right" nowrap>'.date('M-d-Y', $row['added']).'</td>';
					} else {
						echo "\n\t".'<td align="center">-</td>';
					}
				} else {
					echo "\n\t".'<td align="center">-</td>';
					echo "\n\t".'<td align="right" bgcolor="#'.LastHit2bgcolor($row['added']).'" nowrap>'.date('M-d-Y', $row['added']).'</td>';
				}
				echo "\n\t".'<td>'.htmlentities($row['category'], ENT_QUOTES).'&nbsp;</td>';
				echo "\n\t".'<td>'.nl2br(htmlentities($row['description'], ENT_QUOTES)).'&nbsp;</td>';
				echo "\n\t".'<td>'.htmlentities($row['onlychars'], ENT_QUOTES).'&nbsp;</td>';
				echo "\n\t".'<td align="right">'.($row['lasthit'] ? round($row['hitcount'] * ($row['hitcount'] / (($row['lasthit'] - $row['added']) / 86400)), 1) : '-').'</td>';
				echo "\n\t".'<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.$_REQUEST['wordadmin'].'&db='.$_REQUEST['db'].'&action=delete&id='.$row['id']), ENT_QUOTES).'" onClick="return confirm(\'Are you SURE you want to delete this word?\');">delete</a></td>';
				echo "\n".'</tr>';
				unset($word);
			}
			echo '</table>';
			break;

		default:
			break;
	}

} elseif (IsAdminUser() && @$_GET['recentdomains']) {

	$SQLquery  = 'SELECT `domain`, COUNT(`domain`) AS `hitcount` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_recent`';
	$SQLquery .= ' GROUP BY `domain`';
	$result = mysql_query_safe($SQLquery);
	echo '<div id="currentlookupstatus"></div>';
	echo '<table border="1" cellspacing="0" cellpadding="3">';
	echo '<tr><th>domain</th><th>hits</th><th>IPs</th></tr>';
	$DomainsToLookup = array();
	while ($row = mysql_fetch_assoc($result)) {
		$DomainsToLookup[$row['domain']] = $row['hitcount'];
	}
	foreach ($DomainsToLookup as $domain => $hitcount) {
		echo '<tr>';
		echo '<td><a href="'.htmlentities(linkencode('http://'.$domain), ENT_QUOTES).'" target="_blank">'.htmlentities($domain, ENT_QUOTES).'</a></td>';
		echo '<td align="right">'.number_format($hitcount).'</td>';
		echo '<td nowrap id="IPs_'.$domain.'" style="font-style: italic; font-family: monospace; background-color: yellow;">waiting...</td>';
		echo '</tr>';
	}
	echo '</table>';
	foreach ($DomainsToLookup as $domain => $hitcount) {
		echo '<script type="text/javascript">if (document.getElementById("currentlookupstatus")) document.getElementById("currentlookupstatus").innerHTML = "Looking up: <b>'.$domain.'<\\/b>";</script>';
		flush();
		if ($DomainIPs = SafeGetHostByNameL($domain)) {
			$thisDomainIPs = '';
			foreach ($DomainIPs as $ip) {
				@$_SESSION['domain_lookup_success'][$domain][] = $ip;
				$thisDomainIPs .= '<div align=\"right\"';
				if (IPisBanned($ip)) {
					 $thisDomainIPs .= ' style=\"background-color: #'.PHPOP3CLEAN_COL_BLIST.';\"';
				} elseif (IPisWhitelisted($ip)) {
					 $thisDomainIPs .= ' style=\"background-color: #'.PHPOP3CLEAN_COL_WLIST.';\"';
				}
				$thisDomainIPs .= '><tt>'.PadIPtext($ip, true).'</tt></div>';
			}
			echo '<script type="text/javascript">if (document.getElementById("IPs_'.$domain.'")) document.getElementById("IPs_'.$domain.'").innerHTML = "'.PadIPtext($thisDomainIPs, true).'";</script>';
		} else {
			$_SESSION['domain_lookup_failed'][$domain] = true;
			echo '<script type="text/javascript">if (document.getElementById("IPs_'.$domain.'")) document.getElementById("IPs_'.$domain.'").innerHTML = "<div style=\"background-color: orange;\">FAILED LOOKUP</div>";</script>';
		}
		echo '<script type="text/javascript">if (document.getElementById("IPs_'.$domain.'")) document.getElementById("IPs_'.$domain.'").style.backgroundColor = "white";</script>';
		flush();
	}
	echo '<script type="text/javascript">if (document.getElementById("currentlookupstatus")) document.getElementById("currentlookupstatus").innerHTML = "";</script>';

} elseif (@$_REQUEST['emailwhitelistadmin']) {

	echo '<h3>Whitelist admin - Email</h3>';

	switch (@$_REQUEST['action']) {
		case 'add':
			echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post">';
			echo '<i>entered value is matched against end of incoming email addresses, you can put "@example.com" to wildcard-match all addresses at any domain</i><br>';
			echo 'emails (one per line):<br><textarea name="email" rows="8" cols="40">'.htmlentities(@$_REQUEST['email'], ENT_QUOTES).'</textarea><br>';
			if (IsAdminUser()) {
				echo 'account: <input type="text" name="account" value="'.htmlentities(@$_REQUEST['account'], ENT_QUOTES).'"> (optional)<br>';
			}
			echo '<input type="hidden" name="emailwhitelistadmin" value="1">';
			echo '<input type="hidden" name="action" value="insert">';
			echo '<input type="submit" value="Insert">';
			echo '</form>';
			break;

		case 'edit':
			echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post">';
			echo '<i>entered value is matched against end of incoming email addresses, you can put "@example.com" to wildcard-match all addresses at any domain</i><br>';
			echo 'email: <input type="text" name="email" value="'.htmlentities(@$_REQUEST['email'], ENT_QUOTES).'"><br>';
			if (IsAdminUser()) {
				echo 'account: <input type="text" name="account" value="'.htmlentities(@$_REQUEST['account'], ENT_QUOTES).'"> (optional)<br>';
			}
			echo '<input type="hidden" name="oldemail" value="'.htmlentities(@$_REQUEST['email'], ENT_QUOTES).'">';
			echo '<input type="hidden" name="emailwhitelistadmin" value="1">';
			echo '<input type="hidden" name="action" value="update">';
			echo '<input type="submit" value="Update">';
			echo '</form>';
			break;

		case 'insert':
			$insert_emails = explode("\n", $_REQUEST['email']);
			foreach ($insert_emails as $dirty_email) {
				$clean_email = SanitizeEmailAddress($dirty_email);
				$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_email` (`email`, `account`, `added`) VALUES (';
				$SQLquery .= '"'.mysql_escape_string($clean_email).'", ';
				$SQLquery .= '"'.mysql_escape_string(IsAdminUser() ? $_POST['account'] : $_COOKIE['phPOP3cleanUSER']).'", ';
				$SQLquery .= '"'.mysql_escape_string(time()).'")';
				mysql_query_safe($SQLquery);
				unset($dirty_email, $clean_email, $SQLquery);
			}
			$redirect = PHPOP3CLEAN_ADMINPAGE.'?emailwhitelistadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
			echo 'Inserted '.count($insert_emails).' emails.<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
			echo '<script type="text/javascript">location = "'.$redirect.'";</script>';
			break;

		case 'update':
			$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_email` SET ';
			$SQLquery .= '`email` = "'.mysql_escape_string($_POST['email']).'"';
			if (IsAdminUser()) {
				$SQLquery .= ', `account` = "'.mysql_escape_string($_POST['account']).'"';
			}
			$SQLquery .= ' WHERE (`email` = "'.mysql_escape_string($_POST['oldemail']).'")';
			$SQLquery .= ' AND (`account` LIKE "'.mysql_escape_string(IsAdminUser() ? '%' : $_COOKIE['phPOP3cleanUSER']).'")';
			mysql_query_safe($SQLquery);
			$redirect = PHPOP3CLEAN_ADMINPAGE.'?emailwhitelistadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
			echo 'Updated.<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
			echo '<script type="text/javascript">location = "'.$redirect.'";</script>';
			break;

		case 'delete':
			$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_email`';
			$SQLquery .= ' WHERE (`email` = "'.mysql_escape_string($_REQUEST['email']).'")';
			$SQLquery .= ' AND (`account` LIKE "'.mysql_escape_string(IsAdminUser() ? '%' : $_COOKIE['phPOP3cleanUSER']).'")';
			mysql_query_safe($SQLquery);
			$redirect = PHPOP3CLEAN_ADMINPAGE.'?emailwhitelistadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
			echo 'Deleted.<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
			echo '<script type="text/javascript">location = "'.$redirect.'";</script>';
			break;

		default:
			$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_email`';
			$SQLquery .= ' WHERE (`account` LIKE "'.mysql_escape_string(IsAdminUser() ? '%' : $_COOKIE['phPOP3cleanUSER']).'")';
			$SQLquery .= ' ORDER BY `'.mysql_escape_string(@$_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'lasthit').'` '.mysql_escape_string((@$_GET['orderorder'] == 'ASC') ? 'ASC' : 'DESC');
			$result = mysql_query_safe($SQLquery);
			echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?emailwhitelistadmin='.__LINE__.'&action=add'), ENT_QUOTES).'">Add new</a><br>';
			echo '<table border="1" cellspacing="0" cellpadding="3">';

			$fields = array('email', 'hitcount', 'lasthit', 'added');
			$invAscDesc = array(''=>'ASC', 'ASC'=>'DESC', 'ASC'=>'');
			echo '<tr><th>&nbsp;</th><th>Account</th>';
			foreach ($fields as $field) {
				echo '<th nowrap>';
				echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?emailwhitelistadmin='.$_REQUEST['emailwhitelistadmin'].'&orderby='.$field.((@$_REQUEST['orderby'] == $field) ? '&orderorder='.@$invAscDesc[@$_REQUEST['orderorder']] : '')), ENT_QUOTES).'">';
				echo htmlentities($field, ENT_QUOTES).'</a> ';
				echo '<span style="font-size: 18pt;">'.UpDownSymbol($field).'</span>';
				echo '</th>';
			}
			echo '<th>&nbsp;</th></tr>';

			while ($row = mysql_fetch_assoc($result)) {
				echo '<tr>';
				echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?emailwhitelistadmin='.__LINE__.'&action=edit&email='.$row['email'].'&account='.$row['account']), ENT_QUOTES).'">edit</a></td>';
				echo '<td>'.htmlentities($row['account'] ? $row['account'] : '*ALL*', ENT_QUOTES).'</td>';
				echo '<td>'.htmlentities($row['email'], ENT_QUOTES).'</td>';
				echo '<td align="right">'.number_format($row['hitcount']).'</td>';
				if ($row['lasthit']) {
					echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['lasthit']).'">'.date('M-d-Y', $row['lasthit']).'</td>';
					echo '<td align="right">'.($row['added'] ? date('M-d-Y', $row['added']) : '-').'</td>';
				} else {
					echo '<td align="center">-</td>';
					echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['added']).'">'.($row['added'] ? date('M-d-Y', $row['added']) : '-').'</td>';
				}
				echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?emailwhitelistadmin='.__LINE__.'&action=delete&email='.$row['email']), ENT_QUOTES).'" onClick="return confirm(\'Are you sure you want to delete this?\');">delete</a></td>';
				echo '</tr>';
			}
			echo '</table>';
			break;
	}

} elseif (@$_REQUEST['subjectwhitelistadmin']) {

	echo '<h3>Whitelist admin - Subject</h3>';

	switch (@$_REQUEST['action']) {
		case 'add':
		case 'edit':
			echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post">';
			echo 'word: <input type="text" name="word" value="'.htmlentities(@$_REQUEST['word'], ENT_QUOTES).'">';
			echo '<input type="hidden" name="oldword" value="'.htmlentities(@$_REQUEST['word'], ENT_QUOTES).'">';
			echo '<input type="hidden" name="subjectwhitelistadmin" value="1">';
			echo '<input type="hidden" name="action" value="'.(($_REQUEST['action'] == 'add') ? 'insert' : 'update').'">';
			echo '<input type="submit" value="'.(($_REQUEST['action'] == 'add') ? 'Insert' : 'Update').'">';
			echo '</form>';
			break;

		case 'insert':
			$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_subject` (`word`, `account`, `added`) VALUES (';
			$SQLquery .= '"'.mysql_escape_string($_REQUEST['word']).'", ';
			$SQLquery .= '"'.mysql_escape_string(IsAdminUser() ? '' : $_COOKIE['phPOP3cleanUSER']).'", ';
			$SQLquery .= '"'.mysql_escape_string(time()).'")';
			mysql_query_safe($SQLquery);
			$redirect = PHPOP3CLEAN_ADMINPAGE.'?subjectwhitelistadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
			echo 'Inserted.<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
			echo '<script type="text/javascript">location = "'.$redirect.'";</script>';
			break;

		case 'update':
			$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_subject` SET ';
			$SQLquery .= '`word` = "'.mysql_escape_string($_REQUEST['word']).'"';
			$SQLquery .= ' WHERE (`word` = "'.mysql_escape_string($_REQUEST['oldword']).'")';
			$SQLquery .= ' AND (`account` LIKE "'.mysql_escape_string(IsAdminUser() ? '%' : $_COOKIE['phPOP3cleanUSER']).'")';
			mysql_query_safe($SQLquery);
			$redirect = PHPOP3CLEAN_ADMINPAGE.'?subjectwhitelistadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
			echo 'Updated.<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
			echo '<script type="text/javascript">location = "'.$redirect.'";</script>';
			break;

		case 'delete':
			$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_subject`';
			$SQLquery .= ' WHERE (`word` = "'.mysql_escape_string($_REQUEST['word']).'")';
			$SQLquery .= ' AND (`account` LIKE "'.mysql_escape_string(IsAdminUser() ? '%' : $_COOKIE['phPOP3cleanUSER']).'")';
			mysql_query_safe($SQLquery);
			$redirect = PHPOP3CLEAN_ADMINPAGE.'?subjectwhitelistadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
			echo 'Deleted.<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
			echo '<script type="text/javascript">location = "'.$redirect.'";</script>';
			break;

		default:
			$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_subject`';
			$SQLquery .= ' WHERE (`account` LIKE "'.mysql_escape_string(IsAdminUser() ? '%' : $_COOKIE['phPOP3cleanUSER']).'")';
			$SQLquery .= ' ORDER BY `'.mysql_escape_string(@$_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'lasthit').'` '.mysql_escape_string((@$_GET['orderorder'] == 'ASC') ? 'ASC' : 'DESC');
			$result = mysql_query_safe($SQLquery);
			echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?subjectwhitelistadmin='.__LINE__.'&action=add'), ENT_QUOTES).'">Add new</a><br>';
			echo '<table border="1" cellspacing="0" cellpadding="3">';

			$fields = array('word', 'hitcount', 'lasthit', 'added');
			$invAscDesc = array(''=>'ASC', 'ASC'=>'DESC', 'ASC'=>'');
			echo '<tr><th>&nbsp;</th>';
			foreach ($fields as $field) {
				echo '<th nowrap><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?subjectwhitelistadmin='.$_REQUEST['subjectwhitelistadmin'].'&orderby='.$field.((@$_REQUEST['orderby'] == $field) ? '&orderorder='.@$invAscDesc[@$_REQUEST['orderorder']] : '')), ENT_QUOTES).'">'.htmlentities($field, ENT_QUOTES).'</a> <span style="font-size: 18pt;">'.UpDownSymbol($field).'</span></th>';
			}
			echo '<th>&nbsp;</th></tr>';

			while ($row = mysql_fetch_assoc($result)) {
				echo '<tr>';
				echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?subjectwhitelistadmin='.__LINE__.'&action=edit&word='.$row['word']), ENT_QUOTES).'">edit</a></td>';
				echo '<td>'.htmlentities($row['word'], ENT_QUOTES).'</td>';
				echo '<td align="right">'.number_format($row['hitcount']).'</td>';
				if ($row['lasthit']) {
					echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['lasthit']).'">'.date('M-d-Y', $row['lasthit']).'</td>';
					echo '<td align="right">'.($row['added'] ? date('M-d-Y', $row['added']) : '-').'</td>';
				} else {
					echo '<td align="center">-</td>';
					echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['added']).'">'.($row['added'] ? date('M-d-Y', $row['added']) : '-').'</td>';
				}
				echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?subjectwhitelistadmin='.__LINE__.'&action=delete&word='.$row['word']), ENT_QUOTES).'" onClick="return confirm(\'Are you sure you want to delete this?\');">delete</a></td>';
				echo '</tr>';
			}
			echo '</table>';
			break;
	}

} elseif (IsAdminUser() && @$_REQUEST['receivedadmin']) {

	switch (@$_REQUEST['action']) {
		case 'add':
		case 'edit':
			echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post">';
			echo 'Domain: <input type="text" name="domain" value="'.htmlentities(@$_REQUEST['domain'], ENT_QUOTES).'">';
			echo '<input type="hidden" name="olddomain" value="'.htmlentities(@$_REQUEST['domain'], ENT_QUOTES).'">';
			echo '<input type="hidden" name="receivedadmin" value="1">';
			echo '<input type="hidden" name="action" value="'.(($_REQUEST['action'] == 'add') ? 'insert' : 'update').'">';
			echo '<input type="submit" value="'.(($_REQUEST['action'] == 'add') ? 'Insert' : 'Update').'">';
			echo '</form>';
			break;

		case 'insert':
			$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'received_domains` (`domain`, `added`) VALUES (';
			$SQLquery .= '"'.mysql_escape_string($_REQUEST['domain']).'", ';
			$SQLquery .= '"'.mysql_escape_string(time()).'")';
			mysql_query_safe($SQLquery);
			$redirect = PHPOP3CLEAN_ADMINPAGE.'?receivedadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
			echo 'Inserted.<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
			echo '<script type="text/javascript">location = "'.$redirect.'";</script>';
			break;

		case 'update':
			$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'received_domains` SET ';
			$SQLquery .= '`domain` = "'.mysql_escape_string($_REQUEST['domain']).'"';
			$SQLquery .= ' WHERE (`domain` = "'.mysql_escape_string($_REQUEST['olddomain']).'")';
			mysql_query_safe($SQLquery);
			$redirect = PHPOP3CLEAN_ADMINPAGE.'?receivedadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
			echo 'Updated.<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
			echo '<script type="text/javascript">location = "'.$redirect.'";</script>';
			break;

		case 'delete':
			$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'received_domains`';
			$SQLquery .= ' WHERE (`domain` = "'.mysql_escape_string($_REQUEST['domain']).'")';
			mysql_query_safe($SQLquery);
			$redirect = PHPOP3CLEAN_ADMINPAGE.'?receivedadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
			echo 'Deleted.<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
			echo '<script type="text/javascript">location = "'.$redirect.'";</script>';
			break;

		default:
			$SQLquery  = 'SELECT *';
			$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'received_domains`';
			$SQLquery .= ' ORDER BY `'.mysql_escape_string(@$_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'lasthit').'` '.mysql_escape_string((@$_GET['orderorder'] == 'ASC') ? 'ASC' : 'DESC');
			$result = mysql_query_safe($SQLquery);
			echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?receivedadmin='.__LINE__.'&action=add'), ENT_QUOTES).'">Add new</a><br>';
			echo '<table border="1" cellspacing="0" cellpadding="3">';

			$fields = array('domain', 'hitcount', 'lasthit', 'added');
			$invAscDesc = array(''=>'ASC', 'ASC'=>'DESC', 'ASC'=>'');
			echo '<tr><th>&nbsp;</th>';
			foreach ($fields as $field) {
				echo '<th nowrap><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?receivedadmin='.$_REQUEST['receivedadmin'].'&orderby='.$field.((@$_REQUEST['orderby'] == $field) ? '&orderorder='.@$invAscDesc[@$_REQUEST['orderorder']] : '')), ENT_QUOTES).'">'.htmlentities($field, ENT_QUOTES).'</a> <span style="font-size: 18pt;">'.UpDownSymbol($field).'</span></th>';
			}
			echo '<th>&nbsp;</th></tr>';

			while ($row = mysql_fetch_assoc($result)) {
				echo '<tr>';
				echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?receivedadmin='.__LINE__.'&action=edit&domain='.$row['domain']), ENT_QUOTES).'">edit</a></td>';
				echo '<td>'.htmlentities($row['domain'], ENT_QUOTES).'</td>';
				echo '<td align="right">'.number_format($row['hitcount']).'</td>';
				if ($row['lasthit']) {
					echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['lasthit']).'">'.date('M-d-Y', $row['lasthit']).'</td>';
					echo '<td align="right">'.($row['added'] ? date('M-d-Y', $row['added']) : '-').'</td>';
				} else {
					echo '<td align="center">-</td>';
					echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['added']).'">'.date('M-d-Y', $row['added']).'</td>';
				}
				echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?receivedadmin='.__LINE__.'&action=delete&domain='.$row['domain']), ENT_QUOTES).'" onClick="return confirm(\'Are you sure you want to delete this?\');">delete</a></td>';
				echo '</tr>';
			}
			echo '</table>';
			break;
	}

} elseif (IsAdminUser() && @$_REQUEST['autobandomains']) {

	if (@$_REQUEST['add']) {
		$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_autoban` (`domain`, `added`) VALUES (';
		$SQLquery .= ' "'.mysql_escape_string(ereg_replace('[^a-z0-9\.\-]+', '', strtolower($_REQUEST['add']))).'",';
		$SQLquery .= ' "'.mysql_escape_string(time()).'")';
		mysql_query_safe($SQLquery);
	} elseif (@$_REQUEST['delete']) {
		$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_autoban`';
		$SQLquery .= ' WHERE (`domain` = "'.mysql_escape_string($_REQUEST['delete']).'")';
		mysql_query_safe($SQLquery);
	}

	echo '<b>Auto-ban IP Admin</b><br><br>';
	echo '<blockquote>The Auto-Ban feature automatically bans IPs that entered domains resolve to. This is designed to be used for domains that resolve to (typically) 5 different IPs (presumably zombie machines) every lookup.  Use with caution.</blockquote>';
	echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post">';
	echo '<input type="hidden" name="autobandomains" value="'.htmlentities(@$_REQUEST['autobandomains'], ENT_QUOTES).'">';
	echo 'Add auto-ban domain: <input type="text" name="add" value="" size="20">';
	echo '<input type="submit" value="Add">';
	echo '</form>';

	$sortkeys = array('domain'=>0, 'lasthit'=>1, 'added'=>1, 'hitcount'=>1);
	$_REQUEST['orderby'] = ((isset($_REQUEST['orderby']) && isset($sortkeys[$_REQUEST['orderby']])) ? $_REQUEST['orderby'] : 'added');
	$_REQUEST['order']   = (isset($_REQUEST['order']) ? intval($_REQUEST['order']) : 1);
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_autoban`';
	$SQLquery .= ' ORDER BY `'.mysql_escape_string($_REQUEST['orderby']).'` '.($_REQUEST['order'] ? 'DESC' : 'ASC');
	$result = mysql_query_safe($SQLquery);
	echo '<table border="1" cellspacing="0" cellpadding="3"><tr>';
	foreach ($sortkeys as $sortkey => $defaultsortdirection) {
		echo '<th'.(($sortkey === $_REQUEST['orderby']) ? ' bgcolor="yellow"' : '').'><a href="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?autobandomains=1&orderby='.$sortkey.'&order='), ENT_QUOTES);
		if ($sortkey === $_REQUEST['orderby']) {
			echo (@$_REQUEST['order'] ? '0"><span style="font-size: 18pt;">&#8679;</span>' : '1"><span style="font-size: 18pt;">&#8681;</span>');
		} else {
			echo $defaultsortdirection.'"><span style="font-size: 18pt;">&#8681;</span>';
		}
		echo $sortkey.'</a></th>';
	}
	echo '<th>&nbsp;</th></tr>';
	while ($row = mysql_fetch_assoc($result)) {
		echo '<tr>';
		echo '<td align="right"><tt>'.htmlentities($row['domain'], ENT_QUOTES).'</tt></td>';
			if ($row['lasthit']) {
				echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['lasthit']).'">'.date('M-d-Y', $row['lasthit']).'</td>';
				echo '<td align="right">'.($row['added'] ? date('M-d-Y', $row['added']) : '-').'</td>';
			} else {
				echo '<td align="center">-</td>';
				echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['added']).'">'.date('M-d-Y', $row['added']).'</td>';
			}
		echo '<td align="right"><tt>'.number_format($row['hitcount']).'</tt></td>';
		echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?autobandomains=1&delete='.$row['domain']), ENT_QUOTES).'" onClick="return confirm(\'Are you SURE you want to delete this?\');">delete</a></td>';
		echo '</tr>';
	}
	echo '</table>';

} elseif (IsAdminUser() && @$_REQUEST['databaseupdate']) {

	echo '<b>Database update</b><br><br>';
	echo '<a href="http://sourceforge.net/project/showfiles.php?group_id=131372&amp;package_id=146813"><b>Download the latest SQL updates</b></a><br><br>';

	if (is_uploaded_file(@$_FILES['uploaded_sql']['tmp_name'])) {
		$lines = file($_FILES['uploaded_sql']['tmp_name']);

		$ignoredlines   = 0;
		$processedlines = 0;
		foreach ($lines as $line) {
			$line = trim($line);
			if (preg_match('/^(REPLACE|INSERT IGNORE) INTO `phpop3clean_([a-z_]+)` \([a-z0-9_`, ]+\) VALUES \(.*\);$/i', $line, $matches)) {
				$SQLquery = str_replace($matches[1].' INTO `phpop3clean_', $matches[1].' INTO `'.PHPOP3CLEAN_TABLE_PREFIX, $line);
				$result = mysql_query_safe($SQLquery);
				$processedlines++;
			} elseif ($line) {
				$ignoredlines++;
			}
		}
		$phrasetables = array('clean', 'code', 'obfuscated');
		$purgedduplicates = 0;
		foreach ($phrasetables as $tablesuffix) {
			$SQLquery  = 'SELECT `word`, COUNT(*) AS `wordcount`';
			$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$tablesuffix.'`';
			$SQLquery .= ' GROUP BY `word`';
			$SQLquery .= ' ORDER BY `wordcount` DESC';
			$result = mysql_query_safe($SQLquery);
			while ($row = mysql_fetch_assoc($result)) {
				$SQLquery2  = 'SELECT `id`';
				$SQLquery2 .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$tablesuffix.'`';
				$SQLquery2 .= ' WHERE (`word` = "'.mysql_escape_string($row['word']).'")';
				$SQLquery2 .= ' ORDER BY `hitcount` DESC, `lasthit` DESC';
				$result2 = mysql_query_safe($SQLquery2);
				if (mysql_num_rows($result2) <= 1) {
					break;
				}
				$recordcounter = 0;
				while ($row2 = mysql_fetch_assoc($result2)) {
					if (!$recordcounter++) {
						continue;
					}
					$SQLquery3  = 'DELETE';
					$SQLquery3 .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$tablesuffix.'`';
					$SQLquery3 .= ' WHERE (`id` = "'.mysql_escape_string($row2['id']).'")';
					mysql_query_safe($SQLquery3);
					$purgedduplicates++;
				}
			}
		}
		echo 'Ignored <b>'.number_format($ignoredlines).'</b> lines'.($ignoredlines ? ' (they did not appear to be (REPLACE|INSERT IGNORE) INTO queries)' : '').'<br>';
		echo 'Processed <b>'.number_format($processedlines).'</b> lines<br>';
		echo 'Purged <b>'.number_format($purgedduplicates).'</b> duplicate records<br>';

	} else {

		echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post" enctype="multipart/form-data">';
		echo '<input type="hidden" name="databaseupdate" value="insert">';
		echo 'Upload the "(REPLACE|INSERT IGNORE) INTO" SQL file here (one statement per line):<br>';
		if (PHPOP3CLEAN_TABLE_PREFIX != 'phpop3clean_') {
			echo '<i>Note: The table name prefix can be left as "phpop3clean_", it will be auto-replaced with "'.PHPOP3CLEAN_TABLE_PREFIX.'"</i><br>';
		}
		echo '<input type="file" name="uploaded_sql"><br>';
		echo '<input type="submit" value="Upload &amp; Process">';
		echo '</form>';

	}

} elseif (@$_REQUEST['useradmin']) {

	if ($_REQUEST['useradmin'] == 'edit') {

		if (IsAdminUser() && ($_REQUEST['account'] == 'new')) {
			$row = array(
				'account'       => 'user@example.com',
				'password'      => '',
				'hostname'      => '',
				'port'          => 110,
				'active'        => 0,
				'full_login'    => 1,
				'use_retr'      => 0,
				'scan_interval' => 5,
				'last_scanned'  => 0,
			);
		} else {
			$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts`';
			$SQLquery .= ' WHERE (`account` = "'.mysql_escape_string(IsAdminUser() ? $_REQUEST['account'] : $_COOKIE['phPOP3cleanUSER']).'")';
			$result = mysql_query_safe($SQLquery);
			$row = mysql_fetch_assoc($result);
		}

		echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post">';
		echo '<table border="0">';
		if (IsAdminUser()) {
			echo '<tr><td>Email:</td><td><input type="text" name="account" value="'.htmlentities($row['account'], ENT_QUOTES).'" size="40"></td></tr>';
		} else {
			echo '<tr><td>Email:</td><td><b>'.htmlentities($row['account'], ENT_QUOTES).'</b></td></tr>';
		}
		echo '<tr><td>Password:</td><td><input type="text" name="password" value="'.htmlentities($row['password'], ENT_QUOTES).'" size="10"></td></tr>';
		echo '<tr><td>Hostname:</td><td><input type="text" name="hostname" value="'.htmlentities($row['hostname'], ENT_QUOTES).'" size="30"></td></tr>';
		echo '<tr><td>Port:</td><td><input type="text" name="port" value="'.htmlentities($row['port'], ENT_QUOTES).'" size="4"> (default: <b>110</b>)</td></tr>';

		$ActiveStates = array(0=>'disabled', 1=>'active');
		echo '<tr><td>Status:</td><td><select name="active">';
		echo '<option value="0"'.(($row['active'] == '0') ? ' selected' : '').' style="color: red;">disabled</option>';
		echo '<option value="1"'.(($row['active'] == '1') ? ' selected' : '').' style="color: green;">active</option>';
		echo '</select></td></tr>';

		list($user, $domain) = explode('@', $row['account']);
		$LoginStates = array(0=>$user, 1=>$row['account']);
		echo '<tr><td>Login:</td><td><select name="full_login">';
		foreach ($LoginStates as $key => $value) {
			echo '<option value="'.$key.'"';
			if ($row['full_login'] == $key) {
				echo ' selected';
			}
			echo '>'.$value.'</option>';
		}
		echo '</select></td></tr>';

		echo '<tr><td>Use:</td><td><select name="use_retr">';
		$UseRETR = array(0=>'TOP x 99999', 1=>'RETR x');
		foreach ($UseRETR as $key => $value) {
			echo '<option value="'.$key.'"';
			if ($row['use_retr'] == $key) {
				echo ' selected';
			}
			echo '>'.$value.'</option>';
		}
		echo '</select> to retrieve messages (compatability setting)</td></tr>';

		echo '<tr><td>Scan Interval:</td><td>';
		//echo '<select name="scan_interval">';
		//for ($i = 1; $i <= 180; $i++) {
		//	echo '<option value="'.$i.'"';
		//	if ($row['scan_interval'] == $i) {
		//		echo ' selected';
		//	}
		//	echo '>'.$i.'</option>';
		//}
		//echo '</select>';
		echo '<input type="text" name="scan_interval" size="2" value="'.htmlentities($row['scan_interval'], ENT_QUOTES).'">';
		echo ' minutes</td></tr>';
		echo '<tr><td>Last scanned:</td><td>'.htmlentities(date('M j Y, g:i:sa', $row['last_scanned']), ENT_QUOTES).'</td></tr>';

		echo '</table>';
		echo '<input type="hidden" name="oldaccount" value="'.htmlentities($row['account'], ENT_QUOTES).'">';
		echo '<input type="hidden" name="useradmin" value="update">';
		echo '<input type="submit" value="save">';
		echo '</form>';


	} elseif (@$_POST['useradmin'] == 'update') {

		if (IsAdminUser() && ($_POST['oldaccount'] == 'user@example.com')) {
			$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` (`account`, `password`, `hostname`, `port`, `active`, `full_login`, `use_retr`, `scan_interval`) VALUES (';
			$SQLquery .= '"'.mysql_escape_string($_POST['account']).'"';
			$SQLquery .= ', "'.mysql_escape_string($_POST['password']).'"';
			$SQLquery .= ', "'.mysql_escape_string($_POST['hostname']).'"';
			$SQLquery .= ', "'.mysql_escape_string($_POST['port']).'"';
			$SQLquery .= ', "'.mysql_escape_string($_POST['active']).'"';
			$SQLquery .= ', "'.mysql_escape_string($_POST['full_login']).'"';
			$SQLquery .= ', "'.mysql_escape_string($_POST['use_retr']).'"';
			$SQLquery .= ', "'.mysql_escape_string(max(1, intval($_POST['scan_interval']))).'")';
		} else {
			$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` SET';
			$SQLquery .= ' `password` = "'.mysql_escape_string($_POST['password']).'"';
			if (IsAdminUser()) {
				$SQLquery .= ', `account` = "'.mysql_escape_string($_POST['account']).'"';
			}
			$SQLquery .= ', `hostname` = "'.mysql_escape_string($_POST['hostname']).'"';
			$SQLquery .= ', `port` = "'.mysql_escape_string($_POST['port']).'"';
			$SQLquery .= ', `active` = "'.mysql_escape_string($_POST['active']).'"';
			$SQLquery .= ', `full_login` = "'.mysql_escape_string($_POST['full_login']).'"';
			$SQLquery .= ', `use_retr` = "'.mysql_escape_string($_POST['use_retr']).'"';
			$SQLquery .= ', `scan_interval` = "'.mysql_escape_string(max(1, intval($_POST['scan_interval']))).'"';
			$SQLquery .= ' WHERE (`account` = "'.mysql_escape_string(IsAdminUser() ? $_POST['oldaccount'] : $_COOKIE['phPOP3cleanUSER']).'")';
		}
		mysql_query_safe($SQLquery);
		$redirect = PHPOP3CLEAN_ADMINPAGE.'?useradmin='.__LINE__.'&orderby='.@$_POST['orderby'];
		echo 'Record updated<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
		echo '<script type="text/javascript">location = "'.$redirect.'";</script>';

	} elseif (IsAdminUser() && ($_REQUEST['useradmin'] == 'delete')) {

		$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts`';
		$SQLquery .= ' WHERE (`account` = "'.mysql_escape_string($_REQUEST['account']).'")';
		mysql_query_safe($SQLquery);
		$redirect = PHPOP3CLEAN_ADMINPAGE.'?useradmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
		echo 'Record deleted<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
		echo '<script type="text/javascript">location = "'.$redirect.'";</script>';

	} else {

		$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts`';
		if (!IsAdminUser()) {
			$SQLquery .= ' WHERE (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
		}
		$SQLquery .= ' ORDER BY (`active` = "1") DESC';
		$SQLquery .= ', `account` ASC';
		$result = mysql_query_safe($SQLquery);
		echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?useradmin=edit&account=new'), ENT_QUOTES).'">Create new</a><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>&nbsp;</th><th>Account</th><th>Password</th><th>Full Login</th><th>RETR / TOP</th><th>Interval</th><th>Status</th><th>Last Scan</th>'.(IsAdminUser() ? '<th>&nbsp;</th>' : '').'</tr>';
		while ($row = mysql_fetch_assoc($result)) {
			echo '<tr>';
			echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?useradmin=edit&account='.$row['account']), ENT_QUOTES).'">edit</a></td>';
			echo '<td>'.htmlentities($row['account'], ENT_QUOTES).'</td>';
			echo '<td>'.htmlentities($row['password'], ENT_QUOTES).'</td>';
			echo '<td '.($row['full_login'] ? 'align="left">full' : 'align="right">simple').'</td>';
			echo '<td>'.($row['use_retr'] ? 'RETR x' : 'TOP x 99999').'</td>';
			echo '<td>'.$row['scan_interval'].'</td>';
			if ($row['active'] == 1) {
				echo '<td bgcolor="#00FF00">active</td>';
			} else {
				echo '<td bgcolor="#FF0000">disabled</td>';
			}
			echo '<td><a href="#" title="'.FormatTimeInterval(time() - $row['last_scanned']).' ago" style="text-decoration: none; cursor: help; border-bottom: 1px dashed green;">'.htmlentities(date('M j Y g:i:sa', $row['last_scanned']), ENT_QUOTES).'</a></td>';
			if (IsAdminUser()) {
				echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?useradmin=delete&account='.$row['account']), ENT_QUOTES).'" onClick="return confirm(\'Are you SURE you want to delete this account?\');">delete</a></td>';
			}
			echo '</tr>';
		}
		echo '</table>';

	}

} elseif (IsAdminUser() && isset($_REQUEST['imgadmin'])) {

	if (@$_REQUEST['imgadmin'] == 'delete') {

		$SQLquery  = 'SELECT `md5`, `ext` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
		$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($_REQUEST['md5']).'")';
		$result = mysql_query_safe($SQLquery);
		if ($row = mysql_fetch_assoc($result)) {

			if ($_REQUEST['md5']) {
				$AllMatchingFiles = glob(PHPOP3CLEAN_MD5_IMAGE_CACHE.$_REQUEST['md5'].'*');
				foreach ($AllMatchingFiles as $matchingFilename) {
					echo 'Deleting: "'.basename($matchingFilename).'"<br>';
					@unlink($matchingFilename);
				}
			}

			$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
			$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($_REQUEST['md5']).'")';
			mysql_query_safe($SQLquery);

		}
		$redirect = PHPOP3CLEAN_ADMINPAGE.'?imgadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'].'&orderorder='.@$_GET['orderorder'].'&offset='.@$_GET['offset'];
		echo 'Record deleted<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
		echo '<script type="text/javascript">location = "'.$redirect.'";</script>';

	} elseif (@$_POST['imgadmin'] == 'upload') {

		if (is_uploaded_file($_FILES['uploaded_image']['tmp_name'])) {

			ob_start();
			readfile($_FILES['uploaded_image']['tmp_name']);
			$image_data = ob_get_contents();
			ob_end_clean();

			$ThisIsBad = false;
			$WhyItsBad = '';
			BannedImageAttachmentDatabaseCheckSave($_FILES['uploaded_image']['name'], $image_data, $ThisIsBad, $WhyItsBad, $_FILES['uploaded_image']['tmp_name']);
			if ($ThisIsBad) {

				echo $WhyItsBad.'<hr>';

			} else {

				$GIS = @GetImageSize($_FILES['uploaded_image']['tmp_name']);
				$GIStypes = array(1=>'gif', 2=>'jpeg', 3=>'png', 4=>'swf', 5=>'psd', 6=>'bmp', 7=>'tiff', 8=>'tiff', 9=>'jpc', 10=>'jp2', 11=>'jpx', 12=>'jb2', 13=>'swc', 14=>'iff', 15=>'wbmp', 16=>'xbm');
				$image_x   = @$GIS[0];
				$image_y   = @$GIS[1];
				$image_ext = @$GIStypes[@$GIS[2]];
				//$image_md5 = md5($image_data);
				$thisFilesize = strlen($image_data);
				$pattern = AttachedImageDefaultPattern($thisFilesize, $image_ext);
				$calculatedMD5 = FilteredBinaryDataMD5($image_data, $pattern);

				$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'image` (`md5`, `image_data`, `ext`, `width`, `height`, `size`, `pattern`, `added`) VALUES (';
				$SQLquery .= '"'.mysql_escape_string($calculatedMD5).'",';
				$SQLquery .= '"'.mysql_escape_string($image_data).'", ';
				$SQLquery .= '"'.mysql_escape_string($image_ext).'", ';
				$SQLquery .= '"'.mysql_escape_string($image_x).'", ';
				$SQLquery .= '"'.mysql_escape_string($image_y).'", ';
				$SQLquery .= '"'.mysql_escape_string($thisFilesize).'", ';
				$SQLquery .= '"'.mysql_escape_string($pattern).'", ';
				$SQLquery .= '"'.mysql_escape_string(time()).'")';
				mysql_query_safe($SQLquery);
				//$mysql_error = mysql_error();

				$newfilename = PHPOP3CLEAN_MD5_IMAGE_CACHE.$calculatedMD5.'.'.$image_ext;
				if (!move_uploaded_file($_FILES['uploaded_image']['tmp_name'], $newfilename)) {
					echo 'ERROR: failed to move "'.$_FILES['uploaded_image']['tmp_name'].'" to "'.$newfilename.'"';
				//} elseif (eregi('^Duplicate entry', $mysql_error)) {
				//	// shouldn't happen
				//	echo 'ERROR: Image already in database';
				//} elseif ($mysql_error) {
				//	echo $SQLquery.'<hr>'.$mysql_error.'<hr>';
				} else {
					$redirect = PHPOP3CLEAN_ADMINPAGE.'?imgadmin=edit&md5='.$calculatedMD5.'&orderby=added&orderorder='.@$_POST['orderorder'].'&offset='.@$_POST['offset'];
					echo 'Record inserted<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
					echo '<script type="text/javascript">location = "'.$redirect.'";</script>';
				}

			}

		} else {
			echo 'ERROR: Failed to upload file.';
		}

	} elseif (@$_POST['imgadmin'] == 'update') {

		if ($_POST['md5'] != $_POST['oldmd5']) {
			$SQLquery = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
			$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($_POST['md5']).'")';
			mysql_query_safe($SQLquery);
		}

		$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'image` SET';
		$SQLquery .= ' `description` = "'.mysql_escape_string($_POST['description']).'",';
		$SQLquery .= ' `pattern` = "'.mysql_escape_string($_POST['pattern']).'",';
		$SQLquery .= ' `ext` = "'.mysql_escape_string($_POST['ext']).'",';
		$SQLquery .= ' `md5` = "'.mysql_escape_string($_POST['md5']).'",';
		$SQLquery .= ' `size` = LENGTH(`image_data`)';
		$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($_POST['oldmd5']).'")';
		mysql_query_safe($SQLquery);

		$oldname = PHPOP3CLEAN_MD5_IMAGE_CACHE.$_POST['oldmd5'].'.'.$_POST['ext'];
		$newname = PHPOP3CLEAN_MD5_IMAGE_CACHE.$_POST['md5'].'.'.$_POST['ext'];
		if ($newname != $oldname) {
			if (file_exists($newname) && !@unlink($newname)) {
				die('failed to delete existing "'.$newname.'"');
			}
			if (!rename($oldname, $newname)) {
				die('failed to rename "'.$oldname.'" to "'.$newname.'"');
			}
		}
		$redirect = PHPOP3CLEAN_ADMINPAGE.'?imgadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'].'&orderorder='.@$_GET['orderorder'].'&offset='.@$_GET['offset'];
		echo 'Record updated<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
		echo '<script type="text/javascript">location = "'.$redirect.'";</script>';

	} elseif (@$_GET['imgadmin'] == 'scan') {

		$GIStypes = array(1=>'gif', 2=>'jpeg', 3=>'png', 4=>'swf', 5=>'psd', 6=>'bmp', 7=>'tiff', 8=>'tiff', 9=>'jpc', 10=>'jp2', 11=>'jpx', 12=>'jb2', 13=>'swc', 14=>'iff', 15=>'wbmp', 16=>'xbm');

		$SQLquery  = 'SELECT `md5`, `ext`, `image_data` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
		$result = mysql_query_safe($SQLquery);
		$KnownMD5 = array();
		while ($row = mysql_fetch_assoc($result)) {
			$KnownMD5[$row['md5']] = true;
			$KnownMD5[md5($row['image_data'])] = true;
			$filename = PHPOP3CLEAN_MD5_IMAGE_CACHE.$row['md5'].'.'.$row['ext'];
			if (!file_exists($filename)) {
				if ($fp = @fopen($filename, 'wb')) {
					fwrite($fp, $row['image_data']);
					fclose($fp);
					echo '% creating '.basename($filename).' from database image data<br>';
					flush();
				}
			}
		}
		if ($dh = opendir(PHPOP3CLEAN_MD5_IMAGE_CACHE)) {
			while ($file = readdir($dh)) {
				$filename = PHPOP3CLEAN_MD5_IMAGE_CACHE.$file;
				if (is_file($filename)) {
					set_time_limit(PHPOP3CLEAN_PHP_TIMEOUT);
					$thisMD5 = md5_file($filename);
					$filedata = file_get_contents($filename);
					$thisFilesize = filesize($filename);
					$GIS = @GetImageSize($filename);
					$image_x   = @$GIS[0];
					$image_y   = @$GIS[1];
					$image_ext = @$GIStypes[@$GIS[2]];
					$pattern = AttachedImageDefaultPattern($thisFilesize, $image_ext);
					$calculatedMD5 = FilteredBinaryDataMD5($filedata, $pattern);
					if (!@$KnownMD5[$thisMD5] && !@$KnownMD5[$calculatedMD5]) {
						$newfilename = dirname($filename).'/'.$calculatedMD5.'.'.$image_ext;
						if (rename($filename, $newfilename)) {

							$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'image` (`md5`, `size`, `ext`, `pattern`, `width`, `height`, `image_data`, `added`) VALUES (';
							$SQLquery .= '"'.mysql_escape_string($calculatedMD5).'", ';
							$SQLquery .= '"'.mysql_escape_string($thisFilesize).'", ';
							$SQLquery .= '"'.mysql_escape_string($image_ext).'", ';
							$SQLquery .= '"'.mysql_escape_string($pattern).'", ';
							$SQLquery .= '"'.mysql_escape_string($image_x).'", ';
							$SQLquery .= '"'.mysql_escape_string($image_y).'", ';
							$SQLquery .= '"'.mysql_escape_string($filedata).'", ';
							$SQLquery .= '"'.mysql_escape_string(time()).'")';
							mysql_query_safe($SQLquery);

							echo '* Adding '.basename($newfilename).' ['.$file.']<br>';
						} else {
							echo '! Cannot rename('.$filename.', '.dirname($filename).'/'.$thisMD5.'.'.$image_ext.')<br>';
						}
						flush();
					}
				}
			}
		}
		echo '<hr>';
		flush();

		$SQLquery  = 'SELECT `md5`, `ext` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
		$SQLquery .= ' WHERE (`height` = 0)';
		$SQLquery .= ' OR (`width` = 0)';
		$SQLquery .= ' OR (`ext` = "")';
		$result = mysql_query_safe($SQLquery);
		while ($row = mysql_fetch_assoc($result)) {
			$filename = PHPOP3CLEAN_MD5_IMAGE_CACHE.$row['md5'].'.'.$row['ext'];
			if (is_file($filename)) {
				set_time_limit(PHPOP3CLEAN_PHP_TIMEOUT);
				$GIS = @GetImageSize($filename);
				$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'image` SET';
				$SQLquery .= ' `width` = "'.mysql_escape_string($GIS[0]).'"';
				$SQLquery .= ', `height` = "'.mysql_escape_string($GIS[1]).'"';
				$SQLquery .= ', `ext` = "'.mysql_escape_string(@$GIStypes[@$GIS[2]]).'"';
				$SQLquery .= ' WHERE (`md5` = "'.$row['md5'].'")';
				mysql_query_safe($SQLquery);

				echo '* Updating dimensions and/or extension on '.basename($filename).'<br>';
				flush();

				if ($row['ext'] != @$GIStypes[@$GIS[2]]) {
					$newname = dirname($filename).'/'.$row['md5'].'.'.@$GIStypes[@$GIS[2]];
					rename($filename, $newname);

					echo '* * renaming: '.basename($filename).' to '.basename($newname).'<br>';
					flush();
				}
			}
		}

		echo '<hr><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?imgadmin='.__LINE__.'&orderby=added'), ENT_QUOTES).'">Continue</a><br>';

	} elseif (@$_GET['imgadmin'] == 'edit') {

		$SQLquery  = 'SELECT `image_data`, `ext`, `size`, `md5`, `pattern`, `description`, `width`, `height`, (`size` + (`width` * `height`)) AS `BytesWidthHeight` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
		$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($_REQUEST['md5']).'")';
		$result = mysql_query_safe($SQLquery);
		if ($row = mysql_fetch_assoc($result)) {
			echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post" name="attachedimageeditform">';
			echo '<table border="0">';

			echo '<tr><th align="right">MD5:</th><td>';
			if ($row['image_data']) {
				$calculatedMD5 = FilteredBinaryDataMD5($row['image_data'], $row['pattern']);
				echo (($calculatedMD5 != $row['md5']) ? '<span style="background-color: orangered; padding: 3px;">Should be <a href="#" onClick="document.attachedimageeditform.md5.value = \''.$calculatedMD5.'\'; return false;" style="background-color: limegreen;" title="Make it so">'.$calculatedMD5.'</a> according to stored data+pattern</span><br>' : '');
			}
			echo '<input type="text" name="md5" value="'.htmlentities($row['md5'], ENT_QUOTES).'" size="34" maxlength="32" style="font-family: monospace; font-size: 8pt;">';
			echo '</td></tr>';

			if (!$row['pattern']) {
				$row['pattern'] = AttachedImageDefaultPattern($row['size'], $row['ext']);
			}
			echo '<tr><th align="right">Partial Match Pattern:</th><td><input type="text" size="40" name="pattern" value="'.htmlentities($row['pattern'], ENT_QUOTES).'" maxlength="255"><br><i>ex: 17440|144-146;204-205;480-481;488-489</i></td></tr>';

			echo '<tr><th align="right">Description:</th><td><input type="text" size="40" name="description" value="'.htmlentities($row['description'], ENT_QUOTES).'"></td></tr>';
			echo '<tr><th align="right">Image Type:</th><td><input type="text" size="4" maxlength="4" name="ext" value="'.htmlentities($row['ext'], ENT_QUOTES).'" readonly></td></tr>';
			echo '<tr><th align="right">Dimensions:</th><td><input type="text" size="3" readonly value="'.htmlentities($row['width'], ENT_QUOTES).'"> x <input type="text" size="3" value="'.htmlentities($row['height'], ENT_QUOTES).'" readonly></td></tr>';
			echo '<tr><th align="right">BytesWidthHeight:</th><td><input type="text" size="8" value="'.htmlentities($row['BytesWidthHeight'], ENT_QUOTES).'" readonly></td></tr>';
			echo '<tr><td colspan="2" align="center"><input type="submit" value="Save"></td></tr>';
			echo '</table>';
			echo '<input type="hidden" name="orderby" value="'.htmlentities(@$_REQUEST['orderby'], ENT_QUOTES).'">';
			echo '<input type="hidden" name="imgadmin" value="update">';
			echo '<input type="hidden" name="oldmd5" value="'.htmlentities($row['md5'], ENT_QUOTES).'">';
			echo '</form>';
			echo '<hr>'.MD5imageSRC($row['md5'], $row['ext'], true, 3).'<hr>';
		} else {
			echo '<b>Error:</b> cannot find record matching MD5 hash "<tt>'.htmlentities($_REQUEST['md5'], ENT_QUOTES).'</tt>"<br>';
		}

	} else {

		$SQLquery = 'SELECT COUNT(*) as `totalRecords`';
		$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
		$SQLquery .= ' ORDER BY `'.mysql_escape_string(@$_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'lasthit').'` '.mysql_escape_string((@$_GET['orderorder'] == 'ASC') ? 'ASC' : 'DESC');
		$result = mysql_query_safe($SQLquery);
		$row = mysql_fetch_assoc($result);
		$totalRecords = $row['totalRecords'];
		$imgPerPage = 50;

		echo '<b>Banned image attachments</b><br><br>';
		echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?imgadmin=scan'), ENT_QUOTES).'">Scan cache directory for images not in database</a><br>';
		echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post" enctype="multipart/form-data">Upload new image: <input type="hidden" name="imgadmin" value="upload"><input type="hidden" name="orderby" value="'.htmlentities(@$_REQUEST['orderby'], ENT_QUOTES).'"><input type="file" name="uploaded_image"><input type="submit" value="Upload"></form>';

		$linkPages = array();
		for ($i = 0; $i < ceil($totalRecords / $imgPerPage); $i++) {
			if ($i == intval(@$_GET['offset'])) {
				$linkPages[] = '<b>'.($i + 1).'</b>';
			} else {
				$linkPages[] = '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?imgadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'].'&orderorder='.@$_GET['orderorder'].'&offset='.$i), ENT_QUOTES).'">'.htmlentities(($i + 1), ENT_QUOTES).'</a>';
			}
		}
		echo 'Page: '.implode(' | ', $linkPages).' ['.$totalRecords.' images]<br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';

		$fields = array('hitcount', 'lasthit', 'added', 'md5', 'pattern', 'ext', 'description', 'size', 'width', 'height', 'BytesWidthHeight');
		$invAscDesc = array(''=>'ASC', 'ASC'=>'DESC', 'ASC'=>'');
		echo '<tr><th colspan="3">&nbsp;</th>';
		foreach ($fields as $field) {
			echo '<th nowrap><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?imgadmin='.__LINE__.'&orderby='.$field.((@$_REQUEST['orderby'] == $field) ? '&orderorder='.@$invAscDesc[@$_GET['orderorder']] : '')), ENT_QUOTES).'">'.htmlentities($field, ENT_QUOTES).'</a> <span style="font-size: 18pt;">'.UpDownSymbol($field).'</span></th>';
		}
		echo '<th>&nbsp;</th></tr>';

		$rowcounter = 0;
		$SQLquery  = 'SELECT `image_data`, `md5`, `pattern`, `ext`, `size`, `width`, `height`, `description`, `hitcount`, `lasthit`, `added`, (`size` + (`width` * `height`)) AS `BytesWidthHeight`';
		$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
		$SQLquery .= ' ORDER BY `'.(@$_REQUEST['orderby'] ? mysql_escape_string($_REQUEST['orderby']) : 'description').'` '.(@$_GET['orderorder'] ? mysql_escape_string($_REQUEST['orderorder']) : 'DESC');
		$SQLquery .= ' LIMIT '.(intval(@$_GET['offset']) * $imgPerPage).','.$imgPerPage;
		$result = mysql_query_safe($SQLquery);
		while ($row = mysql_fetch_assoc($result)) {
			echo "\n".'<tr bgcolor="#'.(($rowcounter % 2) ? 'DDDDDD' : 'EEEEEE').'">';

			echo "\n\t".'<td align="center">'.((strlen($row['image_data']) > 0) ? MD5imageSRC($row['md5'], $row['ext'], true, 1, 120, $row['width'], $row['height']) : 'n/a').'</td>';

			echo "\n\t".'<td>'.((strlen($row['image_data']) > 0) ? '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?imgadmin=file&md5='.$row['md5']), ENT_QUOTES).'">download</a>' : 'download').'</td>';
			echo "\n\t".'<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?imgadmin=edit&md5='.$row['md5'].'&orderorder='.@$_GET['orderorder'].'offset='.@$_GET['offset']), ENT_QUOTES).'">edit</a></td>';
			echo "\n\t".'<td align="right">'.$row['hitcount'].'</td>';
			if ($row['lasthit']) {
				echo "\n\t".'<td align="right" bgcolor="#'.LastHit2bgcolor($row['lasthit']).'">'.date('M-d-Y', $row['lasthit']).'</td>';
				echo "\n\t".'<td align="right">'.($row['added'] ? date('M-d-Y', $row['added']) : '-').'</td>';
			} else {
				echo "\n\t".'<td align="center">-</td>';
				echo "\n\t".'<td align="right" bgcolor="#'.LastHit2bgcolor($row['added']).'">'.date('M-d-Y', $row['added']).'</td>';
			}

			$calculatedMD5 = FilteredBinaryDataMD5($row['image_data'], $row['pattern']);
			if ($row['image_data'] && ($calculatedMD5 != $row['md5'])) {
				echo "\n\t".'<td><tt style="background-color: #FF0000;">'.$row['md5'].'</tt><br><tt style="background-color: #00CC00;">'.$calculatedMD5.'</tt></td>';
			} else {
				echo "\n\t".'<td><tt>'.$row['md5'].'</tt></td>';
			}

			echo "\n\t".'<td>'.($row['pattern'] ? htmlentities($row['pattern'], ENT_QUOTES) : '&nbsp;').'</td>';
			echo "\n\t".'<td align="right">'.htmlentities($row['ext'], ENT_QUOTES).'</td>';
			echo "\n\t".'<td align="right">'.(($row['description'] !== '') ? htmlentities($row['description'], ENT_QUOTES) : '&nbsp;').'</td>';
			echo "\n\t".'<td align="right">'.number_format($row['size']).'</td>';
			echo "\n\t".'<td align="right">'.htmlentities($row['width'], ENT_QUOTES).'</td>';
			echo "\n\t".'<td align="right">'.htmlentities($row['height'], ENT_QUOTES).'</td>';
			echo "\n\t".'<td align="right">'.number_format($row['BytesWidthHeight']).'</td>';
			echo "\n\t".'<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?imgadmin=delete&md5='.$row['md5'].'&orderorder='.@$_GET['orderorder'].'&offset='.@$_GET['offset']), ENT_QUOTES).'" onClick="return confirm(\'Are you SURE you want to delete this image hash?\');">delete</a></td>';
			echo "\n".'</tr>';
			$rowcounter++;
		}
		echo '</table><br>';
		echo 'Page: '.implode(' | ', $linkPages).' ['.$totalRecords.' images]<br>';

	}

} elseif (IsAdminUser() && @$_REQUEST['exeadmin']) {

	if (@$_GET['exeadmin'] == 'delete') {

		$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'exe`';
		$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($_REQUEST['md5']).'")';
		mysql_query_safe($SQLquery);
		$redirect = PHPOP3CLEAN_ADMINPAGE.'?exeadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
		echo 'Record deleted<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
		echo '<script type="text/javascript">location = "'.$redirect.'";</script>';

	} elseif (@$_POST['exeadmin'] == 'update') {

		$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'exe` SET';
		$SQLquery .= ' `virus_name` = "'.mysql_escape_string($_POST['virus_name']).'",';
		$SQLquery .= ' `pattern` = "'.mysql_escape_string($_POST['pattern']).'",';
		if (eregi('^([0-9]+)\|', $_POST['pattern'], $matches)) {
			$SQLquery .= ' `filesize` = "'.mysql_escape_string($matches[1]).'",';
		}
		$SQLquery .= ' `md5` = "'.mysql_escape_string($_POST['md5']).'"';
		$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($_POST['oldmd5']).'")';
		mysql_query_safe($SQLquery);
		$redirect = PHPOP3CLEAN_ADMINPAGE.'?exeadmin='.__LINE__.'&orderby='.@$_REQUEST['orderby'];
		echo 'Record updated<br><a href="'.htmlentities(linkencode($redirect), ENT_QUOTES).'">continue</a>';
		echo '<script type="text/javascript">location = "'.$redirect.'";</script>';

	} elseif (@$_GET['exeadmin'] == 'edit') {

		$SQLquery  = 'SELECT `filesize`, `virus_data`, `md5`, `pattern`, `virus_name` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'exe`';
		$SQLquery .= ' WHERE (`md5` = "'.mysql_escape_string($_GET['md5']).'")';
		$result = mysql_query_safe($SQLquery);
		if ($row = mysql_fetch_assoc($result)) {
			echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post" name="infectedattachmenteditform">';
			echo '<table border="0">';
			echo '<tr><td colspan="2" align="center"><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?exeadmin=file&md5='.$row['md5']), ENT_QUOTES).'"><b>download virus sample</b></a><br><span style="color: red;">(WARNING! - very likely infected! :)</span><br><br></td></tr>';

			$calculatedMD5 = FilteredBinaryDataMD5($row['virus_data'], $row['pattern']);
			echo '<tr><th align="right">MD5:</th><td>';
			if ((strlen($row['virus_data']) > 0) && ($calculatedMD5 != $row['md5'])) {
				echo '<span style="background-color: orangered; padding: 3px;">Should be <a href="#" onClick="document.infectedattachmenteditform.md5.value = \''.$calculatedMD5.'\'; return false;" style="background-color: limegreen;" title="Make it so">'.$calculatedMD5.'</a> according to stored data+pattern</span><br>';
			}
			echo '<input type="text" name="md5" value="'.htmlentities($row['md5'], ENT_QUOTES).'" size="34" maxlength="32" style="font-family: monospace; font-size: 8pt;"></td></tr>';
			echo '<tr><th align="right">Virus Name:</th><td><input type="text" size="40" name="virus_name" value="'.htmlentities($row['virus_name'], ENT_QUOTES).'"></td></tr>';

			if (!$row['pattern']) {
				$row['pattern'] = $row['filesize'].'|';
			}
			echo '<tr><th align="right">Partial Match Pattern:</th><td><input type="text" size="40" name="pattern" value="'.htmlentities($row['pattern'], ENT_QUOTES).'" maxlength="255"><br><i>ex: 17440|144-146;204-205;480-481;488-489</i></td></tr>';

			echo '<tr><td colspan="2" align="center"><input type="submit" value="Save"></td></tr>';
			echo '<input type="hidden" name="exeadmin" value="update">';
			echo '<input type="hidden" name="oldmd5" value="'.htmlentities($row['md5'], ENT_QUOTES).'">';
			echo '</table>';
			echo '</form>';
		} else {
			echo '<b>Error:</b> cannot find record matching MD5 hash "<tt>'.htmlentities($_GET['md5'], ENT_QUOTES).'</tt>"<br>';
		}

	} else {

		$SQLquery  = 'SELECT `virus_data`, `md5`, `pattern`, `virus_name`, `hitcount`, `lasthit`, `added` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'exe`';
		$SQLquery .= ' ORDER BY `'.mysql_escape_string(@$_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'lasthit').'` '.mysql_escape_string((@$_GET['orderorder'] == 'ASC') ? 'ASC' : 'DESC');
		$result = mysql_query_safe($SQLquery);
		echo '<b>Banned EXE attachments</b><br><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';

		$fields = array('hitcount', 'lasthit', 'added', 'md5', 'pattern', 'virus_name');
		$invAscDesc = array(''=>'ASC', 'ASC'=>'DESC', 'ASC'=>'');
		echo '<tr><th colspan="2">&nbsp;</th>';
		foreach ($fields as $field) {
			echo '<th nowrap><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?exeadmin='.$_GET['exeadmin'].'&orderby='.$field.((@$_REQUEST['orderby'] == $field) ? '&orderorder='.@$invAscDesc[@$_GET['orderorder']] : '')), ENT_QUOTES).'">'.htmlentities($field, ENT_QUOTES).'</a> <span style="font-size: 18pt;">'.UpDownSymbol($field).'</span></th>';
		}
		echo '<th>&nbsp;</th></tr>';

		$lastvirus = '';
		$rowcounter = 0;
		while ($row = mysql_fetch_assoc($result)) {
			if ($row['virus_name'] != $lastvirus) {
				$rowcounter++;
			}
			$lastvirus = $row['virus_name'];
			echo '<tr bgcolor="#'.(($rowcounter % 2) ? 'DDDDDD' : 'EEEEEE').'">';
			echo '<td>'.((strlen($row['virus_data']) > 0) ? '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?exeadmin=file&md5='.$row['md5']), ENT_QUOTES).'">download</a>' : 'download').'</td>';
			echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?exeadmin=edit&md5='.$row['md5']), ENT_QUOTES).'">edit</a></td>';
			echo '<td align="right">'.$row['hitcount'].'</td>';
			if ($row['lasthit']) {
				echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['lasthit']).'" nowrap>'.date('M-d-Y', $row['lasthit']).'</td>';
			} else {
				echo '<td align="center">-</td>';
			}
			if ($row['added']) {
				echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['added']).'" nowrap>'.date('M-d-Y', $row['added']).'</td>';
			} else {
				echo '<td align="center">-</td>';
			}

			$calculatedMD5 = FilteredBinaryDataMD5($row['virus_data'], $row['pattern']);
			if ($row['virus_data'] && ($calculatedMD5 != $row['md5'])) {
				echo '<td><tt style="background-color: #FF0000;">'.$row['md5'].'</tt><br><tt style="background-color: #00CC00;">'.$calculatedMD5.'</tt></td>';
			} else {
				echo '<td><tt>'.$row['md5'].'</tt></td>';
			}

			$patterntext = ($row['pattern'] ? htmlentities(str_replace(';', '; ', $row['pattern']), ENT_QUOTES) : '&nbsp;');
			if (strlen($row['virus_data']) > 0) {
				$patterntext = '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?exeadmin=file&md5='.$row['md5'].'&filtered=1'), ENT_QUOTES).'">'.$patterntext.'</a>';
			}
			echo '<td>'.$patterntext.'</td><td align="right">';


			if (($row['virus_name'] === '') || eregi('^unknown', $row['virus_name'])) {
				echo '<i>unknown</i>';
			} elseif (eregi('^corrupt', $row['virus_name'])) {
				echo '<i>corrupt</i>';
			} elseif (eregi('^ok', $row['virus_name'])) {
				echo '<i>OK (not infected)</i>';
			} else {
				echo '<a href="'.htmlentities(linkencode('http://www.sarc.com/avcenter/venc/data/'.strtolower($row['virus_name']).'.html'), ENT_QUOTES).'" target="_blank" title="Show information about '.htmlentities($row['virus_name'], ENT_QUOTES).' from Symantec Antivirus Research Center (SARC)">'.htmlentities($row['virus_name'], ENT_QUOTES).'</a>';
			}
			echo '</td>';

			echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?exeadmin=delete&md5='.$row['md5']), ENT_QUOTES).'" onClick="return confirm(\'Are you SURE you want to delete this EXE hash?\');">delete</a></td>';
			echo '</tr>';
		}
		echo '</table>';

	}

} elseif (IsAdminUser() && @$_REQUEST['ipadmin']) {

	if (isset($_REQUEST['action'])) {

		switch ($_REQUEST['action']) {
			case 'update':
				$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'ips` SET';
				$SQLquery .= ' `ipmask` = "'.mysql_escape_string($_REQUEST['baseip'].'/'.$_REQUEST['maskbits']).'",';
				$SQLquery .= ' `domains` = "'.mysql_escape_string(strtr($_REQUEST['domains'], array("\n"=>';', "\n"=>''))).'",';
				$SQLquery .= ' `whitelist` = "'.mysql_escape_string(intval(@$_REQUEST['whitelist'])).'"';
				$SQLquery .= ' WHERE (`ipmask` = "'.mysql_escape_string($_REQUEST['oldipmask']).'")';
				break;

			case 'insert':
				$SQLquery  = 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'ips` (`ipmask`, `cmask`, `whitelist`, `added`, `domains`) VALUES (';
				$SQLquery .= '"'.mysql_escape_string($_REQUEST['baseip'].'/'.$_REQUEST['maskbits']).'", ';
				$SQLquery .= '"'.mysql_escape_string(CMask($_REQUEST['baseip'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(intval(@$_REQUEST['whitelist'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(time()).'", ';
				$SQLquery .= '"'.mysql_escape_string(strtr($_REQUEST['domains'], array("\n"=>';', "\n"=>''))).'")';
				break;

			case 'delete':
				$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ips`';
				$SQLquery .= ' WHERE (`ipmask` = "'.mysql_escape_string($_REQUEST['oldipmask']).'")';
				break;

			default:
				die('undefined action "'.$_REQUEST['action'].'"');
				break;
		}
		$result = mysql_query_safe($SQLquery);
		echo 'Success: '.$_REQUEST['action'].'.<br>';
		echo '<script type="text/javascript">if (window.opener) { '.($_REQUEST['action'] == 'delete' ? '' : 'window.opener.location.reload(); ').'window.close(); } else { location = "'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?ipadmin='.__LINE__), ENT_QUOTES).'"; }</script>';
		exit;

	} elseif (@$_REQUEST['bulkadd']) {

		echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?ipadmin='.__LINE__), ENT_QUOTES).'">List all blacklisted IPs</a><hr>';

		if (@$_REQUEST['domainlist']) {
			$DomainListRAW = array_unique(explode("\n", $_REQUEST['domainlist']));
			$DomainList = array();
			foreach ($DomainListRAW as $rawdomaintolookup) {
				if (!preg_match(preg_expression('^(ht|f)tps?\\:', 'i'), $rawdomaintolookup)) {
					$rawdomaintolookup = 'http://'.$rawdomaintolookup;
				}
				//$AllSubdomains = ExtractAllSubdomains(ExtractDomain(strtolower(trim($rawdomaintolookup))));
				$rawdomaintolookup = strtolower(trim($rawdomaintolookup));
				$AllSubdomains = ExtractDomainsFromText($rawdomaintolookup, $rawdomaintolookup, true);
				foreach ($AllSubdomains as $subdomaintolookup) {
					$DomainList[] = $subdomaintolookup;
				}
			}

			echo '<div id="currentlookupstatus"></div>';
			$DomainList = array_unique($DomainList);
			$DomainLookupSuccess = array();
			$DomainLookupFailed  = array();
			$lookedup = 0;
			echo '<div id="lookupstatus">';
			echo '<table border="0" cellspacing="0" cellpadding="3">';
			foreach ($DomainList as $domaintolookup) {
				$lookedup++;
				echo '<tr bgcolor="#'.(($lookedup % 2) ? 'CCCCCC' : 'EEEEEE').'">';
				echo '<td align="right" width="50">'.$lookedup.' / '.count($DomainList).'</td>';
				echo '<td align="right" width="300" style="color: blue;">'.htmlentities($domaintolookup, ENT_QUOTES).'</td>';
				echo '<td align="right" width="100" id="domlookup_'.$lookedup.'" style="background-color: yellow; font-style: italic;">waiting...</td>';
				echo '</tr>';
			}
			echo '</table>';
			echo '</div>';
			$lookedup = 0;
			foreach ($DomainList as $domaintolookup) {
				$lookedup++;
				echo '<script type="text/javascript">if (document.getElementById("currentlookupstatus")) document.getElementById("currentlookupstatus").innerHTML = "Looking up: ['.$lookedup.'/'.count($DomainList).'] <b>'.$domaintolookup.'<\\/b>";</script>';
				echo '<script type="text/javascript">if (document.getElementById("domlookup_'.$lookedup.'")) { document.getElementById("domlookup_'.$lookedup.'").innerHTML = "looking up..."; document.getElementById("domlookup_'.$lookedup.'").style.fontWeight = "bold"; }</script>';
				flush();

				if (@$_SESSION['domain_lookup_failed'][$domaintolookup]) {

					$DHTMLips = false;
					$DomainLookupFailed[] = $domaintolookup;

				} elseif (@$_SESSION['domain_lookup_success'][$domaintolookup]) {

					foreach ($_SESSION['domain_lookup_success'][$domaintolookup] as $ip) {
						$DomainLookupSuccess[$ip][] = $domaintolookup;
					}
					$DHTMLips = @implode('<br>', $_SESSION['domain_lookup_success'][$domaintolookup]);

				} else {

					set_time_limit(PHPOP3CLEAN_PHP_TIMEOUT);
					if (@$domaintolookup) {
						if ($IPs = SafeGetHostByNameL($domaintolookup)) {
							DomainResolvesToTooManyVariedIPs($domaintolookup, $IPs);
							BlackListedDomainIP(array($domaintolookup=>$IPs));

							$DHTMLips = @implode('<br>', $IPs);
							foreach ($IPs as $ip) {
								if (IsIP($ip)) {
									$DomainLookupSuccess[$ip][] = $domaintolookup;
									@$_SESSION['domain_lookup_success'][$domaintolookup][] = $ip;

									$DNSBL_IPs = DNSBLlookup($ip);
									if (is_array($DNSBL_IPs) && (count($DNSBL_IPs) > 0)) {
										BanIP($ip);
									}
								} else {
									$DomainLookupFailed[] = $domaintolookup;
								}
							}
						} else {
							$DHTMLips = false;
							$DomainLookupFailed[] = $domaintolookup;
							$_SESSION['domain_lookup_failed'][$domaintolookup] = true;
						}
					}

				}
				echo '<script type="text/javascript">if (document.getElementById("currentlookupstatus")) document.getElementById("currentlookupstatus").innerHTML = "";</script>';
				echo '<script type="text/javascript">if (document.getElementById("domlookup_'.$lookedup.'")) {';
				echo ' document.getElementById("domlookup_'.$lookedup.'").innerHTML = "'.(($DHTMLips === false) ? 'FAILED LOOKUP' : $DHTMLips).'"; ';
				echo ' document.getElementById("domlookup_'.$lookedup.'").style.backgroundColor = "'.(($DHTMLips === false) ? 'red' : 'limegreen').'";';
				echo ' document.getElementById("domlookup_'.$lookedup.'").style.fontStyle = "normal";';
				echo ' document.getElementById("domlookup_'.$lookedup.'").style.fontWeight = "normal";';
  				echo ' }</script>';
				flush();
			}
			echo '<script type="text/javascript">if (document.getElementById("lookupstatus")) document.getElementById("lookupstatus").innerHTML = "";</script>';
			flush();

			if (@$DomainLookupFailed) {
				$DomainLookupFailed = array_unique($DomainLookupFailed);
				echo '<div style="color: red;">These domains failed lookup:<ul><li>'.implode('</li><li>', $DomainLookupFailed).'</li></ul></div>';
			}

			$CMaskArray = array();
			//ksort($DomainLookupSuccess);
			uksort($DomainLookupSuccess, 'usort_IP');
			foreach ($DomainLookupSuccess as $ip => $arrayofdomains) {
				$cmask = CMask($ip);
				foreach ($arrayofdomains as $domain) {
					$CMaskArray[$cmask][] = str_replace($cmask.'.', '', $ip).'|'.$domain;
				}
			}

			if (!empty($CMaskArray)) {

				echo '<table border="1" cellspacing="0" cellpadding="3">';
				echo '<tr><th>IP</th><th>Domains</th><th>Status</th><th colspan="2">&nbsp;</th></tr>';
				foreach ($CMaskArray as $cmask => $arrayofdomains) {
					$ipLastMin = 255;
					$ipLastMax =   0;
					$domainIPnotesArray = array();
					$arrayofdomains = array_unique($arrayofdomains);
					foreach ($arrayofdomains as $domain) {
						list($ipLast, $domain) = explode('|', $domain);
						$domainIPnotesArray[intval($ipLast)][BaseDomain($domain)] = $domain;
						$ipLastMin = min($ipLastMin, $ipLast);
						$ipLastMax = max($ipLastMax, $ipLast);
					}

					$bestmatchmask = '';
					$alreadyindb = false;
					$whitelisted = false;
					$longIPmin = SafeIP2Long($cmask.'.'.$ipLastMin);
					$longIPmax = SafeIP2Long($cmask.'.'.$ipLastMax);

					$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ips`';
					$SQLquery .= ' WHERE (`ipmask` LIKE "'.mysql_escape_string($cmask).'.%")';
					$result = mysql_query_safe($SQLquery);
					while ($row = mysql_fetch_assoc($result)) {
						$bestmatchmask = $row['ipmask'];
						if ($row['whitelist']) {
							$whitelisted = true;
							break;
						}
						list($min, $max) = IPrangeMinMax($row['ipmask']);
						if (($longIPmin >= $min) && ($longIPmax <= $max)) {
							$alreadyindb = true;
							break;
						}
					}

					echo '<tr>';
					echo '<td><tt><a href="'.htmlentities(linkencode('http://www.domaintools.com/reverse-ip/?hostname='.$cmask.'.'.$ipLastMin), ENT_QUOTES).'" target="_blank">'.htmlentities(SpacedIP($cmask.'.'.(($ipLastMin == $ipLastMax) ? $ipLastMin : '*'), chr(160)), ENT_QUOTES).'</a></tt></td>';
					echo '<td>'.nl2br(htmlentities(implode("\n", $arrayofdomains), ENT_QUOTES)).'</td>';
					echo '<td width="100" bgcolor="#'.($whitelisted ? PHPOP3CLEAN_COL_WLIST : ($alreadyindb ? PHPOP3CLEAN_COL_OK : ($bestmatchmask ? 'FF9900' : PHPOP3CLEAN_COL_BLIST))).'">';
					if (@$bestmatchmask) {
						echo $bestmatchmask.'</td>';
						echo '<td><a href="#" onClick="window.open(\'?ipadmin='.__LINE__.'&amp;ipmask='.urlencode($bestmatchmask).'\', \'ipmaskpopup\', \'width=400,height=725,scrollbars=yes,resizable=yes,status=yes\'); return false;">edit '.($whitelisted ? 'whitelist' : 'blacklist').'</a></td>';
						echo '<td><a href="#" onClick="if (confirm(\'Are you sure you want to remove this '.($whitelisted ? 'whitelist' : 'blacklist').'?\')) window.open(\'?ipadmin='.__LINE__.'&amp;action=delete&amp;oldipmask='.urlencode($bestmatchmask).'\', \'ipmaskpopup\', \'width=400,height=725,scrollbars=yes,resizable=yes,status=yes\'); return false;">remove</a></td>';
					} else {
						echo '<i>Not in database</i></td>';
						$defaultdomainlistarray = array();
						//foreach ($arrayofdomains as $domain) {
							//$defaultdomainlistarray[] = $domain;
						ksort($domainIPnotesArray);
						foreach ($domainIPnotesArray as $ipLast => $basedomainarray) {
							foreach ($basedomainarray as $basedomain => $domain) {
								$defaultdomainlistarray[] = $ipLast.'|'.(IsIP($basedomain) ? '' : $basedomain);
							}
						}
						sort($defaultdomainlistarray);
						for ($i = 32; $i >= 24; $i--) {
							$defaultipmask = $cmask.'.'.$ipLastMin.'/'.$i;
							list($min, $max) = IPrangeMinMax($defaultipmask);
							if (($longIPmin >= $min) && ($longIPmax <= $max)) {
								break;
							}
						}
						echo '<td colspan="2"><a href="#" onClick="window.open(\'?ipadmin='.__LINE__.'&amp;ipmask=new&amp;defaultipmask='.urlencode($defaultipmask).'&amp;defaultdomains='.urlencode(implode(';', $defaultdomainlistarray)).'\', \'ipmaskpopup\', \'width=400,height=725,scrollbars=yes,resizable=yes,status=yes\'); return false;">create blacklist/whitelist</a></td>';
					}
					echo '</tr>';
				}
				echo '</table>';

			} else {

				echo 'No domains were successfully looked up<br>';

			}
		}

		echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" name="form1" method="post">';
		echo '<input type="hidden" name="bulkadd" value="1">';
		echo '<input type="hidden" name="ipadmin" value="1">';
		echo '<b>Add all IPs associated with these domains/IPs to the blacklist:</b><br>';
		echo 'Enter links (domain only, or including "http://" and/or path is OK) - one link per line:<br>';
		echo '<textarea name="domainlist" cols="60" rows="10" style="white-space: nowrap;">'.htmlentities(@$_POST['domainlist'], ENT_QUOTES).'</textarea><br>';
		echo '<input type="submit" value="Add">';
		echo '</form>';
		echo '<script type="text/javascript">document.form1.domainlist.focus();</script>';

	} elseif (@$_GET['ipmask']) {

		echo '<script type="text/javascript">'."\n";
		echo 'function IPrangeShow(ip, mask) {'."\n";
		echo '	var ABC = ip.substring(0, ip.lastIndexOf(".") + 1);'."\n";
		echo '	var D   = ip.substring(ip.lastIndexOf(".") + 1);'."\n";
		echo '	var Dlo = D & ((255 << (32 - mask)) & 255);'."\n";
		echo '	var Dhi = D | ((  1 << (32 - mask)) - 1);'."\n";
		echo '	document.getElementById("iprangediv").innerHTML = ABC + Dlo + " - " + ABC + Dhi;'."\n";
		echo '	return true;'."\n";
		echo '}'."\n";
		echo '</script>'."\n";

		echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" name="form1" method="post">';
		echo '<input type="hidden" name="ipadmin" value="'.htmlentities($_REQUEST['ipadmin'], ENT_QUOTES).'">';
		echo '<input type="hidden" name="oldipmask" value="'.htmlentities($_REQUEST['ipmask'], ENT_QUOTES).'">';

		$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ips`';
		$SQLquery .= ' WHERE (`ipmask` = "'.mysql_escape_string($_REQUEST['ipmask']).'")';
		$result = mysql_query_safe($SQLquery);
		if ($row = mysql_fetch_assoc($result)) {
			echo '<input type="hidden" name="action" value="update">';
		} else {
			echo '<input type="hidden" name="action" value="insert">';
			$row = array('ipmask'=>'/', 'domains'=>'');
		}

		if (($row['ipmask'] == '/') && @$_GET['defaultipmask']) {
			$row['ipmask'] = $_REQUEST['defaultipmask'];
		}
		if (($row['domains'] == '') && @$_GET['defaultdomains']) {
			$row['domains'] = $_REQUEST['defaultdomains'];
		}

		echo '<select name="whitelist"><option value="0"'.(@$_GET['whitelist'] ? '' : ' selected').'>blacklist</option><option value="1"'.(@$_GET['whitelist'] ? ' selected' : '').'>whitelist</option></select> ';

		list($ipmask, $maskbits) = explode('/', $row['ipmask']);
		echo '<b>Base IP</b> <input type="text" name="baseip" value="'.htmlentities($ipmask, ENT_QUOTES).'" onKeyUp="IPrangeShow(this.value, this.form.maskbits.options[this.form.maskbits.options.selectedIndex].value);"> ';
		echo '<select name="maskbits" onKeyUp="IPrangeShow(this.form.baseip.value, this.options[this.options.selectedIndex].value);" onChange="IPrangeShow(this.form.baseip.value, this.options[this.options.selectedIndex].value);">';
		for ($i = 32; $i >= 24; $i--) {
			echo '<option value="'.$i.'"'.(($maskbits == $i) ? ' SELECTED' : '').'>'.$i.'</option>';
		}
		echo '</select><br>';
		echo '<div id="iprangediv"></div><br>';
		echo '<b>Domain list</b> - One domain per line<br>e.g.: <i>123|example.com</i> == (example.com @ 1.2.3.123)<br>';
		$domainlist = explode(';', $row['domains']);
		foreach ($domainlist as $key => $value) {
			if (strlen($value) < 4) {
				$domainlist[$key] = '  '.$value;
			} elseif ($value{3} == '|') {
				// good
			} elseif ($value{2} == '|') {
				$domainlist[$key] = ' '.$value;
			} elseif ($value{1} == '|') {
				$domainlist[$key] = '  '.$value;
			}
		}
		sort($domainlist);
		foreach ($domainlist as $key => $value) {
			$domainlist[$key] = trim($value);
		}
		echo '<textarea rows="18" cols="40" name="domains">'.htmlentities(implode("\n", $domainlist), ENT_QUOTES).'</textarea><br>';
		echo '<input type="submit">';
		echo '</form>';
		echo '<script type="text/javascript">IPrangeShow(document.form1.baseip.value, document.form1.maskbits.options[document.form1.maskbits.options.selectedIndex].value);</script>';

	} else {

		$SQLquery  = 'SELECT *';
		$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ips`';
		$SQLquery .= ' ORDER BY `'.mysql_escape_string(@$_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'lasthit').'` '.mysql_escape_string((@$_GET['orderorder'] == 'ASC') ? 'ASC' : 'DESC');
		$result = mysql_query_safe($SQLquery);

		$fields = array('ipmask', 'domains', 'lasthit', 'added', 'hitcount');
		$invAscDesc = array(''=>'ASC', 'ASC'=>'DESC', 'ASC'=>'');
		echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?ipadmin='.__LINE__.'&bulkadd='.__LINE__), ENT_QUOTES).'">Bulk add domains to blacklist</a><br><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th></th>';
		foreach ($fields as $field) {
			echo '<th nowrap><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?ipadmin='.$_REQUEST['ipadmin'].'&orderby='.$field.((@$_REQUEST['orderby'] == $field) ? '&orderorder='.@$invAscDesc[@$_GET['orderorder']] : '')), ENT_QUOTES).'">'.htmlentities($field, ENT_QUOTES).'</a> <span style="font-size: 18pt;">'.UpDownSymbol($field).'</span></th>';
		}
		echo '</tr>';
		while ($row = mysql_fetch_assoc($result)) {
			echo '<tr>';
			echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?ipadmin='.__LINE__.'&ipmask='.$row['ipmask']), ENT_QUOTES).'">edit</a></td>';
			echo '<td>'.htmlentities($row['ipmask'], ENT_QUOTES).'</td>';
			echo '<td>'.nl2br(htmlentities(str_replace(';', "\n", $row['domains']), ENT_QUOTES)).'</td>';
			if ($row['lasthit']) {
				echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['lasthit']).'">'.date('M-d-Y', $row['lasthit']).'</td>';
				echo '<td align="right">'.($row['added'] ? date('M-d-Y', $row['added']) : '-').'</td>';
			} else {
				echo '<td align="center">-</td>';
				echo '<td align="right" bgcolor="#'.LastHit2bgcolor($row['added']).'">'.date('M-d-Y', $row['added']).'</td>';
			}
			echo '<td align="right">'.number_format($row['hitcount']).'</td>';
			echo '<td><a href="#" onClick="if (confirm(\'Are you SURE you want to delete this IP mask?\')) window.open(\'?ipadmin='.__LINE__.'&action=delete&amp;oldipmask='.rawurlencode($row['ipmask']).'\', \'ipmaskpopup\', \'width=400,height=725,scrollbars=yes,resizable=yes,status=yes\'); return false;">delete</a></td>';
			echo '</tr>';
		}
		echo '</table>';

	}

} elseif (@$_GET['unquarantine']) {

	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'messages`';
	$SQLquery .= ' WHERE (TRIM(`id`) = "'.mysql_escape_string(trim($_REQUEST['unquarantine'])).'")';
	if (!IsAdminUser()) {
		$SQLquery .= ' AND (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
	}
	$result = mysql_query_safe($SQLquery);

	if ($row = mysql_fetch_assoc($result)) {

		echo '<div style="background-color: #CCCCCC; padding: 10px; border: 1px 000000 solid;">';
		$MessageContentsFilename = PHPOP3CLEAN_QUARANTINE.date('Ym', $row['date']).'/'.$row['id'].'.gz';
		if ($zp = @gzopen($MessageContentsFilename, 'rb')) {
			$MessageContents = '';
			while ($buffer = gzread($zp, 4096)) {
				$MessageContents .= $buffer;
			}
			gzclose($zp);

			list($original_header, $original_body) = explode("\r\n\r\n", $MessageContents, 2);
			$ParsedHeader = POP3parseheader($original_header);

			$MessageSplitterGUID = '----=_phPOP3clean_unquarantine_'.time().'_'.md5($row['id']);

			$headers  = 'From: phPOP3clean Email Filter <'.PHPOP3CLEAN_ADMINEMAIL.'>'."\r\n";
			$headers .= 'Bcc: '.PHPOP3CLEAN_ADMINEMAIL."\r\n";
			$headers .= 'MIME-Version: 1.0'."\r\n";
			$headers .= 'Content-Type: multipart/mixed;'."\r\n\t".'boundary="'.$MessageSplitterGUID.'"'."\r\n";

			$clean_subject = eregi_replace('[^a-z0-9 _\\-\\.,;\\:]', '_', $ParsedHeader['subject'][0]);

			$body  = "This is a multi-part message in MIME format.\n\n--".$MessageSplitterGUID."\n";
			$body .= "Content-Type: text/plain;\n\tformat=flowed;\n\tcharset=\"iso-8859-1\";\n\treply-type=original\n";
			$body .= "Content-Transfer-Encoding: 7bit\n\n";
			$body .= wordwrap('This message has been un-quarantined by your spam filter administrator. They apologize for any inconvenience from the delay in receiving this message.')."\n";
			$body .= "--".$MessageSplitterGUID."\n";
			$body .= "Content-Type: message/rfc822;\n\tname=\"".$clean_subject.".eml\n";
			$body .= "Content-Transfer-Encoding: 7bit\n";
			$body .= "Content-Disposition: attachment;\n\tfilename=\"".$clean_subject.".eml\"\n\n";
			$body .= str_replace("\r\n", "\n", $original_header)."\n\n";
			$body .= str_replace("\r\n", "\n", $original_body)."\n";
			$body .= "--".$MessageSplitterGUID."--";

			if (mail($row['account'], '[unquarantined] '.$ParsedHeader['subject'][0], $body, $headers)) {

				echo 'Un-quarantine email sent successfully to "'.$row['account'].'"';

				$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'messages`';
				$SQLquery .= ' SET `undeleted` = "'.time().'"';
				$SQLquery .= ' WHERE (TRIM(`id`) = "'.mysql_escape_string(trim($_REQUEST['unquarantine'])).'")';
				mysql_query_safe($SQLquery);

			} else {
				echo 'FAILED TO: mail('.$row['account'].')';
			}

		} else {
			echo 'FAILED TO: gzopen('.$MessageContentsFilename.', rb)';
		}
		echo '</div><br>';

	} else {

		echo 'could not find any matching entry in the database for email "<b>'.htmlentities($_REQUEST['unquarantine'], ENT_QUOTES).'</b>"';

	}

} elseif (@$_GET['id']) {

	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'messages`';
	$SQLquery .= ' WHERE (TRIM(`id`) = "'.mysql_escape_string(trim($_REQUEST['id'])).'")';
	if (!IsAdminUser()) {
		$SQLquery .= ' AND (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
	}
	$result = mysql_query_safe($SQLquery);

	if ($row = mysql_fetch_assoc($result)) {

		$MessageContentsFilename = PHPOP3CLEAN_QUARANTINE.date('Ym', $row['date']).'/'.$row['id'].'.gz';
		if ($zp = @gzopen($MessageContentsFilename, 'rb')) {
			$MessageContents = '';
			while ($buffer = gzread($zp, 4096)) {
				$MessageContents .= $buffer;
			}
			gzclose($zp);
		} else {
			$MessageContents = 'FAILED TO: gzopen('.$MessageContentsFilename.', rb)';
		}

		echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?date='.$row['date']), ENT_QUOTES).'">Other messages filtered on '.date('j F Y g:i:a', $row['date']).'</a><hr>';

		echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="get">';
		echo '<input type="hidden" name="unquarantine" value="'.htmlentities($_REQUEST['id'], ENT_QUOTES).'">';
		echo '<input type="submit" value="Un-quarantine this message">';
		echo '</form><hr>';

		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><td valign="top" nowrap><b>Message ID</b></td><td>'.htmlentities($row['id'], ENT_QUOTES).'</td></tr>';
		echo '<tr><td valign="top" nowrap><b>Date deleted</b></td><td>'.htmlentities(date('r', $row['date']), ENT_QUOTES).'</td></tr>';
		echo '<tr><td valign="top" nowrap><b>Account</b></td><td>'.htmlentities($row['account'], ENT_QUOTES).'</td></tr>';

		echo '<tr><td valign="top" nowrap><b>Why Is Bad</b></td><td>';
		if (eregi('^Illegal attached image \((.*) = ([0-9a-f]{32}))$', $row['reason'], $matches)) {
			echo str_replace($matches[2], '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?imgadmin=edit&md5='.$matches[2]), ENT_QUOTES).'">'.htmlentities($matches[2], ENT_QUOTES).'</a>', $row['reason']);
		} else {
			echo htmlentities($row['reason'], ENT_QUOTES);
		}
		echo '</td></tr>';

		echo '<tr><td valign="top" nowrap><b>Subject</b></td><td>'.htmlentities($row['subject'], ENT_QUOTES).'</td></tr>';
		echo '<tr><td valign="top" nowrap><b>Message</b></td><td><pre>'.htmlentities($MessageContents, ENT_QUOTES).'</pre></td></tr>';
/*echo '<tr><td valign="top" nowrap><b>Stripped Message</b></td><td><pre>';

pr_var($MessageContents, 'message content before', true);

$headers = str_replace('+OK headers follow.'."\r\n", '', $MessageContents);
pr_var($headers, 'headers');
$ParsedHeader = POP3parseheader($headers);
pr_var($ParsedHeader, 'parsed header');

EncodingDecode(&$MessageContents, $ParsedHeader['content-transfer-encoding'][0]);
pr_var($MessageContents, 'message content after encodingdecode', true);
htmlentitiesDecode($MessageContents);
pr_var($MessageContents, 'message content after htmlentitiesdecode', true);
$MessageContents = strip_tags($MessageContents);
pr_var($MessageContents, 'message content after strip tags', true);
echo htmlentities($MessageContents);
echo '</pre></td></tr>';*/
		echo '</table>';

	} else {

		echo 'could not find any matching entry in the database for email "<b>'.htmlentities($_REQUEST['id'], ENT_QUOTES).'</b>"';

	}

} elseif (@$_GET['date']) {

	echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE), ENT_QUOTES).'">List of all mail filtering</a><hr>';
	echo 'Spam, viruses or other bad things were detected in the scan on <b>'.date('j F Y g:i:a', $_REQUEST['date']).'</b><br>';
	echo '(click a messageID to see all data for that message)<br><br>';

	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'messages`';
	$SQLquery .= ' WHERE (`date` = "'.mysql_escape_string($_REQUEST['date']).'")';
	if (!IsAdminUser()) {
		$SQLquery .= ' AND (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
	}
	$result = mysql_query_safe($SQLquery);

	echo '<table border="1" cellspacing="0" cellpadding="5">';
	echo '<tr><th>MessageID</th><th>Account</th><th>Reason</th><th>Subject</th></tr>';
	while ($row = mysql_fetch_assoc($result)) {
		echo '<tr>';
		echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?id='.$row['id']), ENT_QUOTES).'">'.htmlentities($row['id'], ENT_QUOTES).'</a></td>';
		echo '<td>'.htmlentities($row['account'], ENT_QUOTES).'</td>';
		echo '<td>'.htmlentities($row['reason'], ENT_QUOTES).'</td>';
		echo '<td>'.htmlentities($row['subject'], ENT_QUOTES).'</td>';
		echo '</tr>';
	}
	echo '</table><hr>';

} elseif (@$_GET['recent']) {

	$fields = array('account', 'date', 'id');
	$invAscDesc = array(''=>'ASC', 'ASC'=>'DESC', 'ASC'=>'');

	$SQLquery  = 'SELECT `id`, `account`, `date`, `headers`, `body`';
	$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'messages_recent`';
	if (IsAdminUser()) {
		if (@$_GET['AccountFilter']) {
			$SQLquery .= ' WHERE (`account` LIKE "%'.mysql_escape_string($_GET['AccountFilter']).'%")';
		}
	} else {
		$SQLquery .= ' WHERE (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
	}
	$SQLquery .= ' ORDER BY `'.mysql_escape_string(in_array(@$_GET['orderby'], $fields) ? $_GET['orderby'] : 'date').'` '.mysql_escape_string((@$_GET['orderorder'] == 'ASC') ? 'ASC' : 'DESC');
	$result = mysql_query_safe($SQLquery);

	echo '<table border="1" cellspacing="0" cellpadding="4">';
	echo '<tr>';
	foreach ($fields as $field) {
		echo '<th nowrap><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?recent='.__LINE__.'&AccountFilter='.@$_REQUEST['AccountFilter'].'&orderby='.$field.((@$_REQUEST['orderby'] == $field) ? '&orderorder='.@$invAscDesc[@$_GET['orderorder']] : '')), ENT_QUOTES).'">'.htmlentities($field, ENT_QUOTES).'</a> <span style="font-size: 18pt;">'.UpDownSymbol($field).'</span></th>';
	}
	echo '<th>Size</th><th>Subject</th>';
	echo '</tr>';
	while ($row = mysql_fetch_assoc($result)) {
		$ParsedHeader = POP3parseheader($row['headers']);

		echo '<tr>';
		echo '<td>'.htmlentities($row['account'], ENT_QUOTES).'</td>';
		echo '<td>'.date('j M Y g:i:sa', $row['date']).'</td>';
		echo '<td><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?messages_recent='.$row['id']), ENT_QUOTES).'" target="_blank">'.htmlentities($row['id'], ENT_QUOTES).'</a></td>';
		echo '<td align="right">'.number_format(strlen($row['body'])).'</td>';

		$sender_email = ExtractActualEmailAddress(@$ParsedHeader['from'][0]);
		$sender_link  = '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?emailwhitelistadmin=1&action=add&email='.$sender_email), ENT_QUOTES).'">'.htmlentities(substr($sender_email, 0, strpos($sender_email, '@')), ENT_QUOTES).'</a>';
		$sender_link .= '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?emailwhitelistadmin=1&action=add&email='.strstr($sender_email, '@')), ENT_QUOTES).'"><b>'.htmlentities(strstr($sender_email, '@'), ENT_QUOTES).'</b></a>';
		preg_match(preg_expression('^(.*)'.$sender_email.'(.*)$', 'i'), @$ParsedHeader['from'][0], $matches);
		echo '<td><b>From:</b> <i>'.htmlentities(@$matches[1], ENT_QUOTES).$sender_link.htmlentities($matches[2], ENT_QUOTES).'</i><br>';
		echo '<b>Subject:</b> <i>'.htmlentities(@$ParsedHeader['subject'][0], ENT_QUOTES).'</i></td>';
		unset($sender_email, $sender_link);

		echo '</tr>';
	}
	echo '</table>';

} else {

	if (IsAdminUser()) {
		///////////////////////////////////////////////////////////
		//$DBcleanupSQL[] = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domain_hits`      WHERE (`lasthit` < '.(time() - PHPOP3CLEAN_KEEP_DOMAIN_HITS).') AND (`added` < '.(time() - PHPOP3CLEAN_KEEP_DOMAIN_HITS).')';
		$DBcleanupSQL[] = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domain_hits`      WHERE (`lasthit` < '.(time() - PHPOP3CLEAN_KEEP_DOMAIN_HITS).')';
		$DBcleanupSQL[] = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'messages_scanned` WHERE (`date`    < '.(time() - PHPOP3CLEAN_KEEP_MESSAGES_SCANNED).')';
		$DBcleanupSQL[] = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'messages`         WHERE (`date`    < '.(time() - PHPOP3CLEAN_KEEP_MESSAGES).')';
		$DBcleanupSQL[] = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`            WHERE (`lasthit` < '.(time() - PHPOP3CLEAN_KEEP_IMAGE).') AND (`added` < '.(time() - PHPOP3CLEAN_KEEP_IMAGE).')';
		// other tables are purged each time in phPOP3clean.php

		$DBcleanupSQL[] = 'OPTIMIZE TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'domain_hits`, `'.PHPOP3CLEAN_TABLE_PREFIX.'ips`, `'.PHPOP3CLEAN_TABLE_PREFIX.'messages_scanned`, `'.PHPOP3CLEAN_TABLE_PREFIX.'messages`, `'.PHPOP3CLEAN_TABLE_PREFIX.'image`, `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_recent`, `'.PHPOP3CLEAN_TABLE_PREFIX.'delete_queue`';
		foreach ($DBcleanupSQL as $SQLquery) {
			mysql_query_safe($SQLquery);
		}
		///////////////////////////////////////////////////////////
	}

	echo '<b>Report date range ('.ElapsedTimeNiceDisplay($DateRangeMin, $DateRangeMax, 1).'):</b>';
	echo '<form action="'.htmlentities(linkencode($_SERVER['PHP_SELF']), ENT_QUOTES).'" method="post">';
	echo '<table border="3" cellspacing="0" cellpadding="10">';
	$DateRangeYears = ceil(time() / 31536000); // years to now since 1970
	echo '<tr><td><b>Min:</b></td><td>'.DateDropdown('DateRangeMin', $DateRangeMin, true, true, true, $DateRangeYears, $DateRangeYears, true, true).'</td></tr>';
	echo '<tr><td><b>Max:</b></td><td>'.DateDropdown('DateRangeMax', $DateRangeMax, true, true, true, $DateRangeYears, $DateRangeYears, true, true).'</td></tr>';
	if (IsAdminUser()) {
		echo '<tr><td><b>Account:</b></td><td><input type="text" name="AccountFilter" value="'.htmlentities(@$_REQUEST['AccountFilter'], ENT_QUOTES).'"> <i>"user@example.com" or "example.com"</i></td></tr>';
	}
	echo '<tr><td align="center"><input type="submit" value="Update"></td><td><input type="checkbox" name="showeverymessage" value="x"'.(@$_REQUEST['showeverymessage'] ? ' checked' : '').'> Show filtered message details<br></td></tr>';
	echo '</table></form><br>';

	$RecentScannedCount = array('good'=>array(), 'spam'=>array());
	$RecentMessagesHistogramTable = array('good' => 'messages_recent', 'spam' => 'messages');
	$RecentHours = min(72, (($DateRangeMax - $DateRangeMin) / 3600)); // max 3 days
	foreach ($RecentMessagesHistogramTable as $key => $table) {
		$SQLquery  = 'SELECT COUNT(*) AS `totalcount`, FLOOR(('.mysql_escape_string($DateRangeMax).' - `date`) / 3600) AS `hoursago`';
		$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.$table.'`';
		$SQLquery .= ' WHERE (`date` >= "'.$DateRangeMin.'")';
		$SQLquery .= ' AND (`date` <= "'.$DateRangeMax.'")';
		if (IsAdminUser()) {
			if (@$_REQUEST['AccountFilter']) {
				$SQLquery .= ' AND (`account` LIKE "%'.mysql_escape_string($_REQUEST['AccountFilter']).'%")';
			}
		} else {
			$SQLquery .= ' AND (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
		}
		$SQLquery .= ' GROUP BY `hoursago` ASC';
		$SQLquery .= ' LIMIT 0,'.$RecentHours;
		$result = mysql_query_safe($SQLquery);

		while ($row = mysql_fetch_assoc($result)) {
			$RecentScannedCount[$key][$row['hoursago']] = $row['totalcount'];
		}
	}
	$RecentScannedCountTotal = array_sum(@$RecentScannedCount['good']) + array_sum(@$RecentScannedCount['spam']);
	echo '<table border="0" cellspacing="0" cellpadding="1" style="border: 1px #000000 solid;"><tr>';
	for ($i = ($RecentHours - 1); $i >= 0; $i--) {
		echo '<td valign="bottom" align="center">';
		foreach ($RecentScannedCount as $key => $dummy) {
			echo '<img src="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?pixel='.(($key == 'good') ? '00CC00' : 'CC0000')), ENT_QUOTES).'" alt="" title="'.round(@$RecentScannedCount[$key][$i]).' '.$key.' messages ('.date('F/j h:i', $DateRangeMax - (3600 * ($i + 1))).' - '.date('F/j h:i', $DateRangeMax - (3600 * $i)).')" width="'.PHPOP3CLEAN_RECENT_HIST_SCALING_X.'" height="'.round(@$RecentScannedCount[$key][$i] * PHPOP3CLEAN_RECENT_HIST_SCALING_Y / max(1, $RecentScannedCountTotal)).'" border="0"><br>';
		}
		echo '</td>';
	}
	echo '</tr></table><br><br>';


	if (@$_REQUEST['showeverymessage']) {
		$SQLquery  = 'SELECT `id`, `account`, `date`, `reason`, `subject`, `undeleted`';
		$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'messages`';
		$SQLquery .= ' WHERE (`date` >= "'.$DateRangeMin.'")';
		$SQLquery .= ' AND (`date` <= "'.$DateRangeMax.'")';
		if (IsAdminUser()) {
			if (@$_REQUEST['AccountFilter']) {
				$SQLquery .= ' AND (`account` LIKE "%'.mysql_escape_string($_REQUEST['AccountFilter']).'%")';
			}
		} else {
			$SQLquery .= ' AND (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
		}
		$SQLquery .= ' ORDER BY `date` DESC';
		$result = mysql_query_safe($SQLquery);
		$TotalBad = mysql_num_rows($result);
		$MinDate  = time();

		if ($TotalBad > 0) {
			echo '<table border="1" cellspacing="0" cellpadding="3"><tr><td colspan="2">';
			echo '<table border="0" cellspacing="0" cellpadding="3" width="100%"><tr><td align="center"><b>Spam, viruses or other bad things were detected in scans at these date/times (click a date to see all filtered messages)</b></td></tr>';
			$previousdate = 0;
			while ($row = mysql_fetch_assoc($result)) {

				if ($previousdate != $row['date']) {
					echo '</table></td></tr>';
					echo '<tr><td valign="top" nowrap><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?date='.$row['date']), ENT_QUOTES).'">'.nl2br(date('j M Y'."\n".' g:ia', $row['date'])).'</a></td>';
					echo '<td><table border="0" width="100%" cellspacing="0" cellpadding="3">';
				}
				$reasonlink = '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?id='.$row['id']), ENT_QUOTES).'">'.htmlentities($row['reason'], ENT_QUOTES).'</a>';
				if ($row['undeleted']) {
					$bgcolor = 'FF0000';
				} elseif (ereg('^(Zipped )?(Illegal attachment |Attachment infected)', $row['reason'])) {
					$bgcolor = 'FF9900';
				} elseif (ereg('^Banned IP', $row['reason'])) {
					$bgcolor = 'CCCCFF';
				} elseif (ereg('^DNSBL IP', $row['reason'])) {
					$bgcolor = '9999FF';
				} elseif (ereg('^DNSBL IP', $row['reason'])) {
					$bgcolor = '6699FF';
				} elseif (ereg('^Banned phrase', $row['reason'])) {
					$bgcolor = '6699FF';
				} elseif (ereg('^Illegal attached image', $row['reason'])) {
					$bgcolor = 'CC66FF';
				} elseif (ereg('^Banned domain in Received header', $row['reason'])) {
					$bgcolor = 'CC99FF';
				} else {
					$bgcolor = '99CC66';
				}
				if ($row['undeleted']) {
					echo '<tr bgcolor="#FF0000"><td colspan="3" align="center"><b>This message was undeleted '.date('F j Y g:i:sa', $row['undeleted']).'</b></td></tr>';
				}
				echo '<tr bgcolor="#'.$bgcolor.'">';
				echo '<td width="150">'.htmlentities($row['account'], ENT_QUOTES).'</td>';
				echo '<td width="150">'.htmlentities($row['subject'], ENT_QUOTES).'</td>';
				echo '<td>'.$reasonlink.'</td>';
				echo '</tr>';

				$previousdate = $row['date'];
				$MinDate = min($MinDate, $row['date']);
			}
			echo '</table></td></tr></table><br>';
		}
	}

	$DaysElapsed = max(($DateRangeMax - $DateRangeMin) / 86400, 0.01);

	$SQLquery  = 'SELECT `account`,';
	$SQLquery .= ' SUM(`good`) AS `sumgood`,';
	$SQLquery .= ' SUM(`spam`) AS `sumspam`,';
	$SQLquery .= ' SUM(`virus`) AS `sumvirus`,';
	$SQLquery .= ' SUM(`corrupt`) AS `sumcorrupt`';
	$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'history`';
	$SQLquery .= ' WHERE (`datestamp` >= "'.date('Ymd', $DateRangeMin).'")';
	$SQLquery .= ' AND (`datestamp` <= "'.date('Ymd', $DateRangeMax).'")';
	if (IsAdminUser()) {
		if (@$_REQUEST['AccountFilter']) {
			$SQLquery .= ' AND (`account` LIKE "%'.mysql_escape_string($_REQUEST['AccountFilter']).'%")';
		}
	} else {
		$SQLquery .= ' AND (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
	}
	$SQLquery .= ' GROUP BY `account`';
	$result = mysql_query_safe($SQLquery);
	while ($row = mysql_fetch_assoc($result)) {
		$DeletedCountSpam[$row['account']]      = $row['sumspam'];
		$DeletedCountMalformed[$row['account']] = $row['sumcorrupt'];
		$DeletedCountVirus[$row['account']]     = $row['sumvirus'];
		$ScannedGoodEmails[$row['account']]     = $row['sumgood'];
	}

	$TotalBad = @array_sum(@$DeletedCountSpam) + @array_sum(@$DeletedCountVirus) + @array_sum(@$DeletedCountMalformed);
	$TotalEmailsScanned = $TotalBad + @array_sum(@$ScannedGoodEmails);
	echo 'Total bad messages deleted in last <b>'.number_format($DaysElapsed, 1).'</b> days: <b>'.number_format($TotalBad).'</b> ('.number_format($TotalBad / $DaysElapsed, 1).' / day), that is '.number_format(100 * ($TotalBad / max(1, $TotalEmailsScanned)), 1).'% of incoming email.';

	$DateGETstringElements = array('DateRangeMinMonth', 'DateRangeMinDay', 'DateRangeMinYear', 'DateRangeMinHour', 'DateRangeMinMinute', 'DateRangeMaxMonth', 'DateRangeMaxDay', 'DateRangeMaxYear', 'DateRangeMaxHour', 'DateRangeMaxMinute');
	$DateGETstring = '';
	foreach ($DateGETstringElements as $element) {
		$DateGETstring .= '&'.$element.'='.@$_REQUEST[$element];
	}

	echo '<table border="1" cellspacing="0" cellpadding="3">';
	echo '<tr><th colspan="3">deleted</th><th rowspan="2">good</th><th rowspan="2">bad:good</th><th rowspan="2">account<br><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?AccountFilter='.$DateGETstring), ENT_QUOTES).'" title="show filtering for all accounts">show all</a></th><th rowspan="2">% total</th></tr>';
	echo '<tr><th>spam</th><th>corrupt</th><th>virus</th></tr>';

	$SQLquery  = 'SELECT `account`, `active`';
	$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts`';
	if (IsAdminUser()) {
		if (@$_REQUEST['AccountFilter']) {
			$SQLquery .= ' WHERE (`account` LIKE "%'.mysql_escape_string($_REQUEST['AccountFilter']).'%")';
		}
	} else {
		$SQLquery .= ' WHERE (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
	}
	$SQLquery .= ' ORDER BY SUBSTRING(`account`, LOCATE("@", `account`) + 1) ASC, `account` ASC';
	$result = mysql_query_safe($SQLquery);
	$lastdomain = '';
	$rowcounter = 0;

	while ($row = mysql_fetch_assoc($result)) {
		list($username, $hostname) = explode('@', $row['account']);
		if ($hostname != $lastdomain) {
			$rowcounter++;
		}
		$lastdomain = $hostname;
		echo '<tr bgcolor="#'.($row['active'] ? (($rowcounter % 2) ? 'EEEEEE' : 'DDDDDD') : (($rowcounter % 2) ? 'FFBBBB' : 'FFAAAA')).'">';
		echo '<td align="right">'.((@$DeletedCountSpam[$row['account']]      > 0) ? number_format(@$DeletedCountSpam[$row['account']])      : '-').'</td>';
		echo '<td align="right">'.((@$DeletedCountMalformed[$row['account']] > 0) ? number_format(@$DeletedCountMalformed[$row['account']]) : '-').'</td>';
		echo '<td align="right">'.((@$DeletedCountVirus[$row['account']]     > 0) ? number_format(@$DeletedCountVirus[$row['account']])     : '-').'</td>';
		echo '<td align="right">'.((@$ScannedGoodEmails[$row['account']]     > 0) ? '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?recent='.__LINE__.'&AccountFilter='.$row['account']), ENT_QUOTES).'">'.number_format(@$ScannedGoodEmails[$row['account']]).'</a>' : '-').'</td>';

		$badcount   = @$DeletedCountSpam[$row['account']] + @$DeletedCountMalformed[$row['account']] + @$DeletedCountVirus[$row['account']];
		$goodcount  = @$ScannedGoodEmails[$row['account']];
		$totalcount = $badcount + $goodcount;

		$pctBad  = round(($badcount / max($totalcount, 1)) * 100);
		$pctGood = 100 - $pctBad;
		echo '<td align="center">';
		echo '<img src="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?pixel=CC0000'), ENT_QUOTES).'" border="0" height="10" width="'.$pctBad.'"  alt="" title="'.$badcount.' ('.$pctBad.'%) bad">';
		echo '<img src="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?pixel=00CC00'), ENT_QUOTES).'" border="0" height="10" width="'.$pctGood.'" alt="" title="'.$goodcount.' ('.$pctGood.'%) good"></td>';

		echo '<td align="right">';
		echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?AccountFilter='.$row['account'].$DateGETstring), ENT_QUOTES).'" title="show only filtering for '.htmlentities($row['account'], ENT_QUOTES).'">'.htmlentities($username, ENT_QUOTES).'</a>@';
		echo '<a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?AccountFilter='.$hostname.$DateGETstring), ENT_QUOTES).'" title="show only filtering for all users in '.htmlentities($hostname, ENT_QUOTES).'">'.htmlentities($hostname, ENT_QUOTES).'</a>';
		echo '</td>';

		echo '<td align="left"><img src="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?pixel=333399'), ENT_QUOTES).'" border="0" alt="" height="10" width="'.ceil(($badcount / max($TotalBad, 1)) * 300).'"></td>';
		echo '</tr>';
	}
	echo '<tr bgcolor="#CCCCCC">';
	echo '<td align="right"><b>'.number_format(@array_sum(@$DeletedCountSpam)).'</b></td>';
	echo '<td align="right"><b>'.number_format(@array_sum(@$DeletedCountMalformed)).'</b></td>';
	echo '<td align="right"><b>'.number_format(@array_sum(@$DeletedCountVirus)).'</b></td>';
	echo '<td align="right"><b>'.number_format(@array_sum(@$ScannedGoodEmails)).'</b></td>';
	echo '<td align="center" rowspan="2" colspan="3"><i>all</i></td>';
	echo '</tr>';
	echo '<tr bgcolor="#CCCCCC">';
	echo '<td align="right"><b>'.number_format((@array_sum(@$DeletedCountSpam)      / max(1, $TotalEmailsScanned)) * 100).'%</b></td>';
	echo '<td align="right"><b>'.number_format((@array_sum(@$DeletedCountMalformed) / max(1, $TotalEmailsScanned)) * 100).'%</b></td>';
	echo '<td align="right"><b>'.number_format((@array_sum(@$DeletedCountVirus)     / max(1, $TotalEmailsScanned)) * 100).'%</b></td>';
	echo '<td align="right"><b>'.number_format((@array_sum(@$ScannedGoodEmails)     / max(1, $TotalEmailsScanned)) * 100).'%</b></td>';
	echo '</tr>';
	echo '</table><br><br>';


	$DeleteReasons     = array();
	$BadEmbeddedMIME   = array();
	$BadExtension3char = array();
	$BadAttachedImages = array();
	$BadReceivedHeader = array();
	//$BannedDomains     = array();
	//$BannedIPs         = array();
	$BannedPhrases     = array();
	$GraphsWidth       = 300;

	$SQLquery  = 'SELECT `reason`, COUNT(*) AS MessageCount';
	$SQLquery .= ' FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'messages`';
	$SQLquery .= ' WHERE (`date` >= "'.$DateRangeMin.'")';
	$SQLquery .= ' AND (`date` <= "'.$DateRangeMax.'")';
	if (IsAdminUser()) {
		if (@$_REQUEST['AccountFilter']) {
			$SQLquery .= ' AND (`account` LIKE "%'.mysql_escape_string($_REQUEST['AccountFilter']).'%")';
		}
	} else {
		$SQLquery .= ' AND (`account` = "'.mysql_escape_string($_COOKIE['phPOP3cleanUSER']).'")';
	}
	$SQLquery .= ' GROUP BY `reason`';
	$result = mysql_query_safe($SQLquery);
	while ($row = mysql_fetch_assoc($result)) {
		if (eregi('^Illegal attachment \((.*)\.([a-z0-9]{3,4})\) in zip file \((.*)(\.[a-z]{3})\)$', $row['reason'], $matches)) {
			$row['reason'] = 'Illegal attachment in zip file';
			@$BadExtension3char['zip-'.$matches[2]] += $row['MessageCount'];
			@$AttachmentViruses['unknown'] += $row['MessageCount'];
		} elseif (eregi('^Illegal attachment \((.*)\.([a-z]{3})\)$', $row['reason'], $matches)) {
			@$BadExtension3char[$matches[2]] += $row['MessageCount'];
			$row['reason'] = 'Illegal attachment';
			@$AttachmentViruses['unknown'] += $row['MessageCount'];
		} elseif (eregi('^Illegal attached image \((.*) = ([0-9a-f]{32})\)$', $row['reason'], $matches)) {
			@$BadAttachedImages[$matches[2]] += $row['MessageCount'];
			$row['reason'] = 'Illegal attached image';
		} elseif (eregi('^Banned phrase in (body|subject|code) \((.*)$', $row['reason'], $matches)) {
			if (substr($matches[2], -1, 1) == ')') {
				// trim trailing ) off match
				// maybe could be better fixed by rewrited regex, but I'm lazy today
				$matches[2] = substr($matches[2], 0, -1);
			}
			if (strpos($matches[2], ':::') !== false) {
				list($cleanword, $matchedword) = explode(':::', $matches[2], 2);
				@$BannedPhrases[$cleanword][$matchedword] += $row['MessageCount'];
			} else {
				@$BannedPhrases[$matches[2]][$matches[2]] += $row['MessageCount'];
			}
			$row['reason'] = 'Banned phrase in '.$matches[1];
		} elseif (eregi('^Banned domain in Received header \((.*)\)$', $row['reason'], $matches)) {
			@$BannedReceivedHeader[strtolower($matches[1])] += $row['MessageCount'];
			$row['reason'] = 'Banned domain in Received header';
		} elseif (eregi('^(Banned|DNSBL) IP in (header|body)', $row['reason'], $matches)) {
			$row['reason'] = $matches[0];
		} elseif (eregi('^domain resolves to too many IPs', $row['reason'], $matches)) {
			$row['reason'] = $matches[0];
		} elseif (eregi('^(Zipped )?Attachment infected with virus \((.*)\)$', $row['reason'], $matches)) {
			@$AttachmentViruses[$matches[2]] += $row['MessageCount'];
			$row['reason'] = 'Infected Attachment';
		} elseif (eregi('^Illegal attachment \((.*)\.([a-z]{3})\) claiming to be type \((.*)\)$', $row['reason'], $matches)) {
			@$BadExtension3char[$matches[2]] += $row['MessageCount'];
			@$BadEmbeddedMIME[$matches[3]] += $row['MessageCount'];
			$row['reason'] = 'Illegal attachment claiming to be other MIME type';
			@$AttachmentViruses['unknown'] += $row['MessageCount'];
		} elseif (eregi('^SpamAssassin', $row['reason'], $matches)) {
			$row['reason'] = 'SpamAssassin';
		} elseif (eregi('^Banned phrase in multipart piece header', $row['reason'], $matches)) {
			$row['reason'] = 'Banned phrase in multipart piece header';
		}
		@$DeleteReasons[$row['reason']] += $row['MessageCount'];
	}
	if (!empty($DeleteReasons)) {
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>deleted</th><th>reason</th><th>% total</th></tr>';
		arsort($DeleteReasons);
		$GraphUnit = $GraphsWidth / max(max($DeleteReasons), 1);
		$totalDeleted = array_sum($DeleteReasons);
		foreach ($DeleteReasons as $reason => $count) {
			echo '<tr>';
			echo '<td align="right">'.number_format($count).'</td>';
			echo '<td align="right">'.htmlentities($reason, ENT_QUOTES).'</td>';
			echo '<td align="left"><img src="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?pixel=333399'), ENT_QUOTES).'" border="0" alt="" title="'.$count.' ('.round(100 * ($count / $totalDeleted)).'%)" height="10" width="'.round($count * $GraphUnit).'"></td>';
			echo '</tr>';
		}
		echo '</table><br><br>';
	}

	if (!empty($BadEmbeddedMIME)) {
		echo '<b><a href="#">Bad Embedded MIME type</a></b><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>deleted</th><th>Faked MIME type</th><th>% total</th></tr>';
		arsort($BadEmbeddedMIME);
		$GraphUnit = $GraphsWidth / max(max($BadEmbeddedMIME), 1);
		foreach ($BadEmbeddedMIME as $mimetype => $count) {
			echo '<tr>';
			echo '<td align="right">'.number_format($count).'</td>';
			echo '<td align="right">'.htmlentities($mimetype, ENT_QUOTES).'</td>';
			echo '<td align="left"><img src="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?pixel=333399'), ENT_QUOTES).'" border="0" alt="" height="10" width="'.round($count * $GraphUnit).'"></td>';
			echo '</tr>';
		}
		echo '</table><br><br>';
	}

	if (!empty($AttachmentViruses)) {
		echo '<b><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?exeadmin='.__LINE__.'&orderby=lasthit'), ENT_QUOTES).'">Infected Attachments</a></b><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>deleted</th><th>Attachment Viruses</th><th>% total</th></tr>';
		ksort($AttachmentViruses);
		$GraphUnit = $GraphsWidth / max(max($AttachmentViruses), 1);
		foreach ($AttachmentViruses as $virus => $count) {
			echo '<tr>';
			echo '<td align="right">'.number_format($count).'</td>';
			if (!$virus || ($virus == 'unknown')) {
				echo '<td align="right"><i>unknown</i></td>';
			} elseif ($virus == 'ok') {
				echo '<td align="right"><i>"ok" (not infected)</i></td>';
			} else {
				echo '<td align="right"><a href="'.htmlentities(linkencode('http://www.sarc.com/avcenter/venc/data/'.strtolower($virus).'.html'), ENT_QUOTES).'" target="_blank" title="Show information about '.htmlentities($virus, ENT_QUOTES).' from Symantec Antivirus Research Center (SARC)">'.htmlentities($virus, ENT_QUOTES).'</a></td>';
			}
			echo '<td align="left"><img src="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?pixel=333399'), ENT_QUOTES).'" border="0" alt="" height="10" width="'.round($count * $GraphUnit).'"></td>';
			echo '</tr>';
		}
		echo '</table><br><br>';
	}

	if (!empty($BadExtension3char)) {
		echo '<b><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?exeadmin='.__LINE__.'&orderby=lasthit'), ENT_QUOTES).'">Infected Attachments</a></b><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>deleted</th><th>Bad extention</th><th>% total</th></tr>';
		arsort($BadExtension3char);
		$GraphUnit = $GraphsWidth / max(max($BadExtension3char), 1);
		foreach ($BadExtension3char as $extension => $count) {
			echo '<tr>';
			echo '<td align="right">'.number_format($count).'</td>';
			echo '<td align="right">'.htmlentities($extension, ENT_QUOTES).'</td>';
			echo '<td align="left"><img src="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?pixel=333399'), ENT_QUOTES).'" border="0" alt="" height="10" width="'.round($count * $GraphUnit).'"></td>';
			echo '</tr>';
		}
		echo '</table><br><br>';
	}

	if (!empty($BadAttachedImages)) {
		echo '<b><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?imgadmin='.__LINE__.'&orderby=lasthit'), ENT_QUOTES).'">Banned Attached Images</a></b><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>deleted</th><th>Bad image MD5</th><th>% total</th></tr>';
		arsort($BadAttachedImages);
		$GraphUnit = $GraphsWidth / max(max($BadAttachedImages), 1);
		foreach ($BadAttachedImages as $md5 => $count) {
			echo '<tr>';
			echo '<td align="right">'.number_format($count).'</td>';
			echo '<td align="right"><a href="'.htmlentities(linkencode(MD5imageSRC($md5)), ENT_QUOTES).'">'.MD5imageSRC($md5, '', true, 1, 120).'</a></td>';
			echo '<td align="left"><img src="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?pixel=333399'), ENT_QUOTES).'" border="0" alt="" height="10" width="'.round($count * $GraphUnit).'"></td>';
			echo '</tr>';
		}
		echo '</table><br><br>';
	}

	if (!empty($BadReceivedHeader)) {
		echo '<b><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?receivedadmin='.__LINE__.'&orderby=lasthit'), ENT_QUOTES).'">Banned "Received" header</a></b><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>deleted</th><th>Bad Received Header</th><th>% total</th></tr>';
		arsort($BadReceivedHeader);
		$GraphUnit = $GraphsWidth / max(max($BadReceivedHeader), 1);
		foreach ($BadReceivedHeader as $received => $count) {
			echo '<tr>';
			echo '<td align="right">'.number_format($count).'</td>';
			echo '<td align="right">'.htmlentities($received, ENT_QUOTES).'</td>';
			echo '<td align="left"><img src="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?pixel=333399'), ENT_QUOTES).'" border="0" alt="" height="10" width="'.round($count * $GraphUnit).'"></td>';
			echo '</tr>';
		}
		echo '</table><br><br>';
	}

	if (!empty($BannedPhrases)) {
		echo '<b><a href="'.htmlentities(linkencode(PHPOP3CLEAN_ADMINPAGE.'?wordadmin='.__LINE__), ENT_QUOTES).'">Banned Phrases</a></b><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3" width="100%">';
		echo '<tr><th>deleted</th><th>Banned phrase</th><th>% total</th></tr>';
		$MaxCount = 0;
		foreach ($BannedPhrases as $cleanphrase => $dirtycounts) {
			$MaxCount = max($MaxCount, array_sum($dirtycounts));
		}
		$GraphUnit = $GraphsWidth / $MaxCount;
		foreach ($BannedPhrases as $cleanphrase => $dirtycounts) {
			$counts   = '<b>'.number_format(array_sum($dirtycounts)).'</b><br>';
			$variants = '<b>'.preg_replace('#\(([^\(]+)\)#U', ' ($1) ', htmlentities($cleanphrase, ENT_QUOTES)).'</b><br>';
			foreach ($dirtycounts as $dirtyphrase => $count) {
				if ($dirtyphrase != $cleanphrase) {
					$counts   .= number_format($count).'<br>';
					$variants .= htmlentities($dirtyphrase, ENT_QUOTES).'<br>';
				}
			}
			echo '<tr>';
			echo '<td align="right">'.$counts.'</td>';
			echo '<td align="right">'.$variants.'</td>';
			echo '<td align="left"><img src="'.htmlentities(linkencode($_SERVER['PHP_SELF'].'?pixel=333399'), ENT_QUOTES).'" border="0" alt="" height="10" width="'.round(array_sum($dirtycounts) * $GraphUnit).'"></td>';
			echo '</tr>';
		}
		echo '</table><br><br>';
	}

}

echo '<hr size="1"><a href="http://phpop3clean.sourceforge.net/">phPOP3clean v'.PHPOP3CLEAN_VERSION.'</a>';
?>
</body>
</html>