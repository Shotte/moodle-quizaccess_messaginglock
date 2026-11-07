# Compatibility

| Item | Value |
| --- | --- |
| Plugin version | 1.0.1 (`2026110702`), stable |
| `$plugin->requires` | `2025041400` (Moodle 5.0.0) |
| `$plugin->supported` | `[500, 503]` (Moodle 5.0 to 5.3) |
| Languages | English, German, Spanish, French, Portuguese, Italian, Dutch |

One code base serves all supported Moodle versions. There are no version switches in the code.
Since Moodle 5.1 the Moodle code lives in the `public/` directory; the plugin derives all paths
from `$CFG->dirroot`.

## Tested combinations

Release 1.0.1 adds language files and documentation only; the plugin logic is unchanged from 1.0.0.
It was checked with the full static checks and PHPUnit on Moodle 5.1 / PHP 8.4 / PostgreSQL and
with a fresh installation from the release ZIP.

Release 1.0.0 was tested on the tagged release commit with PHPUnit on every combination below.
Every cell passed.

| Moodle | PHP | PostgreSQL 17 | MySQL 8.4 | MariaDB 11.4 |
| --- | --- | --- | --- | --- |
| 5.0 | 8.3 | passed | – | – |
| 5.0 | 8.4 | passed | passed | – |
| 5.1 | 8.3 | – | passed | – |
| 5.1 | 8.4 | passed | – | – |
| 5.2 | 8.3 | passed | – | – |
| 5.2 | 8.4 | passed | passed | passed |
| 5.3 | 8.3 | passed | – | – |
| 5.3 | 8.4 | passed | passed | passed |

In addition, for the release commit:

- Static checks on Moodle 5.1 / PHP 8.4 / PostgreSQL: PHP lint, Moodle plugin validation, Moodle
  code style (phpcs), PHPDoc.
- Behat acceptance tests (BrowserKit) on Moodle 5.1.
- Fresh installation from the release ZIP and comparison of the installed schema with
  `db/install.xml`.

A dash means the combination was not part of the test matrix. It is expected to work, but it is
not proven by a test run.

## Release testing

From 1.0.1 on, a release is published only after the maintainers' internal CI has passed, on the
same commit:

- the package checks: version and maturity, metadata, licence headers, ZIP structure, fresh
  installation from the release ZIP, schema;
- an upgrade and compatibility matrix of 36 combinations: Moodle 5.0-5.3, PHP 8.3 and 8.4, and per
  database the minimum version required by that Moodle branch and the current reference version
  (PostgreSQL 14/15/16/17, MySQL 8.4, MariaDB 10.11/11.4). Each combination installs the previous
  release, upgrades it, checks that existing settings and locks keep working and runs the full
  PHPUnit suite.

The release notes of each version list the result.

## Not supported

- Moodle 4.5 and older.
- PHP 8.2, PHP 8.5, Microsoft SQL Server and Amazon Aurora are not supported.
