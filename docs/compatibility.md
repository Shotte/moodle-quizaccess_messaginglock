# Compatibility

| Item | Value |
| --- | --- |
| Plugin version | 1.0.0 (`2026110701`), stable |
| `$plugin->requires` | `2025041400` (Moodle 5.0.0) |
| `$plugin->supported` | `[500, 503]` (Moodle 5.0 to 5.3) |
| Languages | English, German |

One code base serves all supported Moodle versions. There are no version switches in the code.
Since Moodle 5.1 the Moodle code lives in the `public/` directory; the plugin derives all paths
from `$CFG->dirroot`.

## Tested combinations for 1.0.0

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

## Not supported

- Moodle 4.5 and older.
- PHP 8.5 has not been tested.
