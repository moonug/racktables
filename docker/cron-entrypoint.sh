#!/bin/bash
# Periodic RackTables jobs: VLAN domain synchronisation and LDAP cache
# cleanup. Kubernetes deployments should use CronJobs instead (see the Helm
# chart); this entrypoint exists for docker-compose and simple VM setups.
set -euo pipefail

secret_file=${RT_SECRET_FILE:-/run/racktables/secret.php}
syncdomain_schedule=${RT_SYNCDOMAIN_SCHEDULE:-*/5 * * * *}
ldap_cache_schedule=${RT_LDAP_CACHE_SCHEDULE:-17 3 * * *}
crontab=/etc/cron.d/racktables

# The jobs need the configuration file the web container normally writes at
# start.
export RT_SECRET_FILE="$secret_file"
php /usr/local/lib/racktables/app-init.php secret

# The scripts require inc/init.php relative to the working directory.
{
	echo "SHELL=/bin/sh"
	echo "PATH=/usr/local/bin:/usr/bin:/bin"
	if [ -n "$syncdomain_schedule" ]; then
		echo "$syncdomain_schedule root cd /usr/share/racktables/wwwroot && " \
			"RT_SECRET_FILE='$secret_file' php ../scripts/syncdomain.php --mode=pullall >/proc/1/fd/1 2>&1"
	fi
	if [ -n "$ldap_cache_schedule" ]; then
		echo "$ldap_cache_schedule root cd /usr/share/racktables/wwwroot && " \
			"RT_SECRET_FILE='$secret_file' php ../scripts/cleanup_ldap_cache.php >/proc/1/fd/1 2>&1"
	fi
} > "$crontab"
chmod 0644 "$crontab"

exec cron -f
