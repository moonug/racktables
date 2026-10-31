#!/bin/bash
# Runs one RackTables command from a short-lived container: periodic jobs do
# not get the configuration file the web container writes at start.
set -euo pipefail

export RT_SECRET_FILE=${RT_SECRET_FILE:-/run/racktables/secret.php}

php /usr/local/lib/racktables/app-init.php secret
exec "$@"
