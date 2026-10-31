# Contributing

The rules for changing this repository live in [AGENTS.md](AGENTS.md). They
are written for coding agents but apply to human contributors the same way
and are deliberately kept in one place, so read that file first.

What it covers: the layout, the build and test commands, the code style, the
worktree routine, and the fact that `master` only accepts changes through a
pull request.

Quick facts before your first commit:

- This is a fork of [RackTables/racktables](https://github.com/RackTables/racktables)
  based on release 0.22.1; `master` carries the fork's work and `upstream` is
  fetch-only.
- CI must pass: PHPUnit on PHP 7.4, 8.1, 8.2 and 8.3, the Debian package build
  and the container job.
- Never commit `wwwroot/inc/secret.php`, `wwwroot/inc/local.php` or `*.phar`;
  `make hooks` installs the checks that catch this.
