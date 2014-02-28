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
        $base10=convBase($numberInput, $fromBaseInput, '0123456789');
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

function b64e($id, $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789[]')
{
    $base = strlen($alphabet);
    $short = '';
    while($id) {
        $id = ($id-($r=$id%$base))/$base;     
        $short = $alphabet{$r} . $short;
    };
    $str = strtr(sprintf("%3s",$short)," ","A");
    return $str;
}

	function SendRaw($Line,$Show) {
		/* This sends information to the server */
		printf("put: %s\n", $Line);@ob_flush();
		fputs($this->Socket,$Line."\r\n");
	}
	public function vhostIt($Numeric, $Acct) {
		$database = pg_connect($this->DatabaseParams);
		$res = pg_query("SELECT ah FROM vhosts WHERE ac = '".strtolower($Acct)."'");
		$rows = pg_num_rows($res);
		$Vhost = pg_fetch_result($res,0);
		if ($res) {
			$buf = sprintf("%s FA %s %s",$this->ServiceNum, $Numeric, $Vhost);
			$this->SendRaw($buf,1);
		}
		$buf = sprintf("%s DS :account sent. %s",$this->ServiceNum,$Acct);
		$this->SendRaw($buf,1);
		pg_close($database);
	}
	/******** CONFIGURATION - BEGIN  ********/
	function FishBot() {
		$this->hlpIdx = <<<EOF
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

*** End Help ***
EOF;

$this->about = <<<EOF
*** About \x02$botname\x02 ***

\x02$botname\x02 is an instance of HyperStar, a multi-client monolithic pseud-
oserver for P10-based IRC networks (and more protocols are to follow).

My other clients are probably just a hostserv. Ask your admin.

*** End Help ***
EOF;
		/* Configuring the bot */
		$this->ServiceName = "HStar"; /* Name of the bot */
		$this->ServiceDesc = "For how to get a vhost type: /msg HStar help"; /* The IRC Name and Discription */
		$this->ServiceNum = "Lw" ; /* Bot numeric */
		$this->s = array();
		$this->srvs = 2;
		
		$this->canHalfOp = true;
		
		$this->s['BotNick'][1] = "X";
		$this->s['BotUser'][1] = "X";
		$this->s['BotHost'][1] = "cservice.umbrellix.tk";
		$this->s['BotModes'][1] = "+oidkr L";
		$this->s['Desc'][1] = "Try these: /msg X help register and /msg X help chanregister";

		$this->s['BotNick'][2] = "H";
		$this->s['BotUser'][2] = "H";
		$this->s['BotHost'][2] = "hostserv.umbrellix.tk";
		$this->s['BotModes'][2] = "+oidkr HStar";
		$this->s['Desc'][2] = "BETA multiclient support for PHPStar";

		$this->ServerName = "host.serv";
		$this->ServerHost = "127.0.0.1"; /* IP/Host to connect to */
		$this->ServerPort = 4400; /* Port to connect to */
		$this->ServerPass = "link"; /* Password to use for the connection between the service and server */
		$this->DeBug = FALSE; /* TRUE = on, FALSE = off */
		/* TIP: If you put DeBug TRUE, and you are starting the script like this: ./fishbot.php &, then it's
		   better to start the robot like this: ./fishbot.php >/dev/null 2>/dev/null &, cause when he is gonna
		   send a message to the terminal and it's closed, then the bot will get killed, cause there isn't a terminal
		   to send anything to it. (except if you use the /dev/null or a file)*/
		
		$this->ChannelsFile = "channels"; /* The file where the channels should be stored, !REMEMBER! If you choose
						     a directory, please make the directory FIRST then start the bot. */
		$this->DatabaseParams = "host=127.0.0.1 dbname=hservice user=j4jackj";
		$this->CServiceParams = "host=127.0.0.1 dbname=cservice user=j4jackj";
		$this->c = pg_pconnect($this->CServiceParams);
		$this->PingPongs = 3; /* After how many ping-pongs should he save the channels into a file? */
		
		
		$this->EB = FALSE; /* Please don't change this */
	}
	/******** CONFIGURATION - END  ********/
	/* DON'T CHANGE THE LINES BELOW, IF YOU DON'T KNOW WHAT YOU ARE DOING */
	
	function LoadChannels() {
		/* Load the channels from the DB */
		$res = pg_query($this->c, "SELECT name, channel_ts, channel_mode, id FROM channels");
		$rows = pg_num_rows($res);
		$chans = pg_fetch_row($res);
		for ($i = 1;$chans != FALSE;$i++) {
			$tmp = sprintf("%s B %s %s %sz %s%s:o %s%s:o",$this->ServiceNum,$chans[0],$chans[1],$chans[2],$this->ServiceNum,$this->b64e(3),$this->ServiceNum,$this->b64e(3));
			$this->SendRaw($tmp,1);
			$tmp = sprintf("%s%s L %s",$this->ServiceNum,$this->b64e(1),$chans[0]);
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
	
function std_make_password($password, $crypt) {
	$valid = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.$*_";
	$salt = "";
	srand((double) microtime() * 1000000);
	if ($password == "") {
		for ($k = 0; $k < 8; $k++)
			$password = $password . $valid[rand(0, strlen($valid)-1)];
	}
	for ($k = 0; $k < 8; $k++)
		$salt = $salt . $valid[rand(0, strlen($valid)-1)];
	$crypt = $salt . md5($salt . $password); return $crypt;
}

function std_check_password($username, $password) {
        $chk = pg_exec($this->c, "SELECT password, id FROM users WHERE lower(user_name) = lower('$username')");
 	if (pg_numrows($chk) == 0)
		return false; // Failed
	$chk = pg_fetch_object($chk, 0);
	$crypt = $chk->password;
	if ($crypt == "")
		return true; // Success
	$salt = substr($crypt, 0, 8);
	$crypt = substr($crypt, 8);
	if (md5($salt . $password) == $crypt)
		return true; // Success!
	return false; // Failed
}

	function LoadChannelOps($channel, $uid) {
		/* Load the channels from the DB */
		$res = pg_query($this->c, "SELECT channel_id, user_id, access FROM levels WHERE channel_id = ".$this->Channels[$channel]["CH-ID"]." AND user_id = ".$uid.";");
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
		$res = pg_query($this->c, "SELECT name, id FROM channels WHERE channel_name = '".$username."'");
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
	
	function StartBot() {
		/* Yup, how about begin with the real work, THE BOTS! */
		$this->Socket = fsockopen($this->ServerHost,$this->ServerPort);
		
		$Time = time();
		$tmp = sprintf('PASS :%s',$this->ServerPass);
		$this->SendRaw($tmp,1);
		$tmp = sprintf('SERVER %s 1 %s %s J10 %s]]] +s :%s',$this->ServerName,$Time,$Time,$this->ServiceNum,$this->ServiceDesc);
		$this->SendRaw($tmp,1);
		
		for ($k = 1; $k <= $this->srvs; $k++) {
			$tmp = sprintf('%s N %s 1 %s %s %s %s B]AAAB %s%s :%s',$this->ServiceNum,$this->s['BotNick'][$k],
							$Time,$this->s['BotUser'][$k],$this->s['BotHost'][$k],$this->s['BotModes'][$k],$this->ServiceNum,$this->b64e($k),$this->s['Desc'][$k]);
			$this->SendRaw($tmp,1);
		}
		$tmp = sprintf('%s EB',$this->ServiceNum);
		$this->SendRaw($tmp,1);
		$this->Counter =0;
		
		
		if ($this->DeBug) {
			printf("Bot sended his own information to the server, waiting for respond.\n");
			@ob_flush();
		}
		
		$this->Idle();
	}
	
	function Idle() {
		/* Checking the incoming information */
		while (!feof($this->Socket)) {
			$this->Get = fgets($this->Socket,384);
			if (!empty($this->Get)) {
				$Args = explode(" ",$this->Get);
				$Cmd = trim($Args[1]);
				$Dest = $this->convBase(substr($Args[2], -3),"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789[]","0123456789");
				switch ($Cmd) {
					case "EB": /* End of Burst */
						$this->EA();printf("%s", $this->Get);@ob_flush();
						break;
					case "G": /* Ping .. Pong :) */
						$this->Pong($Args);printf("%s", $this->Get);@ob_flush();
						break;
					case "P": /* They are talking to us */
						$this->PrivMsg($Dest,$Args,$this->Get);
						break;
					case "N": /* We got a nick-change or a nick-burst msg */
						$this->SaveNicks($Args);printf("%s", $this->Get);@ob_flush();
						break;
					case "M": /* A oper logged in, or a chanmode changed? */
						$this->AddOper($Args);
						printf("%s", $this->Get);@ob_flush();
						break;
					case "Q": /* They quit as well, finally! :P */
						$this->DelUser($Args);printf("%s", $this->Get);@ob_flush();
						break;
					case "B": /* We received a burst line */
						$this->Burst($Args);printf("%s", $this->Get);@ob_flush();
						break;
					case "J": /* Somebody joined a channel */
						$this->AddChan($Args);
						printf("%s", $this->Get);@ob_flush();
						break;
					case "C": /* Somebody joined a channel */
						$this->AddChan($Args);printf("%s", $this->Get);@ob_flush();
						break;
					case "AC": /* Someone logged in to channel services, handle their vhosting */
						$this->Acct[$Args[2]] = str_replace(array("\r", "\n"), "", $Args[3]);
						$this->vhostIt($Args[2],str_replace(array("\r", "\n"), "", $Args[3]));
						break;
					case "L": /* If somebody parts a channel, we have to notice that */
						$this->DelChan($Args);printf("%s", $this->Get);@ob_flush();
						break;
				}
			}
		}
	}
	
	function EA() {
		/* End of Burst received */
		/* [get] AB EB */
		if (empty($this->EB)) {
			$tmp = sprintf('%s EA',$this->ServiceNum);
			$this->SendRaw($tmp,1);
			$this->EB = TRUE;
			$this->LoadChannels();
		}
	}
	
	function JoinChannels() {
		/* Join the channels after receiving a EA from the server */
		foreach($this->Chans as $number => $chan) {
			$tmp = sprintf('%s SH %s',$this->BotNum,$chan);
			$this->SendRaw($tmp,1);
			$this->CheckEmptyChan($chan);
		}
		if ($this->DeBug) {
			printf("Server accepted us.\n");
			printf("Joining all channels.\n");
			@ob_flush();
		}
	}
	
	function Pong($Args) {
		/* The server pinged us, we have to pong him back */
		/* [get] AB G !1061145822.928732 fish.go.moh.yes 1061145822.928732 */
		$this->Counter++;
		if ($this->Counter >= $this->PingPongs) {
			$this->SaveChannels();
			$this->Counter=0; /* Putting it on zer0, for a new save/count */
		}
		$tmp = sprintf('%s Z %s',$this->ServiceNum,$Args[2]);
		$this->SendRaw($tmp,0);
		if ($this->DeBug) {
			printf("Ping Pong?!\n");
			@ob_flush();
		}
	}
	function SendMsg($To,$Msg) {
		/* Sending a msg */
		$tmp = sprintf('%s P %s :%s',$this->BotNum,$To,$Msg);
		$this->SendRaw($tmp,0);
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
						if ($Modes[$i] == "r" && $Status == "+")
							$this->Acct[$Numeric] = $Args[3];
					}
				}
				
			}
		} else {
			for($i=0;$i<$Count;$i++) {
				if ($Modes[$i] == "+") 
					$Status = "+";
				elseif ($Modes[$i] == "-")
					$Status = "-";
				else {
					if ($Modes[$i] == "o" && $Status == "+")
						{ echo "Someone became op";
						$this->Channels[$Chan][$Args[3+$i]]["op"] = true;
					}
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
							$this->SendRaw(sprintf("%s%s O %s :Not enough parameters.", $this->ServiceNum,$this->b64e($Dest), $Sender),1); break; }
						if ($this->std_check_password($Parts[1], $Parts[2])) {
							$this->Acct[$Sender] = $Parts[1];
							$this->AcctID[$Sender] = $this->idByUser($Parts[1]);
							$this->SendRaw(sprintf("%s AC %s %s", $this->ServiceNum, $Sender, $Parts[1]),1);$this->Acct[$sender] = $Parts[1];
							$this->vhostIt($Sender, $Parts[1]);
							$this->SendRaw(sprintf("%s%s O %s :Logged you in successfully as %s. Congratulations.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						} else {
							$this->SendRaw(sprintf("%s%s O %s :Go fuck yourself. Wrong login name or password for %s.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "chanregister":
						if (!isset($this->Acct[$sender])) { $this->SendRaw(sprintf("%s%s O %s :Please log in to me to continue.",$this->ServiceNum,$this->b64e($Dest), $Sender)); break; }
						if ($this->Channels[$Parts[1]][$Sender]["op"]) {
							$chid = time();
							$this->SendRaw(sprintf("%s%s O %s :Registering %s to you.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
							
							$this->SendRaw(sprintf("%s%s WC @%s :%s registered this channel.", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $this->Num2Nick($Sender)),1);
							if (!isset($this->Channels[strtolower($Parts[1])]["CH-ID"])){
								pg_query($this->c, sprintf("INSERT INTO channels (id, name, registered_ts, channel_ts, channel_mode, limit_offset, limit_period, limit_grace, limit_max, last_updated) VALUES (%s, '%s', %s, %s, '+tnCN', 5, 20, 1, 0, 313370083);",$chid, $Parts[1], $chid, $this->Channels[strtolower($Parts[1])]["CH-TS"], time()));
								pg_query($this->c, sprintf("INSERT INTO levels (channel_id, user_id, access, added, last_updated) VALUES (%s, (select id from users where user_name = '%s'), %s, %s, 1933780085);",$chid, $this->Acct[$Sender], "500", time()));
								$this->SendRaw(sprintf("%s%s M %s +z %s", $this->ServiceNum,$this->b64e($Dest), $Sender, $this->Channels[strtolower($Parts[1])]["CH-TS"]),1);
							}
						} else {
							$this->SendRaw(sprintf("%s%s O %s :Not registering %s to you because you are not currently opped on that channel.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "adduser":
						if (!isset($this->Acct[$sender])) {
								$this->SendRaw(sprintf("%s%s O %s :Please log in to me to continue.",$this->ServiceNum,$this->b64e($Dest), $Sender));
								break;
							}
						if ($Parts[3] < $this->LoadChannelOps($Parts[1], $this->idByUser($this->Acct[$Sender]))) {
							$this->SendRaw(sprintf("%s%s O %s :Adding user as requested.", $this->ServiceNum,$this->b64e($Dest), $Sender),1);
							$this->SendRaw(sprintf("%s%s WC @%s :%s requested addition of %s at level %s", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $this->Num2Nick($Sender), $Parts[2], $Parts[3]),1);
								$id = $this->idByChan($Parts[1]);
								$uid = $this->idByUser($this->Acct[$Parts[2]]);
								pg_query($this->c, sprintf("INSERT INTO levels (channel_id, user_id, access, added, last_updated) VALUES ((select id from channels where name = '%s'), (select id from users where user_name = '%s'), %s, %s, 1933780085);",$Parts[1], $Parts[2], $Parts[3], time()));
						} else {
							$this->SendRaw(sprintf("%s%s O %s :Bud, that did not work because you are adding someone at a higher access than your own. :(", $this->ServiceNum,$this->b64e($Dest), $Sender),1);
						}
						break;
					case "op":
						if ($this->LoadChannelOps($Parts[1], $this->idByUser($this->Acct[$Sender])) >= 100) {
							$this->SendRaw(sprintf("%s%s O %s :Opped you successfully in %s. Congratulations.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
							$this->SendRaw(sprintf("%s%s M %s +o %s %s", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $Sender, $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf("%s%s O %s :Go fuck yourself. User does not have access to channel.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "mode":
						if ($this->LoadChannelOps($Parts[1], $this->idByUser($this->Acct[$Sender])) >= 200) {
							$Modes = explode(" ",$Msg,3);
							$this->SendRaw(sprintf("%s%s O %s :Changed modes %s successfully in %s. Congratulations.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Modes[2], $Parts[1]),1);
							$this->SendRaw(sprintf("%s%s M %s %s %s", $this->ServiceNum,$this->b64e($Dest), $Modes[1], $Modes[2], $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf("%s%s O %s :Go fuck yourself. User does not have access to channel. Minimum 200.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "mdop":
						if ($this->LoadChannelOps($Parts[1], $this->idByUser($this->Acct[$Sender])) >= 350) {
							$Modes = explode(" ",$Msg,3);
							$this->SendRaw(sprintf("%s%s O %s :Massively deopped channel and forcejoined/opped you. Congrats.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Modes[2], $Parts[1]),1);
							$this->SendRaw(sprintf("%s CM %s ohvmisp %s", $this->ServiceNum, $Modes[1], $this->Channels[$Parts[1]]["CH-TS"]),1);
							$this->SendRaw(sprintf("%s%s SJ %s %s", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
							$this->SendRaw(sprintf("%s%s M %s +o %s %s", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $Sender, $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf("%s%s O %s :Go fuck yourself. User does not have access to channel. Minimum 350.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "halfop":
						if ($this->LoadChannelOps($Parts[1], $this->idByUser($this->Acct[$Sender])) >= 50) {
							$this->SendRaw(sprintf("%s%s O %s :Half opped you successfully in %s. Congratulations.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
							$this->SendRaw(sprintf("%s%s M %s +h %s %s", $this->ServiceNum,$this->b64e($Dest), $Parts[1], $Sender, $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf("%s%s O %s :Go fuck yourself. User does not have access to channel.", $this->ServiceNum,$this->b64e($Dest), $Sender, $Parts[1]),1);
						}
						break;
					case "help":
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

SYNTAX: firefox http://www.umbrellix.tk/live
        # ;P
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
							$botname = sprintf("%s",$this->s['BotNick'][1]);
							$txhelp = $this->hlpIdx;
							break;
						}
						$help = explode(PHP_EOL, $txhelp);
						$helpsize = array_pop(array_keys($help));
						for ($i = 0;$i <= $helpsize;$i++) {
							$buf = sprintf("%s%s O %s :%s",$this->ServiceNum,$this->b64e($Dest),$Sender,$help[$i]);
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
						$buf = sprintf("%s%s O %s :Set vhost %s on account %s.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[2],$Parts[1]);
						$this->SendRaw($buf,1);
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($database, "INSERT INTO vhosts VALUES ('".$Parts[2]."','".strtolower($Parts[1])."')");
						$this->vhostIt(array_search(strtolower($Parts[1]), array_map('strtolower', $this->Acct)), $Parts[1]);
						break;
					case "cg":
						$buf = sprintf("%s%s O %s :Set gline %s.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($this->c, "INSERT INTO glinechan VALUES ('".strtolower($Parts[1])."')");
						$this->vhostIt(array_search(strtolower($Parts[1]), array_map('strtolower', $this->Acct)), $Parts[1]);
						break;
					case "vhunreg":
						$buf = sprintf("%s%s O %s :Deleted vhost on account %s. Please ask the user of this account to reconnect.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($database, "DELETE FROM vhosts WHERE ac='".strtolower($Parts[1])."'");
						break;
					case "reqapp":
						$buf = sprintf("%s%s O %s :Application (none? this person did not apply!): %s",$this->ServiceNum,$this->b64e("2"),$Sender,system("/usr/bin/env grep -i 'account ".escapeshellcmd($Parts[1])." requested' requests.db"));
						$this->SendRaw($buf,1);
						break;
					case "approve":
						$buf = sprintf("%s%s O %s :Approving %s' vhost, please ask him to reconnect.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$vhost = system("/usr/bin/env grep -vi 'account ".escapeshellcmd($Parts[1])." requested' requests.db > requests.db.new | cut -d' ' -f6 ; mv requests.db.new requests.db");
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($database, "INSERT INTO vhosts VALUES ('".$vhost."','".strtolower($Parts[1])."')");
						$this->vhostIt(array_search($Parts[1], $this->Acct), $Parts[1]);
						break;
					default:
						$buf = sprintf("%s%s O %s :/msg ".$this->BotNick." vhreg <account> <vhost>",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf("%s%s O %s :Set a vHost on someone's account",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf("%s%s O %s :/msg ".$this->BotNick." vhunreg <account>",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf("%s%s O %s :Unset the vHost on someone's account",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf("%s%s O %s :/msg ".$this->BotNick." reqapp <account>",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf("%s%s O %s :See what someone requested for vhost",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						break;
				}
			} else {
				$Parts = explode(" ",$Msg);
				$Cmd = strtolower(trim($Parts[0]));
				switch ($Cmd) {
					case "request":
						$buf = sprintf("%s%s O %s :I just asked the opers to give you your requested vhost. You should receive it forthwithly.",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$buf = sprintf("%s DS :account:%s requested:%s",$this->ServiceNum,$this->Acct[$Sender],$Parts[1]);
						$this->SendRaw($buf,1);
						$buf = sprintf("at %lu account %s requested %s\n",time(),$this->Acct[$Sender],$Parts[1]);
						$writo = fopen("requests.db", "a");
						fwrite($writo, $buf);
						fclose($writo);
						break;
					default:
						$buf = sprintf("%s%s O %s :/msg ".$this->BotNick." request",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$buf = sprintf("%s%s O %s :Request a vHost",$this->ServiceNum,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						break;
				}
			}
			case 4096:
				$Parts = explode(" ",$Msg);
				$Cmd = strtolower(trim($Parts[0]));
				switch ($Cmd) {
					case "adduser":
						if (!isset($this->Acct[$sender])) {
								$this->SendRaw(sprintf("%s%s O %s :Please log in to me to continue.",$this->ServiceNum,$this->b64e($Dest), $Sender));
								break;
							}
						if ($Parts[2] < $this->LoadChannelOps($Target, $this->idByUser($this->Acct[$Sender]))) {
							$this->SendRaw(sprintf("%s%s P %s :%s: Adding user as requested.", $this->ServiceNum,$this->b64e(1), $Target, $this->Num2Nick($Sender)),1);
							$this->SendRaw(sprintf("%s%s WC @%s :%s requested addition of %s at level %s", $this->ServiceNum,$this->b64e(1), $Target, $this->Num2Nick($Sender), $Parts[1], $Parts[2]),1);
								$id = $this->idByChan($Parts[1]);
								$uid = $this->idByUser($this->Acct[$Parts[2]]);
								pg_query($this->c, sprintf("INSERT INTO levels (channel_id, user_id, access, added, last_updated) VALUES ((select id from channels where name = '%s'), (select id from users where user_name = '%s'), %s, %s, 1933780085);",$Target, $Parts[1], $Parts[2], time()));
						} else {
							$this->SendRaw(sprintf("%s%s O %s :Bud, that did not work because you are adding someone at a higher access than your own. :(", $this->ServiceNum,$this->b64e($Dest), $Sender),1);
						}
						break;
					case "op":
						if ($this->LoadChannelOps($Target, $this->idByUser($this->Acct[$Sender])) >= 100) {
							$this->SendRaw(sprintf("%s%s M %s +o %s %s", $this->ServiceNum,$this->b64e(1), $Target, $Sender, $this->Channels[$Target]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf("%s%s P %s :%s: Permission denied.", $this->ServiceNum,$this->b64e($Dest), $Target, $this->Num2Nick($Sender)),1);
						}
						break;
					case "halfop":
						if ($this->LoadChannelOps($Target, $this->idByUser($this->Acct[$Sender])) >= 50) {
							$this->SendRaw(sprintf("%s%s M %s +h %s %s", $this->ServiceNum,$this->b64e(1), $Target, $Sender, $this->Channels[$Target]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf("%s%s P %s :%s: Permission denied.", $this->ServiceNum,$this->b64e($Dest), $Target, $this->Num2Nick($Sender)),1);
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
							$buf = sprintf("%s%s O %s :%s",$this->ServiceNum,$this->b64e(1),$Sender,$help[$i]);
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
				$Users = $Args[7];
			elseif (preg_match("/l/",$Args[4]) || preg_match("/k/",$Args[4]))
				$Users = $Args[6];
		} else
			$Users = $Args[4];
		$Temp = explode(",",$Users);
		foreach ($Temp as $Index => $Num) {
			if (strpos($Num, ":o") == 5) $this->Channels[$Chan][$Num]["op"] = true;
			$Num = str_replace(":ov","",$Num);
			$Num = str_replace(":o","",$Num);
			$Num = str_replace(":v","",$Num);
			$Num = trim($Num);
			$this->Channels[$Chan][$Num]["in"] = TRUE;
			$this->Channels[$Chan]["CH-TS"] = $Args[3];
		}
	}
	
	function AddChan($Args) {
		$Chan = trim(strtolower($Args[2]));
		$Num = trim($Args[0]);
		$this->Channels[$Chan][$Num]["in"] = TRUE;
		$this->Channels[$Chan]["CH-TS"] = $Args[3];
		if (pg_fetch_result(pg_query($this->c, "SELECT gline FROM glinechan WHERE gline = '".strtolower($Chan)."'"))) $this->SendRaw(sprintf("%s GL *@%s :[AUTO] I am glining you because you joined a bad channel.", $this->ServiceNum, $this->Host[$Num]),1);
		if ($Args[1] == "C")
			$this->Channels[$Chan][$Num]["op"] = true;
		if ($this->LoadChannelOps($Chan, $this->idByUser($this->Acct[$Num])) >= 100) {
						$this->SendRaw(sprintf("%s%s O %s :You are authed and have access, so I automatically opped you on %s. Congratulations.", $this->ServiceNum,$this->b64e($Dest), $Num, $Chan),1);
						$this->SendRaw(sprintf("%s%s M %s +o %s %s", $this->ServiceNum,$this->b64e($Dest), $Chan, $Num, $this->Channels[$Chan]["CH-TS"]),1);
		}
		if ($this->LoadChannelOps($Chan, $this->idByUser($this->Acct[$Num])) >= 50) {
						$this->SendRaw(sprintf("%s%s O %s :You are authed and have access, so I automatically half opped you on %s. Congratulations.", $this->ServiceNum,$this->b64e($Dest), $Num, $Chan),1);
						$this->SendRaw(sprintf("%s%s M %s +h %s %s", $this->ServiceNum,$this->b64e($Dest), $Chan, $Num, $this->Channels[$Chan]["CH-TS"]),1);
		}
		if ($this->LoadChannelOps($Chan, $this->idByUser($this->Acct[$Num])) >= 5) {
						$this->SendRaw(sprintf("%s%s O %s :You are authed and have access, so I automatically voiced you on %s. Congratulations.", $this->ServiceNum,$this->b64e($Dest), $Num, $Chan),1);
						$this->SendRaw(sprintf("%s%s M %s +o %s %s", $this->ServiceNum,$this->b64e($Dest), $Chan, $Num, $this->Channels[$Chan]["CH-TS"]),1);
		}
	}
	
	function DelChan($Args) {		
		$Chan = trim(strtolower($Args[2]));
		$Num = trim($Args[0]);
		unset($this->Channels[$Chan][$Num]);
		$this->CheckEmptyChan($Chan);
		@ob_flush();
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
			$Numeric = $Args[3];
			$Nick = $Args[2];
			$this->Nicks[$Numeric] = $Nick;
			return 0;
			
		}
		$Modes = $Args[7];
		if (preg_match("/\+/i",$Modes)) {
			/* Setting the numeric */
			if (preg_match("/r/",$Args[7]) && preg_match("/f/",$Args[7])) {
				$Numeric = $Args[11];
				$this->Acct[$Numeric] = $Args[8];
				$VHost = $Args[9];$Host = $Args[6];
				$this->vhostIt($Numeric, $this->Acct[$Numeric]);
			} elseif (preg_match("/f/",$Args[7])) {
				$VHost = $Args[8];$Numeric = $Args[10];
			} elseif (preg_match("/r/",$Args[7])) {
				$this->Acct[$Numeric] = $Args[8];
				$Numeric = $Args[10];
				$this->vhostIt($Numeric, $this->Acct[$Numeric]);
			} else {
				$Numeric = $Args[9];
			}
			if (preg_match("/o/",$Args[7])) {
				$Oper = true;
			}
		} else {
			$Numeric = $Args[8];
		}
		$this->Hosts[$Numeric] = $Host;
		$this->Nicks[$Numeric] = $Nick;
		$this->Opers[$Numeric] = $Oper;
		$this->IPs[$Numeric] = long2ip($this->convBase(count($Args)-2,"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789[]","0123456789"));
		if ($this->is_blacklisted($this->IPs[$Numeric])) $this->SendRaw(sprintf("%s GL *@%s :[AUTO] DNS-BL listed.", $this->ServiceNum, $this->Host[$Num]),1);
	}

	function Num2Nick($Numeric) {
		/* Changing a numeric into a nick */
		if (!empty($this->Nicks[$Numeric]))
			return $this->Nicks[$Numeric];
		else
			return "N/A";
	}
}

$FishBot = new FishBot();
$FishBot->LoadChannels();
$FishBot->StartBot();

?>
