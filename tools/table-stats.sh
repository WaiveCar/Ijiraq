#!/bin/bash
db=/var/db/waivescreen/main.db

for table in $(sqlite3 $db "select name from sqlite_master where type='table'"); do
  printf "%-8s %s\n" $(sqlite3 $db "select count(*) from $table") $table
done
  
