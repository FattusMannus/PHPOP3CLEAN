<?php
/////////////////////////////////////////////////////////////////
/// phPOP3clean() by James Heinrich <info@silisoftware.com>    //
//  available at http://phpop3clean.sourceforge.net            //
/////////////////////////////////////////////////////////////////

class phPOP3 {

	var $fp         = null;  // socket to POP3 server
	var $echo       = false; // if true, commands and responses are displayed on screen
	var $full_login = true;  // if true, "user@example.com" is used for login; if false only "user" is used for login
	var $UIDcache	= array();

	function phPOP3($hostname, $port, &$errno, &$errstr, $timeout=10, $echo=false) {
		$this->echo = (bool) $echo;
		if ($this->fp = @fsockopen($hostname, $port, $errno, $errstr, $timeout)) {
			socket_set_timeout($this->fp, $timeout);
			// constructor succeeded
		}
		// constructor failed, test for this with is_resource($object->fp)
	}

	function POP3login($username, $password, $hidepassword=false) {
		if (!is_resource($this->fp)) {
			$this->OutputToScreen('POP3login() failed -- !is_resource($this->fp)');
			return false;
		}
		if (!$this->full_login && strpos($username, '@')) {
			// change "user@example.com" to "user" (ignore domain)
			list($username, $domain) = explode('@', $username);
		}
		$loginstatus = $this->ReadUntilResponse();
		$login_retry_counter = 0;
		while (is_null($this->Succeeded($loginstatus))) {
			if (++$login_retry_counter >= 3) {
				$this->OutputToScreen('<font color="#9900CC">POP3login() failed -- ReadUntilResponse() returned NULL too many times.</font>'."\n");
				return false;
			}
			$this->OutputToScreen('<font color="#9900CC">no response from login, trying again...</font>'."\n");
			$loginstatus = $this->ReadUntilResponse();
		}
		$this->OutputToScreen($loginstatus);
		$this->OutputToScreen('<font color="#FFA500">USER '.$username.'</font>'."\n");
		if (!fwrite($this->fp, 'USER '.$username."\r\n")) {
			echo 'Aborting: failed to send USER command';
			exit;
		}
		$response_USER = $this->ReadUntilResponse();
		$this->OutputToScreen(htmlentities($response_USER));
		$this->OutputToScreen('<font color="#FFA500">PASS '.($hidepassword ? str_repeat('*', strlen($password)) : $password).'</font>'."\n");
		if (!fwrite($this->fp, 'PASS '.$password."\r\n")) {
			echo 'Aborting: failed to send PASS command';
			exit;
		}
		$response_PASS = $this->ReadUntilResponse();
		$this->OutputToScreen(htmlentities($response_PASS));

		return $this->Succeeded($response_PASS);
	}


	function POP3logout() {
		if (!is_resource($this->fp)) {
			$this->OutputToScreen('POP3logout() failed -- !is_resource($this->fp)');
			return false;
		}
		$this->OutputToScreen('<font color="#FFA500">QUIT</font>'."\n");
		fwrite($this->fp, 'QUIT'."\r\n");
		$response_QUIT = $this->ReadUntilResponse();
		fclose($this->fp);
		$this->OutputToScreen(htmlentities($response_QUIT));
		return $this->Succeeded($response_QUIT);
	}


	function POP3stat() {
		if (!is_resource($this->fp)) {
			$this->OutputToScreen('POP3stat() failed -- !is_resource($this->fp)');
			return false;
		}
		$this->OutputToScreen('<font color="#FFA500">STAT</font>'."\n");
		if (!fwrite($this->fp, 'STAT'."\r\n")) {
			echo 'Aborting: failed to send STAT command';
			exit;
		}
		$response_STAT = $this->ReadUntilResponse();
		$this->OutputToScreen(htmlentities($response_STAT));
		@list($status, $num_messages, $total_bytes) = explode(' ', $response_STAT);
		if (trim($status) == '+OK') {
			return array(0=>$num_messages, 1=>$total_bytes);
		}
		return false;
	}


