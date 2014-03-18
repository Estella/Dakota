#!/usr/bin/env zsh
# zsh irc client framework
autoload -U colors && colors
for COLOR in RED GREEN YELLOW BLUE MAGENTA CYAN BLACK WHITE; do
    eval $COLOR='$fg_no_bold[${(L)COLOR}]'
    eval BOLD_$COLOR='$fg_bold[${(L)COLOR}]'
done
eval RESET='$reset_color'
zmodload zsh/net/tcp
ztcp -d 4 "$1" "$2"
touch ./hostfile
connie(){
	sleep 20
	onConnected
}

# $3 should only define functions, and it should define all the functions.
# Defined musts:
#      onPrivmsg
#      onNotice
#      onKick
#      onPart
#      onJoin
#      onInvite
#      onMode
# TBI: onModeStripped

nick="$3"

privmsg(){
	targ="$1"
	shift
	echo "PRIVMSG $targ" ":$*" >&4
}

putserv(){
	echo "$*" >&4
}

mode(){
	targ="$1"
	shift
	echo "MODE $targ $*" >&4
}

notice(){
	targ="$1"
	shift
	echo "NOTICE $targ" ":$*" >&4
}

kick(){
	chan="$1"
	targ="$2"
	shift ; shift
	echo "KICK $chan $targ" ":$*" >&4
}

part(){
	chan="$1"
	shift
	echo "PART $chan" ":$*" >&4
}

join(){
	chan="$1"
	shift
	echo "JOIN $chan $*" >&4
}

echo "USER $3 8 * :$4" >&4
echo "NICK $3" >&4

onRaw(){
	recv="$(perl -p -e 's/^:(\S+) (\S+) (\S+) (.+)$/$4/g' <<<$msg | sed -e 's/^://g')"
	if test "$4" = "ERROR" ; then
		sed -e 's/ERROR ://g' <<<"$recv" | read -r recv
		echo "$timestamp $BOLD_RED$4$WHITE $recv"
	elif test "$srcnick" = "$srchost" ; then
		echo "$timestamp $RED!$1:$4$WHITE $recv"
	else
		echo "$timestamp ${BOLD_BLACK}-${BOLD_BLUE}$1${BOLD_BLACK}($BLUE${2}@${3}$BOLD_BLACK)-$WHITE RAW: $4 $5"
	fi
}

onBanned(){
	perl -p -e 's/^:(\S+) (\S+) (\S+) (.+)$/$4/g' <<<"$msg" | sed -e 's/^://g' | read recv
	echo "$timestamp ${BOLD_RED}[Banned from server]$WHITE $recv"
}

onPrivmsg(){
	if grep -i "^ACTION" <<<"$5" >/dev/null ; then
		action="$(perl -p -e 's/^.ACTION //g' <<<$5)"
		echo "$timestamp $BOLD_BLACK<$WHITE$4$BOLD_BLACK> ${BOLD_WHITE}* $1$WHITE $action"
	elif grep -i "^MEIS" <<<"$5" >/dev/null ; then
		action="$(perl -p -e 's/^.MEIS //g' <<<$5)"
		echo "$timestamp $BOLD_BLACK<$WHITE$4$BOLD_BLACK> ${BOLD_WHITE}* $1's$WHITE $action"
	elif grep -i "^VERSION" <<<"$5" >/dev/null ; then
		echo "$timestamp $BOLD_BLACK<$CYAN$4/$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)>$WHITE CTCP$BOLD_WHITE VERSION$WHITE"
		notice "$1" "VERSION zIRCc 0.1 (c) j4jackj & The Dakota Project"
	else
		echo "$timestamp $BOLD_BLACK<$WHITE$1:$BOLD_BLUE$4$BOLD_BLACK>$WHITE $5"
	fi
}

onNotice(){
	if [ "$1" = "$2" ] && grep '\.' <<<"$2" >/dev/null ; then
		echo "$timestamp ${GREEN}!$1$WHITE $5"
	else echo "$timestamp $BOLD_BLACK-$BOLD_MAGENTA$1$BOLD_BLACK($MAGENTA${2}@${3}$BOLD_BLACK)-$WHITE $5"
	fi
}

onKick(){
	echo "$timestamp Kick: $1 $2 $3 $4 $5"
}

onPart(){
	echo "$timestamp $BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)-$WHITE I am leaving $4 ($5)"
}

onInvite(){
	modetarg="$(perl -p -e 's/^(\S+) (\S+) (\S+) :(.*)$/\4/g' <<<$msg)"
	echo "$timestamp ${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE} You are cordially invited to $CYAN$modetarg$WHITE by ${BOLD_WHITE}$srcnick${WHITE} ${BOLD_BLACK}[$CYAN${2}@${3}${BOLD_BLACK}]$WHITE"
}

