# How it works

This page explains the design for administrators, reviewers and contributors.

## Components

| File | Purpose |
| --- | --- |
| `rule.php` | Quiz access rule: setting in the quiz form, saving and deleting the setting, notice for students, fail-closed check in `prevent_access()` |
| `classes/lock_manager.php` | All lock logic: ensure the role, lock, compute lock periods, release |
| `classes/hook_callbacks.php`, `db/hooks.php` | Locks on attempt start through the hook `mod_quiz\hook\attempt_state_changed` |
| `classes/task/release_locks.php`, `db/tasks.php` | Scheduled task (every minute) that lifts locks of quizzes that are no longer active |
| `db/install.xml` | Table `quizaccess_messaginglock` (`quizid`): a row means the lock is enabled for that quiz |
| `db/install.php`, `db/uninstall.php` | Create the role; remove all locks and the role |
| `backup/moodle2/` | Backup and restore of the setting |
| `classes/privacy/provider.php` | Privacy API |
| `settings.php` | Admin setting: lock period for quizzes without a time limit |

There are no core changes and no changes to other plugins.

## Enforcement

- The plugin owns exactly one role, `quizaccess_messaginglock`. It cannot be assigned manually
  (no context levels) and has exactly one capability: `moodle/site:sendmessage` = *Prohibit*.
- A lock is a role assignment of this role in the system context with
  `component = quizaccess_messaginglock` and `itemid = quiz id`. A user is locked while at least
  one such assignment exists.
- Because a prohibit cannot be overridden, Moodle's own permission checks block sending in every
  channel: web, Moodle app and web services.
- The role definition is checked before every lock and on every task run. If it was changed, it
  is restored. No other role or capability is ever modified.

## Time rule

Every attempt (no previews) has a lock end that does not depend on its state. Submitting,
finishing or abandoning does not shorten it.

```text
end(attempt) = base + grace

base  = timestart + effective time limit         if a time limit is set
      = effective close date                     if no time limit and close date < timestart + fallback
      = timestart + fallback (default 4 h)       otherwise

grace = quiz grace period (if overdue handling is "grace period") + quiz/graceperiodmin
```

In addition, an open attempt without a time limit keeps the quiz active until its effective
close date.

Effective values come from the core function `quiz_get_attempt_usertime_sql()`, which applies
user and group overrides. They are read live on every check.

A quiz is **active** while the lock is enabled and at least one attempt has `end > now`. All
locked users of the quiz stay locked while it is active. When it is no longer active, the
scheduled task removes all locks of that quiz.

## Fail-closed behaviour

| Failure | Behaviour |
| --- | --- |
| Lock cannot be set when an attempt starts | The start is rolled back; the student cannot begin |
| Lock missing during the exam time | `prevent_access()` sets it again, otherwise access to the quiz is refused |
| Prohibit removed from the role | Restored before the next lock |
| Release fails for one quiz | Its locks stay; other quizzes are released; retried every minute |
| Cron does not run | No timed release; only already locked users are affected |

The code only locks explicit user IDs and never changes other roles or capabilities, so an error
can never lock all users.

## Backup and restore

The plugin implements the core extension points `backup_mod_quiz_access_subplugin` and
`restore_mod_quiz_access_subplugin` in `backup/moodle2/`. Duplicate, activity and course backup,
import and course copy all use them.

- Only the setting is written: `<enabled>1</enabled>`, and only if the lock is enabled.
- Locks are never backed up. They are role assignments in the system context, which Moodle never
  includes in course or activity backups.
- On restore, the setting is created for the new quiz only. Existing quizzes are never changed.
  Without the element the lock stays disabled.

## Privacy

The plugin table stores quiz settings only. Locks are role assignments, which are exported and
deleted by the privacy provider of the role subsystem. The plugin therefore implements
`null_provider`.
