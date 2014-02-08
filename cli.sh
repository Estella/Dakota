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
	if test "$srcnick" = "$srchost" ; then
		echo "$timestamp ${BOLD_BLACK}-${BOLD_BLUE}$1${BOLD_BLACK}($BLUE$4$BOLD_BLACK)-$WHITE RAW: $5"
	else
		echo "$timestamp ${BOLD_BLACK}-${BOLD_BLUE}$1${BOLD_BLACK}($BLUE${2}@${3}$BOLD_BLACK)-$WHITE RAW: $4 $5"
	fi
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
	echo "$timestamp $BOLD_BLACK-$BOLD_MAGENTA$4/$1$BOLD_BLACK($MAGENTA${2}@${3}$BOLD_BLACK)-$WHITE $5"
}

onKick(){
	echo "Kick: $1 $2 $3 $4 $5"
}

onPart(){
	echo "$timestamp $BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)-$WHITE I am leaving $4 ($5)"
}

onInvite(){
	echo "$timestamp $BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)-$WHITE Invite: $4"
}

onMode(){
	echo "$timestamp $BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)-$WHITE I changed modes: $4"
}

onMotd(){
	echo "$timestamp $BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK(${BLUE}Message of the Day$BOLD_BLACK)-$WHITE $2"
}

onConnected(){
	putserv "WHO $nick"
	echo "$timestamp $BOLD_BLACK-$BOLD_BLUE$1${BOLD_BLACK}-$WHITE You are now connected to IRC."
}

onJoin(){
	echo "$timestamp $BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)-$WHITE I am joining $4."
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
	args="$(perl -p -e 's/^(?:[:](\S+) )?(\S+)(?: (?!:)(.+?))?(?: [:](.+))?$/$3/g' <<<$msg | sed -e 's/^ //g' -e 's/ $//g')"
	lastarg="$(perl -p -e 's/^(?:[:](\S+) )?(\S+)(?: (?!:)(.+?))?(?: [:](.+))?$/$4/g' <<<$msg)"
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
	elif grep -i "JOIN" <<<"$com" >/dev/null ; then
		onJoin "$srcnick" "$srcuser" "$srchost" "$arg"
	elif grep -i "INVITE" <<<"$com" >/dev/null ; then
		onInvite "$srcnick" "$srcuser" "$srchost" "$args"
	elif grep -i "MODE" <<<"$com" >/dev/null ; then
		onMode "$srcnick" "$srcuser" "$srchost" "$arg"
	elif grep -i "^PING" <<<"$msg" >/dev/null ; then
		true
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
	else
		grep -i '^NOTICE AUTH' <<<"$msg" >/dev/null && onNotice "Server" "" "" "AUTH" "$lastarg" || onRaw "$srcnick" "$srcuser" "$srchost" "$com" "$arg"
	fi
done <&4 &

while read com argv1 argv2 args ; do
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
	elif grep -i ":inv" <<<"$com" >/dev/null ; then
		putserv "INVITE $argv1 $argv2"
	elif grep -i ":flag" <<<"$com" >/dev/null ; then
		mode "$argv1 $argv2 $args"
	elif grep -i ":dcb" <<<"$com" >/dev/null ; then
		read user host < hostfile
		onPrivmsg "$nick" "$user" "$host" "$argv1" "ACTION $argv2 $args"
		privmsg "$argv1" "ACTION $argv2 $args"
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
	elif grep -i ":q" <<<"$com" >/dev/null ; then
		putserv "QUIT $argv1 $argv2 $args"
		echo "CLOSING LINK: Quit"
		sleep 5 ; exit
	else
		echo "$timestamp" Huh\?
	fi
done
rm hostfile
exit
