<?php
/////////////////////////////////////////////////////////////////
/// phPOP3clean() by James Heinrich <info@silisoftware.com>    //
//  available at http://phpop3clean.sourceforge.net            //
/////////////////////////////////////////////////////////////////

echo '<html><head><title>phPOP3clean() installation / setup</title></head><body>';

require_once('phPOP3clean.functions.php');
require_once('phPOP3clean.config.php');

$successful = true;

$MustBeDefinedConstants = array(
	'PHPOP3CLEAN_ADMINEMAIL',
	'PHPOP3CLEAN_ADMINPAGE',
	'PHPOP3CLEAN_ADMINPASS',
	'PHPOP3CLEAN_COL_BLIST',
	'PHPOP3CLEAN_COL_OK',
	'PHPOP3CLEAN_COL_WLIST',
	'PHPOP3CLEAN_CONFIG_VERSION',
	'PHPOP3CLEAN_DBHOST',
	'PHPOP3CLEAN_DBNAME',
	'PHPOP3CLEAN_DBPASS',
	'PHPOP3CLEAN_DBUSER',
	'PHPOP3CLEAN_DIRECTORY',
	'PHPOP3CLEAN_DNSBL_ZONE',
	'PHPOP3CLEAN_DOCUMENT_ROOT',
	'PHPOP3CLEAN_HIDE_PASSWORDS',
	'PHPOP3CLEAN_INTERSCAN_WAIT_PERIOD',
	'PHPOP3CLEAN_KEEP_DOMAIN_HITS',
	'PHPOP3CLEAN_KEEP_IMAGE',
	'PHPOP3CLEAN_KEEP_IPS',
	'PHPOP3CLEAN_KEEP_MESSAGES',
	'PHPOP3CLEAN_KEEP_MESSAGES_SCANNED',
	'PHPOP3CLEAN_KEEP_RECENT',
	'PHPOP3CLEAN_KEEP_RECENT_DOM',
	'PHPOP3CLEAN_KEEP_RECENT_IP_MESSAGES',
	'PHPOP3CLEAN_KEEP_VIRUS',
	'PHPOP3CLEAN_KEEP_WORDS_CLEAN',
	'PHPOP3CLEAN_KEEP_WORDS_CODE',
	'PHPOP3CLEAN_KEEP_WORDS_OBFUSCATED',
	'PHPOP3CLEAN_MAX_BODY_SIZE',
	'PHPOP3CLEAN_MAX_MESSAGE_SIZE',
	'PHPOP3CLEAN_MD5_IMAGE_CACHE',
	'PHPOP3CLEAN_PHP_TIMEOUT',
	'PHPOP3CLEAN_POP3_TIMEOUT',
	'PHPOP3CLEAN_PREG_DELIMIT',
	'PHPOP3CLEAN_QUARANTINE',
	'PHPOP3CLEAN_RECENT_HIST_SCALING_X',
	'PHPOP3CLEAN_RECENT_HIST_SCALING_Y',
	'PHPOP3CLEAN_SPAMASSASSIN_VALUE',
	'PHPOP3CLEAN_TABLE_PREFIX',
	'PHPOP3CLEAN_USE_DNSBL',
	'PHPOP3CLEAN_USE_SPAMASSASSIN',
	'PHPOP3CLEAN_USE_TRUNCATED_HEADER',
	'PHPOP3CLEAN_USE_INVALID_DATESTAMP',
);
foreach ($MustBeDefinedConstants as $key => $constantname) {
	if (!defined($constantname)) {
		echo '<font color="red">'.$constantname.' is not defined (you are probably using an old version of phPOP3clean.config.php)</b></font><br>';
		$successful = false;
	}
}
if (!$successful) {
	exit;
}


$constantDirs = array('PHPOP3CLEAN_QUARANTINE', 'PHPOP3CLEAN_MD5_IMAGE_CACHE');
foreach ($constantDirs as $key => $dirname) {
	$dirstring = constant($dirname);
	if (substr($dirstring, -1) != '/') {
		echo '<font color="red">'.$dirname.' ('.$dirstring.') <b>must end with "/"</b></font><br>';
		continue;
	}
	if (is_dir($dirstring)) {
		if (is_writeable($dirstring)) {
			echo '<font color="green">'.$dirname.' ('.$dirstring.') exists and is writeable</font><br>';
		} else {
			echo '<font color="red">'.$dirname.' ('.$dirstring.') exists <b>but is not writeable</b></font><br>';
			$successful = false;
		}
	} else {
		echo '<font color="red">'.$dirname.' ('.$dirstring.') <b>does not exist</b></font><br>';
		$successful = false;
	}
}
if (!$successful) {
	exit;
}
echo '<hr>';

