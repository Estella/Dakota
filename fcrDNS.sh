#!/usr/bin/env bash
# IPv6
ip a | grep "^    inet6 .* scope global" | sed -e 's!/.*!!g' -e 's!^    inet6 !!g' | \
while read line  ; do printf '[%s]: ' "$line" ; result="$(dig +short -x $line | sed -e 's&\.$&&g')" ; test "$(dig +short $result AAAA)" = "$line" && echo $result || echo ; done
# IPv4
ip a | grep "^    inet .* scope global" | sed -e 's!/.*!!g' -e 's!^    inet !!g' | \
while read line  ; do printf '%s: ' "$line" ; result="$(dig +short -x $line | sed -e 's&\.$&&g')" ; test "$(dig +short $result A)" = "$line" && echo $result || echo ; done
