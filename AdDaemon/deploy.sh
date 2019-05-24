#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR
git pull

lib/update_db.php

ver=$(git describe)
last_commit=$(git log -1 --format="%at")

cat > lib/const.php << ENDL
<?php
\$VERSION="$ver";
\$LASTCOMMIT="$last_commit";
ENDL
