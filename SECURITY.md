# Security policy

## Supported versions

| Version | Supported |
| --- | --- |
| 1.0.x | yes |
| 0.x (pre-releases) | no, please upgrade to 1.0.x |

## Reporting a vulnerability

Please **do not** report security issues in public GitHub issues, pull requests or discussions.

Report them privately through GitHub: open the **Security** tab of this repository and choose
**Report a vulnerability**. Please include:

- the plugin version and the Moodle, PHP and database versions;
- the steps to reproduce the issue;
- the impact you observed or expect, for example a student who can still send messages during
  an exam, or a user who is locked who should not be.

We will acknowledge your report, investigate it and keep you informed about the fix. Please give
us reasonable time to release a fix before you disclose the issue publicly. We are happy to credit
you in the release notes unless you prefer to stay anonymous.

## Scope

In scope are weaknesses in this plugin, for example:

- ways for a student to send Moodle messages while the lock should be active;
- locks that affect users or periods they should not affect;
- privilege escalation or data exposure caused by the plugin.

Out of scope are issues in Moodle core or other plugins. Please report those to the Moodle
security team as described at
[moodle.org/security](https://moodle.org/security/).

Known, documented limitations (see [docs/known-limitations.md](docs/known-limitations.md)) are not
vulnerabilities.
