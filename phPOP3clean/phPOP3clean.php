<?php
/////////////////////////////////////////////////////////////////
/// phPOP3clean() by James Heinrich <info@silisoftware.com>    //
//  available at http://phpop3clean.sourceforge.net            //
/////////////////////////////////////////////////////////////////

define('DEBUG', false);

if (DEBUG) {
	include_once('mydebug.inc');
	include_once('script_timer.inc.php' );
	$timer = new script_timer();
	$timer->start_timer();
}
require_once('phPOP3clean.functions.php');
require_once('phPOP3.class.php');
define('PHPOP3CLEAN_NONFORCEDLOGIN', true);
require_once('phPOP3clean.login.php');
if (DEBUG) { $timer->set_marker('includes loaded'); }

if (!mysql_table_exists(PHPOP3CLEAN_TABLE_PREFIX.'accounts')) {
	WarningEmail('FAILURE! Failed to select `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts`', 'Failed to select SQL database `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` '.@$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."\n".mysql_error());
	die('Table `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` does not exist. Please run <a href="'.PHPOP3CLEAN_DIRECTORY.'phPOP3clean.install.php">phPOP3clean.install.php</a> first.');
}

///////////////////////////////////////////////////////////////////////////////
// HERE YOU SHOULD VALIDATE ALL REQUEST VARIABLES YOU USE
$_GET['onlyid']    = (isset($_GET['onlyid']) ? ereg_replace('[^0-9<>]', '', $_GET['onlyid']) : false);
$_GET['nocache']   = (bool) @$_GET['nocache'];
$_GET['show']      = (bool) ((@$_GET['show'] === '0') ? false : IsAuthenticatedUser());
$_GET['onlyemail'] = SanitizeEmailAddress(@$_GET['onlyemail']);
if (DEBUG) { $timer->set_marker('validation of input values done'); }
///////////////////////////////////////////////////////////////////////////////

$starttime = getmicrotime();

$BadEmailsFound     = 0;
$GoodEmailsFound    = 0;
$GoodEmailsSkipped  = 0;
$LargeEmailsSkipped = 0;
$WhiteEmailsSkipped = 0;
$nowtime            = time();
$NewMessagesScanned = array();
$Timing				= array();

///////////////////////////////////////////////////////////////////////////////
$timingstart = getmicrotime();


// Prune recent messages table
$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'messages_recent`';
$SQLquery .= ' WHERE (`date` < '.($nowtime - PHPOP3CLEAN_KEEP_RECENT).')';
mysql_query($SQLquery);
if (mysql_affected_rows() > 0) {
	$SQLquery  = 'OPTIMIZE TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'messages_recent`';
	mysql_query($SQLquery);
}
unset($SQLquery);

// Prune recent IPs table
$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_recent`';
$SQLquery .= ' WHERE (`date` < '.($nowtime - PHPOP3CLEAN_KEEP_RECENT_DOM).')';
mysql_query($SQLquery);
if (mysql_affected_rows() > 0) {
	$SQLquery  = 'OPTIMIZE TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_recent`';
	mysql_query($SQLquery);
}
unset($SQLquery);

// Prune recent Messages-IPs table
$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ip_message_recent`';
$SQLquery .= ' WHERE (`date` < '.($nowtime - PHPOP3CLEAN_KEEP_RECENT_IP_MESSAGES).')';
mysql_query($SQLquery);
if (mysql_affected_rows() > 0) {
	$SQLquery  = 'OPTIMIZE TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'ip_message_recent`';
	mysql_query($SQLquery);
}
unset($SQLquery);

