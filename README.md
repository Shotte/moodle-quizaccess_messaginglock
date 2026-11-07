# Moodle Quiz Messaging Lock

`quizaccess_messaginglock` is a quiz access rule for Moodle 5.0 to 5.3. It adds one setting to
the quiz settings form:

> **Lock Moodle messaging during the exam**

When the setting is enabled, students cannot send Moodle messages from the moment they start
their quiz attempt. The lock is enforced on the server, so it also applies in a second browser
tab, in the Moodle app and through web services. It stays in place after an early submission
and is lifted automatically once the exam time has ended for everyone who started.

Current release: **1.0.0 (stable)**.

## Features

- One Yes/No setting per quiz. New quizzes default to "Yes"; existing quizzes keep their value.
- Server-side enforcement through Moodle's own capability system
  (`moodle/site:sendmessage`). Hiding the messaging UI is not used as a security measure.
- The lock period follows the quiz timing, including user and group overrides, extensions and
  the grace period. Changes made during the exam take effect within about one minute.
- Locks are lifted automatically by a scheduled task that runs every minute.
- Fail-closed: if a lock cannot be set, the student cannot start the attempt.
- The setting is kept when a quiz is duplicated, backed up and restored, or imported.
  Locks themselves are never copied.
- English and German user interface. The language follows the Moodle language settings.
- Privacy API implemented. The plugin stores quiz settings only.

## Requirements

| Component | Supported versions |
| --- | --- |
| Moodle | 5.0, 5.1, 5.2, 5.3 |
| PHP | 8.3, 8.4 |
| Database | PostgreSQL, MySQL, MariaDB |
| Cron | must run at least once per minute |

See [docs/compatibility.md](docs/compatibility.md) for the tested combinations.

## Installation

Install the release ZIP through *Site administration > Plugins > Install plugins*. The plugin
is installed into `mod/quiz/accessrule/messaginglock`.

See [docs/installation.md](docs/installation.md) for details, manual installation and upgrades.

## Documentation

- [Installation and upgrade](docs/installation.md)
- [Configuration and use](docs/configuration.md)
- [Compatibility](docs/compatibility.md)
- [Known limitations](docs/known-limitations.md)
- [How it works](docs/architecture.md)
- [Changelog](CHANGELOG.md)

## Support and contributing

- Bug reports and feature requests: GitHub issues.
- Security issues: please do not open a public issue. See [SECURITY.md](SECURITY.md).
- Contributions: see [CONTRIBUTING.md](CONTRIBUTING.md).

## License

Copyright 2026 Shotte.

This program is free software: you can redistribute it and/or modify it under the terms of the
GNU General Public License as published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version. See [LICENSE](LICENSE).
