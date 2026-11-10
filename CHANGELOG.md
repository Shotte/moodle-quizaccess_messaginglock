# Changelog

All notable changes to this plugin are documented in this file. Versions follow
[Semantic Versioning](https://semver.org/). The Moodle version number (`$plugin->version`) is
given in brackets.

## [1.0.2] - unreleased (2026111000)

### Changed

- Only English strings ship with the plugin, as required for the Moodle Marketplace.
  Translations are provided through the Moodle language packs (AMOS). The German, Spanish,
  French, Portuguese, Italian and Dutch strings of 1.0.1 are kept in the repository
  (`tests/fixtures/lang`) for the AMOS submission.
- Product name "Quiz Messaging Lock".
- No change to the lock logic, settings or database. See the upgrade notes in
  `docs/installation.md` if your site uses one of the removed languages.

## [1.0.1] - 2026-11-07 (2026110702)

### Added

- Spanish (`es`), French (`fr`), Portuguese (`pt`), Italian (`it`) and Dutch (`nl`) translations.
- English project documentation, contribution guidelines and security policy.

### Changed

- No change to the lock logic, settings or database. Upgrading from 1.0.0 changes no data.

## [1.0.0] - 2026-11-07 (2026110701)

First stable release.

### Features

- Quiz setting "Lock Moodle messaging during the exam" (Yes/No). New quizzes default to "Yes".
- Server-side lock of `moodle/site:sendmessage` from the start of an attempt until the exam time
  has ended for everyone who started, including time limit overrides, extensions and grace period.
- Scheduled task (every minute) that lifts expired locks and repairs the lock role if it was
  changed.
- Admin setting for the lock period of quizzes without a time limit (default 4 hours).
- The setting is kept on duplicate, backup and restore, course import and course copy. Locks are
  never copied.
- English and German language strings.
- Privacy API (`null_provider`): the plugin stores quiz settings only.

### Compatibility

- Moodle 5.0 to 5.3, PHP 8.3 and 8.4, PostgreSQL, MySQL and MariaDB.

### Upgrading

- Upgrading from the pre-release versions 0.1.0 to 0.3.0 changes no data and no database tables.
- Backups made with versions before 0.3.0 do not contain the setting. Quizzes restored from such
  backups have the lock disabled.
