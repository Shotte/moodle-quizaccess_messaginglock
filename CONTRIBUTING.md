# Contributing

Thank you for your interest in improving Quiz Messaging Lock.

## How changes are accepted

This GitHub repository is the public home of the plugin. Development and continuous integration
take place in the maintainers' private environment. Therefore:

1. Pull requests are reviewed on GitHub first.
2. If a change is accepted, a maintainer imports it into the private development environment and
   runs the full test suite there. Pull requests do not trigger any automated test runs on GitHub
   or on the maintainers' infrastructure.
3. The change is published to `main` on GitHub with the next release or update. The pull request
   is then closed with a reference to the published commit. Your authorship is kept in the commit
   (`Author` or `Co-authored-by`).

Please open an issue before you start work on a larger change, so that we can agree on the
approach first.

## Scope

The plugin deliberately has one quiz setting and one admin setting. New settings or features are
only added when there is a clear need that cannot be met otherwise. Bug fixes, compatibility fixes
for supported Moodle versions, test improvements and documentation fixes are always welcome.

The design rules are:

- no core changes and no changes to other plugins;
- enforcement only on the server, through `moodle/site:sendmessage`; hiding UI elements is never
  a security measure;
- fail-closed behaviour as described in [docs/architecture.md](docs/architecture.md);
- only documented Moodle APIs.

## Code requirements

- Follow the [Moodle coding style](https://moodledev.io/general/development/policies/codingstyle).
  Code, comments, commit messages and pull request texts are in English.
- Every PHP file has the standard Moodle GPL boilerplate, `@package quizaccess_messaginglock`,
  `@copyright` and `@license` tags.
- Add or update PHPUnit tests for every change in behaviour. Tests live in `tests/`.
- Only `lang/en` ships with the plugin. Keep the translations in `tests/fixtures/lang` consistent
  with it: every string key exists in every file (checked by `tests/language_test.php`).
- The code must work on all supported Moodle versions (see
  [docs/compatibility.md](docs/compatibility.md)) without version switches.

You can run the checks locally with
[moodle-plugin-ci](https://moodlehq.github.io/moodle-plugin-ci/), for example `phplint`,
`phpcs`, `phpdoc`, `validate` and `phpunit`.

## Translations

English (`lang/en`) is the source language and the only language shipped with the plugin.
Translations are contributed through [AMOS](https://lang.moodle.org/). The translations
prepared up to 1.0.1 (German, Spanish, French, Portuguese, Italian, Dutch) are kept in
`tests/fixtures/lang` for the AMOS submission.

## Commit messages

Use a short summary line in English (imperative mood, at most about 72 characters), followed by a
blank line and an explanation if needed. Reference the issue, for example `Fixes #12`.

## License

By contributing, you agree that your contribution is licensed under the GNU General Public License
version 3 or later, the license of this project.
