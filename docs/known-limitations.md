# Known limitations

These limitations follow from how Moodle quizzes work. They are documented on purpose and are
not planned to be changed by additional settings.

## Late starters

A quiz is "active" as long as at least one attempt is still within its exam time. When no exam
time is open any more, all students of that quiz are unlocked.

A student who starts only after that opens a new lock period that applies to them alone.
Students who finished earlier are not locked again and can send messages to the late starter,
even before the late starter begins.

No plugin can close this gap without locking students on suspicion for an open-ended period.
Locking everyone until the quiz close date would be possible, but would lock students for days
with long quiz windows.

Recommendation: run make-up exams as a separate quiz, or let all students start at the same time.

## Quizzes without a time limit

Without a time limit Moodle has no end time for an attempt. The plugin then uses:

- lock period = start of the attempt + admin setting *Lock period without time limit*
  (default 4 hours);
- an earlier quiz close date shortens this period;
- an attempt that is still open keeps the lock until the close date, if one is set.

The lock is therefore always finite. The setting is recommended for quizzes with a time limit.

## Long windows with continuously overlapping starts

If new attempts keep starting before the previous ones end, the lock lasts until the last
attempt ends. This follows directly from the rule that everyone stays locked while anyone is
still writing.

## Scope of the lock

- Only sending Moodle messages is locked. Receiving remains possible.
- Other communication channels (forum posts, chat or other activities, external services) are
  not covered.
- Site administrators cannot be locked. Moodle grants administrators every capability.

## Timing

- Locks are lifted by a scheduled task. With cron running every minute, a lock ends about one
  minute after the exam time. If cron does not run, locks are not lifted.

## Backup and restore

- Backups made with versions before 0.3.0 do not contain the setting. Quizzes restored from such
  backups have the lock disabled. Check the setting after restoring old backups.
- Restoring a backup with user data while the original exam is still running: Moodle keeps the
  start time of restored attempts. If a student opens such an attempt in the restored quiz before
  the original exam time has ended, they are locked until that time.

## Language

- The name of the lock role is not translated by Moodle. It is stored in the site language that
  was active when the plugin was installed.