onMode(){
	modetarg="$(perl -p -e 's/^(\S+) (\S+) (\S+) (.*)$/\3/g' <<<$msg)"
	modechg="$(perl -p -e 's/^(\S+) (\S+) (\S+) (.+)$/\4/g' <<<$msg | sed -e 's/^://g')"
	modecmd="$(perl -p -e 's/^(\S+) (\S+) (\S+) (.+)$/\2/g' <<<$msg)"
	[ "$modecmd" = "MODE" ] && echo "$timestamp ${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE} mode/$CYAN$modetarg ${BOLD_BLACK}[${WHITE}$modechg${BOLD_BLACK}]$WHITE by ${BOLD_WHITE}$srcnick${WHITE}"
	[ "$modecmd" = "324" ] && (
		modetarg="$(perl -p -e 's/^(\S+) (\S+) (\S+) (\S+) (.*)$/\4/g' <<<$msg)"
		modechg="$(perl -p -e 's/^(\S+) (\S+) (\S+) (\S+) (.+)$/\5/g' <<<$msg | sed -e 's/^://g')"
		echo "$timestamp ${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE} Current mode of $BOLD_WHITE$modetarg$WHITE is $modechg"
	)
}

onCreationDate(){
        tim="$(perl -p -e 's/^(\S+) (\S+) (\S+) (\S+) (.*)$/\5/g' <<<$msg)"
	date=$(date --date "@$tim")
        chan="$(perl -p -e 's/^(\S+) (\S+) (\S+) (\S+) (.+)$/\4/g' <<<$msg)"
	echo "$timestamp ${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE} Channel $CYAN$chan$WHITE was created $date"
	echo "$timestamp ${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE} Channel Timestamp is used in server to server transactions to prevent channel hacking."
}

onMotd(){
	echo "$timestamp $BOLD_RED!$1!$WHITE $2"
}

onConnected(){
	putserv "WHO $nick"
	echo "$timestamp $BOLD_BLACK-$BOLD_BLUE$1${BOLD_BLACK}-$WHITE You are now connected to IRC."
}

onJoin(){
	echo "$timestamp ${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE} $BOLD_CYAN$1 ${BOLD_BLACK}[$CYAN${2}@${3}${BOLD_BLACK}]$WHITE has joined $BOLD_WHITE$4$WHITE"
}

onPart(){
	lastarg="$(perl -p -e 's/^:(\S+) (\S+) (\S+) :(.+)$/$4/g' <<<$msg)"
	echo "$timestamp ${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE} $CYAN$1 ${BOLD_BLACK}[$WHITE${2}@${3}${BOLD_BLACK}]$WHITE has left $BOLD_WHITE$4$WHITE ${BOLD_BLACK}[$WHITE${lastarg}$BOLD_BLACK]$WHITE"
}

onQuit(){
	quitmsg="$(perl -p -e 's/^:(\S+) (\S+) :(.*)$/$3/g' <<<$msg)"
	echo "$timestamp ${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE} $CYAN$1 ${BOLD_BLACK}[$WHITE${2}@${3}${BOLD_BLACK}]$WHITE has quit ${BOLD_BLACK}[$WHITE${quitmsg}$BOLD_BLACK]$WHITE"
}

onNick(){
	quitmsg="$(perl -p -e 's/^:(\S+) (\S+) :(.+)$/$3/g' <<<$msg)"
	echo "$timestamp ${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE} $CYAN$1$WHITE is now known as $BOLD_CYAN${quitmsg}$WHITE"
}

onWho(){
	# Example $2: j4jackj2 * ~j4jackj2 kossy.doesn.t.know.how.to.use.inspircd * j4jackj2 Hr@ :0 HI!
	parsed="$(cut -d' ' -f1,3,4,6<<<$2)"
	nickuser="$(cut -d' ' -f3<<<$2)"
	nickhost="$(cut -d' ' -f4<<<$2)"
	if test "$(cut -d' ' -f1<<<$2)" = "$(cut -d' ' -f6<<<$2)" ; then
		echo "${nickuser} ${nickhost}" > ./hostfile
		user="$nickuser"
		host="$nickhost"
	fi
}

