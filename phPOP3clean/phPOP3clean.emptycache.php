<?php
require_once(dirname(__FILE__).'/phPOP3clean.config.php');
require_once(dirname(__FILE__).'/phPOP3clean.login.php');
if (!IsAdminUser()) {
	echo 'You do not have permission to use this file';
	exit;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>phPOP3clean :: Archive and purge quarantined messages</title>
	<style type="text/css">
		BODY,TD,TH {
			font-family: sans-serif;
			font-size: 9pt;
		}
	</style>
</head>
<body>
<?php
/////////////////////////////////////////////////////////////////
/// phPOP3clean() by James Heinrich <info@silisoftware.com>    //
//  available at http://phpop3clean.sourceforge.net            //
/////////////////////////////////////////////////////////////////

$yearmonth = (ereg('^[0-9]{6}$', @$_GET['go']) ? $_GET['go'] : '');
$totalfiles  = 0;
$filecounter = 0;
$tarcounter  = -1;
$filesintar  = 1000;
$starttime = time();
if ($yearmonth && is_dir(PHPOP3CLEAN_QUARANTINE.$yearmonth.'/')) {
	if ($dh = opendir(PHPOP3CLEAN_QUARANTINE.$yearmonth.'/')) {
		while (($file = readdir($dh)) !== false) {
			if (is_file(PHPOP3CLEAN_QUARANTINE.$yearmonth.'/'.$file)) {
				$totalfiles++;
			}
		}
		rewinddir($dh);
		echo '<hr>0 / '.number_format($totalfiles).' [0.0% done]<hr>';

		while (($file = readdir($dh)) !== false) {
			if (is_file(PHPOP3CLEAN_QUARANTINE.$yearmonth.'/'.$file)) {
				if (($filecounter % $filesintar) === 0) {
					set_time_limit(300);
					$pct_done = (($tarcounter * $filesintar) + $filecounter) / $totalfiles;
					if ($pct_done > 0) {
						$timeleft = (1 - $pct_done) * ((time() - $starttime) / $pct_done);
					}
					$tarcounter++;
					$filecounter = 0;

					$command = 'cd "'.PHPOP3CLEAN_QUARANTINE.'" && tar -cf "'.$yearmonth.'_'.$tarcounter.'.tar" "'.$yearmonth.'/'.$file.'"';
					echo '<hr>';
					echo number_format(($tarcounter * $filesintar) + $filecounter).' / '.number_format($totalfiles);
					echo (($pct_done > 0)  ? ' ['.number_format($pct_done * 100, 1).'% done]'             : '');
					echo (isset($timeleft) ? ' ('.number_format($timeleft /  60, 1).' minutes remaining)' : '');
					echo '<br>'.$command.'<hr>';
				} else {
					$command = 'cd "'.PHPOP3CLEAN_QUARANTINE.'" && tar -rf "'.$yearmonth.'_'.$tarcounter.'.tar" "'.$yearmonth.'/'.$file.'"';
					echo '. ';
				}
				$filecounter++;
				echo `$command`;
				flush();
			}
		}
		for ($i = 0; $i <= $tarcounter; $i++) {
			$tarfile = PHPOP3CLEAN_QUARANTINE.$yearmonth.'_'.$i.'.tar';
			if (file_exists($tarfile)) {
				chmod($tarfile, 0666);
			}
		}
		closedir($dh);
	}
	$deleCommand = 'rm -dvrf "'.PHPOP3CLEAN_QUARANTINE.$yearmonth.'"';
	$deleteCommandOutput = `$deleCommand`;
	echo '<hr><b>'.$deleCommand.'</b><hr>';
	echo '<textarea rows="10" cols="100" wrap="off">'.htmlentities($deleteCommandOutput).'</textarea><hr>';
	echo 'Processed '.number_format(($tarcounter * $filesintar) + $filecounter).' files in '.number_format((time() - $starttime) / 60, 2).' minutes<hr>';

} elseif (@$_GET['go']) {

	echo '!isdir('.PHPOP3CLEAN_QUARANTINE.$yearmonth.'/'.')<br>';

} else {

	if ($dh = @opendir(PHPOP3CLEAN_QUARANTINE)) {
		$thisyearmonth = date('Ym');
		$SubDirsLinks = '';
		while ($subdir = readdir($dh)) {
			if (is_dir(PHPOP3CLEAN_QUARANTINE.$subdir) && ereg('^[0-9]{6}$', $subdir)) {
				$SubDirsLinks .= '<li>'.PHPOP3CLEAN_QUARANTINE.$subdir.' -- click here to proceed:';
				$SubDirsLinks .= ' <a href="'.$_SERVER['PHP_SELF'].'?go='.$subdir.'"';
				if ($subdir == date('Ym')) {
					$SubDirsLinks .= ' onClick="return confirm(\'Are you SURE you want to archive and purge the *current* month (not usually recommended)\');"';
				}
				$SubDirsLinks .= '>do it</a></li>';
			}
		}
		closedir($dh);

		echo 'This file allows you to TAR up all of the quarntined messages from a previous month (in batches of 1000 messages per TAR file) and remove the files and directories, to allow you to archive them offsite if you want, and generally just free up server disk space. Note that the TAR files themselves are created in the quanrantine root directory and it is up to you to download and/or remove them yourself.<br><br>';
		if ($SubDirsLinks) {
			echo 'TAR up all the files in:<ul>'.$SubDirsLinks.'</ul>';
		} else {
			echo 'There are no previous-month archived data directories available.';
		}

	} else {
		echo 'ERROR: Could not opendir("'.PHPOP3CLEAN_QUARANTINE.'")<br>';
	}

}

?>
</body>
</html>