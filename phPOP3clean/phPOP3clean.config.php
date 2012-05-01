<?php
/////////////////////////////////////////////////////////////////
/// phPOP3clean() by James Heinrich <info@silisoftware.com>    //
//  available at http://phpop3clean.sourceforge.net            //
/////////////////////////////////////////////////////////////////

// You MUST modify these configuration values:
define('PHPOP3CLEAN_ADMINEMAIL', '');      // Your email address (warnings and notices will be sent here)
define('PHPOP3CLEAN_ADMINPASS',  '');      // Admin-level login password for administration section (username="admin"). Please make this hard to guess.

define('PHPOP3CLEAN_DBHOST', 'localhost'); // MySQL hostname (default = 'localhost' which is usually fine)
define('PHPOP3CLEAN_DBUSER', '');          // MySQL username
define('PHPOP3CLEAN_DBPASS', '');          // MySQL password
define('PHPOP3CLEAN_DBNAME', '');          // MySQL database
// end MUST-modify

// You MAY need to modify these settings for server compatability
define('PHPOP3CLEAN_DOCUMENT_ROOT', getcwd()); // some server may not set this correctly, should be set to something like "/home/httpd/vhosts/example.com/httpdocs"
//define('PHPOP3CLEAN_DOCUMENT_ROOT', '/var/www/vhosts/example.com/httpdocs/'); // some server may not set this correctly, should be set to something like "/home/httpd/vhosts/example.com/httpdocs"

define('PHPOP3CLEAN_ADMINPAGE', 'http://'.$_SERVER['HTTP_HOST'].'/mail/phPOP3clean.admin.php');      // web-absolute path (including http:// and domain)
// end MAY-compatability

// You MAY modify these values if you choose
define('PHPOP3CLEAN_MAX_MESSAGE_SIZE',      0);  // emails larger than this (in bytes) will be skipped (default =   1MB). Set to 0 for no limit.
define('PHPOP3CLEAN_MAX_BODY_SIZE',          0);  // emails larger than this (in bytes) will be skipped (default = 200kB). Set to 0 for no limit.

define('PHPOP3CLEAN_USE_SPAMASSASSIN',             true);  // if true, look for X-Spam-Status header and delete message if SpamAssassin calls it spam
define('PHPOP3CLEAN_SPAMASSASSIN_VALUE',            0.0);  // SpamAssassin hits must exceed required value by this amount (default 0.0) to be deleted

define('PHPOP3CLEAN_USE_DNSBL',                    true);  // if true, IPs found in emails are matched against a DNS Black List
define('PHPOP3CLEAN_DNSBL_ZONE', 'sbl-xbl.spamhaus.org');  // zone on which to perform DNSBL lookups [suggestions: sbl-xbl.spamhaus.org, zen.spamhaus.org, dnsbl.sorbs.net, etc]

define('PHPOP3CLEAN_USE_TRUNCATED_HEADER',        false);  // if true, messages that appear to have truncated headers will be deleted
define('PHPOP3CLEAN_USE_INVALID_DATESTAMP',       false);  // if true, messages that appear to have invalid datestamps will be deleted

define('PHPOP3CLEAN_PREG_DELIMIT',                  '/');  // delimiter for preg_match expressions
define('PHPOP3CLEAN_TABLE_PREFIX',       'phpop3clean_');  // prefix for MySQL tables
define('PHPOP3CLEAN_HIDE_PASSWORDS',               true);  // if true, obfuscate passwords in scan output

