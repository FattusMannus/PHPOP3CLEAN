<?php
/////////////////////////////////////////////////////////////////
/// phPOP3clean() by James Heinrich <info@silisoftware.com>    //
//  available at http://phpop3clean.sourceforge.net            //
/////////////////////////////////////////////////////////////////

require_once(dirname(__FILE__).'/phPOP3clean.config.php');
require_once(dirname(__FILE__).'/phPOP3clean.functions.php');
require_once(dirname(__FILE__).'/phPOP3clean.login.php');
if (!IsAdminUser()) {
	echo 'You do not have permission to use this file';
	exit;
}
set_time_limit(300);

if (@$_REQUEST['nodata']) {

	header('Content-type: application/octet-stream');
	header('Content-Disposition: attachment; filename=phpop3clean_sql_nodata_'.date('Ymd').'.sql');

	// INFECTED ATTACHMENTS
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'exe` ORDER BY `virus_name` ASC';
	$result = mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		echo 'INSERT IGNORE INTO `phpop3clean_exe` (`filesize`, `md5`, `pattern`, `virus_name`, `added`) VALUES (';
		echo     mysql_escape_string($row['filesize']).", ";
		echo "'".mysql_escape_string($row['md5'])."', ";
		echo "'".mysql_escape_string($row['pattern'])."', ";
		echo "'".mysql_escape_string($row['virus_name'])."', ";
		echo     mysql_escape_string($row['added']).");\n";
	}
	echo "\n";

	// ATTACHED IMAGES
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image` ORDER BY `added` ASC';
	$result = mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		echo 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'image` (`md5`, `description`, `pattern`, `size`, `ext`, `added`) VALUES (';
		echo "'".mysql_escape_string($row['md5'])."', ";
		echo "'".mysql_escape_string($row['description'])."', ";
		echo "'".mysql_escape_string($row['pattern'])."', ";
		echo     mysql_escape_string($row['size']).", ";
		echo "'".mysql_escape_string($row['ext'])."', ";
		echo     mysql_escape_string($row['added']).");\n";
	}
	echo "\n";

	// RECEIVED-FROM DOMAINS
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'received_domains` ORDER BY `domain` ASC';
	$result = mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		echo 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'received_domains` (`domain`, `added`) VALUES (';
		echo "'".mysql_escape_string($row['domain'])."', ";
		echo     mysql_escape_string($row['added']).");\n";
	}
	echo "\n";

	// WHITELISTED IPS
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ips` WHERE (`whitelist` = "1") ORDER BY `ipmask` ASC';
	$result = mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		$row['domains'] = str_replace("\r\n", "\n", $row['domains']);
		$row['domains'] = str_replace("\r",   "\n", $row['domains']);
		echo 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'ips` (`ipmask`, `cmask`, `whitelist`, `added`, `domains`) VALUES (';
		echo "'".mysql_escape_string($row['ipmask'])."', ";
		echo "'".mysql_escape_string($row['cmask'])."', ";
		echo "'".mysql_escape_string('1')."', ";
		echo "'".mysql_escape_string(time())."', ";
		echo "'".mysql_escape_string($row['domains'])."');\n";
	}
	echo "\n";

	// CLEAN PHRASES
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'words_clean` ORDER BY `word` ASC';
	$result = mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		echo 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'words_clean` (`word`, `description`, `category`, `example`, `isregex`, `casesensitive`, `added`) VALUES (';
		echo "'".mysql_escape_string($row['word'])."', ";
		echo "'".mysql_escape_string($row['description'])."', ";
		echo "'".mysql_escape_string($row['category'])."', ";
		echo "'".mysql_escape_string($row['example'])."', ";
		echo     mysql_escape_string($row['isregex']).", ";
		echo "'".mysql_escape_string($row['casesensitive'])."', ";
		echo     mysql_escape_string($row['added']).");\n";
	}
	echo "\n";

	// CODE PHRASES
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'words_code` ORDER BY `word` ASC';
	$result = mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		echo 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'words_code` (`word`, `description`, `category`, `example`, `isregex`, `casesensitive`, `added`) VALUES (';
		echo "'".mysql_escape_string($row['word'])."', ";
		echo "'".mysql_escape_string($row['description'])."', ";
		echo "'".mysql_escape_string($row['category'])."', ";
		echo "'".mysql_escape_string($row['example'])."', ";
		echo     mysql_escape_string($row['isregex']).", ";
		echo "'".mysql_escape_string($row['casesensitive'])."', ";
		echo     mysql_escape_string($row['added']).");\n";
	}
	echo "\n";

	// OBFUSCATED PHRASES
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'words_obfuscated` ORDER BY `word` ASC';
	$result = mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		echo 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'words_obfuscated` (`word`, `description`, `category`, `example`, `casesensitive`, `added`) VALUES (';
		echo "'".mysql_escape_string($row['word'])."', ";
		echo "'".mysql_escape_string($row['description'])."', ";
		echo "'".mysql_escape_string($row['category'])."', ";
		echo "'".mysql_escape_string($row['example'])."', ";
		echo "'".mysql_escape_string($row['casesensitive'])."', ";
		echo     mysql_escape_string($row['added']).");\n";
	}
	echo "\n";

	// AUTO-BAN DOMAINS
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_autoban`';
	$SQLquery .= ' WHERE (`lasthit` > '.(time() - (14 * 86400)).')';
	$SQLquery .= ' ORDER BY `domain` ASC';
	$result = mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		echo 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_autoban` (`domain`, `added`) VALUES (';
		echo "'".mysql_escape_string($row['domain'])."', ";
		echo     mysql_escape_string($row['added']).");\n";
	}
	echo "\n";

	exit;

} elseif (@$_REQUEST['fullexe']) {

	header('Content-type: application/octet-stream');
	header('Content-Disposition: attachment; filename=phpop3clean_sql_fulldata-exe_'.date('Ymd').'.sql');
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'exe`';
	$result = mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		echo 'INSERT IGNORE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'exe` (`filesize`, `md5`, `pattern`, `virus_name`, `added`, `virus_data`) VALUES (';
		echo     mysql_escape_string($row['filesize']).", ";
		echo "'".mysql_escape_string($row['md5'])."', ";
		echo "'".mysql_escape_string($row['pattern'])."', ";
		echo "'".mysql_escape_string($row['virus_name'])."', ";
		echo     mysql_escape_string($row['added']).", ";

		$exe_data = $row['virus_data'];
		$data_len = strlen($exe_data);
		echo "0x";
		for ($i = 0; $i < $data_len; $i++) {
			echo str_pad(dechex(ord($exe_data{$i})), 2, '0', STR_PAD_LEFT);
		}
		echo ");\n";
	}
	exit;

} elseif (@$_REQUEST['fullimg']) {

	header('Content-type: application/octet-stream');
	header('Content-Disposition: attachment; filename=phpop3clean_sql_fulldata-image_'.date('Ymd').'.sql');
	$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'image`';
	$result = mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		echo 'INSERT IGNORE INTO `phpop3clean_image` (`md5`, `description`, `pattern`, `size`, `ext`, `added`, `image_data`) VALUES (';
		echo "'".mysql_escape_string($row['md5'])."', ";
		echo "'".mysql_escape_string($row['description'])."', ";
		echo "'".mysql_escape_string($row['pattern'])."', ";
		echo     mysql_escape_string($row['size']).", ";
		echo "'".mysql_escape_string($row['ext'])."', ";
		echo     mysql_escape_string($row['added']).", ";

		$img_data = $row['image_data'];
		$data_len = strlen($img_data);
		echo "0x";
		for ($i = 0; $i < $data_len; $i++) {
			echo str_pad(dechex(ord($img_data{$i})), 2, '0', STR_PAD_LEFT);
		}
		echo ");\n";
	}
	exit;

} else {

	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	echo '<html><head><title>phPOP3clean :: database export</title></head><body>';
	echo 'Dump data:<ul>';
	echo '<li><a href="'.$_SERVER['PHP_SELF'].'?nodata=1">No EXE or IMG data</a></li>';
	echo '<li><a href="'.$_SERVER['PHP_SELF'].'?fullexe=1">Full EXE data</a></li>';
	echo '<li><a href="'.$_SERVER['PHP_SELF'].'?fullimg=1">Full IMG data</a></li>';
	echo '</ul>';
	echo '</body></html>';

}


?>