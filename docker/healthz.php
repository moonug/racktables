<?php
# Readiness endpoint for container deployments. RackTables renders even its
# fatal errors with HTTP 200, so a probe on index.php cannot tell a working
# instance from a broken one. This file answers with a status code instead.
# The database version is deliberately not checked: after an image upgrade
# the administrator has to reach the UI to finish the upgrade.

require_once __DIR__ . '/inc/pre-init.php';

$installed = FALSE;
if ($found_secret_file)
	try
	{
		connectDB();
		$installed = FALSE !== $dbxlink->query ("SELECT varvalue FROM Config WHERE varname = 'DB_VERSION'")->fetchColumn();
	}
	catch (Throwable $e)
	{
		$installed = FALSE;
	}

http_response_code ($installed ? 200 : 503);
header ('Content-Type: text/plain');
echo $installed ? "ok\n" : "unavailable\n";
