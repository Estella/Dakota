#!/usr/bin/env php
<?php
/*
 * dvorakbot.php - a rewrite of IPaytonn's EponaPHP.php
 * 
 * Copyright 2014 Jack Johnson <jforjackjohnson@yahoo.co.uk>
 * 
The MIT License (MIT)

Copyright (c) 2014 I_Is_Payton_ and j4jackj

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Thank you for choosing MIT.
 * 
 */

class AnoPHP {
	
	function AnoPHP() {
		$this->Server = "irc.freenode.net";
		$this->FirstChannel = "##wrpl";
		$this->ConnectPassword = "dvorakbot:posterlane";
		$this->BotNick = "DvorakBot";
		//$this->OnConnectCMD = "PRIVMSG NickServ@services. :IDENTIFY lolwtfux";
		$this->Port = 6667;
		$this->TriggerChar = "!";
	}
	
	function SendRaw($input) {
		echo $input.PHP_EOL;
		fwrite($this->Socket,sprintf("%s\r\n",$input));
	}
	
	function SendMsg($channel,$input) {
		fwrite($this->Socket,sprintf("PRIVMSG %s :%s\r\n",$channel,$input));
	}
	
	function SendNtc($channel,$input) {
		fwrite($this->Socket,sprintf("NOTICE %s :%s\r\n",$channel,$input));
	}
	
	function Idle() {
		$this->SendRaw("PASS {$this->ConnectPassword}");
		$this->SendRaw("USER {$this->BotNick} {$this->BotNick} {$this->BotNick} :AnoPHP IRC bot");
		$this->SendRaw("NICK {$this->BotNick}");
		while (!feof($this->Socket)) {
			$this->Get = fgets($this->Socket, 4096);
			echo $this->Get.PHP_EOL;
			$line = explode(" ", $this->Get);
			$source = substr($line[0], 1);
			$dsource = $line[0];
			$sourceNick = strstr($source, "!", 1);
			$sourceUser = substr(strstr(strstr($source, "@", 1), "!"), 1);
			$sourceHost = substr(strstr($source, "@"), 1);
			$cmd = $line[1];
			$target = $line[2];
			$lastParameter = trim(substr(strstr($this->Get, " :"), 2));
			switch (strtoupper($cmd)) {
				case "PRIVMSG":
					$this->Privmsg($sourceNick, $sourceUser, $sourceHost, $target, $lastParameter);
					break;
				case "PONG":
					break;
				case "MODE":
					$this->SendRaw("JOIN {$this->FirstChannel}");
					break;
				default:
					
					break;
			} 
			switch (strtoupper($dsource)) {
				case "PING":
					$this->SendRaw("PONG {$cmd}");
					break;
			}
		}
	}
	
	function Privmsg($srcN, $srcU, $srcH, $target, $privmsg) {
		var_dump($privmsg);
			$cmdline = explode(" ", $privmsg, 2);
			$cmd = $cmdline[0];
			$args = $cmdline[1];
			switch (strtolower($cmd)) {
				case "!about":
					$this->SendMsg($target, "Hi. This is the PianoPHP bot made by j4jackj. PianoPHP's idea stemmed from EponaPHP, a bot made by a certain Payton.");
					break;
				case "!md5":
					$this->SendMsg($target, sprintf("MD5 sum of input: %s", md5($cmdline[1])));
					break;
				case "!sha512":
					$this->SendMsg($target, sprintf("SHA512 sum of input: %s", hash("sha512", $cmdline[1])));
					break;
				case "!sha1":
					$this->SendMsg($target, sprintf("SHA512 sum of input: %s", sha1($cmdline[1])));
					break;
				case "!join":
					$this->SendNtc($srcN, sprintf("JOINING %s", $cmdline[1]));
					$this->SendRaw(sprintf("JOIN %s", $cmdline[1]));
					break;
				case "!part":
					$this->SendMsg($target, sprintf("Goodbye!"));
					$this->SendRaw(sprintf("PART %s", $target));
					break;
				case "!kill":
					$this->SendMsg($target, sprintf("\x01ACTION kills %s with a large baseball bat.\x01", $args));
					break;
				case "!random":
					$this->SendMsg($target, sprintf("Random number: %s", mt_rand()));
					break;
				case "hi":
					$this->SendMsg($target, sprintf("Hello, %s.", $srcN));
					break;
				default:
					break;
			}
	}
	
	function StartBot() {
		$this->Socket = fsockopen($this->Server, $this->Port);
		
		
		
		$this->Idle();
	}
	
}
$AnoPHP = new AnoPHP();
$AnoPHP->StartBot();
