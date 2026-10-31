<?php
# Container helper. Writes the deployment configuration and, in the "init"
# mode, creates a fresh database. Ships in the image, not in the repository.

$root = getenv ('RT_ROOT') ?: '/usr/share/racktables';
$mode = $argv[1] ?? 'init';

function rtEnv ($name)
{
	$value = getenv ($name);
	if ($value !== FALSE && $value !== '')
		return $value;
	$file = getenv ("${name}_FILE");
	if ($file === FALSE || $file === '')
		return '';
	$content = @file_get_contents ($file);
	if ($content === FALSE)
		rtFail ("cannot read ${file}");
	return trim ($content);
}

function rtFail ($message)
{
	fwrite (STDERR, "racktables-init: ${message}\n");
	exit (1);
}

function rtFlag ($name)
{
	$value = strtolower (rtEnv ($name));
	return ! in_array ($value, array ('', '0', 'no', 'false', 'off'), TRUE);
}

$secret_file = getenv ('RT_SECRET_FILE') ?: '/run/racktables/secret.php';
$db_host = rtEnv ('RT_DB_HOST') ?: 'localhost';
$db_port = rtEnv ('RT_DB_PORT') ?: '3306';
$db_name = rtEnv ('RT_DB_NAME') ?: 'racktables';
$db_user = rtEnv ('RT_DB_USER') ?: 'racktables';
$db_password = rtEnv ('RT_DB_PASSWORD');
$admin_password = rtEnv ('RT_ADMIN_PASSWORD');
$dsn = "mysql:host=${db_host};port=${db_port};dbname=${db_name}";

$secret = "<?php\n"
	. '$pdo_dsn = ' . var_export ($dsn, TRUE) . ";\n"
	. '$db_username = ' . var_export ($db_user, TRUE) . ";\n"
	. '$db_password = ' . var_export ($db_password, TRUE) . ";\n";
if (! @mkdir (dirname ($secret_file), 0750, TRUE) && ! is_dir (dirname ($secret_file)))
	rtFail ("cannot create " . dirname ($secret_file));
if (file_put_contents ($secret_file, $secret) === FALSE)
	rtFail ("cannot write ${secret_file}");
# The web server runs as www-data, the file carries the database password.
# When the entrypoint does not run as root the group cannot be changed.
if (@chgrp ($secret_file, 'www-data'))
	@chmod ($secret_file, 0640);
else
	@chmod ($secret_file, 0644);

if ($mode === 'secret')
	exit (0);
if ($mode !== 'init')
	rtFail ("unknown mode '${mode}', expected 'secret' or 'init'");

$pdo = NULL;
for ($attempt = 0; $attempt < 60; $attempt++)
{
	try
	{
		$pdo = new PDO ($dsn, $db_user, $db_password, array (PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
		break;
	}
	catch (PDOException $e)
	{
		$pdo = NULL;
		sleep (2);
	}
}
if ($pdo === NULL)
	rtFail ("database ${db_host}:${db_port}/${db_name} is not reachable");

$tables = $pdo->query ('SHOW TABLES')->fetchAll (PDO::FETCH_COLUMN, 0);
if (count ($tables))
{
	echo "database ${db_name} is already initialized\n";
	if (rtFlag ('RT_DEMO_DATA'))
		echo "RT_DEMO_DATA is ignored: the sample inventory loads into an empty database only\n";
	exit (0);
}

# init_database_static() connects on its own, so verify the server first
# (above) instead of relying on its error handling, and treat anything but a
# clean TRUE as a failure: it renders errors as HTML and returns FALSE.
require_once "${root}/wwwroot/inc/pre-init.php";
require_once "${root}/wwwroot/inc/dictionary.php";
require_once "${root}/wwwroot/inc/config.php";
require_once "${root}/wwwroot/inc/install.php";
ob_start();
$result = init_database_static();
$output = ob_get_clean();
if ($result !== TRUE)
{
	fwrite (STDERR, strip_tags ($output) . "\n");
	rtFail ("database initialisation failed");
}

if ($admin_password === '')
	rtFail ("database is empty: set RT_ADMIN_PASSWORD to create the administrator account");

$statement = $pdo->prepare
(
	"INSERT INTO UserAccount (user_id, user_name, user_password_hash, user_realname) " .
	"VALUES (1, 'admin', ?, 'RackTables Administrator')"
);
if (! $statement->execute (array (sha1 ($admin_password))))
	rtFail ("cannot create the administrator account");

# Optional demo inventory: the ISA Net story from scripts/make-demo-data.php.
if (rtFlag ('RT_DEMO_DATA'))
{
	$demo = "${root}/scripts/init-demo-data.sql";
	if (! is_readable ($demo))
		rtFail ("${demo} is not readable");
	$pdo->exec (file_get_contents ($demo));
	echo "demo inventory loaded\n";
}

echo "database ${db_name} initialized\n";
