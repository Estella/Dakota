#!/usr/bin/env zsh

#HALP!
# So this is going to be the listener file for my ircd.
# I don't know nor care what my IRCd will be written in.
# But when complete, it will destroy all other IRCds with
# its code cleanliness. (cue wIRCd advert)
# I will probably be running this listen script, lol,
# so this will incorporate features from the old Citrus ircd.

zmodload zsh/net/tcp
ztcp -l 6667
listenfd=$REPLY

while ztcp -a 10 ; do
	ipaddr="$(ztcp -L | grep \"^$REPLY\" | cut -f 5 -d' ')"
	theirport="$(ztcp -L | grep \"^$REPLY\" | cut -f 6 -d' ')"
	theirident="$(echo $theirport, $myport | nc $ipaddr 113)"
	grep -i '^$' <<<"$theirident" && theirident="noident"
	if test "$theirident" \!= "noident" ; then
		theirid="$(perl -p -e 's/.*://g' <<<\"$theirident\")"
	fi
	./accept.sh "$theirid" $ipaddr <>$REPLY &
done
