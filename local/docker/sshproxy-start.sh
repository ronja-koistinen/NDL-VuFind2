#!/bin/sh

set -eu

# we copy these to get around the ssh client's owner, group and mode checks
install -o 0 -g 0 -m 0600 /tmp/ssh_known_hosts /etc/ssh/
install -o 0 -g 0 -m 0600 /tmp/ssh.conf /etc/ssh/ssh_config.d/

printf "listen-address 0.0.0.0:8090\nforward-socks5 / localhost:$SOCKS5_PROXY_PORT .\n" \
   > /etc/privoxy/config

/usr/sbin/privoxy --no-daemon /etc/privoxy/config &
/usr/bin/ssh -N -g -D "$SOCKS5_PROXY_PORT" -i /root/identity \
    -J "$SSH_JUMP_SEQUENCE" "$SOCKS5_PROXY_DST"
