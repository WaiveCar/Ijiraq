#!/bin/bash

export WHO=adorno
export DEST=/home/$WHO
export PATH=/usr/bin:/usr/sbin:$PATH:$DEST
export BASE=$DEST/WaiveScreen
export DEV=$BASE.sshfs
export VID=$DEST/capture
export EV=/tmp/event
export DISPLAY=${DISPLAY:-:0}
#
# Valid values are "production" and "development"
#
# These are used for things like flask so you really
# shouldn't be lazy and shorten them unless you want
# to somehow accomodate for that fact.
#
export ENV=`cat $DEST/.env`
pkill osd_cat

if [ ! -d $EV ]; then 
  mkdir -p $EV 
  chmod 0777 $EV
fi

[[ $ENV = 'development' ]] && export BASE=$DEV
[[ $USER = 'root' ]] && SUDO= || SUDO=/usr/bin/sudo

help() {
  # just show the local fuctions
  declare -F | sed s/'declare -f//g' | sort
}

onscreen() {
  if [ ! -e /tmp/offset ]; then
    offset=0
  else
    offset=$( cat /tmp/offset )
  fi

  ts=$( printf "%03d" $(( $(date +%s) - $(cat /tmp/startup) )))
  size=14

  #from=$( caller 1 | awk ' { print $2":"$1 } ' )
  echo "$ts" $1 | osd_cat \
      -c $2 \
      -u black \
      -O 1 \
      -o $offset \
      -d $3 \
      -f lucidasanstypewriter-bold-$size &

  echo $1
  offset=$(( (offset + size + 9) % ((size + 9) * 25) ))

  echo $offset > /tmp/offset
  chmod 0666 /tmp/offset
}
announce() {
  onscreen "$*" white 20
}
info() {
  onscreen "$*" white 10
}
warn() {
  onscreen "$*" yellow 40
}
error() {
  onscreen "$*" red 90
}

who_am_i() {
  info $(uuid) $ENV
}

event_is_set() {
  return [ -e $EV/$1 ] 
}

set_event() {
  pid=${2:-$!}
  [ -e $EV/$1 ] || announce Event:$1
  echo -n $pid > $EV/$1
  echo `date +%R:%S` $1
}

modem_enable() {
  for i in $( seq 1 5 ); do
    $SUDO mmcli -m 0 -e

    if [ ! $? ]; then 
      warn "Searching for modem"
      sleep 1
      continue
    fi

    # You won't find these options in the manpage, they are from
    # cli/mmcli-modem-location.c in the ModemManager source code
    # over at https://www.freedesktop.org/software/ModemManager/
    $SUDO mmcli -m 0 \
      --location-enable-gps-raw \
      --location-enable-agps \
      --location-enable-gps-nmea \
      --location-set-enable-signal

      #
      # I don't quite know what this option does (but I didn't 
      # study the code). Our Quectel 25A modems seem to not 
      # understand it.
      #
      #--location-enable-gps-unmanaged \

    set_event modem
    break
  done
}

modem_connect() {
  for i in 1 2; do
    $SUDO mmcli -m 0 --simple-connect="apn=internet"
    wwan=`ip addr show | grep wwp | head -1 | awk -F ':' ' { print $2 } '`

    if [ -z "$wwan" ]; then
      warn  "No modem found. Trying again"
      sleep 4
    else
      break
    fi
  done

  # get ipv6
  $SUDO dhclient $wwan &

  # Show the config | find ipv4 | drop the LHS | replace the colons with equals | drop the whitespace | put everything on one line
  eval `mmcli -b 0 | grep -A 3 IPv4 | awk -F '|' ' { print $2 } ' | sed s'/: /=/' | sed -E s'/\s+//' | tr '\n' ';'`

  $SUDO ip addr add $address/$prefix dev $wwan
  $SUDO ip route add default via $gateway dev $wwan

  cat << ENDL | $SUDO tee /etc/resolv.conf
  nameserver 8.8.8.8
  nameserver 4.2.2.1
  nameserver 2001:4860:4860::8888 
  nameserver 2001:4860:4860::8844
ENDL
  set_event net ''

  sleep 9

  if ping -c 1 -i 0.3 waivescreen.com; then
    announce "waivescreen.com found" 
  else
    warn "waivescreen.com unresolvable!"

    while ! mmcli -m 0; do
      announce "Waiting for modem"
      sleep 9
    done

    hasip=$( ip addr show $wwan | grep inet | wc -l )
    myphone=$( mmcli  -m 0 | grep own | awk ' { print $NF } ' )
    if (( hasip > 0 )); then
      warn "Data plan issues."
    else
      warn "No IP assigned."
    fi
    error "$myphone"
  fi
}

ssh_hole() {
  $SUDO $BASE/ScreenDaemon/dcall emit_startup | /bin/sh
  set_event ssh
}

