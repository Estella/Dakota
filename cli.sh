#!/usr/bin/env zsh
# zsh irc client framework
autoload -U colors && colors
for COLOR in RED GREEN YELLOW BLUE MAGENTA CYAN BLACK WHITE; do
    eval $COLOR='%{$fg_no_bold[${(L)COLOR}]%}'
    eval BOLD_$COLOR='%{$fg_bold[${(L)COLOR}]%}'
done
eval RESET='%{$reset_color%}'
zmodload zsh/net/tcp
ztcp -d 4 "$1" "$2"

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
		print -P -- "$BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK($BLUE$4$BOLD_BLACK)-$WHITE RAW: $5"
	else
		print -P -- "$BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)-$WHITE RAW: $4 $5"
	fi
}

onPrivmsg(){
	if grep -i "^ACTION" <<<"$5" >/dev/null ; then
		action="$(perl -p -e 's/^.ACTION //g' <<<$5)"
		print -P -- "$BOLD_BLACK<$WHITE$4$BOLD_BLACK> ${BOLD_WHITE}* $1$WHITE $action"
	elif grep -i "^VERSION" <<<"$5" >/dev/null ; then
		print -P -- "<$CYAN$4/$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)>$WHITE CTCP$BOLD_WHITE VERSION$WHITE"
		notice "$4" "VERSION zIRCc 0.1 (c) j4jackj & The Dakota Project"
	else
		print -P -- "<$CYAN$4/$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)>$WHITE $5"
	fi
}

onNotice(){
	print -P -- "$BOLD_BLACK-$BOLD_MAGENTA$4/$1$BOLD_BLACK($MAGENTA${2}@${3}$BOLD_BLACK)-$WHITE $5"
}

onKick(){
	echo "Kick: $1 $2 $3 $4 $5"
}

onPart(){
	print -P -- "$BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)-$WHITE I am leaving $4 ($5)"
}

onInvite(){
	print -P -- "$BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)-$WHITE Invite: $4"
}

onMode(){
	print -P -- "$BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)-$WHITE I changed modes: $4"
}

onMotd(){
	print -P -- "$BOLD_BLACK-$BOLD_BLUE$1(${BLUE}Message of the Day$BOLD_BLACK)-$WHITE $2"
}

onConnected(){
	print -P -- "$BOLD_BLACK-$BOLD_BLUE$1(${BLUE}Message of the Day$BOLD_BLACK)-$WHITE You are now connected to IRC."
}

onJoin(){
	print -P -- "$BOLD_BLACK-$BOLD_BLUE$1$BOLD_BLACK($BLUE${2}@${3}$BOLD_BLACK)-$WHITE I am joining $4."
}
( while read source com arg ; do
	msg="$source $com $arg"
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
		onJoin "$srcnick" "$srcuser" "$srchost" "$args"
	elif grep -i "INVITE" <<<"$com" >/dev/null ; then
		onInvite "$srcnick" "$srcuser" "$srchost" "$args"
	elif grep -i "MODE" <<<"$com" >/dev/null ; then
		onMode "$srcnick" "$srcuser" "$srchost" "$arg"
	elif grep -i "375" <<<"$com" >/dev/null ; then
		onMotd "$srcnick" "$lastarg"
	elif grep -i "376" <<<"$com" >/dev/null ; then
		onConnected "$srcnick"
	elif grep -i "372" <<<"$com" >/dev/null ; then
		onMotd "$srcnick" "$lastarg"
	else
		grep -i '^NOTICE AUTH' <<<"$msg" >/dev/null && onNotice "Server" "" "" "AUTH" "$lastarg" || onRaw "$srcnick" "$srcuser" "$srchost" "$com" "$arg"
	fi
done <&4 ) &

while read com argv1 argv2 args ; do
	if grep -i ":m" <<<"$com" >/dev/null ; then
		privmsg "$argv1" "$argv2 $args"
	elif grep -i ":n" <<<"$com" >/dev/null ; then
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
		privmsg "$argv1" "ACTION $argv2 $args"
	elif grep -i ":put" <<<"$com" >/dev/null; then
		putserv "$argv1 $argv2 $args"
	elif grep -i ":q" <<<"$com" >/dev/null ; then
		putserv "QUIT $argv1 $argv2 $args"
		echo "CLOSING LINK: Quit"
		sleep 5 ; exit
	else
		echo Unrecognised command.
	fi
done

exit
