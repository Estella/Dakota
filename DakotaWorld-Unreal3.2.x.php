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
			$buf = sprintf(":%s AL %s %s",$this->s['BotNick'][2], $Numeric, $Vhost);
			$this->SendRaw($buf,1);
		}
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
\x02CHANOP\x02                Level-ops \$3 with \$4 on \$2
                      Requires 100 level and level above oplevel specif-
                      ied
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
		$this->s['BotModes'][1] = "+oiS";
		$this->s['Desc'][1] = "Try these: /msg X help register and /msg X help chanregister";

		$this->s['BotNick'][2] = "H";
		$this->s['BotUser'][2] = "H";
		$this->s['BotHost'][2] = "hostserv.umbrellix.tk";
		$this->s['BotModes'][2] = "+oiS";
		$this->s['Desc'][2] = "BETA multiclient support for PHPStar";

		$this->ServerName = "services.";
		$this->ServerHost = "127.0.0.1"; /* IP/Host to connect to */
		$this->ServerPort = 8667; /* Port to connect to */
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
			$tmp = sprintf(":%s ~ %s %s %sr :~%s &*!*ircap*@*",$this->ServerName,$chans[1],$chans[0],$chans[2],$this->s['BotNick'][1]);
			$this->SendRaw($tmp,1);
			$tmp = sprintf(":%s ! %s :I am ChanServ, your channel service. :D",$this->s['BotNick'][1],$chans[0]);
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
	
	function StartBot() {
		/* Yup, how about begin with the real work, THE BOTS! */
		$this->Socket = fsockopen($this->ServerHost,$this->ServerPort);
		
		$Time = time();
		$tmp = sprintf('PASS :%s',$this->ServerPass);
		$this->SendRaw($tmp,1);
		$tmp = sprintf('SERVER %s 1 :%s',$this->ServerName,$this->ServiceDesc);
		$this->SendRaw($tmp,1);
		$tmp = sprintf('PROTOCTL NICKv2 SJOIN SJOIN2 SJ3 ESVID TOKEN');
		$this->SendRaw($tmp,1);
		
		for ($k = 1; $k <= $this->srvs; $k++) { //& nick hopcount timestamp	username hostname server servicestamp +usermodes virtualhost :realname
			$tmp = sprintf('& %s 1 %s %s 127.0.0.1 %s %s %s :%s',$this->s['BotNick'][$k],
							$Time,$this->s['BotUser'][$k],$this->ServerName,$this->s['BotModes'][$k],$this->s['BotHost'][$k],$this->s['Desc'][$k]);
			$this->SendRaw($tmp,1);
		}
		$tmp = sprintf(':%s ES',$this->ServerName);
		$this->SendRaw($tmp,1);
		$tmp = sprintf(':%s AO 0 %s 2311 * 0 0 0 :...',$this->ServerName,$Time);
		$this->SendRaw($tmp,1);
		$this->Counter =0;
		
		
		if ($this->DeBug) {
			printf("Bot sended his own information to the server, waiting for respond.\n");
			@ob_flush();
		}
		
		$this->LoadChannels();
		
		$this->Idle();
	}
	
	function Idle() {
		/* Checking the incoming information */
		while (!feof($this->Socket)) {
			$this->Get = fgets($this->Socket,384);
			if (!empty($this->Get)) {
				$Args = explode(" ",$this->Get);
				printf("%s", $this->Get);@ob_flush();
				$Cmd = trim($Args[1]);
				$Dest = $this->s['BotNick'];
				switch ($Cmd) {
					case "PRIVMSG": /* They are talking to us */
					case "!": /* They are talking to us */
						$this->PrivMsg($Dest,$Args,$this->Get);
						break;
					case "MODE":
					case "o":
					case "G": /* A oper logged in, or a chanmode changed? */
						$this->AddOper($Args);
						printf("%s", $this->Get);@ob_flush();
						break;
					case "QUIT":
					case ",": /* They quit as well, finally! :P */
						$this->DelUser($Args);printf("%s", $this->Get);@ob_flush();
						break;
					case "SJOIN":
					case "~": /* We received a burst line */
						$this->Burst($Args);printf("%s", $this->Get);@ob_flush();
						break;
					case "JOIN":
					case "C": /* Somebody joined a channel */
						$this->AddChan($Args);
						printf("%s", $this->Get);@ob_flush();
						break;
					case "PART":
					case "D": /* If somebody parts a channel, we have to notice that */
						$this->DelChan($Args);printf("%s", $this->Get);@ob_flush();
						break;
					case "NICK":
					case "&": /* We got a nick-change or a nick-burst msg */
						$this->SaveNicks($Args);printf("%s", $this->Get);@ob_flush();
						break;
				}
				switch ($Args[0]) {
					case "NICK":
					case "&": /* We got a nick-change or a nick-burst msg */
						$this->SaveNicks($Args);printf("%s", $this->Get);@ob_flush();
						break;
					case "PING":
					case "8": /* Ping .. Pong :) */
						$this->Pong($Args);printf("%s", $this->Get);@ob_flush();
						break;
				}
			}
		}
	}
	
	function EA() {
		/* End of Burst received */
		/* [get] AB EB */
		if (empty($this->EB)) {
			$tmp = sprintf(':%s ES',$this->ServerName);
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
		$tmp = sprintf('9 %s %s',$this->ServerName,$Args[1], $Args[2]);$tmp = sprintf('PONG %s %s',$this->ServerName,$Args[1],$Args[2]);
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
			$Modes = trim($Args[3]);
			$Count = strlen($Modes);
			$Status = false;
			for($i=0;$i<$Count;$i++) {
				if ($Modes[$i] == "+") 
					$Status = "+";
				elseif ($Modes[$i] == "-")
					$Status = "-";
				else {
					if ($Modes[$i] == "o" && $Status == "+")
						{ echo "Someone became op";
						$this->Channels[$Chan][$Args[2+$i]]["op"] = true;
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
	
	function OpUser($Bot,$Channel,$User,$Level){
		if ($Level >= 50) {
			$tmp = sprintf(":%s G %s +h %s %s", $this->s['BotNick'][$Bot], $Channel, $User, $this->Channels[$Parts[1]]["CH-TS"]);
		} if ($Level >= 100) {
			$tmp = sprintf(":%s G %s +o %s %s", $this->s['BotNick'][$Bot], $Channel, $User, $this->Channels[$Parts[1]]["CH-TS"]);
		} if ($Level >= 200) {
			$tmp = sprintf(":%s G %s +ao %s %s %s", $this->s['BotNick'][$Bot], $Channel, $User, $User, $this->Channels[$Parts[1]]["CH-TS"]);
		} if ($Level >= 350) {
			$tmp = sprintf(":%s G %s +qo %s %s %s", $this->s['BotNick'][$Bot], $Channel, $User, $User, $this->Channels[$Parts[1]]["CH-TS"]);
		}
		$this->SendRaw($tmp,1);
	}
	
	function PrivMsg($Dest,$Args,$Line) {
		/* Somebody msg'ed something to me or to a channel */
		/* [get] ABAAG P #blaat :Joh, wzp?	<-- Chan-msg
		   [get] ABAAG P ADAAA :Joh, wzp?	<-- Priv-msg
		*/
		$Sender = trim(substr($Args[0],1));
		$Target = trim($Args[2]);
		$Msg = explode(":",$Line,3);
		$Msg = trim($Msg[2]);
		var_dump($Sender);		var_dump($Target);
		if ($Target[0] == '#') { 
			$Dest = 4096;
			$Msg = explode("!",$Msg,2);
			$Msg = $Msg[1];
		} 
		switch ($Target) {
			case "X":
				$Parts = explode(" ",$Msg);
				$Cmd = strtolower(trim($Parts[0]));
				switch ($Cmd) {
					case "login":
						if (sizeof($Parts)<2) {
							$this->SendRaw(sprintf(":%s B %s :Not enough parameters.", $Target, $Sender),1); break; }
						if ($this->std_check_password($Parts[1], $Parts[2])) {
							$this->Acct[$Sender] = $Parts[1];
							$this->AcctID[$Sender] = $this->idByUser($Parts[1]);
							$this->SendRaw(sprintf(":%s n %s +d %s", $this->ServerName, $Sender, $Parts[1]),1);$this->Acct[$sender] = $Parts[1];
							$this->vhostIt($Sender, $Parts[1]);
							$this->SendRaw(sprintf(":%s B %s :Logged you in successfully as %s. Congratulations.", $Target, $Sender, $Parts[1]),1);
						} else {
							$this->SendRaw(sprintf(":%s B %s :Go fuck yourself. Wrong login name or password for %s.", $Target, $Sender, $Parts[1]),1);
						}
						break;
					case "chanregister":
						if (!isset($this->Acct[$sender])) { $this->SendRaw(sprintf(":%s B %s :Please log in to me to continue.",$Target, $Sender)); break; }
						if ($this->Channels[$Parts[1]][$Sender]["op"] or $this->Opers[$Numeric]) {
							$chid = time();
							$this->SendRaw(sprintf(":%s B %s :Registering %s to you or specified user.", $Target, $Sender, $Parts[1]),1);
							if (!isset($this->Channels[strtolower($Parts[1])]["CH-ID"])){
								pg_query($this->c, sprintf("INSERT INTO channels (id, name, registered_ts, channel_ts, channel_mode, limit_offset, limit_period, limit_grace, limit_max, last_updated) VALUES (%s, '%s', %s, %s, '+tnCT', 5, 20, 1, 0, 313370083);",$chid, $Parts[1], $chid, $this->Channels[strtolower($Parts[1])]["CH-TS"], time()));
								pg_query($this->c, sprintf("INSERT INTO levels (channel_id, user_id, access, added, last_updated) VALUES (%s, (select id from users where user_name = '%s'), %s, %s, 1933780085);",$chid, $this->Acct[$Sender], "500", time()));
								$tmp = sprintf(":%s ~ %s %s %sr :&%s &*!*ircap*@*",$this->ServerName,$chans[1],$chans[0],$chans[2],$this->s['BotNick'][1]);
								$this->SendRaw($tmp,1);
								$tmp = sprintf(":%s ! %s :I am ChanServ, your channel service. :D",$this->s['BotNick'][1],$chans[0]);
								$this->SendRaw($tmp,1);
							}
						} else {
							$this->SendRaw(sprintf(":%s B %s :Not registering %s to you because you are not currently opped on that channel.", $Target, $Sender, $Parts[1]),1);
						}
						break;
					case "adduser":
						if (!isset($this->Acct[$sender])) {
								$this->SendRaw(sprintf(":%s B %s :Please log in to me to continue.",$Target, $Sender));
								break;
							}
						if ($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) > 499) {
							$this->SendRaw(sprintf(":%s B %s :Adding user as requested.", $Target, $Sender),1);
								$id = $this->idByChan($Parts[1]);
								$uid = $this->idByUser($this->Acct[$Parts[2]]);
								pg_query($this->c, sprintf("INSERT INTO levels (channel_id, user_id, access, added, last_updated) VALUES ((select id from channels where name = '%s'), (select id from users where user_name = '%s'), %s, %s, 1933780085);",$Parts[1], $Parts[2], $Parts[3], time()));
						} else {
							$this->SendRaw(sprintf(":%s B %s :Bud, that did not work because you aren't an owner of that channel. :(", $Target, $Sender),1);
						}
						break;
					case "deluser":
						if (!isset($this->Acct[$sender])) {
								$this->SendRaw(sprintf(":%s B %s :Please log in to me to continue.",$Target, $Sender));
								break;
							}
						if ($this->LoadChannelOps($Parts[1], $Parts[3]) > $this->LoadChannelOps($Parts[1], $this->Acct[$Sender])) {
							$this->SendRaw(sprintf(":%s B %s :Deletion user as requested.", $Target, $Sender),1);
								$id = $this->idByChan($Parts[1]);
								$uid = $this->idByUser($this->Acct[$Parts[2]]);
								pg_query($this->c, sprintf("DELETE FROM levels WHERE channel_id = (select id from channels where name = '%s') AND user_id = (select id from users where name = '%s');",$Parts[1], $Parts[2]));
						} else {
							$this->SendRaw(sprintf(":%s B %s :Bud, that did not work because you are deleting someone from a higher access than your own. :(", $Target, $Sender),1);
						}
						break;
					case "op":
						if ($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 100) {
							$this->SendRaw(sprintf(":%s B %s :Opped you successfully with oplevel %s in %s. Congratulations.", $Target, $Sender,$this->LoadChannelOps($Parts[1], $this->Acct[$Sender]), $Parts[1]),1);
							$this->OpUser(1,$Parts[1],$Sender,$this->LoadChannelOps($Parts[1], $this->Acct[$Sender]));
						} else {
							$this->SendRaw(sprintf(":%s B %s :Go fuck yourself. User does not have access to channel.", $Target, $Sender, $Parts[1]),1);
						}
						break;
					case "chanop":
						if (($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 100) and ($Parts[3] < $this->LoadChannelOps($Parts[1], $this->Acct[$Sender]))) {
							$this->SendRaw(sprintf(":%s B %s :Will chanop as specified", $Target, $Sender,$this->LoadChannelOps($Parts[1], $this->idByUser($this->Acct[$Sender])), $Parts[1]),1);
							$this->SendRaw(sprintf(":%s G %s +o %s:%s %s", $Target, $Parts[1], array_search($Parts[2], $this->Nicks), (501 - $Parts[3]), $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf(":%s B %s :Go fuck yourself. Not enough access to op user at level.", $Target, $Sender, $Parts[1]),1);
						}
						break;
					case "deop":
						if ($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 100) {
							$this->SendRaw(sprintf(":%s B %s :Will deop (using KICK and SVSJOIN) as specified", $Target, $Sender,$this->LoadChannelOps($Parts[1], $this->idByUser($this->Acct[$Sender])), $Parts[1]),1);
							$this->SendRaw(sprintf(":%s H %s %s :Removing from channel to deop correctly and compatibly", $Target, array_search($Parts[2], $this->Nicks)),1);	
							$this->SendRaw(sprintf(":%s BX %s %s", $Target, $Parts[2], $Parts[1]),1);
						} else {
							$this->SendRaw(sprintf(":%s B %s :Go fuck yourself. Not enough access to op user at level.", $Target, $Sender, $Parts[1]),1);
						}
						break;
					case "mode":
						if ($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 400) {
							$Modes = explode(" ",$Msg,3);
							$this->SendRaw(sprintf(":%s B %s :Changed modes %s successfully in %s. Congratulations.", $Target, $Sender, $Modes[2], $Parts[1]),1);
							$this->SendRaw(sprintf(":%s G %s %s %s", $Target, $Modes[1], $Modes[2], $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf(":%s B %s :Go fuck yourself. User does not have access to channel. Minimum 400.", $Target, $Sender, $Parts[1]),1);
						}
						break;
					case "mdop":
						if ($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 350) {
							$Modes = explode(" ",$Msg,3);
							$this->SendRaw(sprintf(":%s B %s :Massively deopped channel and forcejoined/opped you. Congrats.", $Target, $Sender, $Modes[2], $Parts[1]),1);
							$this->SendRaw(sprintf(":%s n %s -ohvmisp %s", $Target, $Modes[1], $this->Channels[$Parts[1]]["CH-TS"]),1);
							$this->SendRaw(sprintf(":%s BX %s %s", $Target, $Sender, $Parts[1]),1);
							$this->SendRaw(sprintf(":%s G %s +o %s %s", $Target, $Parts[1], $Sender, $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf(":%s B %s :Go fuck yourself. User does not have access to channel. Minimum 350.", $Target, $Sender, $Parts[1]),1);
						}
						break;
					case "halfop":
						if (($this->LoadChannelOps($Parts[1], $this->Acct[$Sender]) >= 50) && $this->canHalfOp) {
							$this->SendRaw(sprintf(":%s B %s :Half opped you successfully in %s. Congratulations.", $Target, $Sender, $Parts[1]),1);
							$this->SendRaw(sprintf(":%s G %s +h %s %s", $Target, $Parts[1], $Sender, $this->Channels[$Parts[1]]["CH-TS"]),1);
						} elseif ($this->LoadChannelOps($Parts[1], $this->idByUser($this->Acct[$Sender])) >= 50) {
							$this->SendRaw(sprintf(":%s B %s :Opped (level 50) you successfully in %s. Congratulations.", $Target, $Sender, $Parts[1]),1);
							$this->SendRaw(sprintf(":%s G %s +o %s:50 %s", $Target, $Parts[1], $Sender, $this->Channels[$Parts[1]]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf(":%s B %s :Go fuck yourself. User does not have access to channel or halfops impossible on ircd.", $Target, $Sender, $Parts[1]),1);
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

SYNTAX: firefox http://www.umbrellix.tk/live/index.php
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
							$buf = sprintf(":%s B %s :%s",$Target,$Sender,$help[$i]);
							$this->SendRaw($buf,1);
						}
				}
				break;
			case $this->s['BotNick'][3]:
				case "register":
				$Parts = explode(" ",$Msg);
				$Cmd = strtolower(trim($Parts[0]));
						if (!isset($this->Acct[$sender])) { $this->SendRaw(sprintf(":%s B %s :Please log in to me to continue.",$Target, $Sender)); break; }
							$chid = time();
							$this->SendRaw(sprintf(":%s B %s :Registering %s to you or specified user.", $Target, $Sender, $Parts[1]),1);
							if (pg_fetch_result(pg_query(),0,0)){
								pg_query($this->c, sprintf("INSERT INTO channels (id, name, registered_ts, channel_ts, channel_mode, limit_offset, limit_period, limit_grace, limit_max, last_updated) VALUES (%s, '%s', %s, %s, '+tnCT', 5, 20, 1, 0, 313370083);",$chid, $Parts[1], $chid, $this->Channels[strtolower($Parts[1])]["CH-TS"], time()));
								pg_query($this->c, sprintf("INSERT INTO levels (channel_id, user_id, access, added, last_updated) VALUES (%s, (select id from users where user_name = '%s'), %s, %s, 1933780085);",$chid, $this->Acct[$Sender], "500", time()));
							}
					break;
			case "H":
				if (!empty($this->Opers[$Sender])) {
				$Parts = explode(" ",$Msg);
				$Cmd = strtolower(trim($Parts[0]));
				switch ($Cmd) {
					case "vhost":
						$buf = sprintf(":%s AL %s %s",$this->ServerName,$Parts[1],$Parts[2]);
						$this->SendRaw($buf,1);
						break;
					case "vhreg":
						$buf = sprintf(":%s B %s :Set vhost %s on account %s.",$this->ServerName,$this->b64e("2"),$Sender,$Parts[2],$Parts[1]);
						$this->SendRaw($buf,1);
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($database, "INSERT INTO vhosts VALUES ('".$Parts[2]."','".strtolower($Parts[1])."')");
						$this->vhostIt(array_search(strtolower($Parts[1]), array_map('strtolower', $this->Acct)), $Parts[1]);
						break;
					case "cg":
						$buf = sprintf(":%s B %s :Set gline %s.",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($this->c, "INSERT INTO glinechan VALUES ('".strtolower($Parts[1])."')");
						$this->vhostIt(array_search(strtolower($Parts[1]), array_map('strtolower', $this->Acct)), $Parts[1]);
						break;
					case "vhunreg":
						$buf = sprintf(":%s B %s :Deleted vhost on account %s. Please ask the user of this account to reconnect.",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($database, "DELETE FROM vhosts WHERE ac='".strtolower($Parts[1])."'");
						break;
					case "reqapp":
						$buf = sprintf(":%s B %s :Application (none? this person did not apply!): %s",$this->ServerName,$this->b64e("2"),$Sender,system("/usr/bin/env grep -i 'account ".escapeshellcmd($Parts[1])." requested' requests.db"));
						$this->SendRaw($buf,1);
						break;
					case "approve":
						$buf = sprintf(":%s B %s :Approving %s' vhost, please ask him to reconnect.",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$vhost = system("/usr/bin/env grep -vi 'account ".escapeshellcmd($Parts[1])." requested' requests.db > requests.db.new | cut -d' ' -f6 ; mv requests.db.new requests.db");
						$database = pg_connect($this->DatabaseParams);
						$res = pg_query($database, "INSERT INTO vhosts VALUES ('".$vhost."','".strtolower($Parts[1])."')");
						$this->vhostIt(array_search($Parts[1], $this->Acct), $Parts[1]);
						break;
					default:
						$buf = sprintf(":%s B %s :/msg ".$this->BotNick." vhreg <account> <vhost>",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf(":%s B %s :Set a vHost on someone's account",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf(":%s B %s :/msg ".$this->BotNick." vhunreg <account>",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf(":%s B %s :Unset the vHost on someone's account",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf(":%s B %s :/msg ".$this->BotNick." reqapp <account>",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);

						$buf = sprintf(":%s B %s :See what someone requested for vhost",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						break;
				}
			} else {
				$Parts = explode(" ",$Msg);
				$Cmd = strtolower(trim($Parts[0]));
				switch ($Cmd) {
					case "request":
						$buf = sprintf(":%s B %s :I just asked the opers to give you your requested vhost. You should receive it forthwithly.",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
						$this->SendRaw($buf,1);
						$buf = sprintf("%s DS :account:%s requested:%s",$this->ServerName,$this->Acct[$Sender],$Parts[1]);
						$this->SendRaw($buf,1);
						$buf = sprintf("at %lu account %s requested %s\n",time(),$this->Acct[$Sender],$Parts[1]);
						$writo = fopen("requests.db", "a");
						fwrite($writo, $buf);
						fclose($writo);
						break;
					default:
						$buf = sprintf(":%s B %s :/msg ".$this->BotNick." request",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
						$buf = sprintf(":%s B %s :Request a vHost",$this->ServerName,$this->b64e("2"),$Sender,$Parts[1]);
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
								$this->SendRaw(sprintf(":%s B %s :Please log in to me to continue.",$Target, $Sender));
								break;
							}
						if ($Parts[2] > $this->LoadChannelOps($Target, $this->Acct[$Sender])) {
							$this->SendRaw(sprintf("%s%s P %s :%s: Adding user as requested.", $this->ServerName,$this->b64e(1), $Target, $this->Num2Nick($Sender)),1);
							$this->SendRaw(sprintf("%s%s WC @%s :%s requested addition of %s at level %s", $this->ServerName,$this->b64e(1), $Target, $this->Num2Nick($Sender), $Parts[1], $Parts[2]),1);
								$id = $this->idByChan($Parts[1]);
								$uid = $this->idByUser($this->Acct[$Parts[2]]);
								pg_query($this->c, sprintf("INSERT INTO levels (channel_id, user_id, access, added, last_updated) VALUES ((select id from channels where name = '%s'), (select id from users where user_name = '%s'), %s, %s, 1933780085);",$Target, $Parts[1], $Parts[2], time()));
						} else {
							$this->SendRaw(sprintf(":%s B %s :Bud, that did not work because you are adding someone at a higher access than your own. :(", $Target, $Sender),1);
						}
						break;
					case "op":
						if ($this->LoadChannelOps($Target, $this->Acct[$Sender]) >= 100) {
							$this->SendRaw(sprintf(":%s G %s +o %s:%s %s", $this->ServerName,$this->b64e(1), $Target, $Sender, (501 - $this->LoadChannelOps($Target, $this->Acct[$Sender])), $this->Channels[$Target]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf(":%s B %s :Go fuck yourself. User does not have access to channel.", $this->s['BotNick'][$Dest], $Sender, $Parts[1]),1);
						}
						break;
					case "halfop":
						if ($this->LoadChannelOps($Target, $this->Acct[$Sender]) >= 50) {
							$this->SendRaw(sprintf(":%s G %s +h %s %s", $this->ServerName,$this->b64e(1), $Target, $Sender, $this->Channels[$Target]["CH-TS"]),1);
						} else {
							$this->SendRaw(sprintf("%s%s P %s :%s: Permission denied.", $this->s['BotNick'][$Dest], $Target, $this->Num2Nick($Sender)),1);
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
							$buf = sprintf(":%s B %s :%s",$this->ServerName,$this->b64e(1),$Sender,$help[$i]);
							$this->SendRaw($buf,1);
						}
				}
				break;
		}
	}
	function Burst($Args) {
		/* When we receive an SJOIN message, we have to know how many users are in the chan
		   so we can build a function, when the channel is empty, the bot should part.
		*/
		/* [get] :server.name ~ timestamp channel +modes[ modeparams] :memberlist &ban "exempt 'invex 
		   [get] :server.name ~ timestamp channel +modes[ modeparams] :memberlist &ban "exempt 'invex */
		$Chan = trim(strtolower($Args[2]));
		if (preg_match("/\+/",$Args[4])) {
			if (preg_match("/kl/",$Args[4]) || preg_match("/lk/",$Args[4]))
				$Users = $Args[7];
			elseif (preg_match("/l/",$Args[4]) || preg_match("/k/",$Args[4]))
				$Users = $Args[6];
		} else
			$Users = implode(",", array_slice($Args, 1));
		$Temp = explode(",",$Users);
		foreach ($Temp as $Index => $Num) {
			if (strpos($Num, "@") == 0) $this->Channels[$Chan][$Num]["op"] = true;
			if (strpos($Num, "~") == 0) $this->Channels[$Chan][$Num]["op"] = true;
			if (strpos($Num, "*") == 0) $this->Channels[$Chan][$Num]["op"] = true;

			$Num = str_replace("+","",$Num);
			$Num = str_replace("%","",$Num);
			$Num = str_replace("@","",$Num);
			$Num = str_replace("&","",$Num);
			$Num = str_replace("~","",$Num);
			$Num = trim($Num);
			$this->Channels[$Chan][$Num]["in"] = TRUE;
			$this->Channels[$Chan]["CH-TS"] = $Args[3];
		}
	}
	
	function AddChan($Args) {
		$Chan = trim(strtolower($Args[2]));
		$Num = trim(substr($Args[0],1));
		$this->Channels[$Chan][$Num]["in"] = TRUE;
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
			$Numeric = $Args[2];
			$Nick = $Args[2];
			$this->Nicks[$Numeric] = $Nick;
			return 0;
			
		}
		$Modes = $Args[8];
		if (preg_match("/\+/i",$Modes)) {
			if (preg_match("/o/",$Modes)) {
				$Oper = true;
			}
		} else {
			$Numeric = $Nick;
		}
		$this->Hosts[$Numeric] = $Host;
		$this->Nicks[$Numeric] = $Nick;
		$this->Opers[$Numeric] = $Oper;
		$this->IPs[$Numeric] = long2ip($this->convBase(count($Args)-2,"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789[]","0123456789"));
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
