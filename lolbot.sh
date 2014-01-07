#### IRC client part
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

####
#
#
#
#### Event based scripting in Zsh is so fun!
#!/usr/bin/env zsh
nick="j4Bawtty"
realname="u"
touch ~/joiners
onPrivmsg(){
	echo "$5"
	if grep -i "hrows (.*) in the brig to awai" <<<"$5" ; then
		privmsg "$4" "$1: You have been charged with misuse of the brig."
		privmsg "$4" "You have been found guilty, and are sentenced to leave the channel. Rejoin if necessary, but don't misuse the brig like that again."
		kick "$4" "$1" "Killed ($4 (Guilty of brig misuse))"
	elif grep -i "kickall" <<<"$5" ; then
		privmsg "$4" "$1: Please do not use the kick-all function of whatever bot you may have."
		kick "$4" "$1" "Killed ($4 (Kick all attempt))"
	elif grep -i "^u" <<<"$5" ; then
		privmsg "$4" "u"
	elif grep -i "fuck you" <<<"$5" ; then
		privmsg "$4" "$1: Fuck you too, twatface!"
	elif grep -i "hi" <<<"$5" ; then
		privmsg "$4" "Hello to you too, $1. How may I help you?"
	fi
}


onNotice(){
}
onKick(){
}
onPart(){
}
onJoin(){
}
onInvite(){
	chan="$(perl -p -e 's/^(.*) (.*)/$2/g' <<<$4)"
	echo "$chan" >>"$HOME/joiners"
	join "$chan"
}
onMode(){
}
onRaw(){
}

onConnected(){
while read chan ; do
	echo "JOIN $chan" >&4
done < ~/joiners
done="lol"
}

