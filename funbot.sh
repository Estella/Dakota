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
