# Installation and upgrade

## Requirements

- Moodle 5.0, 5.1, 5.2 or 5.3
- PHP 8.3 or 8.4
- PostgreSQL, MySQL or MariaDB
- Moodle cron running at least once per minute. Locks are lifted by a scheduled task. Without
  cron, locked students stay locked.

## Before you install or upgrade

1. Choose a time when no quiz with the messaging lock is running. Existing locks are kept during
   an upgrade; the timing only avoids surprises.
2. Back up the database and the Moodle code directory.
3. Verify the checksum of the release ZIP against the `.sha256` file published with the release:
   - Linux/macOS: `sha256sum -c moodle-quizaccess_messaginglock-1.0.2.zip.sha256`
   - Windows PowerShell: `Get-FileHash moodle-quizaccess_messaginglock-1.0.2.zip -Algorithm SHA256`

## Install through the web interface

1. Go to *Site administration > Plugins > Install plugins*.
2. Upload `moodle-quizaccess_messaginglock-1.0.2.zip`. If Moodle asks for the plugin type,
   choose *Quiz access rule* (`quizaccess`).
3. Confirm the plugin check and run the database upgrade.
4. Check *Site administration > Plugins > Plugins overview*: `quizaccess_messaginglock` is
   listed with version `2026111000`.

On a fresh installation Moodle creates the table `quizaccess_messaginglock` and the role
"Quiz messaging lock". Do not assign or edit this role manually; the plugin manages it.

## Install manually

Use this if plugin installation through the web interface is disabled on your site.

1. Extract the ZIP into `mod/quiz/accessrule/` below your Moodle code directory, so that the
   plugin ends up in `mod/quiz/accessrule/messaginglock`. Since Moodle 5.1 the code directory is
   the `public/` folder of your Moodle installation.
2. Run `php admin/cli/upgrade.php` as the web server user, or open the notifications page as an
   administrator.

## Upgrade from an earlier version

Upgrading from 1.0.1, 1.0.0 or from the pre-releases 0.1.0, 0.2.0 or 0.3.0 uses the same steps
as above. The upgrade changes no data and no database tables. Quiz settings and running locks are
kept.

### Upgrade from 1.0.1

1.0.2 no longer ships the German, Spanish, French, Portuguese, Italian and Dutch strings; the
plugin logic is unchanged. After the upgrade, users of these languages see English until the
Moodle language pack contains a translation of this plugin. The lock role keeps the name it was
created with.

If you use one of these languages, either:

- stay on 1.0.1 until your language pack contains the translation (1.0.2 brings no functional
  change), or
- keep the 1.0.1 strings as a local language customisation: take
  `lang/<code>/quizaccess_messaginglock.php` from the 1.0.1 release ZIP and import it under
  *Site administration > Language > Language customisation > Import custom strings*, or with
  `php admin/tool/customlang/cli/import.php --lang=<code> --source=<file> --checkin`.
  Local customisations take precedence over language packs; remove them once the language pack
  contains the translation if you want to use it.

## Downgrade

Moodle does not support downgrading a plugin. To go back, restore the database and code backup
you made before the upgrade.

## Uninstall

Go to *Site administration > Plugins > Plugins overview* and uninstall *Messaging lock quiz
access rule*. The uninstaller removes all locks of the plugin first, then the role and the
plugin's table.