while read -r mesg ; do
	timestamp=$(date "+${BOLD_BLACK}%d %m %Y ${WHITE}%H${BOLD_BLACK}:${WHITE}%M")
	tr -d '' <<<"$mesg" | perl -p -e 's!\\!\\\\!g' | read msg
	source="$(perl -p -e 's/^(?:[:](\S+) )?(\S+)(?: (?!:)(.+?))?(?: [:](.+))?$/$1/g' <<<$msg)"
	com="$(perl -p -e 's/^(?:[:](\S+) )?(\S+)(?: (?!:)(.+?))?(?: [:](.+))?$/$2/g' <<<$msg)"
	arg="$(perl -p -e 's/^(?:[:](\S+) )?(\S+) (\S+)$/$3/g' <<<$msg | sed -e 's/^ //g' -e 's/ $//g')"
	test "$source" = "PING" && echo "PONG $com $arg" >&4
	test "$com" = "PING" && echo "PONG $arg" >&4
	args="$(perl -p -e 's/^(?:[:](\S+) )?(\S+)(?: (?!:)(.+?))?(?: [:](.+))?$/$3/g' <<<$msg | sed -e 's/^://g' -e 's/:$//g' -e 's/^ //g' -e 's/ $//g')"
	lastarg="$(perl -p -e 's/^:(\S+) (\S+) (\S+) :(.+)$/$4/g' <<<$msg)"
	srcnick="$(perl -p -e 's/^(?:[:](\S+) )?(\S+)(?: (?!:)(.+?))?(?: [:](.+))?$/$1/g' <<<$msg | perl -p -e 's/^(.*)!(.*)@(.*)$/$1/g')"
	srcuser="$(perl -p -e 's/^(?:[:](\S+) )?(\S+)(?: (?!:)(.+?))?(?: [:](.+))?$/$1/g' <<<$msg | perl -p -e 's/^(.*)!(.*)@(.*)$/$2/g')"
	srchost="$(perl -p -e 's/^(?:[:](\S+) )?(\S+)(?: (?!:)(.+?))?(?: [:](.+))?$/$1/g' <<<$msg | perl -p -e 's/^(.*)!(.*)@(.*)$/$3/g')"
	if grep -i "PRIVMSG" <<<"$com" >/dev/null ; then
		onPrivmsg "$srcnick" "$srcuser" "$srchost" "$args" "$lastarg"
	elif grep -i "NOTICE" <<<"$com" >/dev/null ; then
		onNotice "$srcnick" "$srcuser" "$srchost" "$args" "$lastarg"
	elif grep -i "KICK" <<<"$com" >/dev/null ; then
		onKick "$srcnick" "$srcuser" "$srchost" "$args" "$lastarg"
	elif grep -i "PART" <<<"$com" >/dev/null ; then
		onPart "$srcnick" "$srcuser" "$srchost" "$args" "$lastarg"
	elif grep -i "QUIT" <<<"$com" >/dev/null ; then
		onQuit "$srcnick" "$srcuser" "$srchost"
	elif grep -i "NICK" <<<"$com" >/dev/null ; then
		onNick "$srcnick" "$srcuser" "$srchost"
	elif grep -i "JOIN" <<<"$com" >/dev/null ; then
		onJoin "$srcnick" "$srcuser" "$srchost" "$arg"
	elif grep -i "INVITE" <<<"$com" >/dev/null ; then
		onInvite "$srcnick" "$srcuser" "$srchost" "$args"
	elif grep -i "MODE" <<<"$com" >/dev/null ; then
		onMode "$srcnick" "$srcuser" "$srchost" "$msg"
	elif grep -i "324" <<<"$com" >/dev/null ; then
		onMode "$srcnick" "$srcuser" "$srchost" "$msg"
	elif grep -i "329" <<<"$com" >/dev/null ; then
		onCreationDate "$srcnick"
	elif grep -i "^PING" <<<"$msg" >/dev/null ; then
		echo 'PING :hi' >&4
	elif grep -i "^PONG" <<<"$msg" >/dev/null ; then
		true
	elif grep -i "375" <<<"$com" >/dev/null ; then
		onMotd "$srcnick" "$lastarg"
	elif grep -i "376" <<<"$com" >/dev/null ; then
		onConnected "$srcnick"
	elif grep -i "352" <<<"$com" >/dev/null ; then
		onWho "$srcnick" "$arg"
	elif grep -i "372" <<<"$com" >/dev/null ; then
		onMotd "$srcnick" "$lastarg"
	elif grep -i "465" <<<"$com" >/dev/null ; then
		onBanned "$srcnick" "$lastarg"
	else
		grep -i '^NOTICE AUTH' <<<"$msg" >/dev/null && onNotice "Server" "" "" "AUTH" "$lastarg" || onRaw "$srcnick" "$srcuser" "$srchost" "$com" "$arg"
	fi
done <&4 &