// Prune recent Blacklisted IPs table
$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'ips`';
$SQLquery .= ' WHERE (`added` < '.($nowtime - PHPOP3CLEAN_KEEP_IPS).')';
$SQLquery .= ' AND (`whitelist` = "0")';
mysql_query($SQLquery);
if (mysql_affected_rows() > 0) {
	$SQLquery  = 'OPTIMIZE TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'ips`';
	mysql_query($SQLquery);
}
unset($SQLquery);

// Prune Auto-ban domains table
$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_autoban`';
$SQLquery .= ' WHERE (`added` < '.($nowtime - PHPOP3CLEAN_KEEP_IPS).')';
mysql_query($SQLquery);
if (mysql_affected_rows() > 0) {
	$SQLquery  = 'OPTIMIZE TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'domains_autoban`';
	mysql_query($SQLquery);
}
unset($SQLquery);

// Prune virus definitions domains table
$SQLquery  = 'DELETE FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'exe`';
$SQLquery .= ' WHERE ((`added` < '.($nowtime - PHPOP3CLEAN_KEEP_VIRUS).') AND (`lasthit` = 0))';
$SQLquery .= ' OR ((`lasthit` > 0) AND (`lasthit` < '.($nowtime - (2 * PHPOP3CLEAN_KEEP_VIRUS)).'))';
mysql_query($SQLquery);
if (mysql_affected_rows() > 0) {
	$SQLquery  = 'OPTIMIZE TABLE `'.PHPOP3CLEAN_TABLE_PREFIX.'exe`';
	mysql_query($SQLquery);
}
unset($SQLquery);

// Prune recent Blacklisted Words tables
$WordTypes = array('clean', 'obfuscated', 'code');
foreach ($WordTypes as $key => $value) {
	$acceptable_age = constant('PHPOP3CLEAN_KEEP_WORDS_'.strtoupper($value));
	$cutofftime_hit   = $nowtime - $acceptable_age;
	$cutofftime_nohit = $nowtime - round($acceptable_age / 2);
	$tablename = PHPOP3CLEAN_TABLE_PREFIX.'words_'.$value;
	$SQLquery  = 'DELETE FROM `'.$tablename.'`';
	$SQLquery .= ' WHERE ((`added` < '.$cutofftime_hit.')';
	$SQLquery .= ' AND (`lasthit` < '.$cutofftime_hit.'))';
	$SQLquery .= ' OR ((`added` < '.$cutofftime_nohit.')';
	$SQLquery .= ' AND (`lasthit` = 0))';
	mysql_query($SQLquery);
	if (mysql_affected_rows() > 0) {
		$SQLquery  = 'OPTIMIZE TABLE `'.$tablename.'`';
		mysql_query($SQLquery);
	}
	unset($acceptable_age, $cutofftime_hit, $cutofftime_nohit, $tablename, $SQLquery);
}
unset($WordTypes, $key, $value);

if (DEBUG) { $timer->set_marker( 'pruning done' ); }
@$Timing['database_pruning'] += (getmicrotime() - $timingstart);

///////////////////////////////////////////////////////////////////////////////

$SQLquery  = 'SELECT * FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts`';
$SQLquery .= ' WHERE (`active` = 1)';
if (!isset($_GET['nocache']) && !isset($_GET['onlyid']) && !isset($_GET['onlyemail'])) {
	$SQLquery .= ' AND ((`last_scanned` + ('.PHPOP3CLEAN_INTERSCAN_WAIT_PERIOD.' * `scan_interval`)) < '.mysql_escape_string(time()).')';
}
$result = mysql_query($SQLquery);
unset($SQLquery);

$LoginInfo = array();
while ($row = mysql_fetch_assoc($result)) {
	$LoginInfo[] = array(
		'email'      => $row['account'],
		'username'   => substr($row['account'], 0, strpos($row['account'], '@')),
		'hostname'   => $row['hostname'],
		'password'   => $row['password'],
		'port'       => $row['port'],
		'full_login' => $row['full_login'],
		'use_retr'   => $row['use_retr'],
	);
}
@mysql_free_result($result);

if (!is_array($LoginInfo) || count($LoginInfo) === 0) {
	die('ERROR: There are no active accounts in `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts` (that have not already been scanned in the last '.PHPOP3CLEAN_INTERSCAN_WAIT_PERIOD.' seconds)');
}
if (DEBUG) { $timer->set_marker('accounts retrieved from db'); }

$required_login_keys = array('hostname', 'username', 'email');
foreach ($LoginInfo as $LoginInfoArray) {
	if ($_GET['onlyemail'] && ($LoginInfoArray['email'] != $_GET['onlyemail'])) {
		continue;
	}
	foreach ($required_login_keys as $required_login_key) {
		if (!isset($LoginInfoArray[$required_login_key]) || ($LoginInfoArray[$required_login_key] === '')) {
			EchoToScreen('ERROR: $LoginInfoArray['.$required_login_key.'] is blank! skipping account ('.@$LoginInfoArray['email'].')...'."\n\n");
			continue;
		}
	}

	EchoToScreen('Connecting to '.$LoginInfoArray['email'].' ('.gethostbyname($LoginInfoArray['hostname']).':'.$LoginInfoArray['port'].')...'."\n\n");

	$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'accounts`';
	$SQLquery .= ' SET `last_scanned` = '.mysql_escape_string(time()).'';
	$SQLquery .= ' WHERE (`account` = "'.mysql_escape_string($LoginInfoArray['email']).'")';
	mysql_query($SQLquery);
	unset($SQLquery);

	$errno  = '';
	$errstr = '';
	$timingstart = getmicrotime();
	$phPOP3 = new phPOP3($LoginInfoArray['hostname'], $LoginInfoArray['port'], $errno, $errstr, PHPOP3CLEAN_POP3_TIMEOUT, $_GET['show']);
	@$Timing['new_phPOP3'] += (getmicrotime() - $timingstart);
	if (is_resource($phPOP3->fp)) {

		$phPOP3->full_login = (bool) $LoginInfoArray['full_login'];
		$timingstart = getmicrotime();
		$POP3login = $phPOP3->POP3login($LoginInfoArray['email'], $LoginInfoArray['password'], PHPOP3CLEAN_HIDE_PASSWORDS);
		@$Timing['POP3login'] += (getmicrotime() - $timingstart);
		if ($POP3login === false) {

//			WarningEmail('Login failed for '.$LoginInfoArray['email'], 'phPOP3clean - Login failed for '.$LoginInfoArray['username'].':'.$LoginInfoArray['password'].'@'.$LoginInfoArray['hostname'].':'.$LoginInfoArray['port'].' at '.date('F j Y g:i:sa')."\n\n".wordwrap('The most common cause of this is that someone is already logged in to this account, probably the user themselves. If it happens only rarely, ignore this message. If it happens continually then maybe the wrong password is set.'));
			$phPOP3->OutputToScreen('<font color="red">Login failed for '.$LoginInfoArray['username'].(PHPOP3CLEAN_HIDE_PASSWORDS ? '' : ':'.$LoginInfoArray['password']).'@'.$LoginInfoArray['hostname'].':'.$LoginInfoArray['port'].'</font>'."\n");

		} elseif ($POP3login === null) {

			// login timed out?
			$phPOP3->OutputToScreen('<font color="red">Login failed for '.$LoginInfoArray['username'].(PHPOP3CLEAN_HIDE_PASSWORDS ? '' : ':'.$LoginInfoArray['password']).'@'.$LoginInfoArray['hostname'].':'.$LoginInfoArray['port'].' (timed out after '.PHPOP3CLEAN_POP3_TIMEOUT.' seconds)</font>'."\n");

		} else {

			$unprocessed_deletions = MessageDeleteQueueCount($LoginInfoArray['email']);
			$phPOP3->OutputToScreen('<font color="#0000FF">There are '.$unprocessed_deletions.' messages queued for deletion</font>'."\n");
			if ($unprocessed_deletions > 0) {
				// some messages left in the delete queue from a failed previous scan
				MessageDeleteQueueProcess($LoginInfoArray['email']);
				unset($phPOP3);
				$phPOP3 = new phPOP3($LoginInfoArray['hostname'], $LoginInfoArray['port'], $errno, $errstr, 5, $_GET['show']);
				$phPOP3->full_login = (bool) $LoginInfoArray['full_login'];
				$timingstart = getmicrotime();
				$POP3login = $phPOP3->POP3login($LoginInfoArray['email'], $LoginInfoArray['password'], PHPOP3CLEAN_HIDE_PASSWORDS);
				@$Timing['POP3login'] += (getmicrotime() - $timingstart);
				if (($POP3login === null) || ($POP3login === false)) {
					// failed to log back in, sleep until next scan
					continue;
				}
			}
			unset($unprocessed_deletions);

			$timingstart = getmicrotime();
			$STAT = $phPOP3->POP3stat();
			@$Timing['POP3stat'] += (getmicrotime() - $timingstart);

			if (is_array($STAT) && (count($STAT) === 2)) {
				$phPOP3->OutputToScreen('<font color="#0000FF">There are '.$STAT[0].' messages</font>'."\n\n");
				$phPOP3->POP3getMessageNumFromUID(null); // initialize $phPOP3->UIDcache
				for ($i = $STAT[0]; $i >= 1; $i--) {

					if (ereg('\<([0-9]+)', $_GET['onlyid'], $matches)) {
						if ($i >= $matches[1]) {
							continue;
						}
					} elseif (ereg('\>([0-9]+)', $_GET['onlyid'], $matches)) {
						if ($i <= $matches[1]) {
							continue;
						}
					} elseif (is_numeric($_GET['onlyid']) && ($i != $_GET['onlyid'])) {
						continue;
					}

					set_time_limit(PHPOP3CLEAN_PHP_TIMEOUT);
					$DebugMessages = array();
					$ThisIsBad = false;

					// skip previously scanned
					$timingstart = getmicrotime();
					if (!$_GET['nocache'] && is_array($phPOP3->UIDcache)) {
						$cached_MessageID = array_search($i, $phPOP3->UIDcache);
						if ($cached_MessageID !== false) {
							$SQLquery  = 'SELECT `messageid` FROM `'.PHPOP3CLEAN_TABLE_PREFIX.'messages_scanned`';
							$SQLquery .= ' WHERE (`messageid` = "'.mysql_escape_string($cached_MessageID).'")';
							$SQLquery .= ' AND (`account` = "'.mysql_escape_string($LoginInfoArray['email']).'")';
							$result = mysql_query($SQLquery);
							unset($SQLquery);

							if (mysql_num_rows($result) > 0) {
								// this email has already been scanned, skip it
								$GoodEmailsSkipped++;
								$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Skipping message ID "'.$cached_MessageID.'" because is in `'.PHPOP3CLEAN_TABLE_PREFIX.'messages_scanned` cache';
								$phPOP3->OutputToScreen('<font color="#0000FF">skipping message #'.$i.' (id "'.$cached_MessageID.'") - previously scanned</font><br><br>');
								unset($cached_MessageID, $messageSize);
								@mysql_free_result($result);
								@$Timing['SkipOld'] += (getmicrotime() - $timingstart);
								continue;
							}
							@mysql_free_result($result);
						}
					}
					@$Timing['SkipOld'] += (getmicrotime() - $timingstart);

					$timingstart = getmicrotime();
					$MessageID = $phPOP3->POP3getMessageID($i);
					@$Timing['POP3getMessageID'] += (getmicrotime() - $timingstart);
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Message #'.$i.' for account '.$LoginInfoArray['email'].' has message ID "'.$MessageID.'"';

					$timingstart = getmicrotime();
					$messageSize = $phPOP3->POP3getMessageSize($i);
					@$Timing['POP3getMessageSize'] += (getmicrotime() - $timingstart);


					$timingstart = getmicrotime();
					$header = $phPOP3->POP3getMessageHeader($i);
					@$Timing['POP3getMessageHeader'] += (getmicrotime() - $timingstart);
					$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] $phPOP3->POP3getMessageHeader() returned '.strlen($header).' byte header';
					$ParsedHeader = POP3parseheader($header, $LoginInfoArray['email']);

					InsertSpamScanned($LoginInfoArray['email'], $MessageID, @$ParsedHeader['from'][0], @$ParsedHeader['subject'][0], $nowtime);


					if (isset($ParsedHeader['to'][0]) && (strpos($ParsedHeader['to'][0], $LoginInfoArray['email']) === false)) {
						$phPOP3->OutputToScreen('<font color="#0000FF">Header[To]:</font>      [<font color="navy">'.htmlentities(@$ParsedHeader['to'][0]).'</font>]'."\n");
					}
					$phPOP3->OutputToScreen('<font color="#0000FF">Header[From]:</font>        [<font color="navy">'.htmlentities(@$ParsedHeader['from'][0]).'</font>]'."\n");
					$phPOP3->OutputToScreen('<font color="#0000FF">Header[Subject]:</font>     [<font color="navy">'.htmlentities(@$ParsedHeader['subject'][0]).'</font>]'."\n");
					$phPOP3->OutputToScreen('<font color="#0000FF">Header[Date]:</font>        [<font color="navy">'.htmlentities(@$ParsedHeader['date'][0]).'</font>]'."\n");
					$phPOP3->OutputToScreen('<font color="#0000FF">Header[Message-ID]:</font>  [<font color="navy">'.htmlentities(@$ParsedHeader['message-id'][0]).'</font>]'."\n");
					$phPOP3->OutputToScreen('<font color="#0000FF">Header[Return-Path]:</font> [<font color="navy">'.htmlentities(@$ParsedHeader['return-path'][0]).'</font>]'."\n");

					if (!@$_GET['skipwhitelist']) {
						// sender email whitelist
						$timingstart = getmicrotime();
						$WhitelistEmailKeys = array('from', 'return-path');
						foreach ($WhitelistEmailKeys as $key) {
							$matchedEmail = IsWhiteListedEmail(@$ParsedHeader[$key][0], $LoginInfoArray['email']);
							if ($matchedEmail) {
								$WhiteEmailsSkipped++;
								$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Skipping message #'.$i.' (id "'.$MessageID.'") because $ParsedHeader['.$key.'] is in `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_email` (['.$matchedEmail.'] matches ['.$ParsedHeader[$key][0].'])';
								$phPOP3->OutputToScreen('<font color="#0000FF">skipping message #'.$i.' (id "'.$MessageID.'") - $ParsedHeader['.$key.'] is in `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_email` (['.htmlentities($matchedEmail.'] matches ['.$ParsedHeader[$key][0]).'])</font><br><br>');

								IncrementHistory($LoginInfoArray['email'], 'good');
								InsertRecent($LoginInfoArray['email'], $MessageID, $header, '', $DebugMessages);

								unset($SQLquery, $MessageID, $messageSize, $header, $ParsedHeader, $key, $matchedEmail);
								@$Timing['IsWhiteListedEmail'] += (getmicrotime() - $timingstart);
								continue 2;
							}
							unset($matchedEmail);
							$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] "'.ExtractActualEmailAddress(@$ParsedHeader[$key][0]).'" not whitelisted for account "'.$LoginInfoArray['email'].'"';
						}
						unset($key);
						@$Timing['IsWhiteListedEmail'] += (getmicrotime() - $timingstart);
					}


					// subject word whitelist
					$timingstart = getmicrotime();
					if (isset($ParsedHeader['subject'][0]) && ($word = IsWhiteListedSubject($ParsedHeader['subject'][0]))) {
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Skipping message #'.$i.' (id "'.$MessageID.'") because $ParsedHeader[subject] ('.$word.') is in `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_subject`';
						$phPOP3->OutputToScreen('<font color="#0000FF">skipping message #'.$i.' (id "'.$MessageID.'") - $ParsedHeader[subject] ('.htmlentities($word).') is in `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_subject`</font><br><br>');

						//InsertSpamScanned($LoginInfoArray['email'], $MessageID, @$ParsedHeader['from'][0], @$ParsedHeader['subject'][0], $nowtime);
						IncrementHistory($LoginInfoArray['email'], 'good');
						InsertRecent($LoginInfoArray['email'], $MessageID, $header, '', $DebugMessages);

						$SQLquery  = 'UPDATE `'.PHPOP3CLEAN_TABLE_PREFIX.'whitelist_subject`';
						$SQLquery .= ' SET `lasthit` = "'.$nowtime.'"';
						$SQLquery .= ', `hitcount` = `hitcount` + 1';
						$SQLquery .= ' WHERE `word` = "'.mysql_escape_string($word).'"';
						mysql_query($SQLquery);
						unset($SQLquery);
						unset($MessageID, $messageSize, $header, $ParsedHeader, $word);
						@$Timing['IsWhiteListedSubject'] += (getmicrotime() - $timingstart);
						continue;
					}
					unset($word);
					@$Timing['IsWhiteListedSubject'] += (getmicrotime() - $timingstart);


					// header SpamAssassin
					if ($ThisIsBad !== true) {
						if (PHPOP3CLEAN_USE_SPAMASSASSIN === true) {
							$timingstart = getmicrotime();
							if (isset($ParsedHeader['x-spam-status'][0])) {
								$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] $ParsedHeader[x-spam-status][0] = "'.$ParsedHeader['x-spam-status'][0].'"';
								if (preg_match(preg_expression('^Yes, (hits|score)=([0-9\\.]+) required=([0-9\\.]+)', 'i'), $ParsedHeader['x-spam-status'][0], $matches)) {
									if (($matches[2] - PHPOP3CLEAN_SPAMASSASSIN_VALUE) >= $matches[3]) {
										$phPOP3->OutputToScreen('<font color="red">SpamAssassin says IS spam</font><br>');
										$ThisIsBad = true;
										$WhyItsBad = 'SpamAssassin score '.$matches[2].' out of '.$matches[3];
									} else {
										$phPOP3->OutputToScreen('<font color="purple">SpamAssassin says IS spam ('.$matches[2].'/'.$matches[3].'), but does not exceed threshold by more than "'.PHPOP3CLEAN_SPAMASSASSIN_VALUE.'"</font><br>');
									}
								} else {
									$phPOP3->OutputToScreen('<font color="green">SpamAssassin says is not spam</font><br>');
								}
								unset($matches);
							}
							@$Timing['SpamAssassin'] += (getmicrotime() - $timingstart);
						}
					}
					// end header SpamAssassin


					// header DNSBL
					if ($ThisIsBad !== true) {
						$timingstart = getmicrotime();
						$EmailHeaderKeys = array('from', 'return-path', 'received');
						$ResolvedDomains = array();
						$matched_ips     = array();
						$matched_domains = array();
						foreach ($EmailHeaderKeys as $header_key) {
							if (isset($ParsedHeader[$header_key]) && is_array($ParsedHeader[$header_key])) {
								foreach ($ParsedHeader[$header_key] as $key => $value) {
									preg_match_all('/[^0-9]([0-9]{1,3}(\.[0-9]{1,3}){3})[^0-9]/', strtolower(@$ParsedHeader[$header_key][$key]), $matches_ip,  PREG_PATTERN_ORDER);
									preg_match_all('/@([a-z0-9\-\.]+)/',                          strtolower(@$ParsedHeader[$header_key][$key]), $matches_dom, PREG_PATTERN_ORDER);
									$matched_ips     = array_merge($matched_ips,     $matches_ip[1]);
									$matched_domains = array_merge($matched_domains, $matches_dom[1]);
								}
							}
						}
						//$ResolvedDomains['IP'] = array_unique($matched_ips);
						$matched_ips = array_unique($matched_ips);
						foreach ($matched_ips as $ip) {
							@$ResolvedDomains[$ip][] = $ip;
						}
						$matched_domains = array_unique($matched_domains);
						foreach ($matched_domains as $matched_domain) {
							$resolved_ips = SafeGetHostByNameL($matched_domain);
							if (is_array($resolved_ips)) {
								if (isset($ResolvedDomains[$matched_domain])) {
									$ResolvedDomains[$matched_domain] = array_unique(array_merge($ResolvedDomains[$matched_domain], $resolved_ips));
								} else {
									$ResolvedDomains[$matched_domain] = $resolved_ips;
								}
							}
						}
						$ResolvedDomains = array_unique($ResolvedDomains);

						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] DNSBL looking up domains from headers ('.implode(';', array_keys($ResolvedDomains)).')';
						$examined_ips = array();
						foreach ($ResolvedDomains as $domain => $IPs) {
							foreach ($IPs as $IP) {
								RecentMessageIPid($LoginInfoArray['email'], $MessageID, $IP);

								$examined_ips[] = $IP;
								$DNSBL_IPs = DNSBLlookup($IP);
								if (is_array($DNSBL_IPs) && (count($DNSBL_IPs) > 0)) {
									$phPOP3->OutputToScreen('<font color="red">found DNSBL-listed IP in headers: ('.$domain.':'.$IP.')</font>'."\n");
									BanIP($IP);
									$ThisIsBad = true;
									$WhyItsBad = 'DNSBL IP in header ('.$domain.':'.$IP.')';
									$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: DNSBL IP in headers ('.$domain.':'.implode(';', $IPs).')';
									break 2;
								}
							}
						}
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] DNSBL examined IPs ('.implode(';', array_unique($examined_ips)).')';

						if ($ThisIsBad !== true) {
							$BannedIPdomains = BlackListedDomainIP($ResolvedDomains);
							if (is_array($BannedIPdomains) && (count($BannedIPdomains) > 0)) {
								$bad_IPs_found = array_keys($BannedIPdomains);
								foreach ($bad_IPs_found as $bad_IP_found) {
									DeleteOldMessagesWithIP($bad_IP_found);
								}
								$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] found: Banned IP in headers ('.implode(';', $bad_IPs_found).')';
								EchoToScreen('<font color="red">found banned IP in headers ('.implode(';', $bad_IPs_found).')</font>'."\n");
								$ThisIsBad = true;
								$WhyItsBad = 'Banned IP in headers ('.implode(';', $bad_IPs_found).')';
							}
						}

						@$Timing['DNSBL_header'] += (getmicrotime() - $timingstart);
					}
					// end header DNSBL


					if (($messageSize < PHPOP3CLEAN_MAX_MESSAGE_SIZE) || ($ThisIsBad === true)) {
						// retrieve message contents so that it can be analyzed, or
						// qurantined if header-DNSBL or header-SpamAssassin already
						// flagged message as spam

						$timingstart = getmicrotime();
						$MessageContents = $phPOP3->POP3getMessageContents($i, $LoginInfoArray['use_retr']);
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] $phPOP3->POP3getMessageContents() returned '.strlen($MessageContents).' byte $MessageContents';
						$MessageContents = str_replace(trim($header), '', $MessageContents);
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] $MessageContents reduced to '.strlen($MessageContents).' bytes after stripping out header';
						@$Timing['POP3getMessageContents'] += (getmicrotime() - $timingstart);

						$timingstart = getmicrotime();
						if (!$phPOP3->POP3noop()) {
							$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] SERVER CONNECTION LOST!';

							$phPOP3->OutputToScreen('<hr><font color="red">SERVER CONNECTION LOST!</font><hr>');

							// the previous operation probably disconnected the server connection
							WarningEmail('$phPOP3->POP3getMessageContents() aborted connection ['.((!isset($ParsedHeader['date']) || ($ParsedHeader['date'] === '')) ? 'missing' : 'valid').' date] ('.$LoginInfoArray['email'].' #'.$i.')', $LoginInfoArray['email']."\n".'Message #'.$i."\n\n".$header.str_repeat('~', 60)."\n\n".$MessageContents);

							// don't scan this one next time
							//InsertSpamScanned($LoginInfoArray['email'], $MessageID, @$ParsedHeader['from'][0], @$ParsedHeader['subject'][0], $nowtime);

							// Abort script and let next run through skip this message.
							exit;
						}
						@$Timing['POP3noop'] += (getmicrotime() - $timingstart);
						$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] Server connection NOT lost on message retrieval';

						$phPOP3->OutputToScreen('<font color="#0000FF">Size:</font> [<font color="navy">'.number_format($messageSize).' bytes</font>]'."\n");
					}


					if ($ThisIsBad !== true) {
						$MessageContents = $phPOP3->POP3getMessageContents($i, $LoginInfoArray['use_retr']);
						$ThisIsBad = ExamineMessageContents($header, $MessageContents, $WhyItsBad, $DebugMessages, $LoginInfoArray['email']);
					}
					if ($ThisIsBad === true) {
						$phPOP3->OutputToScreen('<font color="red">'.htmlentities($WhyItsBad).'</font>'."\n");
					}


					if (($ThisIsBad !== true) && (!isset($ParsedHeader['date']) || $ParsedHeader['date'] === '') && PHPOP3CLEAN_USE_INVALID_DATESTAMP) {
						$timingstart = getmicrotime();
						if (strlen(trim($MessageContents)) == 0) {
							$phPOP3->OutputToScreen('<font color="red">Date is NOT OK</font>'."\n");
							$ThisIsBad = true;
							$WhyItsBad = 'Invalid or missing Datestamp';
							//WarningEmail('empty date with empty message', $LoginInfoArray['email']."\n".'Message #'.$i."\n".$MessageID."\n\n".$phPOP3->POP3getMessageContents($i));
							$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] BAD: empty date with empty message';
						} else {
							$DebugMessages[] = '['.basename(__FILE__).'.'.__LINE__.'] empty date with non-empty message';
							//WarningEmail('empty date with non-empty message', $LoginInfoArray['email']."\n".'Message #'.$i."\n\n".$phPOP3->POP3getMessageContents($i));
						}
						@$Timing['header_date'] += (getmicrotime() - $timingstart);
					}

					if ($ThisIsBad !== true) {

						//InsertSpamScanned($LoginInfoArray['email'], $MessageID, @$ParsedHeader['from'][0], @$ParsedHeader['subject'][0], $nowtime);

						$phPOP3->OutputToScreen('<font color="green">Message is OK</font>'."\n\n");
						@$NewMessagesScanned[$LoginInfoArray['email']][] = array('id'=>$MessageID, 'subject'=>@$ParsedHeader['subject'][0], 'from'=>@$ParsedHeader['from'][0]);

						IncrementHistory($LoginInfoArray['email'], 'good');
						InsertRecent($LoginInfoArray['email'], $MessageID, $header, $MessageContents, $DebugMessages);

						//BOXCAR
						if (defined('PHPOP3CLEAN_BOXCAR_EMAIL')) {
							$headers = "From: ".@$ParsedHeader['from'][0] . "\r\n";
							mail(PHPOP3CLEAN_BOXCAR_EMAIL, str_replace("_"," ",@$ParsedHeader['subject'][0]) , "Contents", $headers);
						}

					} else {

						$delete = true; // failsafe check in case quarantining fails

						if (eregi('^(Infected|Zipped|Illegal)+ Attachment', $WhyItsBad)) {
							IncrementHistory($LoginInfoArray['email'], 'virus');
						} elseif (eregi('^(Invalid or missing Datestamp|truncated header|HTML in headers|Corrupt Message-Id header)', $WhyItsBad)) {
							IncrementHistory($LoginInfoArray['email'], 'corrupt');
						} else {
							IncrementHistory($LoginInfoArray['email'], 'spam');
						}

						$SQLquery  = 'INSERT INTO `'.PHPOP3CLEAN_TABLE_PREFIX.'messages` (`id`, `date`, `account`, `reason`, `subject`) VALUES (';
						$SQLquery .= '"'.mysql_escape_string($MessageID).'", ';
						$SQLquery .= '"'.mysql_escape_string($nowtime).'", ';
						$SQLquery .= '"'.mysql_escape_string($LoginInfoArray['email']).'", ';
						$SQLquery .= '"'.mysql_escape_string($WhyItsBad).'", ';
						$SQLquery .= '"'.mysql_escape_string(@$ParsedHeader['subject'][0]).'")';

						$MessageContentsFilename = PHPOP3CLEAN_QUARANTINE.date('Ym', $nowtime).'/'.eregi_replace('[^a-z0-9'.preg_quote('!@#$%^&()_+=[]{};\',.').']', '_', $MessageID).'.gz';
						if (mysql_query($SQLquery)) {
							if (!is_dir(dirname($MessageContentsFilename))) {
								if (@mkdir(dirname($MessageContentsFilename))) {
									if (chmod(dirname($MessageContentsFilename), 0777)) {
										// excellent
									} else {
										WarningEmail('phPOP3clean quarantine failure', __FILE__.' line:'.__LINE__."\n\n".'FAILED: chmod('.dirname($MessageContentsFilename).', 0777)');
									}
								} else {
									WarningEmail('phPOP3clean quarantine failure', __FILE__.' line:'.__LINE__."\n\n".'FAILED: mkdir('.dirname($MessageContentsFilename).')');
								}
							}
							if (is_writable(dirname($MessageContentsFilename))) {
								if ($zp = @gzopen($MessageContentsFilename, 'wb')) {
									gzwrite($zp, $header.$MessageContents);
									gzclose($zp);
									touch($MessageContentsFilename);
									if (!chmod($MessageContentsFilename, 0777)) {
										WarningEmail('chmod($MessageContentsFilename, 0777) failed', 'failed:'."\n".'chmod('.$MessageContentsFilename.', 0777)');
									}
								} else {
									WarningEmail('gzopen() failed', __FILE__.' line:'.__LINE__."\n\n".'failed:'."\n".'gzopen('.$MessageContentsFilename.', wb)');
									$delete = false;
								}
							} else {
								WarningEmail('quarantine directory non-writable', __FILE__.' line:'.__LINE__."\n\n".'!is_writable():'."\n".dirname($MessageContentsFilename));
								$delete = false;
							}
						} else {
							$errormessage = mysql_error();
							if (!preg_match(preg_expression('^Duplicate entry', 'i'), $errormessage)) {
								WarningEmail('SQL failed', $SQLquery."\n\n".$errormessage);
							}
						}
						unset($SQLquery, $MessageContentsFilename);

						$BadEmailsFound++;
						$phPOP3->OutputToScreen('<font color="red"><b>Message queued for deletion</b> ['.htmlentities($MessageID).'] ('.htmlentities($WhyItsBad).')</font>'."\n");
						if ($delete === true) {
							MessageQueueForDelete($LoginInfoArray['email'], $MessageID);
							$phPOP3->OutputToScreen('<font color="#FF4500">There are now '.MessageDeleteQueueCount($LoginInfoArray['email']).' messages queued for deletion</font>'."\n\n");
						}
						unset($delete);
					}
					unset($DebugMessages, $MessageID, $messageSize, $header, $ParsedHeader, $ThisIsBad, $WhyItsBad, $MessageContents);
				}
				unset($i);

			} else {

				$phPOP3->OutputToScreen('<font color="red">STAT failed</font>'."\n");

			}
			unset($STAT);

			$timingstart = getmicrotime();
			$phPOP3->OutputToScreen('<font color="#0000FF">Processing message delete queue ('.MessageDeleteQueueCount($LoginInfoArray['email']).' emails to be deleted)</font>'."\n");
			MessageDeleteQueueProcess($LoginInfoArray['email']);
			@$Timing['MessageDeleteQueueProcess'] += (getmicrotime() - $timingstart);
			unset($POP3login);
		}

	} else {

		EchoToScreen('<font color="red">failed to open "'.$LoginInfoArray['hostname'].':'.$LoginInfoArray['port'].'"  --  '.$errno.' - '.$errstr.')</font>');

	}
	unset($phPOP3, $errno, $errstr);
	EchoToScreen('<hr>');
}
unset($LoginInfo, $LoginInfoArray);