define('PHPOP3CLEAN_INTERSCAN_WAIT_PERIOD',          60);  // phPOP3clean will not rescan accounts any more frequently than this (default = 60 seconds)
define('PHPOP3CLEAN_PHP_TIMEOUT',                    30);  // PHP timeout for scripts (default = 30 seconds)
define('PHPOP3CLEAN_DNS_TIMEOUT',                     2);  // timeout for DNS lookups (default = 2 seconds)
define('PHPOP3CLEAN_POP3_TIMEOUT',                   10);  // POP3 login timeout (default = 10 seconds)
define('PHPOP3CLEAN_KEEP_RECENT',             2 * 86400);  // how long to keep recent messages in database for review (default = 2 days)
define('PHPOP3CLEAN_KEEP_RECENT_DOM',         1 * 86400);  // how long to keep recent  domains in database for review (default = 1 day)
define('PHPOP3CLEAN_KEEP_RECENT_IP_MESSAGES', 1 * 86400);  // how long to keep recent messages-vs-IPs in database for potential later deletion if later found to be in DNSBL (default = 1 day)
define('PHPOP3CLEAN_KEEP_DOMAIN_HITS',       30 * 86400);  // how long to recently seen in database for review (default = 30 days)
define('PHPOP3CLEAN_KEEP_MESSAGES_SCANNED',  30 * 86400);  // how long to scanned messages in database to prevent rescanning (default = 30 days)
define('PHPOP3CLEAN_KEEP_MESSAGES',          30 * 86400);  // how long to keep recent messages in database for review (default = 30 days)
define('PHPOP3CLEAN_KEEP_IMAGE',             30 * 86400);  // how long to keep attached images blacklist. These take up a lot of space, so purge if no longer catching anything (default = 30 days)
define('PHPOP3CLEAN_KEEP_VIRUS',            180 * 86400);  // how long to keep virus definitions that have not caught anything (default = 180 days)
define('PHPOP3CLEAN_KEEP_IPS',               30 * 86400);  // how long to keep recent IPs in blacklist. It's good to expire IP blacklists after a while to prevent false positives. (default = 14, should be more like 60 days if DNSBL is disabled)
define('PHPOP3CLEAN_KEEP_WORDS_CLEAN',       60 * 86400);  // how long to keep "clean"      word blacklists. It's good to expire word blacklists after a while for performance reasons. Entries that haven't been matched in this long will be purged (default = 60 days). Entries that have never been matched will be purged after HALF this time.
define('PHPOP3CLEAN_KEEP_WORDS_OBFUSCATED',  60 * 86400);  // how long to keep "obfuscated" word blacklists. It's good to expire word blacklists after a while for performance reasons. Entries that haven't been matched in this long will be purged (default = 60 days). Entries that have never been matched will be purged after HALF this time.
define('PHPOP3CLEAN_KEEP_WORDS_CODE',        60 * 86400);  // how long to keep "code"       word blacklists. It's good to expire word blacklists after a while for performance reasons. Entries that haven't been matched in this long will be purged (default = 60 days). Entries that have never been matched will be purged after HALF this time.

if (!defined('PHPOP3CLEAN_DIRECTORY')) {
	define('PHPOP3CLEAN_DIRECTORY', './phPOP3clean/');     // relative to current directory, requires trailing slash. May already be defined in phPOP3clean.admin.php, and this value should be set to the same as that value
}
define('PHPOP3CLEAN_QUARANTINE',      rtrim(PHPOP3CLEAN_DOCUMENT_ROOT, '/\\').'/quarantine/');    // absolute path, must have trailing slash
define('PHPOP3CLEAN_MD5_IMAGE_CACHE', rtrim(PHPOP3CLEAN_DOCUMENT_ROOT, '/\\').'/md5imagecache/'); // absolute path, must have trailing slash

define('PHPOP3CLEAN_COL_BLIST',        'FF0000');  // admin section, color of blacklisted IPs
define('PHPOP3CLEAN_COL_WLIST',        '00FFFF');  // admin section, color of whitelisted IPs
define('PHPOP3CLEAN_COL_OK',           '00FF00');  // admin section, color of undefined/neutral IPs
define('PHPOP3CLEAN_RECENT_HIST_SCALING_X',  10);  // horizontal scaling factor for recent messages histogram (default = 10)
define('PHPOP3CLEAN_RECENT_HIST_SCALING_Y', 500);  // vertical scaling factor for recent messages histogram (default = 500)

define('PHPOP3CLEAN_BOXCAR_EMAIL', '0f3a3e.1598065@push.boxcar.io');  // vertical scaling factor for recent messages histogram (default = 500)
// end MAY-modify

// you SHOULD NOT modify these values:
define('PHPOP3CLEAN_CONFIG_VERSION', '200804170000');
// end SHOULD NOT
?>