$CreateTableSQLs['accounts']          = "(`account` varchar(200) NOT NULL default '', `password` varchar(50) NOT NULL default '', `port` mediumint(9) unsigned NOT NULL default '110', `active` tinyint(4) NOT NULL default '0', PRIMARY KEY (`account`));";
$CreateTableSQLs['domain_hits']       = "(`domain` varchar(50) NOT NULL default '', `ip` varchar(15) NOT NULL default '', `lasthit` int(11) NOT NULL default '0', `hitcount` int(11) NOT NULL default '0', PRIMARY KEY (`domain`,`ip`));";
$CreateTableSQLs['domains_recent']    = "(`domain` varchar(200) NOT NULL default '', `date` int(11) NOT NULL default '0', PRIMARY KEY (`domain`));";
$CreateTableSQLs['ips']               = "(`ipmask` varchar(18) NOT NULL default '', `cmask` varchar(11) NOT NULL default '', `added` int(11) NOT NULL default '0', `lasthit` int(11) NOT NULL default '0', `hitcount` mediumint(9) NOT NULL default '0', `domains` text NOT NULL, PRIMARY KEY (`ipmask`), UNIQUE KEY `cmask` (`cmask`));";
$CreateTableSQLs['received_domains']  = "(`domain` varchar(200) NOT NULL default '', `added` int(11) NOT NULL default '0', `lasthit` int(11) NOT NULL default '0', `hitcount` int(11) NOT NULL default '0', PRIMARY KEY (`domain`));";
$CreateTableSQLs['words_clean']       = "(`id` int(11) NOT NULL auto_increment, `word` text NOT NULL default '', `added` int(11) NOT NULL default '0', `lasthit` int(11) NOT NULL default '0', `hitcount` mediumint(9) NOT NULL default '0', `category` varchar(255) NOT NULL default '', `description` varchar(255) NOT NULL default '', `onlychars` varchar(255) NOT NULL default '', `example` text NOT NULL default '', `isregex` TINYINT(4) NOT NULL default '0', PRIMARY KEY (`id`));";
$CreateTableSQLs['words_code']        = "(`id` int(11) NOT NULL auto_increment, `word` text NOT NULL default '', `added` int(11) NOT NULL default '0', `lasthit` int(11) NOT NULL default '0', `hitcount` mediumint(9) NOT NULL default '0', `category` varchar(255) NOT NULL default '', `description` varchar(255) NOT NULL default '', `onlychars` varchar(255) NOT NULL default '', `example` text NOT NULL default '', `isregex` TINYINT(4) NOT NULL default '0', PRIMARY KEY (`id`));";
$CreateTableSQLs['words_obfuscated']  = "(`id` int(11) NOT NULL auto_increment, `word` text NOT NULL default '', `added` int(11) NOT NULL default '0', `lasthit` int(11) NOT NULL default '0', `hitcount` mediumint(9) NOT NULL default '0', `category` varchar(255) NOT NULL default '', `description` varchar(255) NOT NULL default '', `onlychars` varchar(255) NOT NULL default '', `example` text NOT NULL default '', `isregex` TINYINT(4) NOT NULL default '0', PRIMARY KEY (`id`));";
$CreateTableSQLs['exe']               = "(`filesize` int(11) NOT NULL default '0', `md5` varchar(32) NOT NULL default '', `pattern` varchar(255) NOT NULL default '', `virus_name` varchar(50) NOT NULL default '', `virus_data` longblob NOT NULL, `lasthit` int(11) NOT NULL default '0', `hitcount` mediumint(8) unsigned NOT NULL default '0', `added` int(11) NOT NULL default '0', PRIMARY KEY (`filesize`,`md5`));";
$CreateTableSQLs['image']             = "(`md5` varchar(32) NOT NULL default '', `description` varchar(255) NOT NULL default '', `image_data` longblob NOT NULL, `pattern` varchar(255) NOT NULL default '', `size` int(10) unsigned NOT NULL default '0', `ext` varchar(4) NOT NULL default '', `lasthit` int(11) NOT NULL default '0', `hitcount` mediumint(8) unsigned NOT NULL default '0', `added` int(11) NOT NULL default '0', PRIMARY KEY (`md5`));";
$CreateTableSQLs['messages']          = "(`id` varchar(200) NOT NULL default '', `date` int(11) NOT NULL default '0', `account` varchar(200) NOT NULL default '', `reason` varchar(255) NOT NULL default '', `subject` varchar(255) NOT NULL default '', PRIMARY KEY (`id`));";
$CreateTableSQLs['messages_recent']   = "(`id` varchar(50) NOT NULL default '', `date` int(11) NOT NULL default '0', `account` varchar(50) NOT NULL default '', `headers` blob NOT NULL, `body` mediumblob NOT NULL, `debug` blob NOT NULL, PRIMARY KEY (`id`));";
$CreateTableSQLs['messages_scanned']  = "(`account` varchar(50) NOT NULL default '', `messageid` varchar(50) NOT NULL default '', `date` int(11) NOT NULL default '0', `from` varchar(50) NOT NULL default '', `subject` varchar(200) NOT NULL default '', PRIMARY KEY (`account`,`messageid`));";
$CreateTableSQLs['whitelist_email']   = "(`email` varchar(200) NOT NULL default '', `added` int(11) NOT NULL default '0', `lasthit` int(11) NOT NULL default '0', `hitcount` int(11) NOT NULL default '0', PRIMARY KEY (`email`));";
$CreateTableSQLs['history']           = "(`account` VARCHAR(50) NOT NULL, `datestamp` INT UNSIGNED NOT NULL, `good` SMALLINT UNSIGNED NOT NULL, `spam` SMALLINT UNSIGNED NOT NULL, `virus` SMALLINT UNSIGNED NOT NULL, `corrupt` SMALLINT UNSIGNED NOT NULL, PRIMARY KEY (`account`, `datestamp`));";
$CreateTableSQLs['whitelist_subject'] = "(`word` VARCHAR(50) NOT NULL, `added` INT NOT NULL, `hitcount` INT NOT NULL, `lasthit` INT NOT NULL, PRIMARY KEY (`word`));";
$CreateTableSQLs['domains_autoban']   = "(`domain` VARCHAR(50) NOT NULL, `lasthit` INT NOT NULL, `hitcount` INT NOT NULL, `added` int(11) NOT NULL default '0', PRIMARY KEY (`domain`));";
$CreateTableSQLs['delete_queue']      = "(`account` VARCHAR(100) NOT NULL, `messageid` VARCHAR(100) NOT NULL, PRIMARY KEY (`account`,`messageid`));";
$CreateTableSQLs['ip_message_recent'] = "(`account` VARCHAR(100) NOT NULL, `messageid` VARCHAR(100) NOT NULL, `ip` VARCHAR(15) NOT NULL, `ip_cmask` varchar(11) NOT NULL, `date` int(11) NOT NULL, PRIMARY KEY (`messageid`,`account`,`ip`), KEY `ip_cmask` (`ip_cmask`), KEY `ip` (`ip`));";

