#!/bin/sh -e

THISDIR=$(dirname "$0")
: "${PHPUNIT_BIN:=phpunit}"

command -v php >/dev/null || {
	echo 'ERROR: PHP CLI binary is not available!' >&2
	exit 3
}
command -v "$PHPUNIT_BIN" >/dev/null || {
	echo "ERROR: $PHPUNIT_BIN is not an executable command" >&2
	exit 4
}

case $("$PHPUNIT_BIN" --version) in
	'PHPUnit '[6789].*)
		BOOTSTRAP_FILE=bootstrap.php
		;;
	*)
		echo 'ERROR: unsupported PHPUnit version' >&2
		"$PHPUNIT_BIN" --version >&2
		exit 5
esac

# At this point it makes sense to test specific functions.
echo "Running PHPUnit tests using bootstrap file '$BOOTSTRAP_FILE'."

cd "$THISDIR"
"$PHPUNIT_BIN" --group small --bootstrap "$BOOTSTRAP_FILE"
