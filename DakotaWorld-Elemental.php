#!/usr/local/bin/php
<?php
/*
 * Copyright (C) 2003/2004 AliTriX - alitrix@alitrix.nefast.com - http://alitrix.nefast.com
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/*
   REQUIRED:
   Hm.. there isn't a lot required, you need ofcourse PHP atleast, this one is tested on version 4.x,
   I think version 3.x works as well.
   I tested this script on u2.10.11.04+asuka(1.0.4)
*/
 
/* ABOUT THIS SCRIPT:
   This is a script made by AliTriX and it's a "clone" of the orginial fishbot from Quakenet.
   I didn't see the script of the real fishbot, so I don't know if my bot works the same as the original one
   and if my commands are the same. I just tested the real fishbot and tryed to make a script that looks like
   it. If you found bugs or got any idea's, your e-mail is welcome to alitrix@eggdrop-support.org
*/

/* COMMANDS:
   There are no special commands, just check http://www.fishbot.net, click on the left menu on "Commands", that
   are the actions/msgs that the bot react on.
   
   IRCOP COMMANDS:
   /msg <botname> showcommands (and you get a simpel list)
*/

/*
   CHANGES:
   Check the file ChangeLog for more info!

   TODO:
   - Try to translate this script into C, if I ever learn it ;)
   - Got more idea's?
*/
	global $ServiceName,$ServiceDesc,$ServiceNum,$BotNum;
	global $BotNick,$BotUser,$BotModes,$BotHost,$ServerHost;
	global $ServerPass,$DeBug,$Socket,$ChannelsFile,$Counter;
	global $PingPongs, $Opers, $EB;
	global $Chans;
	global $Nicks;
	global $Channels;
	$Chans = array();
	$Nicks = array();
	$Channels = array();
class FishBot {

	/******** CONFIGURATION - BEGIN  ********/
	function FishBot() {
		

$this->about = <<<EOF
*** About \x02$botname\x02 ***

\x02$botname\x02 is an instance of Galactic, a multi-client monolithic pseud-
oserver for P10-based IRC networks (and more protocols are to follow).

This will soon be modular. If you want to aid it this happening, please
come to irc.umbrellix.tk #asterirc and discuss the development plan
there. We accept all suggestions.
    
My other clients are probably just a hostserv. Ask your admin.

*** End Help ***
EOF;


		/* Configuring the bot */
		$this->ServiceName = "HStar"; /* Name of the bot */
		$this->ServiceDesc = "For how to get a vhost type: /msg HStar help"; /* The IRC Name and Discription */
		$this->ServiceNum = "0LW" ; /* Bot numeric */
		$this->PartyLineHost = "0.0.0.0"; // Host to listen for party line connections on (leave 0.0.0.0 for all)
		$this->PartyLinePort = 17447; //Leave at 17447, won't impact security as opers have to log in and have a valid access >500 on channel *
		$this->s = array();
		$this->srvs = 3;
		
		$this->canHalfOp = true;
		
		$this->s['BotNick'][1] = "X";
		$this->s['BotUser'][1] = "X";
		$this->s['BotHost'][1] = "cservice.umbrellix.tk";
		$this->s['BotModes'][1] = "+oiDS";
		$this->s['Desc'][1] = "Try these: /msg X help register and /msg X help chanregister";

		$this->s['BotNick'][2] = "H";
		$this->s['BotUser'][2] = "H";
		$this->s['BotHost'][2] = "hostserv.umbrellix.tk";
		$this->s['BotModes'][2] = "+oiDS";
		$this->s['Desc'][2] = "Hostname and Operator services";

		$this->s['BotNick'][3] = "AsterIRC";
		$this->s['BotUser'][3] = "Global";
		$this->s['BotHost'][3] = "services.umbrellix.tk";
		$this->s['BotModes'][3] = "+oiDgS";
		$this->s['Desc'][3] = "Global Noticer (Opers: use /msg H global)";
		
		$this->NetworkName = "AsterIRC";		
		$this->NetworkHostSuffix = "users.umbrellix.tk";
		$this->CloakKey1 = "9lxfg,4908fi03hfghrl45608.pr89fr349h8fl.9,485hg,895fr34895f89";
		$this->CloakKey2 = "5fb.y4DHp.y4dh,pfd<h.pfH246h<PfH<PiyHOeuIBHoh,pyh<PFhA>ypdoid";
		$this->RelayChan = "#announce";
		$this->NetworkWebIRCPrefix = "gateway/web/asterirc/";		
		$this->NetworkWebIRCIdent = "WebChat";
		// Mibbit uses the ident 'Mibbit' if set up right.
		$this->NetworkMibbitPrefix = "gateway/web/mibbit/"; 
		$this->NetworkTorPrefix = "gateway/tor-loc/";	
		$this->NetworkTorIdent = "tor";	

		$this->ServerName = "ircd.";
		$this->ServerHost = "tcp://127.0.0.3"; /* IP/Host to connect to */
		$this->ServerPort = 6667; /* Port to connect to */
		$this->ServerPass = "link"; /* Password to use for the connection between the service and server */
		$this->DeBug = TRUE; /* TRUE = on, FALSE = off */
		/* TIP: If you put DeBug TRUE, and you are starting the script like this: ./fishbot.php &, then it's
		   better to start the robot like this: ./fishbot.php >/dev/null 2>/dev/null &, cause when he is gonna
		   send a message to the terminal and it's closed, then the bot will get killed, cause there isn't a terminal
		   to send anything to it. (except if you use the /dev/null or a file)*/
		
		$this->ChannelsFile = "channels"; /* The file where the channels should be stored, !REMEMBER! If you choose
						     a directory, please make the directory FIRST then start the bot. */
		$this->DatabaseParams = "host=127.0.0.1 dbname=hservice user=j4jackj";
		$this->CServiceParams = "host=127.0.0.1 dbname=cservice user=j4jackj";
		$this->c = pg_pconnect($this->CServiceParams);
		$this->d = pg_pconnect($this->DatabaseParams);
		$this->PingPongs = 3; /* After how many ping-pongs should he save the channels into a file? */
		
		
		$this->EB = FALSE; /* Please don't change this */
	}
	
	function isAkilled($Numeric, $Host, $Ident) {
		$res = pg_query($this->d, "SELECT host, user FROM glines;");
		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$idHost = pg_fetch_result($res, $i, "host");
			$idIdent = pg_fetch_result($res, $i, "user");
			if (fnmatch($idHost, $Host)) { return true; } else { if (fnmatch($idIdent, $Ident)) { return true; } else { return false; } }
		}
	}
		
	function StartBot() {
		/* Yup, how about begin with the real work, THE BOTS! */
		$this->Socket = fsockopen($this->ServerHost,$this->ServerPort);
		//$this->PartyLine = socket_create(AF_INET, SOCK_STREAM, "tcp");
		//$clients = array($this->PartyLine);
		//socket_bind($this->PartyLine, $this->PartyLineHost, $this->PartyLinePort);
		//socket_listen($this->PartyLine);

		$Time = time();
		$tmp = sprintf('CAPAB :EX IE SERVICES SVS RSFNC EUID',$this->ServerPass, $this->ServiceNum);
		$this->SendRaw($tmp,1);
		$tmp = sprintf('PASS %s TS 6 :%s',$this->ServerPass, $this->ServiceNum);
		$this->SendRaw($tmp,1);
		$tmp = sprintf('SERVER %s 1 :%s',$this->ServerName,$this->ServiceDesc);
		$this->SendRaw($tmp,1);
		
		for ($k = 1; $k <= $this->srvs; $k++) {
			$tmp = sprintf(':%s EUID %s 1 %s %s %s %s 0 %s * %s :%s',$this->ServiceNum,$this->s['BotNick'][$k],
							$Time,$this->s['BotModes'][$k],$this->s['BotUser'][$k],$this->s['BotHost'][$k],$this->b64e($k),$this->s['BotNick'][$k],$this->s['Desc'][$k]);
			$this->SendRaw($tmp,1);
		}
		$tmp = sprintf(':%s SVINFO 6 6 0',$this->ServiceNum);
		$this->SendRaw($tmp,1);
		$tmp = sprintf('%s EA',$this->ServiceNum);
		$this->SendRaw($tmp,1);
		$this->Counter =0;
		
		
		if ($this->DeBug) {
			printf("Bot sended his own information to the server, waiting for respond.\n");
			@ob_flush();
		}
		do {
			$this->Get = fgets($this->Socket,512);
			if (feof($this->Socket)) die("Socket returned end of file.\n");
			if ($this->Get != "") $this->Idle();
		} while (true); 
	}
	
/* Found this on the PHP website as a user comment */
function convBase($numberInput, $fromBaseInput, $toBaseInput)
{
    if ($fromBaseInput==$toBaseInput) return $numberInput;
    $fromBase = str_split($fromBaseInput,1);
    $toBase = str_split($toBaseInput,1);
    $number = str_split($numberInput,1);
    $fromLen=strlen($fromBaseInput);
    $toLen=strlen($toBaseInput);
    $numberLen=strlen($numberInput);
    $retval='';
    if ($toBaseInput == '0123456789')
    {
        $retval=0;
        for ($i = 1;$i <= $numberLen; $i++)
            $retval = bcadd($retval, bcmul(array_search($number[$i-1], $fromBase),bcpow($fromLen,$numberLen-$i)));
        return $retval;
    }
    if ($fromBaseInput != '0123456789')
        $base10=$this->convBase($numberInput, $fromBaseInput, '0123456789');
    else
        $base10 = $numberInput;
    if ($base10<strlen($toBaseInput))
        return $toBase[$base10];
    while($base10 != '0')
    {
        $retval = $toBase[bcmod($base10,$toLen)].$retval;
        $base10 = bcdiv($base10,$toLen,0);
    }
    return $retval;
}

/* B64 encoder from stackexchange adapted for p10 */