	function POP3delete($messagenumber) {
		if (!is_resource($this->fp)) {
			$this->OutputToScreen('POP3stat() failed -- !is_resource($this->fp)');
			return false;
		}
		$this->OutputToScreen('<font color="#FFA500">DELE '.$messagenumber.'</font>'."\n");
		if (!fwrite($this->fp, 'DELE '.$messagenumber."\r\n")) {
			echo 'Aborting: failed to send DELE command';
			exit;
		}
		$response_DELE = $this->ReadUntilResponse();
		$this->OutputToScreen(htmlentities($response_DELE));
		return $this->Succeeded($response_DELE);
	}


	function POP3noop() {
		if (!is_resource($this->fp)) {
			$this->OutputToScreen('POP3noop() failed -- !is_resource($this->fp)');
			return false;
		}
		$this->OutputToScreen('<font color="#FFA500">NOOP</font>'."\n");
		if (!fwrite($this->fp, 'NOOP'."\r\n")) {
			echo 'Aborting: failed to send NOOP command';
			exit;
		}
		$response_NOOP = $this->ReadUntilResponse();
		$this->OutputToScreen(htmlentities($response_NOOP));
		return $this->Succeeded($response_NOOP);
	}


	function POP3getMessageHeader($messagenum) {
		if (!is_resource($this->fp)) {
			$this->OutputToScreen('POP3getMessageHeader() failed -- !is_resource($this->fp)');
			return false;
		}
		$this->OutputToScreen('<font color="#FFA500">TOP '.$messagenum.' 0</font>'."\n");
		if (!fwrite($this->fp, 'TOP '.$messagenum.' 0'."\r\n")) {
			echo 'Aborting: failed to send TOP command';
			exit;
		}
		return str_replace('+OK headers follow.'."\r\n", '', $this->ReadUntilDot());
	}


	function POP3getMessageID($messagenum) {
		if (!is_resource($this->fp)) {
			$this->OutputToScreen('POP3getMessageID() failed -- !is_resource($this->fp)');
			return false;
		}
		$this->OutputToScreen('<font color="#FFA500">UIDL '.$messagenum.'</font>'."\n");
		if (!fwrite($this->fp, 'UIDL '.$messagenum."\r\n")) {
			echo 'Aborting: failed to send UIDL command';
			exit;
		}
		$messageid = trim($this->ReadUntilResponse(true));
		$this->OutputToScreen($messageid."\n");
		return str_replace($messagenum.' ', '', $messageid); // strip messageNum off beginning of MessageID
	}


	function POP3getMessageNumFromUID($messageid) {
		if (!is_resource($this->fp)) {
			$this->OutputToScreen('POP3getMessageNumFromUID() failed -- !is_resource($this->fp)');
			return false;
		}

		if (count($this->UIDcache) === 0) {
			//$this->OutputToScreen('<font color="#FFA500">UIDL '.$messagenum.'</font>'."\n");
			if (!fwrite($this->fp, 'UIDL'."\r\n")) {
				echo 'Aborting: failed to send UIDL command';
				exit;
			}
			$UIDLdump = explode("\n", $this->ReadUntilDot());
			foreach ($UIDLdump as $key => $value) {
				$UIDarray = explode(' ', trim($value), 2);
				if ((count($UIDarray) === 2) && ereg('^[0-9]+$', $UIDarray[0])) {
					$this->UIDcache[$UIDarray[1]] = $UIDarray[0];
				}
				unset($UIDarray);
			}
		}

		//return (@$this->UIDcache[$messageid] ? $this->UIDcache[$messageid] : false);
		if (!is_null($messageid) && is_array($this->UIDcache) && (count($this->UIDcache) > 0) && isset($this->UIDcache[$messageid])) {
			$result = $this->UIDcache[$messageid];
		} else {
			$result = false;
		}
		return $result;
	}