if (is_array($NewMessagesScanned) && (count($NewMessagesScanned) > 0)) {
	foreach ($NewMessagesScanned as $NewMessageAccount => $NewMessageAccountArray) {
		$GoodEmailsFound += count($NewMessageAccountArray);
	}
	unset($NewMessageAccount, $NewMessageAccountArray);
}


EchoToScreen('');

$endtime = getmicrotime();
$totalProcessingTime = $endtime - $starttime;

echo 'phPOP3clean v'.PHPOP3CLEAN_VERSION.'<br>';
echo 'Finished processing in '.number_format($totalProcessingTime, 3).' seconds<br>';
echo 'Scan ended at '.date('F j Y g:i:sa').'<br>';
echo '<table border="0">';
echo '<tr><td><b>Messages Scanned:</b></td><td align="right"><b>'.number_format($BadEmailsFound + $GoodEmailsFound + $GoodEmailsSkipped + $LargeEmailsSkipped + $WhiteEmailsSkipped).'</b></td><td>&nbsp;</td></tr>';
echo '<tr><td>New bad:</td><td align="right">'.number_format($BadEmailsFound).'</td><td>(deleted)</td></tr>';
echo '<tr><td>New good:</td><td align="right">'.number_format($GoodEmailsFound).'</td><td>&nbsp;</td></tr>';
echo '<tr><td>Old good:</td><td align="right">'.number_format($GoodEmailsSkipped).'</td><td>(skipped)</td></tr>';
echo '<tr><td>Skipped (too large):</td><td align="right">'.number_format($LargeEmailsSkipped).'</td><td>(skipped)</td></tr>';
echo '<tr><td>Skipped (whitelist):</td><td align="right">'.number_format($WhiteEmailsSkipped).'</td><td>(skipped)</td></tr>';
echo '</table>';
if (is_array($Timing) && (count($Timing) > 0)) {
	arsort($Timing);
	echo 'Timing:<ul>';
	foreach ($Timing as $key => $value) {
		echo '<li>'.$key.' = '.number_format($value, 3).'</li>';
	}
	unset($key, $value);
	echo '</ul>';
	echo number_format($totalProcessingTime - array_sum($Timing), 3).' seconds unaccounted for.<br>';
}

if (DEBUG) { $timer->get_output(true, 4); }

echo '</body></html>';

?>