function b64e($id, $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
{
    $base = strlen($alphabet);
    $short = '';
    while($id) {
        $id = ($id-($r=$id%$base))/$base;     
        $short = $alphabet{$r} . $short;
    };
    $str = strtr(sprintf("%6s",$short)," ","A");
    return $str;
}

	public function vhostIt($Numeric, $Acct) {
		$database = pg_connect($this->DatabaseParams);
		$res = pg_query("SELECT ah FROM vhosts WHERE ac = '".strtolower($Acct)."'");
		$rows = pg_num_rows($res);
		$Vhost = pg_fetch_result($res,0);
		if ($rows) {
			$buf = sprintf("%s FA %s %s",$this->ServiceNum, $Numeric, $Vhost);
			$this->SendRaw($buf,1);
		} else {
			$buf = sprintf("%s FA %s %s.%s",$this->ServiceNum, $Numeric, $Acct, $this->NetworkHostSuffix);
			$this->SendRaw($buf,1);
		}
		$res = pg_query("SELECT aj FROM ajchans WHERE acct = '".strtolower($Acct)."'");
		$numrows = pg_num_rows($res);
		for ($i=0;$i<$numrows;$i++) {
			$buf = sprintf("%s SJ %s %s",$this->ServiceNum, $Numeric, pg_fetch_result($res,$i,"aj"));
			$this->SendRaw($buf,1);
		}
		$this->SendRaw($buf,1);
		pg_close($database);
	}
	 function vhostRet($Numeric, $Acct) {
		$database = pg_connect($this->DatabaseParams);
		$res = pg_query("SELECT ah FROM vhosts WHERE ac = '".strtolower($Acct)."'");
		$rows = pg_num_rows($res);
		$Vhost = pg_fetch_result($res,0);
		if ($rows) {
			return sprintf("%s FA %s %s",$this->ServiceNum, $Numeric, $Vhost);
		} else {
			return sprintf("%s FA %s %s.%s",$this->ServiceNum, $Numeric, $Acct, $this->NetworkHostSuffix);
		}
		$res = pg_query("SELECT aj FROM ajchans WHERE acct = '".strtolower($Acct)."'");
		$numrows = pg_num_rows($res);
		pg_close($database);
	}
	
	/******** CONFIGURATION - END  ********/
	/* DON'T CHANGE THE LINES BELOW, IF YOU DON'T KNOW WHAT YOU ARE DOING */
	
	function LoadChannels() {
		/* Load the channels from the DB */
		$res = pg_query($this->c, "SELECT name, channel_ts, channel_mode, id FROM channels");
		$rows = pg_num_rows($res);
		$chans = pg_fetch_row($res);
		for ($i = 1;$chans != FALSE;$i++) {
			$tmp = sprintf("%s B %s %s %sRz %s%s:298",$this->ServiceNum,$chans[0],$chans[1],$chans[2],$this->ServiceNum,$this->b64e(51));
			$this->SendRaw($tmp,1);
			$tmp = sprintf("%s%s L %s :I only joined to set registered channel modes thru BURST.",$this->ServiceNum,$this->b64e(51),$chans[0]);
			$this->SendRaw($tmp,1);
			$chans = pg_fetch_row($res);
			$this->Channels[strtolower($chans[0])]["CH-ID"] = $chans[3];
			$this->Channels[strtolower($chans[0])]["CH-TS"] = $chans[1];
		}
		$this->SendRaw($buf,1);
	}
   function is_blacklisted($ip) {
   // written by satmd, do what you want with it, but keep the author please
   $result=Array();
   $dnsbl_check=array("6667.163.94.246.46.ip-port.exitlist.torproject.org"); // Srsly, change this.
   if ($ip) {
       $quads=explode(".",$ip);
       $rip=$quads[3].".".$quads[2].".".$quads[1].".".$quads[0];
       for ($i=0; $i<count($dnsbl_check); $i++) {
           if (checkdnsrr($rip.".".$dnsbl_check[$i].".","A")) {
              $result[]=Array($dnsbl_check[$i],$rip.".".$dnsbl_check[$i]);
           }
         }
      return $result;
   }
}
 function is_blacklisted_tor($ip) {
   // written by satmd, do what you want with it, but keep the author please
   $result=Array();
   $dnsbl_check=array("6667.163.94.246.46.ip-port.exitlist.torproject.org"); // Srsly, change this.
   if ($ip) {
       $quads=explode(".",$ip);
       $rip=$quads[3].".".$quads[2].".".$quads[1].".".$quads[0];
       for ($i=0; $i<count($dnsbl_check); $i++) {
           if (checkdnsrr($rip.".".$dnsbl_check[$i].".","A")) {
              $result[]=Array($dnsbl_check[$i],$rip.".".$dnsbl_check[$i]);
           }
         }
      return $result;
   }
}
	
function std_make_password($password, $crypt="") {
	$valid = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.$*_";
	$salt = "";
	srand((double) microtime() * 1000000);
	if ($password == "") {
		for ($k = 0; $k < 8; $k++)
			$password = $password . $valid[rand(0, strlen($valid)-1)];
	}
	for ($k = 0; $k < 8; $k++)
		$salt = $salt . $valid[rand(0, strlen($valid)-1)];
	$crypt = $salt . hash("sha512",$salt . $password); return $crypt;
}

function std_check_password($username, $password) {
        $chk = pg_exec($this->c, "SELECT password, id FROM users WHERE lower(user_name) = lower('{$username}')");
 	if (pg_numrows($chk) == 0)
		return false; // Failed
	$chk = pg_fetch_object($chk, 0);
	$crypt = $chk->password;
	if ($crypt == "")
		return true; // Success
	$salt = substr($crypt, 0, 8);
	$crypt = substr($crypt, 8);
	if (md5($salt . $password) == $crypt) {
		pg_query($this->c, "UPDATE users SET password = '".$this->std_make_password($password)."' WHERE lower(user_name) = lower('{$username}')");
		return true; // Success! except we updated the user's password lol so that it would not be lamely encrypted
	} elseif (hash("sha512",$salt . $password) == $crypt)
		return true; // Success!
	return false; // Failed
}

	function LoadChannelOps($channel, $uid) {
		/* Load the channels from the DB */
		$res = pg_query($this->c, "SELECT channel_id, user_id, access FROM levels WHERE channel_id = (select id from channels where name = '".$channel."') AND user_id = (select id from users where user_name = '".$uid."');");
		$rows = pg_num_rows($res);
		$chans = pg_fetch_row($res);
		return $chans[2];
	}
	
	function idByUser($username) {
		$res = pg_query($this->c, "SELECT user_name, id FROM users WHERE user_name = '".$username."'");
		$rows = pg_num_rows($res);
		$chans = pg_fetch_row($res);
		return $chans[1];
	}
	function idByChan($username) {
		$res = pg_query($this->c, "SELECT name, id FROM channels WHERE name = '".$username."'");
		$rows = pg_num_rows($res);
		$chans = pg_fetch_array($res);
		return $chans[1];
	}	
	function UserbyID($username) {
		$res = pg_query($this->c, "SELECT user_name, id FROM users WHERE id = '".$username."'");
		$rows = pg_num_rows($res);
		$chans = pg_fetch_array($res);
		return $chans[0];
	}
	function SaveChannels() {
		/* Save channels into the database file */
		if (!file_exists($this->ChannelsFile)) {
			exec('touch '.$this->ChannelsFile);
			printf("Had problems..!\n");
			@ob_flush();
		}
		
		$handle = fopen($this->ChannelsFile,'w+');
		foreach($this->Chans as $number => $chan) {
			@$content .= sprintf("%s\r\n",trim($chan));
		}
		if (!empty($content))
			fwrite($handle,$content);
		fclose($handle);
		if ($this->DeBug) {
			printf("Channels saved.\n");
			@ob_flush();
		}
	}
	
	function isIdentified($Numeric) {
		$res = pg_query($this->d, "SELECT nickname FROM nicks WHERE username = '".$this->Acct[$Numeric]."';");
		while ($idNick[] = pg_fetch_array($res));
		var_dump($res);
		var_dump($idNick);
		foreach ($idNick as $nickname) {
			if ($nickname == $this->Nicks[$Numeric]) return true;
		}
		return false; // Should only get here for unidentified users
	}
	function isWrongNick($Numeric,$Account="") {
		if ($Account == "" and $this->isRegNick($Numeric)) return true;
		$res = pg_query($this->d, "SELECT username FROM nicks WHERE nickname = '".$this->Nicks[$Numeric]."';");
		$idNick = pg_fetch_result($res, "username");
		foreach ($idNick as $nickname) {
			if ($nickname != $this->Acct[$Numeric]) return true;
		}
		return false; // Should only get here for unidentified users
	}
	function isRegNick($Numeric) {
		$res = pg_query($this->d, "SELECT username FROM nicks WHERE nickname = '".$this->Nicks[$Numeric]."';");
		$idNick = pg_fetch_result($res, "username");
		$rows = pg_num_rows($res);
		if ($rows) { return true; } else { return false; }
	}
	
	function isProtectNick($Numeric) {
		$res = pg_query($this->d, "SELECT protect FROM nicks WHERE nickname = '".$this->Nicks[$Numeric]."';");
		$idNick = pg_fetch_result($res, "protect");
		$rows = pg_num_rows($res);
		if ($idNick == "a") { return true; } else { return false; }
	}
	
	function DoCloak($Numeric,$WillAcct) {
		var_dump($this->Hosts[$Numeric]);
		if ((strlen($this->Acct[$Numeric]) >= 2) and $WillAcct) {
			$this->SendRaw(sprintf("%s SM %s +x", $this->ServiceNum, $Numeric),1);
			$this->vhostIt($Numeric,$this->Acct[$Numeric]);
			$this->SendRaw(sprintf("%s SID %s %s", $this->ServiceNum, $Numeric,$this->Acct[$Numeric]),1);
		} else {
			if (preg_match("/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/", $this->Hosts[$Numeric])) {
				$hostActive = sprintf("%s.%s.%s/%s",
				implode(".", array_slice(explode(".",$this->Hosts[$Numeric]),0,2)), 
				substr($this->convBase(hash("sha384", $this->CloakKey2.implode(".", array_slice(explode(".",$this->Hosts[$Numeric]),1,1)).$this->CloakKey1),"0123456789ABCDEF","0123456789abcdefghijklmnopqrstuvwxyz"),20,4),
				substr($this->convBase(hash("sha384", $this->CloakKey2.$this->Hosts[$Numeric].$this->CloakKey1),"0123456789ABCDEF","0123456789abcdefghijklmnopqrstuvwxyz"),20,4),
				$this->NetworkName);
			} elseif (preg_match("/:/", $this->IPs[$Numeric]) and !(preg_match("/\./", $this->Hosts[$Numeric]))) {
				$hostActive = sprintf("%s:%s:%s:%s:%s/%s",
				implode(":", array_slice(explode(":",$this->Hosts[$Numeric]),0,4)), 
				substr($this->convBase(hash("sha384", $this->CloakKey2.implode(":", array_slice(explode(":",$this->IPs[$Numeric]),4,1)).$this->CloakKey1),"0123456789ABCDEF","0123456789abcdefghijklmnopqrstuvwxyz"),0,4),
				substr($this->convBase(hash("sha384", $this->CloakKey2.implode(":", array_slice(explode(":",$this->IPs[$Numeric]),5,1)).$this->CloakKey1),"0123456789ABCDEF","0123456789abcdefghijklmnopqrstuvwxyz"),0,4),
				substr($this->convBase(hash("sha384", $this->CloakKey2.implode(":", array_slice(explode(":",$this->IPs[$Numeric]),6,1)).$this->CloakKey1),"0123456789ABCDEF","0123456789abcdefghijklmnopqrstuvwxyz"),0,4),
				substr($this->convBase(hash("sha384", $this->CloakKey2.$this->Hosts[$Numeric].$this->CloakKey1),"0123456789ABCDEF","0123456789abcdefghijklmnopqrstuvwxyz"),0,4),
				$this->NetworkName
				);
			} elseif (!preg_match("/i2p$/", $this->Hosts[$Numeric])) {
				$HostSlice = explode(".",$this->Hosts[$Numeric]);
				var_dump($HostSlice);
				$lastSlice = array_pop($HostSlice);
				$nlstSlice = array_pop($HostSlice);
				$tlstSlice = array_pop($HostSlice);
				if (count($HostSlice) > 0) {
					foreach ($HostSlice as $slice) {
						if ($hostActive != "") { $hostActive = $hostActive . "." . substr($this->convBase(hash("sha384",$this->CloakKey2.$slice.$this->CloakKey1),"0123456789ABCDEF","0123456789abcdefghijklmnopqrstuvwxyz"),0,strlen($slice)); } else { $hostActive = substr($this->convBase(hash("sha384",$this->CloakKey2.$slice.$this->CloakKey1),"0123456789ABCDEF","0123456789abcdefghijklmnopqrstuvwxyz"),0,strlen($slice)); }
						var_dump($hostActive);
						var_dump($slice);
					}
					$hostActive = $hostActive .".". $tlstSlice. ".". $nlstSlice .".". $lastSlice . "/" . $this->NetworkName;
				} elseif ($tlstSlice) {
					$hostActive = substr($this->convBase(hash("sha384",$this->CloakKey2.$tlstSlice.$this->CloakKey1),"0123456789ABCDEF","0123456789abcdefghijklmnopqrstuvwxyz"),0,strlen($tlstSlice)).".". $nlstSlice .".". $lastSlice . "/" . $this->NetworkName;
				} else {
					$hostActive = substr($this->convBase(hash("sha384",$this->CloakKey2.$nlstSlice.$this->CloakKey1),"0123456789ABCDEF","0123456789abcdefghijklmnopqrstuvwxyz"),0,strlen($nlstSlice)).".". $lastSlice . "/" . $this->NetworkName;
				}
				
		}
	}
			if (preg_match("/i2p$/", $this->Hosts[$Numeric])) {
				return;
			}
			
			$this->SendRaw(sprintf(":%s NOTICE #connexit :*** \x02Cloak activation\x02: Numeric %s, nick %s, and cloaked-hostname %s%s", $this->ServiceNum, $Numeric, $this->Num2Nick($Numeric), $hostPrefix, $hostActive),1);
			$this->SendRaw(sprintf("%s FA %s %s%s", $this->ServiceNum, $Numeric, $hostPrefix, $hostActive),1);
			$this->SendRaw(sprintf("%s SM %s +x", $this->ServiceNum, $Numeric),1);

}

	function OpUser($Bot,$Channel,$User,$Level){
		if ($Level >= 50) {
			$tmp = sprintf(":%s TMODE %s %s +h %s", $this->b64e($Bot), $this->Channels[$Parts[1]]["CH-TS"], $Channel, $User);
		} if ($Level >= 100) {
			$tmp = sprintf(":%s TMODE %s %s +o %s", $this->b64e($Bot), $this->Channels[$Parts[1]]["CH-TS"], $Channel, $User);
		} if ($Level >= 200) {
			$tmp = sprintf(":%s TMODE %s %s +ao %s %s", $this->b64e($Bot), $this->Channels[$Parts[1]]["CH-TS"], $Channel, $User, $User);
		} if ($Level >= 350) {
			$tmp = sprintf(":%s TMODE %s %s +yo %s %s", $this->b64e($Bot), $this->Channels[$Parts[1]]["CH-TS"], $Channel, $User, $User);
		}
		$this->SendRaw($tmp,1);
	}
	
	function Idle() {
			if (!empty($this->Get)) {
				$Args = explode(" ",trim($this->Get));
				$Cmd = trim($Args[1]);
				if (!(preg_match("/@/", $Args[2]))) {
					$Dest = $this->convBase(substr($Args[2], -5),"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789");
					$Src = $this->convBase(substr($Args[2], -5),"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789");
				} else {
					$Dest = array_search(strstr($Args[2], "@", TRUE), $this->s['BotNick']);
				}
				switch ($Cmd) {
					case "PING": /* Ping .. Pong :) */
						$this->Pong($Args);
						break;
					case "PRIVMSG": /* They are talking to us */
						$this->PrivMsg($Dest,$Args,$this->Get);
						break;
					case "NICK": /* We got a nick-change or a nick-burst msg */
					case "EUID": /* We got a nick-change or a nick-burst msg */
						$this->SaveNicks($Args);$this->SendRaw(sprintf(":%s NOTICE #connexit :*** \x02Connect/NickChange\x02: %s", $this->ServiceNum, $this->Get),1);
						break;
					case "MODE":
						$this->AddOper($Args);
						break;
					case "TMODE": /* A oper logged in, or a chanmode changed? */
						$this->AddChop($Args);
						break;
					case "QUIT": /* They quit as well, finally! :P */
						$this->DelUser($Args);$this->SendRaw(sprintf(":%s NOTICE #connexit :*** \x02Exit\x02: %s", $this->ServiceNum, $this->Get),1);
						break;
					case "SJOIN": /* We received a burst line */
						$this->Burst($Args);
						break;
					case "JOIN": /* Somebody joined a channel */
						$this->AddChan($Args);
						break;
					case "ENCAP": /* Someone logged in to channel services, handle their vhosting */
						if ($Args[3] = "SU") {
							$this->Acct[$Args[4]] = str_replace(array("\r", "\n"), "", $Args[5]);
							$this->vhostIt($Args[4],str_replace(array("\r", "\n"), "", $Args[5]));
							$this->SendRaw(sprintf(":%s NOTICE #connexit :*** \x02Auth\x02: %s", $this->ServiceNum, $this->Get),1);
						} elseif ($Args[3] = "SASL") {
							$this->DoSASL($Args);
						}
						break;
					case "PART": /* If somebody parts a channel, we have to notice that */
						$this->DelChan($Args);
						break;
					default:
						/* We do not know. So we'll send it to the snotice channel. */
						$this->SendRaw(sprintf(":%s NOTICE #connexit :*** \x02Raw data\x02: %s", $this->ServiceNum, $this->Get),1);
				}
			}
	}
	
	function DoSASL($Args){
		if ($Args[6] == "S") {
			$Mechanism[$Args[4]] = substr($Args[7],1);
			$this->SendRaw(sprintf(":%s ENCAP %s SASL %s%s %s C :+", $this->ServiceNum, substr($Args[3],0,3), $Args[0], $Args[4]),1);
		} else {
			if ($Mechanism[$Args[4]] = "PLAIN") {
			$this->SASL[$Args[4]]["Data"] = explode("\x00",base64_decode(substr($Args[7],1)));
			if ($this->std_check_password($this->SASL[$Args[4]]["Data"][1], $this->SASL[$Args[5]]["Data"][2])) {
				$this->SendRaw(sprintf(":%s ENCAP %s SVSLOGIN %s * %s %s %s", $this->ServiceNum, substr($Args[3],0,3), $Args[0], $Args[3], $this->vhostRet($Args[4], $this->SASL[$Args[4]]["Data"][1]), $this->SASL[$Args[3]]['Data'][1]),1);
				$this->SendRaw(sprintf(":%s ENCAP %s SASL %s %s D S", $this->ServiceNum, substr($Args[3],0,3), $Args[0], $Args[3]),1);
				unset($this->SASL[$Args[5]]);
			} else {
				$this->SendRaw(sprintf(":%s ENCAP %s SASL %s %s D F", $this->ServiceNum, substr($Args[3],0,3), $Args[0], $Args[3]),1);
				unset($this->SASL[$Args[5]]);
			}
		} elseif ($Mechanism[$Args[3]] = "X-GPG") { ; }
		}
	}
	
	function JoinChannels() {
		/* Join the channels after receiving a EA from the server */
	//	foreach($this->Chans as $number => $chan) {
	//		$tmp = sprintf('%s SH %s',$this->BotNum,$chan);
	//		$this->SendRaw($tmp,1);
	//		$this->CheckEmptyChan($chan);
	//	}
	}
	
	function Pong($Args) {
		/* The server pinged us, we have to pong him back */
		/* [get] AB G !1061145822.928732 fish.go.moh.yes 1061145822.928732 */
		$tmp = sprintf(':%s PONG %s',$this->ServiceNum,$Args[2]);
		$this->SendRaw($tmp,0);
	}
	function SendMsg($To,$Msg) {
		/* Sending a msg */
		$tmp = sprintf(':%s PRIVMSG %s :%s',$this->BotNum,$To,$Msg);
		$this->SendRaw($tmp,0);
	}
	
	function SendRaw($Msg,$luldick="lel") {
		/* Sending a msg */
		fwrite($this->Socket, sprintf('%s\r\n',$Msg));
	}
	
	function AddChop($Args) {
		// I didn't even bother documenting this xD
		$Modes = trim($Args[4]);
		$Count = strlen($Modes);
		$Status = false;
		for($i=0;$i<$Count;$i++) {
			if ($Modes[$i] == "+") 
				$Status = "+";
			elseif ($Modes[$i] == "-")
				$Status = "-";
			else {
				if (($Modes[$i] == "y" || $Modes[$i] == "a" || $Modes[$i] == "o") && $Status == "+")
					{ echo "Someone became op";
					$this->Channels[$Args[3]][$Args[4+$i]]["op"] = true;
				}
			}
		}
	}
	
	function AddOper($Args) {
		/* When we receive a MODE, we have to check if a person is authing himself as
		   a oper, so we can tell the arrays that. */
		/* [get] ABAAC M AliTriX +iw */
		$Numeric = trim($Args[0]);
		$Target = trim($Args[2]);
		if (substr($Target,0,1) != "#") { /* Only user-modes are interested */
			$Modes = trim($Args[3]);
			$Count = strlen($Modes);
			$Status = false;
			for($i=0;$i<$Count;$i++) {
				if ($Modes[$i] == "+") 
					$Status = "+";
				elseif ($Modes[$i] == "-")
					$Status = "-";
				else {
					if (!empty($Status)) {
						if ($Modes[$i] == "o" && $Status == "+")
							$this->Opers[$Numeric] = true;
						if ($Modes[$i] == "o" && $Status == "-")
							unset($this->Opers[$Numeric]);
					}
				}
				
			}
	}
	
	function DelUser($Args) {
		/* When a user quites, we have to notice our arrays about that. */
		/* [get] ABAAC Q :Quit: leaving */
		$Numeric = trim($Args[0]);
		unset($this->Nicks[$Numeric],$this->Opers[$Numeric]);
		foreach($this->Channels as $chan => $sub_array) {
			foreach ($sub_array as $num => $x)
				if ($Numeric == $num) {
					unset($this->Channels[$chan][$num]);
					$this->CheckEmptyChan($chan);
				}
		}
	}
	
	function PrivMsg($Dest,$Args,$Line) {
		/* Somebody msg'ed something to me or to a channel */
		/* [get] ABAAG P #blaat :Joh, wzp?	<-- Chan-msg
		   [get] ABAAG P ADAAA :Joh, wzp?	<-- Priv-msg
		*/
		$Sender = trim($Args[0]);
		$Target = trim($Args[2]);
		$Msg = explode(":",$Line,2);
		$Msg = trim($Msg[1]);
		if ($Target[0] == '#') { 
			$Dest = 4096;
			$Msg = explode("!",$Msg,2);
			$Msg = $Msg[1];
		}
		switch ($Dest) {
			case 1:
				$Parts = explode(" ",$Msg);
				$Cmd = strtolower(trim($Parts[0]));
				switch ($Cmd) {
					case "login":
						if (sizeof($Parts)<2) {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Not enough parameters.", $this->ServiceNum,$this->b64e($Dest), $Sender),1); break; }
						if ($this->std_check_password($Parts[1], $Parts[2])) {
							$this->Acct[$Sender] = $Parts[1];
							$this->SendRaw(sprintf(":%s NOTICE #connexit :*** \x02Auth\x02: %s", $this->ServiceNum, $this->Num2Nick($Sender), $Parts[1]),1);
							$this->AcctID[$Sender] = $this->idByUser($Parts[1]);
							$this->SendRaw(sprintf("%s AC %s R %s", $this->ServiceNum, $Sender, $Parts[1]),1);$this->Acct[$sender] = $Parts[1];
							$this->SendRaw(sprintf("%s SID %s %s", $this->ServiceNum, $Sender, $Parts[1]),1);$this->Acct[$sender] = $Parts[1];
							$this->SendRaw(sprintf("%s SM %s +x", $this->ServiceNum, $Sender),1);
							$this->vhostIt($Sender, $Parts[1]);
							usleep(50000);
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Logged you in successfully as %s. Congratulations. Remember to op yourself in channels you have the right to do so in.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
							if ($this->isIdentified($Sender)) {
								$this->SendRaw(sprintf(":%s%s NOTICE %s :\x02IDENTIFICATION SUCCESSFUL!\x02 You are now identified for this nickname.", $this->ServiceNum,$this->b64e($Dest), $Sender),1);
								$this->SendRaw(sprintf("%s%s SW %s :[\x02CService Nickname Protection\x02] This user is currently identified for his nickname.", $this->ServiceNum, $this->b64e(1), $Numeric),1);
								unset($this->NickHold[$Numeric]);
							} else {
								$this->SendRaw(sprintf("%s%s SW %s :[\x02CService Nickname Protection\x02] This user is not currently identified for his nickname.", $this->ServiceNum, $this->b64e(1), $Numeric),1);
							}
							} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Go fuck yourself. Wrong login name or password for %s.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "autoregain":
						if ($this->isIdentified($Sender)) { 
							pg_query($this->d,"UPDATE nicks SET protect='a' WHERE username='{$this->Acct[$Sender]}'");
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Your nickname will now automatically be regained by services.", $this->ServiceNum,$this->b64e($Dest), $Sender),1);
						}
						break;
					case "noregain":
						if ($this->isIdentified($Sender)) { 
							pg_query($this->d,"UPDATE nicks SET protect='b' WHERE username='{$this->Acct[$Sender]}'");
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Your nickname will no longer be regained by services.", $this->ServiceNum,$this->b64e($Dest), $Sender),1);
						}
						break;
					case "addnick":
						if ($this->isWrongNick($Sender, $this->Acct[$Sender])) { $this->SendRaw(sprintf(":%s%s NOTICE %s :Someone else already owns the nickname \x02%s\x02.",$this->ServiceNum,$this->b64e(1), $Sender, $this->Nicks[$Sender]),1); break; }
						if ($this->isIdentified($Sender, $this->Acct[$Sender])) { $this->SendRaw(sprintf(":%s%s NOTICE %s :You already own the nickname \x02%s\x02.",$this->ServiceNum,$this->b64e(1), $Sender, $this->Nicks[$Sender]),1); break; }
						if (!($this->isIdentified($Sender, $this->Acct[$Sender])) and !($this->isWrongNick($Sender, $this->Acct[$Sender])) and (isset($this->Acct[$sender]))) {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Congratulations, you now own the nickname \x02%s\x02.",$this->ServiceNum,$this->b64e(1), $Sender, $this->Nicks[$Sender]),1);
							pg_query($this->d, "INSERT INTO nicks VALUES ('".$this->Acct[$Sender]."', '".$this->Nicks[$Sender]."');");
							$this->SendRaw(sprintf("%s%s SW %s :[\x02CService Nickname Protection\x02] This user is currently identified for his nickname.", $this->ServiceNum, $this->b64e(1), $Numeric),1);
							break;
						}
						$this->SendRaw(sprintf(":%s%s NOTICE %s :You should never receive this message.",$this->ServiceNum,$this->b64e(1), $Sender),1);
						break;
					case "regain":
						if (!($this->isIdentified($Sender, $this->Acct[$Sender])) and !($this->isWrongNick($Sender, $this->Acct[$Sender])) and (isset($this->Acct[$sender]))) {
							$res = pg_query($this->d, "SELECT nickname FROM nicks WHERE username = '".$this->Acct[$sender]."';");
							$idNick = pg_fetch_result($res, "nickname");
							if (pg_num_rows($res)) {
								$this->SendRaw(sprintf(":%s%s NOTICE %s :Congratulations, you have now regained the nickname \x02%s\x02.",$this->ServiceNum,$this->b64e(1), $Sender, $idNick),1);
								$this->SendRaw(sprintf(":%s%s NOTICE %s :[\x02CService Nickname Protection\x02] Your nickname has been regained by \x02%s\x02.",$this->ServiceNum,$this->b64e(1), array_search($idNick, $this->Nicks), $this->Nicks[$Sender]),1);$this->SendRaw(sprintf("%s SX %s :[\x02CService Nickname Protection\x02] Your nickname has been regained by \x02%s\x02.",$this->ServiceNum, array_search($idNick, $this->Nicks), $this->Nicks[$Sender]),1);
								usleep(10000);
								$this->SendRaw(sprintf("%s SN %s %s",$this->ServiceNum, $Sender, $idNick),1);
							}
							break;
						}
						$this->SendRaw(sprintf(":%s%s NOTICE %s :You should never receive this message.",$this->ServiceNum,$this->b64e(1), $Sender),1);
						break;
					case "chanregister":
						if (!isset($this->Acct[$sender])) { $this->SendRaw(sprintf(":%s%s NOTICE %s :Please log in to me to continue.",$this->ServiceNum,$this->b64e($Dest), $Sender)); break; }
						if ($this->Channels[$Parts[1]][$Sender]['op']) {
							$chid = time();
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Registering %s to %s.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1], $Parts[2] ? $Parts[2] : $this->Acct[$Sender]),1);
							$this->SendRaw(sprintf("%s%s P %s :%s has registered this channel to %s.", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $this->Num2Nick($Sender), $Parts[2] ? $Parts[2] : $this->Acct[$Sender]),1);
							if (!isset($this->Channels[strtolower($Parts[1])]["CH-ID"])){
								pg_query($this->c, sprintf("INSERT INTO channels (id, name, registered_ts, channel_ts, channel_mode, limit_offset, limit_period, limit_grace, limit_max, last_updated) VALUES (%s, '%s', %s, %s, '+tnCN', 5, 20, 1, 0, 313370083);",$chid, $Parts[1], $chid, $this->Channels[strtolower($Parts[1])]["CH-TS"], time()));
								pg_query($this->c, sprintf("INSERT INTO levels (channel_id, user_id, access, added, last_updated) VALUES (%s, (select id from users where user_name = '%s'), %s, %s, 1933780085);",$chid, $Parts[2] ? $Parts[2] : $this->Acct[$Sender], "500", time()));
								$this->LoadChannels();
							}
						} elseif ($this->Opers[$Sender]) {
							$chid = time();
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Forcefully registering %s to %s.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1], $Parts[2] ? $Parts[2] : $this->Acct[$Sender]),1);
							$this->SendRaw(sprintf("%s%s P %s :%s, an oper, has forced the registration of this channel to %s.", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $this->Num2Nick($Sender), $Parts[2] ? $Parts[2] : $this->Acct[$Sender]),1);
							if (!isset($this->Channels[strtolower($Parts[1])]["CH-ID"])){
								pg_query($this->c, sprintf("INSERT INTO channels (id, name, registered_ts, channel_ts, channel_mode, limit_offset, limit_period, limit_grace, limit_max, last_updated) VALUES (%s, '%s', %s, %s, '+tnCN', 5, 20, 1, 0, 313370083);",$chid, $Parts[1], $chid, $this->Channels[strtolower($Parts[1])]["CH-TS"], time()));
								pg_query($this->c, sprintf("INSERT INTO levels (channel_id, user_id, access, added, last_updated) VALUES (%s, (select id from users where user_name = '%s'), %s, %s, 1933780085);",$chid, $Parts[2] ? $Parts[2] : $this->Acct[$Sender], "500", time()));
								$this->LoadChannels();
							}
						} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Not registering %s to you because you are not currently opped on that channel.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "register":
						if (isset($this->Acct[$sender])) {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Sorry, you may only have one account.",$this->ServiceNum,$this->b64e($Dest), $Sender));
							break;
						}
						$cookie=md5(microtime() . time() . CRC_SALT_0003 . $Parts[1] . $Parts[2]);
						$expire=time()+86400; // 1 day
						$this->SendRaw(sprintf(":%s%s NOTICE %s :Your account is pending activation. Please check your email for more info.",$this->ServiceNum,$this->b64e($Dest), $Sender));
						pg_query("insert into pendingusers (user_name,cookie,expire,email,language,question_id,verificationdata,poster_ip) values ('" . $Parts[1] . "','" . $cookie . "'," . (int)$expire . ",'" . strtolower($Parts[2]) . "',1," . (int)$Parts[3] . ",'" . $Parts[4] . "','127.0.0.1')");
						$boundary=md5(time());
						mail($_POST["email"],$mail_subject_new,"To continue the registration process, in IRC, type /msg X confirm" . $cookie,
							"From: " . $mail_from_new . "\nReply-To: " . $mail_from_new . "\nX-Mailer: " . NETWORK_NAME . " Channel Service"
							);
						break;
					case "confirm":
						$res=pg_safe_exec("select * from pendingusers where cookie='$cookie'");
						$user=pg_fetch_object($res,0);
						$lowusername = strtolower( $user->user_name );
						$valid="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
						$password="";
						srand((double) microtime() * 1000000);
						for ($i=0;$i<12;$i++) {
							$password=$password . $valid[rand(0,strlen($valid)-1)];
						}
						for ($i=0;$i<8;$i++) {
							$salt=$salt . $valid[rand(0,strlen($valid)-1)];
						}
						$crypt=$salt . md5($salt . $password);
						$verificationdata = prepare_dbtext_db( $user->verificationdata );
	
						$q = "insert into users (user_name,password,flags,email,last_updated,last_updated_by,language_id,question_id,verificationdata,post_forms,signup_ts,signup_ip) " . " values " . " ('" . $user->user_name . "','$crypt',0,'" . $user->email . "'," . "  now()::abstime::int4,'Web Page New User'," . $user->language . "," . $user->question_id . ",'" . $verificationdata . "',0,now()::abstime::int4,'" . cl_ip() . "')";
						//echo $q;
						$res=pg_query($q);
						$this->SendRaw(sprintf(":%s%s NOTICE %s :Your account is name is \x02%s\x02 and your password is \x02%s\x02.",$this->ServiceNum,$this->b64e($Dest), $Sender, $user->user_name));
						$ucount = pg_query("SELECT count_count FROM counts WHERE count_type='1'");
						$newcount = $uobj->count_count+1;
						if ($newcount==$MAX_ALLOWED_USERS) {
							pg_query("INSERT INTO locks VALUES (3,now()::abstime::int4,0)");
						}
						pg_query("UPDATE counts SET count_count='" . ($newcount+0) . "' WHERE count_type='1'");
						break;
					case "adduser":
						if (!isset($this->Acct[$Sender])) {
								$this->SendRaw(sprintf(":%s%s NOTICE %s :Please log in to me to continue.",$this->ServiceNum,$this->b64e($Dest), $Sender),1);
								break;
							}
						if ($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) > 440) {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Adding user as requested.", $this->ServiceNum,$this->b64e($Dest), $Sender),1);
							$this->SendRaw(sprintf("%s%s WC @%s :%s requested addition of %s at level %s", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $this->Num2Nick($Sender), $Parts[2], $Parts[3]),1);
								$id = $this->idByChan($Parts[1]);
								$uid = $this->idByUser($this->Acct[$Parts[2]]);
								pg_query($this->c, sprintf("INSERT INTO levels (channel_id, user_id, access, added, last_updated) VALUES ((select id from channels where name = '%s'), (select id from users where lower(user_name) = lower('%s')), %s, %s, 1933780085);",$Parts[1], $Parts[2], $Parts[3], time()));
						} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Bud, that did not work because you aren't an owner of that channel. :(", $this->ServiceNum,$this->b64e($Dest), $Sender),1);
						}
						break;
					case "deluser":
						if (!isset($this->Acct[$Sender])) {
								$this->SendRaw(sprintf(":%s%s NOTICE %s :Please log in to me to continue.",$this->ServiceNum,$this->b64e($Dest), $Sender));
								break;
							}
						if ($this->LoadChannelOps($Parts[1], $Parts[2]) < $this->LoadChannelOps($Parts[1], $this->Acct[$Sender])) {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Deletion user as requested.", $this->ServiceNum,$this->b64e($Dest), $Sender),1);
							$this->SendRaw(sprintf("%s%s WC @%s :%s requested addition of %s at level %s", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $this->Num2Nick($Sender), $Parts[2], $Parts[3]),1);
								$id = $this->idByChan($Parts[1]);
								$uid = $this->idByUser($this->Acct[$Parts[2]]);
								pg_query($this->c, sprintf("DELETE FROM levels WHERE channel_id = (select id from channels where name = '%s') AND user_id = (select id from users where user_name = '%s');",$Parts[1], $Parts[2]));
						} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Bud, that did not work because you are deleting someone from a higher access than your own. :(", $this->ServiceNum,$this->b64e($Dest), $Sender),1);
						}
						break;
					case "op":
						if ($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 100) {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Opped you successfully with oplevel %s in %s. Congratulations.", $this->ServiceNum,$this->b64e($Dest), $Sender,$this->LoadChannelOps($Parts[1], $this->Acct[$Sender]), $Parts[1]),1);
							$this->SendRaw(sprintf("%s%s M %s +o %s:%s %s", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $Sender, (501 - $this->LoadChannelOps($Parts[1], $this->Acct[$Sender])), $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Go fuck yourself. User does not have access to channel.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "chanop":
						if (($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 100) and ($Parts[3] < $this->LoadChannelOps($Parts[1], $this->Acct[$Sender]))) {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Will chanop as specified", $this->ServiceNum,$this->b64e($Dest), $Sender,$this->LoadChannelOps($Parts[1], $this->idByUser($this->Acct[$Sender])), $Parts[1]),1);
							$this->SendRaw(sprintf("%s%s M %s +o %s:%s %s", $this->ServiceNum,$this->b64e($Dest), $Parts[1], array_search($Parts[2], $this->Nicks), (501 - $Parts[3]), $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Go fuck yourself. Not enough access to op user at level.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "deop":
						if ($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 100) {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Will deop (using KICK and SVSJOIN) as specified", $this->ServiceNum,$this->b64e($Dest), $Sender,$this->LoadChannelOps($Parts[1], $this->idByUser($this->Acct[$Sender])), $Parts[1]),1);
							$this->SendRaw(sprintf("%s K %s %s :Removing from channel to deop correctly and compatibly", $this->ServiceNum, $Parts[1], array_search($Parts[2], $this->Nicks)),1);	
							$this->SendRaw(sprintf("%s%s SJ %s %s", $this->ServiceNum,$this->b64e($Dest), array_search($Parts[2], $this->Nicks), $Parts[1]),1);
						} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Go fuck yourself. Not enough access to op user at level.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "mode":
						if ($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 400) {
							$Modes = explode(" ",$Msg,3);
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Changed modes %s successfully in %s. Congratulations.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Modes[2], $Parts[1]),1);
							$this->SendRaw(sprintf("%s%s M %s %s %s", $this->ServiceNum,$this->b64e($Dest), $Modes[1], $Modes[2], $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Go fuck yourself. User does not have access to channel. Minimum 400.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "mdop":
						if ($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 350) {
							$Modes = explode(" ",$Msg,3);
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Massively deopped channel and forcejoined/opped you. Congrats.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Modes[2], $Parts[1]),1);
							$this->SendRaw(sprintf("%s CM %s ohvmisp %s", $this->ServiceNum, $Modes[1], $this->Channels[$Parts[1]]["CH-TS"]),1);
							$this->SendRaw(sprintf("%s%s SJ %s %s", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
							$this->SendRaw(sprintf("%s%s M %s +o %s %s", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $Sender, $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Go fuck yourself. User does not have access to channel. Minimum 350.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "autojoin":
						$buf = sprintf(":%s%s NOTICE %s :Set autojoin channel %s.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$res = pg_query($this->d, "INSERT INTO ajchans VALUES ('".$Parts[1]."','".strtolower($this->Acct[$Sender])."')");
						break;
					case "decloakme":
						$buf = sprintf(":%s%s NOTICE %s :Unset cloak.",$this->ServiceNum,$this->b64e("1"),$Sender);
						$this->SendRaw($buf,1);
						$this->SendRaw(sprintf("%s FA %s %s", $this->ServiceNum,$Sender,$this->Hosts[$Sender]),1);
						break;
					case "cloakme":
						$buf = sprintf(":%s%s NOTICE %s :Reset cloak.",$this->ServiceNum,$this->b64e("1"),$Sender);
						$this->SendRaw($buf,1);
						$this->DoCloak($Sender,FALSE);
						break;
					case "autopart":
						$buf = sprintf(":%s%s NOTICE %s :Unset autojoin channel %s.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$res = pg_query($this->d, "DELETE FROM ajchans WHERE aj = '".$Parts[1]."' AND acct = '".strtolower($this->Acct[$Sender])."'");
						break;
					case "halfop":
						if (($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 50) && $this->canHalfOp) {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Half opped you successfully in %s. Congratulations.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
							$this->SendRaw(sprintf("%s%s M %s +h %s %s", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $Sender, $this->Channels[$Parts[1]]["CH-TS"]),1);
						} elseif ($this->LoadChannelOps($Parts[1], $this->idByUser($this->Acct[$Sender])) >= 50) {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Opped (level 50) you successfully in %s. Congratulations.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
							$this->SendRaw(sprintf("%s%s M %s +o %s:50 %s", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $Sender, $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Go fuck yourself. User does not have access to channel or halfops impossible on ircd.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "help":
					$botname = sprintf("%s",$this->s['BotNick'][1]);
						switch (strtolower(trim($Parts[1]))) {
							case "chanregister":
								$botname = sprintf("%s",$this->s['BotNick'][1]);
								$txhelp = <<<EOF
*** \x02$botname\x02 Help ***

Registers a channel under your account name.
You must have ops in the channel.

SYNTAX: /msg $botname CHANREGISTER #channelname
EOF;
								break;
							case "register":
								$botname = sprintf("%s",$this->s['BotNick'][1]);
								$txhelp = <<<EOF
*** \x02$botname\x02 Help ***

Registers an account name.
This is used to 'log in' to services.

Available secret questions:
1: What is your mother's maiden name?
2: What is/was your dog's/cat's name?
3: what is your father's birthday?

SYNTAX: /msg $botname REGISTER <Username> <Email> <Secret question numeric> <Secret answer>

You will be sent an email containing your magic cookie.
*** End Help ***
EOF;
								break;
							case "login":
								$botname = sprintf("%s",$this->s['BotNick'][1]);
								$txhelp = <<<EOF
*** \x02$botname\x02 Help ***

Logs in to a CService account.

SYNTAX: /msg $botname LOGIN <username> <password>

*** End Help ***
EOF;
							break;
							case "op":
								$botname = sprintf("%s",$this->s['BotNick'][1]);
								$txhelp = <<<EOF
*** \x02$botname\x02 Help ***

Ops you in a channel in which you have more than 100 chanlev.

SYNTAX: /msg $botname op <#channel>

*** End Help ***
EOF;
							break;
							case "halfop":
								$botname = sprintf("%s",$this->s['BotNick'][1]);
								$txhelp = <<<EOF
*** \x02$botname\x02 Help ***

Half-ops you in a channel in which you have more than 50 access.

SYNTAX: /msg $botname LOGIN <username> <password>

*** End Help ***
EOF;
							break;
							case "about":
								$txhelp = $this->about;
								break;
							default:
							$this->XhlpIdx = <<<EOF
*** \x02$botname\x02 Help ***
   
\x02X\x02 allows you to 'register' a channel. Although at this time it does not
prevent takeovers or fix them, it will soon (with DEOPALL).
   
Currently available commands are:
\x02HALFOP\x02                Halfops you in a channel in which you have
                      enough access (level 50)
\x02OP\x02                    Ops you in a channel in which you have
                      enough access (level 100)
\x02LOGIN\x02                 Logs you into CService.
\x02CHANREGISTER\x02          Allows you to register your channel with
                      CService.
\x02MDOP\x02                  If you are chanlev 350 or above, you may
                      mass-deop your channel. This removes \x02ALL\x02 ops,
                      halfops and voices and forcejoins and ops just
                      you. Use with care, and only if your channel has
                      been taken over.
\x02REGISTER\x02              If you wish to register your username,
                      go to the site mentioned in /msg $botname help
                      register
                      and follow the instructions given to you at
                      CService customs.
\x02CHANOP\x02                Level-ops \$3 with \$4 on \$2
                      Requires 100 level and level above oplevel specif-
                      ied
   
*** End Help ***
EOF;
							$txhelp = $this->XhlpIdx;
							break;
						}
						$help = explode(PHP_EOL, $txhelp);
						$helpsize = array_pop(array_keys($help));
						for ($i = 0;$i <= $helpsize;$i++) {
							$buf = sprintf(":%s%s NOTICE %s :%s",$this->ServiceNum,$this->b64e($Dest),$Sender,$help[$i]);
							$this->SendRaw($buf,1);
						}
				}
				break;
			case 2:
				if (!empty($this->Opers[$Sender])) {
				$Parts = explode(" ",$Msg);
				$Cmd = strtolower(trim($Parts[0]));
				switch ($Cmd) {
					case "vhost":
						$buf = sprintf("%s FA %s %s",$this->ServiceNum,$Parts[1],$Parts[2]);
						$this->SendRaw($buf,1);
						break;
					case "vhreg":
						$buf = sprintf(":%s%s NOTICE %s :Set vhost %s on account %s.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[2],$Parts[1]);
						$this->SendRaw($buf,1);
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($database, "INSERT INTO vhosts VALUES ('".$Parts[2]."','".strtolower($Parts[1])."')");
						$this->vhostIt(array_search(strtolower($Parts[1]), array_map('strtolower', $this->Acct)), $Parts[1]);
						break;
					case "raw":
						if ($this->LoadChannelOps("*", $this->Acct[$Sender]) < 700) {
							$buf = sprintf(":%s%s NOTICE %s :Due to its destructive potential, only channel service admins are allowed to use this command.",$this->ServiceNum,$this->b64e("2"),$Sender);
							$this->SendRaw($buf,1);
							break;
						}
						$buf = sprintf(":%s%s NOTICE %s :Raw traffic sent.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[2],$Parts[1]);
						$this->SendRaw($buf,1);
						$buf = sprintf("%s",implode(" ", array_slice($Parts,1)));
						$this->SendRaw($buf,1);
						break;
					case "akill":
						if ($this->LoadChannelOps("*", $this->Acct[$Sender]) < 700) {
							$buf = sprintf(":%s%s NOTICE %s :Due to its destructive potential, only channel service admins are allowed to use this command.",$this->ServiceNum,$this->b64e("2"),$Sender);
							$this->SendRaw($buf,1);
							break;
						}
						$buf = sprintf(":%s%s NOTICE %s :Akill set.",$this->ServiceNum,$this->b64e("2"),$Sender);
						$this->SendRaw($buf,1);
						$res = pg_query($this->c, "INSERT INTO glines VALUES ('".strtolower($Parts[1])."', '".strtolower($Parts[2])."')");
						break;
					case "global":
						$buf = sprintf(":%s%s NOTICE %s :Sending a \x02GLOBAL NOTICE\x02 as specified.",$this->ServiceNum,$this->b64e("2"),$Sender);
						$this->SendRaw($buf,1);
						$buf = sprintf(":%s%s NOTICE $* :[\x02%s\x02] %s",$this->ServiceNum,$this->b64e("3"),$this->Num2Nick($Sender),implode(" ",array_slice($Parts, 1)));
						$this->SendRaw($buf,1);
						break;
					case "wallchans":
						$buf = sprintf(":%s%s NOTICE %s :Sending a \x02MESSAGE TO ALL CHANNELS\x02 as specified.",$this->ServiceNum,$this->b64e("2"),$Sender);
						$this->SendRaw($buf,1);
						$buf = sprintf(":%s%s NOTICE $* :[\x02%s\x02] %s",$this->ServiceNum,$this->b64e("3"),$this->Num2Nick($Sender),implode(" ",array_slice($Parts, 1)));
						$this->SendRaw($buf,1);
						$chanarray = array_keys($this->Channels);
						foreach ($chanarray as $channame) {
							if ($channame != $this->RelayChan) {
							$buf = sprintf("%s%s P %s :[\x02%s\x02] %s",$this->ServiceNum,$this->b64e("3"),$channame,$this->Num2Nick($Sender),implode(" ",array_slice($Parts, 1)));
							$this->SendRaw($buf,1); }
						}
						break;
					case "cg":
						if ($this->LoadChannelOps("*", $this->Acct[$Sender]) < 700) {
							$buf = sprintf(":%s%s NOTICE %s :Due to its destructive potential, only channel service admins are allowed to use this command.",$this->ServiceNum,$this->b64e("2"),$Sender);
							$this->SendRaw($buf,1);
							break;
						}
						$buf = sprintf(":%s%s NOTICE %s :Set gline %s.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($this->c, "INSERT INTO glinechan VALUES ('".strtolower($Parts[1])."')");
						$this->vhostIt(array_search(strtolower($Parts[1]), array_map('strtolower', $this->Acct)), $Parts[1]);
						break;
					case "vhunreg":
						$buf = sprintf(":%s%s NOTICE %s :Deleted vhost on account %s. Please ask the user of this account to reconnect.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($database, "DELETE FROM vhosts WHERE ac='".strtolower($Parts[1])."'");
						break;
					case "reqapp":
						$buf = sprintf(":%s%s NOTICE %s :Application (none? this person did not apply!): %s",$this->ServiceNum,$this->b64e("2"),$Sender,system("/usr/bin/env grep -i 'account ".escapeshellcmd($Parts[1])." requested' requests.db"));
						$this->SendRaw($buf,1);
						break;
					case "approve":
						$buf = sprintf(":%s%s NOTICE %s :Approving %s' vhost, please ask him to reconnect.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$vhost = system("/usr/bin/env grep -vi 'account ".escapeshellcmd($Parts[1])." requested' requests.db > requests.db.new | cut -d' ' -f6 ; mv requests.db.new requests.db");
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($database, "INSERT INTO vhosts VALUES ('".$vhost."','".strtolower($Parts[1])."')");
						$this->vhostIt(array_search($Parts[1], $this->Acct), $Parts[1]);
						break;
					case "on":
						$buf = sprintf(":%s%s NOTICE %s :Reset cloak.",$this->ServiceNum,$this->b64e("1"),$Sender);
						$this->SendRaw($buf,1);
						$this->DoCloak($Sender,TRUE);
						break;
					default:
						$buf = sprintf(":%s%s NOTICE %s :/msg ".$this->BotNick." vhreg <account> <vhost>",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf(":%s%s NOTICE %s :Set a vHost on someone's account",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf(":%s%s NOTICE %s :/msg ".$this->BotNick." vhunreg <account>",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf(":%s%s NOTICE %s :Unset the vHost on someone's account",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf(":%s%s NOTICE %s :/msg ".$this->BotNick." reqapp <account>",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf(":%s%s NOTICE %s :See what someone requested for vhost",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						break;
				}
			} else {
				$Parts = explode(" ",$Msg);
				$Cmd = strtolower(trim($Parts[0]));
				switch ($Cmd) {
					case "on":
						$buf = sprintf(":%s%s NOTICE %s :Reset cloak.",$this->ServiceNum,$this->b64e("1"),$Sender);
						$this->SendRaw($buf,1);
						$this->DoCloak($Sender,TRUE);
						break;
					case "request":
						$buf = sprintf(":%s%s NOTICE %s :I just asked the opers to give you your requested vhost. You should receive it forthwithly.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$buf = sprintf("%s DS :account:%s requested:%s",$this->ServiceNum,$this->Acct[$Sender],$Parts[1]);
						$this->SendRaw($buf,1);
						$buf = sprintf("at %lu account %s requested %s\n",time(),$this->Acct[$Sender],$Parts[1]);
						$writo = fopen("requests.db", "a");
						fwrite($writo, $buf);
						fclose($writo);
						break;
					default:
						$buf = sprintf(":%s%s NOTICE %s :/msg ".$this->BotNick." request",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$buf = sprintf(":%s%s NOTICE %s :Request a vHost",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$buf = sprintf(":%s%s NOTICE %s :/msg ".$this->BotNick." on",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$buf = sprintf(":%s%s NOTICE %s :Cloak you with your vhost",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						break;
				}
			}
			break;
			case 4096:
				$Parts = explode(" ",$Msg);
				$Cmd = strtolower(trim($Parts[0]));
				switch ($Cmd) {
					case "adduser":
						if (!isset($this->Acct[$Sender])) {
								$this->SendRaw(sprintf(":%s%s NOTICE %s :Please log in to me to continue.",$this->ServiceNum,$this->b64e(1), $Sender));
								break;
							}
						if ($Parts[2] > $this->LoadChannelOps($Target, $this->Acct[$Sender])) {
							$this->SendRaw(sprintf("%s%s P %s :%s: Adding user as requested.", $this->ServiceNum,$this->b64e(1), $Target, $this->Num2Nick($Sender)),1);
							$this->SendRaw(sprintf("%s%s WC @%s :%s requested addition of %s at level %s", $this->ServiceNum,$this->b64e(1), $Target, $this->Num2Nick($Sender), $Parts[1], $Parts[2]),1);
								$id = $this->idByChan($Parts[1]);
								$uid = $this->idByUser($this->Acct[$Parts[2]]);
								pg_query($this->c, sprintf("DELETE FROM levels WHERE channel_id =  (select id from channels where name = '%s') AND user_id = (select id from users where user_name = '%s');",$Target, $Parts[1]));
								pg_query($this->c, sprintf("INSERT INTO levels (channel_id, user_id, access, added, last_updated) VALUES ((select id from channels where name = '%s'), (select id from users where lower(user_name) = '%s'), %s, %s, 1933780085);",$Target, strtolower($Parts[1]), $Parts[2], time()));
						} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Bud, that did not work because you are adding someone at a higher access than your own. :(", $this->ServiceNum,$this->b64e($Dest), $Sender),1);
						}
						break;
					case "op":
						if ($this->LoadChannelOps($Target, $this->Acct[$Sender]) >= 100) {
							$this->SendRaw(sprintf("%s%s M %s +o %s:%s %s", $this->ServiceNum,$this->b64e(1), $Target, $Sender, (501 - $this->LoadChannelOps($Target, $this->Acct[$Sender])), $this->Channels[$Target]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf(":%s%s NOTICE %s :Go fuck yourself. User does not have access to channel.", $this->ServiceNum,$this->b64e(1), $Sender, $Parts[1]),1);
						}
						break;
					case "halfop":
						if ($this->LoadChannelOps($Target, $this->Acct[$Sender]) >= 50) {
							$this->SendRaw(sprintf("%s%s M %s +h %s %s", $this->ServiceNum,$this->b64e(1), $Target, $Sender, $this->Channels[$Target]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf("%s%s P %s :%s: Permission denied.", $this->ServiceNum,$this->b64e(1), $Target, $this->Num2Nick($Sender)),1);
						}
						break;
					case "down":
						if ($this->LoadChannelOps($Target, $this->Acct[$Sender]) >= 50) {
							$this->SendRaw(sprintf("%s K %s %s :Deop kick for compatibility", $this->ServiceNum, $Target,$Sender),1);
							$this->SendRaw(sprintf("%s%s SJ %s %s", $this->ServiceNum,$this->b64e(1), $Sender, $Target),1);
						} else {
							$this->SendRaw(sprintf("%s%s P %s :%s: Permission denied.", $this->ServiceNum,$this->b64e(1), $Target, $this->Num2Nick($Sender)),1);
						}
						break;
					case "help":
						switch (strtolower(trim($Parts[1]))) {
							case "op":
								$botname = sprintf("%s",$this->s['BotNick'][1]);
								$txhelp = <<<EOF
*** \x02$botname\x02 Help ***

Ops you in a channel in which you have more than 100 chanlev.

SYNTAX: /msg $botname op <#channel>

*** End Help ***
EOF;
							break;
							case "halfop":
								$botname = sprintf("%s",$this->s['BotNick'][1]);
								$txhelp = <<<EOF
*** \x02$botname\x02 Help ***

Half-ops you in a channel in which you have more than 50 access.

SYNTAX: /msg $botname LOGIN <username> <password>

*** End Help ***
EOF;
							break;
							case "about":
								$txhelp = $this->about;
								break;
							default:
							$botname = sprintf("%s",$this->s['BotNick'][1]);
							$txhelp = $this->hlpIdx; 
							break;
						}
						$help = explode(PHP_EOL, $txhelp);
						$helpsize = array_pop(array_keys($help));
						for ($i = 0;$i <= $helpsize;$i++) {
							$buf = sprintf(":%s%s NOTICE %s :%s",$this->ServiceNum,$this->b64e(1),$Sender,$help[$i]);
							$this->SendRaw($buf,1);
						}
				}
				break;
		}
	}
	
	function Burst($Args) {
		/* When we receive a burst message, we have to know how many users are in the chan
		   so we can build a function, when the channel is empty, the bot should part.
		*/
		/* [get] AB B #coder-com 1064938432 ABAAA:o 
		   [get] AB B #coder-com 1064938432 +lk 15 test ABAAA:o */
		$Chan = trim(strtolower($Args[2]));
		if (preg_match("/\+/",$Args[4])) {
			if (preg_match("/kl/",$Args[4]) || preg_match("/lk/",$Args[4]))
				$this->Users[$Chan] = $Args[7];
			elseif (preg_match("/l/",$Args[4]) || preg_match("/k/",$Args[4]))
				$this->Users[$Chan] = $Args[6];
		} else {
			$this->Users[$Chan] = $Args[4];
		}
		if (isset($this->Users[$Chan])) {
		$Temp = explode(",",$this->Users[$Chan]);
		foreach ($Temp as $Index => $Num) {
			if (strpos($Num, ":o") == 5) $this->Channels[$Chan][substr($Num,0,5)]["op"] = true;
			if (strpos($Num, ":0") == 5) $this->Channels[$Chan][substr($Num,0,5)]["op"] = true;
			if (strpos($Num, ":1") == 5) $this->Channels[$Chan][substr($Num,0,5)]["op"] = true;
			if (strpos($Num, ":2") == 5) $this->Channels[$Chan][substr($Num,0,5)]["op"] = true;
			if (strpos($Num, ":3") == 5) $this->Channels[$Chan][substr($Num,0,5)]["op"] = true;
			if (strpos($Num, ":4") == 5) $this->Channels[$Chan][substr($Num,0,5)]["op"] = true;
			if (strpos($Num, ":5") == 5) $this->Channels[$Chan][substr($Num,0,5)]["op"] = true;
			if (strpos($Num, ":6") == 5) $this->Channels[$Chan][substr($Num,0,5)]["op"] = true;
			if (strpos($Num, ":7") == 5) $this->Channels[$Chan][substr($Num,0,5)]["op"] = true;
			if (strpos($Num, ":8") == 5) $this->Channels[$Chan][substr($Num,0,5)]["op"] = true;
			if (strpos($Num, ":9") == 5) $this->Channels[$Chan][substr($Num,0,5)]["op"] = true;
			$Num = str_replace(":ov","",$Num);
			$Num = str_replace(":o","",$Num);
			$Num = str_replace(":v","",$Num);
			$Num = trim($Num);
			$this->Channels[$Chan][substr($Num,0,5)]["in"] = TRUE;
			$this->Channels[$Chan]["CH-TS"] = $Args[3];
		} }
	}
	
	function AddChan($Args) {
		$Chan = trim(strtolower($Args[2]));
		$Num = trim($Args[0]);
		$this->Channels[$Chan][$Num]["in"] = TRUE;
		$this->Channels[$Chan]["CH-TS"] = $Args[3];
		var_dump($this->LoadChannelOps($Chan, $this->Acct[$Num]));
		if ($Args[1] == "C")
			$this->Channels[$Chan][$Num]["op"] = true;
		if ($this->LoadChannelOps($Chan, $this->Acct[$Num]) >= 100) {
			$this->SendRaw(sprintf(":%s%s NOTICE %s :Opped you successfully with oplevel %s in %s. Congratulations.", $this->ServiceNum,$this->b64e(1), $Num,$this->LoadChannelOps($Chan, $this->Acct[$Num]), $Parts[1], $Chan),1);
			$this->SendRaw(sprintf("%s%s M %s +o %s:%s %s", $this->ServiceNum,$this->b64e(1), $Chan, $Num, (501 - $this->LoadChannelOps($Chan, $this->Acct[$Num])), $this->Channels[$Chan]["CH-TS"]),1);
		}
		if (($this->LoadChannelOps($Chan, $this->Acct[$Num]) >= 50) and ($this->LoadChannelOps($Chan, $this->Acct[$Num]) <= 99)) {
			$this->SendRaw(sprintf(":%s%s NOTICE %s :Half opped you successfully in %s. Congratulations.", $this->ServiceNum,$this->b64e(1), $Num, $Chan),1);
			$this->SendRaw(sprintf("%s%s M %s +h %s %s", $this->ServiceNum,$this->b64e(1), $Chan, $Num, $this->Channels[$Chan]["CH-TS"]),1);
		}
	}
	
	function DelChan($Args) {		
		$Chan = trim(strtolower($Args[2]));
		$Num = trim($Args[0]);
		unset($this->Channels[$Chan][$Num]);
		$this->CheckEmptyChan($Chan);
		@ob_flush();
	}
	
	
	function SendRaw($Line,$Show) {
		/* This sends information to the server */
		echo $Line.PHP_EOL;
		fwrite($this->Socket,$Line."\r\n");
	}
	
	function CheckEmptyChan($Chan) {
	return;
	}
	
	function SaveNicks($Args) {
		/* Somebody changed his nick or a server is telling us his users */
		/* [get] AP N Q 2 100 TheQBot Q.AliTriX.nl +oiwdk B]AAAB APAAA :The Q Bot
		   [get] AB N AliTriX 1 1061147585 wer alitrix.homelinux.net +oiwg B]AAAB ABAAG :Ali
		   [get] ABAAG N test 1061154478 */
		$Nick = $Args[2];
		$Oper = false;
		
		if (count($Args) == 4) { /* Nick change */
			$Numeric = substr($Args[0],-9);
			$Nick = $Args[2];
			$this->Nicks[$Numeric] = $Nick;
			if ($this->isIdentified($Numeric)) {
				return;
			} else {
			if ($this->isWrongNick($Numeric, $this->Acct[$Numeric])) {
				$this->SendRaw(sprintf(":%s%s NOTICE %s :                  ---===[\x02CService Nickname Protection\x02]===---", $this->ServiceNum,$this->b64e(1), $Numeric),1);
				$this->SendRaw(sprintf(":%s%s NOTICE %s :Your current nickname is registered and protected. If this is your nickname, please", $this->ServiceNum,$this->b64e(1), $Numeric),1);
				$this->SendRaw(sprintf(":%s%s NOTICE %s :  log in to the appropriate CService account. If you do not change your nick, the", $this->ServiceNum,$this->b64e(1), $Numeric),1);
				$this->SendRaw(sprintf(":%s%s NOTICE %s :           nickname's owner is entitled to force you off the network.", $this->ServiceNum,$this->b64e(1), $Numeric),1);
				$this->SendRaw(sprintf(":%s%s NOTICE %s :           To log in, type \x02/msg %s@%s LOGIN \x1fusername password\x1f\x02", $this->ServiceNum,$this->b64e(1), $Numeric, $this->s['BotNick'][1], $this->ServerName),1);
			} }
			return 0;
		}
		$Modes = $Args[5]; /*
		if (preg_match("/\+/i",$Modes)) {
			* Setting the numeric *
			if (preg_match("/r/",$Args[7]) && preg_match("/f/",$Args[7])) {
				$Numeric = $Args[11];
				$this->Acct[$Numeric] = $Args[8];
				$VHost = $Args[9];
				$this->vhostIt($Numeric, $this->Acct[$Numeric]);
			if (strlen($Args[10]) > 8) {
				$LongestRun = 25 - strlen($Args[10]);
				if (!($LongestRun = 1)) $IPv6 = str_replace("_", str_repeat($Args[10], $LongestRun), $Args[10]);
				$this->IPs[$Numeric] = implode(":", str_split($this->convBase($Args[10],"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789abcdef"),4));
			} else {
				$this->IPs[$Numeric] = long2ip($this->convBase($Args[10],"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789"));
			}
			} elseif (preg_match("/f/",$Args[7])) {
				$VHost = $Args[8];$Numeric = $Args[10];
			if (strlen($Args[9]) > 8) {
				$LongestRun = 25 - strlen($Args[10]);
				if (!($LongestRun = 1)) $IPv6 = str_replace("_", str_repeat($Args[9], $LongestRun), $Args[10]);
				$this->IPs[$Numeric] = implode(":", str_split($this->convBase($Args[9],"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789abcdef"),4));
			} else {
				$this->IPs[$Numeric] = long2ip($this->convBase($Args[9],"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789"));
			}
			} elseif (preg_match("/r/",$Args[7])) {
				$Numeric = $Args[10];
				$this->Acct[$Numeric] = $Args[8];
				$this->vhostIt($Numeric, $this->Acct[$Numeric]);
				if (strlen($Args[9]) > 6) {
					$LongestRun = 25 - strlen($Args[10]);
					if (!($LongestRun = 1)) $IPv6 = str_replace("_", str_repeat($Args[9], $LongestRun), $Args[10]);
					$this->IPs[$Numeric] = implode(":", str_split($this->convBase($Args[9],"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789abcdef"),4));
				} else {
					$this->IPs[$Numeric] = long2ip($this->convBase($Args[9],"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789"));
				}
				$this->vhostIt($Numeric, $this->Acct[$Numeric]);
			} else {
				$Numeric = $Args[9];
			if (strlen($Args[8]) > 6) {
				$LongestRun = 25 - strlen($Args[10]);
				if (!($LongestRun = 1)) $IPv6 = str_replace("_", str_repeat($Args[8], $LongestRun), $Args[10]);
				$this->IPs[$Numeric] = implode(":", str_split($this->convBase($Args[8],"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789abcdef"),4));
			} else {
				var_dump($this->convBase($Args[8],"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789"));
				$this->IPs[$Numeric] = long2ip($this->convBase($Args[8],"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789"));
			}
			}
			if (preg_match("/o/",$Args[7])) {
				$Oper = true;
			}
		} else {
			$Numeric = $Args[8];
			if (strlen($Args[7]) > 6) {
				$LongestRun = 25 - strlen($Args[10]);
				if (!($LongestRun = 1)) $IPv6 = str_replace("_", str_repeat($Args[7], $LongestRun), $Args[10]);
				$this->IPs[$Numeric] = implode(":", str_split($this->convBase($Args[7],"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789abcdef"),4));
			} else {
				$this->IPs[$Numeric] = inet_ntop($this->convBase($Args[7],"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789","0123456789"));
			}
			}*/
		$Numeric = $Args[9];
		$Host = $Args[6];
		$this->Hosts[$Numeric] = ($Args[10] == "*") ? $Args[7] : $Args[10];
		$this->IPs[$Numeric] = ($Args[9] == "0") ? "0.0.0.0" : $Args[9];
		$this->Acct[$Numeric] = ($Args[11] == "*") ? "" : $Args[11];
		$this->Nicks[$Numeric] = $Nick;
		$this->Opers[$Numeric] = $Oper;
		$this->Ident[$Numeric] = $Args[6];
		
		$this->DoCloak($Numeric,TRUE); 
		
		if ($this->is_blacklisted_tor($this->IPs[$Numeric]) and !($this->Acct[$Numeric])) {
			$this->SendRaw(sprintf(":%s%s KILL %s :[\x02CService Network Protection\x02] You use Tor in a manner unacceptable to our network.", $this->ServiceNum, $this->b64e(1), $Numeric),1);
		} 
			if ($this->isIdentified($Numeric)) {
			} else {
			if ($this->isWrongNick($Numeric, $this->Acct[$Numeric])) {
$this->SendRaw(sprintf(":%s%s NOTICE %s :                  ---===[\x02CService Nickname Protection\x02]===---", $this->ServiceNum,$this->b64e(1), $Numeric),1);
$this->SendRaw(sprintf(":%s%s NOTICE %s :Your current nickname is registered and protected. If this is your nickname, please", $this->ServiceNum,$this->b64e(1), $Numeric),1);
$this->SendRaw(sprintf(":%s%s NOTICE %s :  log in to the appropriate CService account. If you do not change your nick, the", $this->ServiceNum,$this->b64e(1), $Numeric),1);
$this->SendRaw(sprintf(":%s%s NOTICE %s :           nickname's owner is entitled to force you off the network.", $this->ServiceNum,$this->b64e(1), $Numeric),1);
$this->SendRaw(sprintf(":%s%s NOTICE %s :           To log in, type \x02/msg %s@%s LOGIN \x1fusername\x1f \x1fpassword\x1f\x02", $this->ServiceNum,$this->b64e(1), $Numeric, $this->s['BotNick'][1], $this->ServerName),1);
			} else {
			} }
	}

	function Num2Nick($Numeric) {
		/* Changing a numeric into a nick */
		if (!empty($this->Nicks[$Numeric]))
			return $this->Nicks[$Numeric];
		else
			return ".n.a.";
	}
}

}
$FishBot = new FishBot();
$FishBot->LoadChannels();
$FishBot->StartBot();

?>
