# Configuration and use

## Quiz setting

In the quiz settings form, section *Extra restrictions on attempts*, teachers find:

> **Lock Moodle messaging during the exam** (Yes/No)

- New quizzes default to "Yes".
- Existing quizzes keep their stored value. Quizzes that existed before the plugin was installed
  have the setting "No".
- Quizzes created without the settings form (for example through web services) get "No".

While the setting is enabled, students see a short notice on the quiz page that they cannot
send Moodle messages from the start of their attempt.

## How long the lock lasts

- The lock starts for each student when they start their attempt.
- Exam time of an attempt: start of the attempt plus the effective time limit, including user
  and group overrides and the grace period.
- Everyone who started stays locked until the latest exam time of the quiz has ended. An early
  submission does not end the lock.
- After that, the scheduled task lifts all locks of the quiz within about one minute.
- If the teacher disables the setting or deletes the quiz, the locks are lifted immediately.
- Changes to the time limit, overrides or close date made during the exam are read live and take
  effect within about one minute.

## Admin setting: lock period without time limit

*Site administration > Plugins > Activity modules > Quiz > Messaging lock quiz access rule*

| Setting | Default | Meaning |
| --- | --- | --- |
| Lock period without time limit (`quizaccess_messaginglock/fallbackduration`) | 4 hours | Lock period of an attempt in a quiz without a time limit, counted from the start of the attempt. An earlier close date shortens it. While the attempt is still open, the lock remains until the close date. |

The messaging lock works best for quizzes with a time limit. See
[known limitations](known-limitations.md).

## What is locked

- Sending Moodle messages in all conversations, including group conversations, in the browser,
  in the Moodle app and through web services.
- Receiving messages remains possible.
- Other Moodle features are not changed.

## Scheduled task

*Release quiz messaging locks* (`\quizaccess_messaginglock\task\release_locks`) runs every
minute. Keep the default schedule. If a single quiz fails, its locks stay in place and are
retried in the next run; other quizzes are not affected.

## Languages

The plugin ships strings in English (`en`), German (`de`), Spanish (`es`), French (`fr`),
Portuguese (`pt`), Italian (`it`) and Dutch (`nl`). Moodle selects the language from the user's
and site's language settings. There is no plugin-specific language setting, and the stored quiz
settings do not depend on the language.

A translation is only shown if the corresponding Moodle language pack is installed on the site.
If a Moodle language pack contains a translation of this plugin (from
[AMOS](https://lang.moodle.org/)), Moodle uses it in preference to the strings shipped with the
plugin.

Known limitation: the name of the lock role is not translated by Moodle and is shown in the
site language that was active at installation.