foreach (@$CreateTableSQLs as $tablename => $structure) {
	if (mysql_table_exists(PHPOP3CLEAN_TABLE_PREFIX.$tablename)) {
		$color = 'orange';
		$status = 'already exists, skipping';
	} else {
		$SQLquery = 'CREATE TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.$tablename.'` '.$structure;
		mysql_query($SQLquery);
		if (mysql_error()) {
			$color = 'red';
			$status = 'FAILED!<hr>'.mysql_error().'<hr>';
			$successful = false;
		} else {
			$color = 'green';
			$status = 'success';
		}
	}
	echo '<font color="'.$color.'">CREATE TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.$tablename.'` - '.$status.'</font><br>';
	flush();
	if (!$successful) {
		exit;
	}
}

echo '<hr>';

$RenameTableSQLs['whitelist'] = 'whitelist_email';

foreach (@$RenameTableSQLs as $oldname => $newname) {
	$SQLquery = 'RENAME TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.$oldname.'` TO `'.PHPOP3CLEAN_TABLE_PREFIX.$newname.'`';
	if (mysql_table_exists(PHPOP3CLEAN_TABLE_PREFIX.$newname)) {
		$color = 'orange';
		$status = 'already renamed, skipping';
	} else {
		mysql_query($SQLquery);
		if (mysql_error()) {
			$color = 'red';
			$status = 'FAILED!<hr>'.mysql_error().'<hr>';
			$successful = false;
		} else {
			$color = 'green';
			$status = 'success';
		}
	}
	echo '<font color="'.$color.'">'.$SQLquery.' - '.$status.'</font><br>';
	flush();
	if (!$successful) {
		exit;
	}
}

echo '<hr>';

$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` ADD `full_login` TINYINT DEFAULT \'1\' NOT NULL AFTER `active`';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'image` ADD `height` SMALLINT UNSIGNED NOT NULL AFTER `size`';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'image` ADD `width` SMALLINT UNSIGNED NOT NULL AFTER `size`';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` ADD `scan_interval` TINYINT DEFAULT \'1\' NOT NULL AFTER `active`';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` ADD `last_scanned` INT NOT NULL';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'ips` ADD `whitelist` TINYINT DEFAULT \'0\' NOT NULL AFTER `cmask`';
$UpdateTableSQLs[] = 'REPLACE INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'ips` (`ipmask`, `whitelist`, `cmask`, `added`, `domains`) VALUES (\''.mysql_escape_string(@$_SERVER['SERVER_ADDR'].'/32').'\', 1, \''.mysql_escape_string(substr($_SERVER['SERVER_ADDR'], 0, strrpos($_SERVER['SERVER_ADDR'], '.'))).'\', '.time().', \''.mysql_escape_string(substr($_SERVER['SERVER_ADDR'], strrpos($_SERVER['SERVER_ADDR'], '.') + 1).'|'.@$_SERVER['HTTP_HOST']).'\')';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` ADD `hostname` VARCHAR(100) NOT NULL AFTER `password`';
$UpdateTableSQLs[] =       'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` SET `hostname` = SUBSTRING(`account`, LOCATE(\'@\', `account`) + 1) WHERE (`hostname` = \'\')';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` ADD `use_retr` TINYINT DEFAULT \'1\' NOT NULL AFTER `active`';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` CHANGE `scan_interval` `scan_interval` SMALLINT NOT NULL DEFAULT \'3\'';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'messages` ADD `undeleted` INT DEFAULT \'0\' NOT NULL AFTER `subject`';
$UpdateTableSQLs[] =  'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_recent`';
$domains_recent_indexes = mysql_table_indexes(PHPOP3CLEAN_TABLE_PREFIX.'domains_recent');
if (isset($domains_recent_indexes['PRIMARY'])) {
	$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_recent` DROP PRIMARY KEY';
	$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_recent` ADD PRIMARY KEY (`domain`)';
}
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_recent` ADD `ips` VARCHAR(100) NOT NULL AFTER `domain`';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_email` ADD `lasthit` INT DEFAULT \'0\' NOT NULL AFTER `added`';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_email` ADD `account` varchar(200) NOT NULL default \'\' AFTER `added`';
$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_subject` ADD `account` varchar(200) NOT NULL default \'\' AFTER `added`';

$wordtypetables = array('clean', 'code', 'obfuscated');
foreach ($wordtypetables as $wordtype) {
	$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$wordtype.'` ADD `casesensitive` TINYINT DEFAULT \'0\' NOT NULL AFTER `isregex`';
	$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$wordtype.'` ADD `account` varchar(200) NOT NULL default \'\' AFTER `word`';
	$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$wordtype.'` ADD `category` varchar(255) NOT NULL default \'\' AFTER `hitcount`';
	$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$wordtype.'` ADD `example` text AFTER `description`';
	$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$wordtype.'` ADD `spaces_quantified` TINYINT NOT NULL';
	$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$wordtype.'` ADD `dotall` TINYINT DEFAULT \'1\' NOT NULL';
	if (!mysql_column_exists(PHPOP3CLEAN_TABLE_PREFIX.'words_'.$wordtype, 'id')) {
		$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$wordtype.'` DROP PRIMARY KEY';
		$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$wordtype.'` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST';
	}
	$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$wordtype.'` CHANGE `word` `word` TEXT NOT NULL';
	$UpdateTableSQLs[] =  'ALTER TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'words_'.$wordtype.'` ADD `onlychars` varchar(255) NOT NULL default \'\' AFTER `description`';
}

foreach ($UpdateTableSQLs as $key => $SQLquery) {
	mysql_query($SQLquery);
	$mysql_error = mysql_error();
	if ($mysql_error) {
		if (!mysql_affected_rows() || eregi('^Duplicate column', $mysql_error)) {
			// ignore
			$color = 'orange';
			$status = 'already applied, skipping';
		} else {
			$color = 'red';
			$status = 'FAILED!<blockquote>'.htmlentities($SQLquery).'</blockquote>'.$mysql_error.'<hr>';
		}
	} else {
		$color = 'green';
		$status = 'success';
	}
	echo '<font color="'.$color.'">Table structure update #'.($key + 1).': '.$status.'</font><br>';
	flush();
	if (!$successful) {
		exit;
	}
}


echo '<hr>';

echo '<hr>Database creation/update successful. Continue to <a href="'.PHPOP3CLEAN_ADMINPAGE.'">admin page</a>.';
echo '</body></html>';

?>