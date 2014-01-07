#!/usr/bin/env zsh
# zsh irc client framework
zmodload zsh/net/tcp
ztcp -d 4 "$1" "$2"

. "$3"

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
	echo "PRIVMSG $targ" ":$*"
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
	echo "JOIN $chan" >&4
}

echo "USER $nick 8 * :$realname" >&4
echo "NICK $nick" >&4

while read source com arg ; do
	echo "$source" "$com" "$arg"
	test "$source" = "PING" && echo "PONG $com" >&4
	test "$com" = "PING" && echo "PONG $arg" >&4
	args="$(perl -p -e 's/(..*):(.*)$/$1/g' <<<$arg)"
	lastarg="$(perl -p -e 's/^.* :(.*)$/$1/g' <<<$arg)"
	srcnick="$(perl -p -e 's/:(.*)!(.*)@(.*)/$1/g' <<<$source)"
	srcuser="$(perl -p -e 's/:(.*)!(.*)@(.*)/$2/g' <<<$source)"
	srchost="$(perl -p -e 's/:(.*)!(.*)@(.*)/$3/g' <<<$source)"
	if grep -i "PRIVMSG" <<<"$com" ; then
		onPrivmsg "$srcnick" "$srcuser" "$srchost" "$args" "$lastarg"
	elif grep -i "NOTICE" <<<"$com" ; then
		onNotice "$srcnick" "$srcuser" "$srchost" "$args" "$lastarg"
	elif grep -i "KICK" <<<"$com" ; then
		onNotice "$srcnick" "$srcuser" "$srchost" "$args" "$lastarg"
	elif grep -i "PART" <<<"$com" ; then
		onNotice "$srcnick" "$srcuser" "$srchost" "$args" "$lastarg"
	elif grep -i "JOIN" <<<"$com" ; then
		onNotice "$srcnick" "$srcuser" "$srchost" "$args"
	elif grep -i "INVITE" <<<"$com" ; then
		onInvite "$srcnick" "$srcuser" "$srchost" "$args"
	elif grep -i "MODE" <<<"$com" ; then
		onMode "$srcnick" "$srcuser" "$srchost" "$arg"
	elif grep -i "376" <<<"$com" ; then
		onConnected
	else
		onRaw "$srcnick" "$srcuser" "$srchost" "$com" "$arg"
	fi
done <&4

