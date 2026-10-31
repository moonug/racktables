# Security policy

## Reporting a vulnerability

Do not open a public issue for a security problem. Use GitHub's private
reporting instead: the repository's **Security** tab, **Report a
vulnerability**. That opens a draft advisory visible only to the maintainer.

Please include the affected version (tag or commit), the configuration needed
to reproduce it, and the impact you expect.

## Scope

This repository is a fork of [RackTables/racktables](https://github.com/RackTables/racktables)
based on release 0.22.1. A problem in code inherited from the original project
belongs upstream as well; report it here if the fork is affected, and the fix
will be carried over.

The deployment artifacts in this repository (container image, compose file,
Helm chart) are in scope too: the image is expected to run without bundled
credentials or configuration files.