	function POP3getMessageSize($messagenum) {
		if (!is_resource($this->fp)) {
			$this->OutputToScreen('POP3getMessageSize() failed -- !is_resource($this->fp)');
			return false;
		}
		$this->OutputToScreen('<font color="#FFA500">LIST '.$messagenum.'</font>'."\n");
		if (!fwrite($this->fp, 'LIST '.$messagenum."\r\n")) {
			echo 'Aborting: failed to send LIST command';
			exit;
		}
		@list($id, $messagesize) = explode(' ', $this->ReadUntilResponse(true), 2);
		return trim($messagesize);
	}


	function POP3getMessageContents($messagenum, $useRETR=true) {
		if (!is_resource($this->fp)) {
			$this->OutputToScreen('POP3getMessageContents() failed -- !is_resource($this->fp)');
			return false;
		}
		if ($useRETR) {
			$this->OutputToScreen('<font color="#FFA500">RETR '.$messagenum.'</font>'."\n");
			if (!fwrite($this->fp, 'RETR '.$messagenum."\r\n")) {
				echo 'Aborting: failed to send RETR command';
				exit;
			}
		} else {
			$this->OutputToScreen('<font color="#FFA500">TOP '.$messagenum.' 99999</font>'."\n");
			if (!fwrite($this->fp, 'TOP '.$messagenum.' 99999'."\r\n")) {
				echo 'Aborting: failed to send TOP command';
				exit;
			}
		}
		$MessageContents = $this->ReadUntilDot();
		$MessageContents = preg_replace('/\+OK ([0-9]+ octets|headers) follow\.(\r\n){1,2}/i', '', $MessageContents);
		return $MessageContents;
	}


	function ReadUntilDot() {
		$data = '';
		$exitloop = false;
		while (true) {
			if (!is_resource($this->fp)) {
				$this->OutputToScreen('ReadUntilDot() failed -- !is_resource($this->fp)');
				return false;
			}
			$thisline = fgets($this->fp, 1024);
			if ($thisline === false) {
				break;
			}
			// a single dot on a line by itself indicates end of returned data
			if (rtrim($thisline, "\r\n") == '.') {
				$exitloop = true;
			}

			// if a dot is the first character on a line, it is escaped with another dot
			// so remove all leading dots
			if (strlen($thisline) == 0) {
				$exitloop = true;
			} elseif (@$thisline{0} == '.') {
				$thisline = substr($thisline, 1);
			}
			$data .= $thisline;
			if ($exitloop) {
				break;
			}
		}
		return $data;
	}


	function ReadUntilResponse($postresponseonly=false) {
		$data = '';
		while (true) {
			if (!is_resource($this->fp)) {
				$this->OutputToScreen('ReadUntilResponse() failed -- !is_resource($this->fp)');
				return false;
			}
			$buffer = fgets($this->fp, 1024);
			$data .= $buffer;

			$postresponse = '';
			if (substr($buffer, 0, 3) == '+OK') {
				$postresponse = ltrim(substr($buffer, 3));
				break;
			} elseif (substr($buffer, 0, 4) == '-ERR') {
				$postresponse = ltrim(substr($buffer, 4));
				break;
			} elseif ($buffer == '') {
				break;
			}
		}
		if ($postresponseonly) {
			return $postresponse;
		}
		return $data;
	}


	function Succeeded($response) {
		@list($status, $message) = explode(' ', $response, 2);
		if (ereg('^\\+OK', $status)) {
			// succeeded
			return true;
		} elseif (ereg('^\\-ERR', $status)) {
			// failed
			return false;
		}
		// undefined response?
		return null;
	}


	function OutputToScreen($text) {
		if ($this->echo) {
			echo nl2br($text);
			flush();
		}
		return true;
	}

}

?>