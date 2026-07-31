#!/bin/sh

if [ $# -ne 3 ]; then
	echo "This script creates a MySQL database for unit testing in a CI environment."
	echo "Don't run it on a production system because it may cause lots of damage."
	echo "Usage: $0 <MySQL database name> <MySQL user name> <MySQL user password>"
	exit 1
fi

DBNAME="$1"
USERNAME="$2"
PASSWORD="$3"
THISDIR=$(dirname "$0")
BASEDIR=$(readlink -f "$THISDIR/..")

# Allow overriding the MySQL host (default: localhost, i.e. the local unix
# socket). Set RT_DB_HOST=127.0.0.1 (or any TCP host) for CI environments
# where the server runs in a sidecar container reachable only over TCP.
: "${RT_DB_HOST:=localhost}"
MYSQL_OPTS="-h $RT_DB_HOST -u root"

if mysql $MYSQL_OPTS -e "SHOW TABLES FROM $DBNAME" >/dev/null 2>&1; then
	echo "Error: database $DBNAME already exists!"
	exit 1
fi

if [ -e "$BASEDIR/wwwroot/inc/secret.php" ]; then
	echo "Error: '$BASEDIR/wwwroot/inc/secret.php' already exists!"
	exit 1
fi

# The purpose of the explicit "mysql" DB below is not to fix a real bug
# but to prevent an error on my working copy when the MySQL client
# is configured (through ~/.my.cnf) to connect to the same database as
# I am trying to initialize with this script. In that specific case
# the client tries to connect to the database that doesn't yet exist
# and this script fails, hence the override to "mysql". -- Denis
mysql $MYSQL_OPTS mysql -e "CREATE DATABASE ${DBNAME} CHARACTER SET utf8 COLLATE utf8_general_ci;" || exit 2
mysql $MYSQL_OPTS -e "CREATE USER ${USERNAME}@'%' IDENTIFIED BY '${PASSWORD}';" || exit 2
mysql $MYSQL_OPTS -e "GRANT ALL PRIVILEGES ON ${DBNAME}.* TO ${USERNAME}@'%';" || exit 2

# "~/.my.cnf" is only useful for the local (unix socket) developer workflow,
# where it lets the mysql CLI pick up the test credentials without -u/-p.
# In CI the server is reached over TCP as root with no password; writing a
# my.cnf with the test user's password here would make the later root calls
# send that password (mismatch) and fail. Skip it when RT_DB_HOST is set.
if [ "$RT_DB_HOST" = "localhost" ] && ! [ -e ~/.my.cnf ]; then
	cat > ~/.my.cnf <<EOF
[client]
user=$USERNAME
password=$PASSWORD
EOF
fi

cat > "$BASEDIR/wwwroot/inc/secret.php" <<EOF
<?php
\$pdo_dsn = 'mysql:host=${RT_DB_HOST};port=3306;dbname=${DBNAME}';
\$db_username = '${USERNAME}';
\$db_password = '${PASSWORD}';
EOF

cat > "$BASEDIR/cli_install.php" <<EOF
<?php
require_once 'wwwroot/inc/pre-init.php';
require_once 'wwwroot/inc/dictionary.php';
require_once 'wwwroot/inc/config.php';
require_once 'wwwroot/inc/install.php';
ob_start();
init_database_static();
ob_end_clean();
EOF

cd "$BASEDIR" || exit 3
php cli_install.php || exit 3
mysql $MYSQL_OPTS "$DBNAME" -e "INSERT INTO UserAccount (user_id, user_name, user_password_hash) VALUES (1, 'admin', SHA1('${PASSWORD}'));" || exit 3