screen_daemon() {
  down screen_daemon
  # TODO: We need to use some polkit thing so we can
  # access the modem here and not run this as root in the future
  {
    $SUDO FLASK_ENV=$ENV $BASE/ScreenDaemon/ScreenDaemon.py &
  } | $SUDO tee -a /var/log/screendaemon.log

  set_event screen_daemon
}

sensor_daemon() {
  down sensor_daemon
  $SUDO $BASE/ScreenDaemon/SensorDaemon.py &
  set_event sensor_daemon
}

git_waivescreen() {
  {
    # Make sure we're online
    wait_for net

    if [ -e $DEST/WaiveScreen ]; then
      cd $DEST/WaiveScreen
      git stash
      git pull
    else  
      cd $DEST
      git clone git@github.com:WaiveCar/WaiveScreen.git
      ainsl $DEST/.bashrc 'PATH=$PATH:$HOME/.local/bin' 'HOME/.local/bin'
    fi
  } &
}

uuid() {
  UUID=/etc/UUID
  if [ ! -e $UUID ] ; then
    $SUDO dmidecode -t 4 | grep ID | sed -E s'/ID://;s/\s//g' | $SUDO tee $UUID
  fi
  cat $UUID
}

sync_scripts() {
  rsync --exclude=.xinitrc -aqzr $DEV/Linux/fai-config/files/home/ $DEST
  chmod 0600 $DEST/.ssh/KeyBounce $DEST/.ssh/github $DEST/.ssh/dev
}

wait_for() {
  path=${2:-$EV}/$1

  if [ ! -e "$path" ]; then
    until [ -e "$path" ]; do
      echo `date +%R:%S` WAIT $1
      sleep 0.5
    done

    # Give it a little bit after the file exists to
    # avoid unforseen race conditions
    sleep 0.05
  fi
}

dev_setup() {
  #
  # Note: this usually runs as normal user
  #
  # echo development > $DEST/.env
  $SUDO dhclient enp3s0 
  [ -e $DEV ] || mkdir $DEV

  if [ -z "$SUDO" ]; then
    warn "Hey, you can't be root to do sshfs"
  fi

  sshfs -o uid=$(id -u $WHO),gid=$(id -g $WHO) dev:/home/chris/code/WaiveScreen $DEV -C -o allow_root
  export BASE=$DEV
  set_event net ''
}


install() {
  cd $BASE/ScreenDaemon
  $SUDO pip3 install -r requirements.txt 
}

screen_display_loop() {
  export DISPLAY=${DISPLAY:-:0}
  [[ $ENV = 'development' ]] && wait_for net

  if [ ! -e $BASE ]; then
    git_waivescreen
    wait_for $BASE ''
  fi

  local app=$BASE/ScreenDisplay/display.html 
  if [ -e $app ]; then
    chromium --app=file://$app &
    set_event screen_display
  else
    error "Can't find $app. Exiting"
    exit 
  fi
}

screen_display() {
  {
    while pgrep Xorg; do

      while pgrep chromium; do
        sleep 5
      done

      if event_is_set screen_display; then
        screen_display_single
      else
        break
      fi
    done
  } > /dev/null &
}

down() {
  cd $EV
  if [ -n "$1" ]; then
    [ -s "$pidfile" ] && kill $( cat $pidfile )
    [ -e "$pidfile" ] && rm $pidfile
  else
    for pidfile in $( ls ); do
      echo $pidfile
      [ -s "$pidfile" ] && kill $( cat $pidfile )
      [ -e "$pidfile" ] && rm $pidfile
    done
  fi
}

upgrade() {
  # Since everything is in memory and already loaded
  # we can try to just pull things down
  cd $BASE
  
  # We make sure that local changes (there shouldn't be any)
  # get tossed aside and pull down the new code.
  git stash

  if git pull; then
    # If there's script updates we try to pull those down
    # as well
    rsync --exclude=.xinitrc -aqzr $BASE/Linux/fai-config/files/home/ $DEST
    chmod 0600 $DEST/.ssh/KeyBounce $DEST/.ssh/github $DEST/.ssh/dev

    # Now we take down the browser.
    down screen_display

    # This stuff shouldn't be needed
    # But right now it is.
    $SUDO pkill start-x-stuff
    $SUDO pkill -f ScreenDisplay

    # And the server (which in practice called us from a ping command)
    down screen_daemon
    $SUDO pkill -f ScreenDaemon

    # And lastly the sensor daemon
    down sensor_daemon
    $SUDO pkill -f SensorDaemon

    sensor_daemon
    screen_daemon
    screen_display 
  fi
}

xrestart() {
  {
    $SUDO pkill Xorg
    $SUDO xinit
  } &
}

location() {
  $SUDO mmcli -m 0 --location-get
  $SUDO mmcli -m 0 --location-status
}

nop() { 
  true
}