while read -r com argv1 argv2 args ; do
	timestamp=$(date "+${BOLD_BLACK}%d %m %Y ${WHITE}%H${BOLD_BLACK}:${WHITE}%M")
	if grep -i "^:m$" <<<"$com" >/dev/null ; then
		read user host < hostfile
		onPrivmsg "$nick" "$user" "$host" "$argv1" "$argv2 $args"
		privmsg "$argv1" "$argv2 $args"
	elif grep -i ":hm" <<<"$com" >/dev/null ; then
		privmsg "$argv1" "$argv2 $args"
	elif grep -i ":n" <<<"$com" >/dev/null ; then
		read user host < hostfile
		onNotice "$nick" "$user" "$host" "$argv1" "$argv2 $args"
		notice "$argv1" "$argv2 $args"
	elif grep -i ":k" <<<"$com" >/dev/null ; then
		kick "$argv1" "$argv2" "Kicked: $args"
	elif grep -i ":l" <<<"$com" >/dev/null ; then
		part "$argv1" "I'm gone... $argv2 $args"
	elif grep -i ":j" <<<"$com" >/dev/null ; then
		join "$argv1" "$argv2"
		putserv "MODE $argv1"
	elif grep -i ":inv" <<<"$com" >/dev/null ; then
		putserv "INVITE $argv1 $argv2"
	elif grep -i ":flag" <<<"$com" >/dev/null ; then
		mode "$argv1 $argv2 $args"
	elif grep -i ":dcb" <<<"$com" >/dev/null ; then
		read user host < hostfile
		onPrivmsg "$nick" "$user" "$host" "$argv1" "ACTION $argv2 $args"
		privmsg "$argv1" "ACTION $argv2 $args"
	elif grep -i ":me$" <<<"$com" >/dev/null ; then
		read user host < hostfile
		onPrivmsg "$nick" "$user" "$host" "$activetarg" "ACTION $argv1 $argv2 $args"
		privmsg "$activetarg" "ACTION $argv1 $argv2 $args"
	elif grep -i ":meis" <<<"$com" >/dev/null ; then
		read user host < hostfile
		onPrivmsg "$nick" "$user" "$host" "$argv1" "MEIS $argv2 $args"
		privmsg "$argv1" "MEIS $argv2 $args"
	elif grep -i ":ctcp" <<<"$com" >/dev/null ; then
		privmsg "$argv1" "$argv2 $args"
	elif grep -i ":put" <<<"$com" >/dev/null; then
		putserv "$argv1 $argv2 $args"
	elif grep -i ":nick" <<<"$com" >/dev/null; then
		putserv "NICK $argv1 $argv2 $args"
		nick="$argv1"
	elif grep -i ":active" <<<"$com" >/dev/null; then
		echo "$timestamp" "${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE}" "I have set the active target to $argv1"
		activetarg="$argv1"
	elif grep -i ":q" <<<"$com" >/dev/null ; then
		putserv "QUIT :$argv1 $argv2 $args"
		echo "CLOSING LINK: Quit"
		sleep 5 ; exit
	elif grep -i "/help" <<<"$com" >/dev/null ; then
		while read line ; do
			echo "$timestamp ${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE} $line"
		done << EOF
Quick zIRCc help:
:active changes the active target. Requires one argument, new active PRIVMSG target.
:nick changes your nickname. Requires as many arguments as your ircd's NICK command takes.
:ctcp sends a CTCP to someone. You will receive a notice telling you what the reply is.
:me will do a /me in the active target.
:meis is a new feature. The IRCd sees it as a channel CTCP. Do NOT use when other clients will not interpret it correctly.
This CTCP is to a target, like :ctcp, but without needing a CTCP question.
:put is like Irssi's /raw.
:m <target> <message> sends <message> to <target> as a PRIVMSG.
:hm is as above, but does not reprint what you type.
:n sends a NOTICE. Same as :m otherwise.
:inv will invite someone to some channel. this should be DUH on the syntax.
:l will leave a channel, specified as the argument.
:j will join a channel, specified as the argument, or part all channels if the argument is 0 (only works on most IRCds)
:flag is like /mode on most IRC clients.
:dcb is :me but to a target other than the active target.
EOF
	else
		read user host < hostfile
		test -n "$activetarg" && ( privmsg "$activetarg" "$com $argv1 $argv2 $args"
		onPrivmsg "$nick" "$user" "$host" "$activetarg" "$com $argv1 $argv2 $args"
		)|| echo "$timestamp" "${BOLD_BLUE}-${WHITE}!${BOLD_BLUE}-${WHITE}" Huh\? WTF are you on about\? Tell me in words I\'ll understand. "(no active target to send message to)"
	fi
done
rm hostfile
exit
