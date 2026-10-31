# AGENTS.md

Guidance for AI coding agents. Keep this file under 8 KB: facts about the
code, the build and the environment that every agent needs.

## Project

RackTables is a datacenter and server room management application: a PHP front
end under `wwwroot/`, a MySQL/MariaDB database, and helper scripts for
talking to network equipment. This repository is a fork of
[RackTables/racktables](https://github.com/RackTables/racktables) based on
release 0.22.1. `master` carries the fork's work; `upstream/master` tracks the
original project and is never pushed to.

The repository is public. Code, comments, commit messages, pull request text
and issues must not contain internal hostnames, internal e-mail addresses,
ticket keys or links to private resources.

## Build & test

```bash
php -l <file>                                     # syntax check; code must stay PHP 7.4 compatible
tests/ci_setup_mysql.sh <db> <user> <password>    # create a database, secret.php and cli_install.php
PHPUNIT_BIN=phpunit tests/express_phpunit.sh      # PHPUnit, small group
docker build -t racktables:dev .                  # container image
docker compose up -d --build --wait               # image + MariaDB + schema and sample data
helm lint deploy/helm/racktables --set externalDatabase.password=x
helm template ci deploy/helm/racktables --set externalDatabase.password=x
make hooks                                        # pre-commit checks, once per clone
```

`ci_setup_mysql.sh` refuses to run when the database or `wwwroot/inc/secret.php`
already exists; drop the database, drop the user it created and move the file
away to start over. The database name must end with `_unittest`: the test
bootstrap refuses any other name. `PHPUNIT_BIN` needs an absolute path (or one
relative to `tests/`, where the script changes directory) for a local phar.
`RT_DB_HOST` points the scripts at a TCP server instead of the local socket,
`RT_MYSQL_BIN` selects a MySQL client binary other than `mysql`.

The `.deb` is built inside a Debian container:
`docker run --rm -v "$PWD":/src -w /src debian:12 bash -c 'apt-get update &&
apt-get install -y --no-install-recommends build-essential dpkg-dev debhelper &&
dpkg-buildpackage -us -uc -b'`.

CI (`.github/workflows/phpunit.yml`) runs PHPUnit on PHP 7.4, 8.1, 8.2 and 8.3,
the package build and a container job. Match it before claiming a change works.

## Layout

- `wwwroot/` — the application; `wwwroot/inc/` is the code, `document root` is
  `wwwroot` itself.
- `gateways/` — perl and shell scripts RackTables runs to reach devices.
- `plugins/` — optional plugins; the directory must exist.
- `scripts/` — CLI entry points: `syncdomain.php`, `cleanup_ldap_cache.php`,
  `reload_dictionary.php`, the sample inventory `init-sample-racks.sql`, the
  generated demo inventory `init-demo-data.sql` and its builder
  `make-demo-data.php`.
- `tests/` — PHPUnit suite and upgrade fixtures in `tests/data/` (database
  dumps, not demo data).
- `debian/`, `Dockerfile`, `docker/`, `docker-compose.yml`,
  `deploy/helm/racktables/` — packaging and deployment.

## Conventions

- Follow the surrounding code: tabs for indentation, a space before the
  parenthesis in declarations and calls (`commitAddPort ($object_id, ...)`),
  upstream naming.
- Comments explain why, never what the code already says; keep them to one or
  two lines.
- Tests live in `tests/*Test.php`, methods named `testSomething`, PHPUnit 9
  assertions and data providers, no tests for private behaviour.
- New features go with a test; the upgrade path goes with a batch in
  `wwwroot/inc/upgrade.php`.

## Worktrees

Every task that writes files runs in a linked worktree on a task branch from a
fresh `origin/master`, not in the primary checkout:

```bash
git fetch origin
git worktree add .worktrees/<name> -b <name> origin/master
```

The primary checkout stays on its branch and takes no tracked-file writes.
Local state that is not in git — `wwwroot/inc/secret.php`, `tests/phpunit-9.phar`,
the test database — exists only there, so a worktree needs its own database
before it can run the suite. Confirm `git rev-parse --show-toplevel` and the
branch before the first write. Never run `git push --all`: stale local
branches would reappear on the remote.

## Commits & PRs

- `master` is protected: changes land through a pull request only, never by a
  direct push. `upstream` is fetch-only.
- Commit, amend and push only when the request asks for it. Do not rewrite
  published history without an explicit request.
- Subject: imperative, English, at most 72 characters, no trailing period, no
  implementation detail. Body explains why the change is needed.
- No `Co-Authored-By` or generated-by footers. Pull request title equals the
  commit subject.
- GitHub operations use `gh`; write operations (PR, comment, merge, label)
  only when the task asks for them.

## Safety

- Never commit `wwwroot/inc/secret.php`, `wwwroot/inc/local.php`, `*.phar` or
  container build artifacts; the pre-commit hook installed by `make hooks`
  rejects them and checks the syntax of staged PHP files.
- Never touch `~/.my.cnf`; it belongs to the developer, not to the build.
- Keep the sample data and the upgrade fixtures apart: `tests/data/*.sql` are
  old schemas, `scripts/init-sample-racks.sql` is inventory for a demo.
