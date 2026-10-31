#!/bin/bash
# Runs php-fpm and nginx in one container, the way a web server pair is
# usually shipped. tini is PID 1 and forwards signals here; this script only
# has to stop both daemons gracefully and exit with the status that killed
# the pair.
set -euo pipefail

# php-fpm runs with clear_env = no, so the workers see this variable and
# RackTables finds the configuration file (see pre-init.php).
export RT_SECRET_FILE=${RT_SECRET_FILE:-/run/racktables/secret.php}

php /usr/local/lib/racktables/app-init.php secret

php-fpm8.2 --nodaemonize --fpm-config /etc/php/8.2/fpm/php-fpm.conf &
fpm_pid=$!

nginx -g 'daemon off;' &
nginx_pid=$!

# QUIT is a graceful stop for both daemons.
shutdown() {
	kill -QUIT "$nginx_pid" "$fpm_pid" 2>/dev/null || true
}
trap shutdown TERM INT

set +e
wait -n
status=$?
set -e

shutdown
wait || true
exit "$status"
