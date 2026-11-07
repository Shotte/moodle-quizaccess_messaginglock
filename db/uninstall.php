<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Uninstall steps for quizaccess_messaginglock.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Removes every lock and the lock role.
 *
 * @return bool
 */
function xmldb_quizaccess_messaginglock_uninstall() {
    global $DB;

    // First every lock assignment of this component, whatever role carries it - an admin may
    // have renamed the role, in which case a second role holds locks. Nobody may stay locked.
    role_unassign_all(['component' => \quizaccess_messaginglock\lock_manager::COMPONENT]);

    $shortname = \quizaccess_messaginglock\lock_manager::ROLE_SHORTNAME;
    if ($roleid = $DB->get_field('role', 'id', ['shortname' => $shortname])) {
        delete_role($roleid);
    }
    return true;
}
