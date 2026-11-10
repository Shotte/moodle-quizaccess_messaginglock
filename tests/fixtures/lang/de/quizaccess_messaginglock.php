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
 * German strings for quizaccess_messaginglock.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['fallbackduration'] = 'Sperrfrist ohne Zeitbegrenzung';
$string['fallbackduration_desc'] = 'Sperrfrist eines Versuchs in einem Test ohne Zeitbegrenzung, gerechnet ab Versuchsbeginn. Eine frühere Schließzeit verkürzt sie. Solange der Versuch noch offen ist, bleibt die Sperre bis zur Schließzeit bestehen.';
$string['lockfailed'] = 'Die Moodle-Nachrichten konnten für Ihren Versuch nicht gesperrt werden. Deshalb können Sie den Test im Moment nicht bearbeiten. Bitte wenden Sie sich an Ihre Lehrkraft.';
$string['messaginglockenabled'] = 'Moodle-Nachrichten während der Prüfung sperren';
$string['messaginglockenabled_help'] = 'Wenn diese Einstellung aktiviert ist, können Prüflinge während der Prüfungszeit keine Moodle-Nachrichten senden. Die Sperre bleibt auch nach einer vorzeitigen Testabgabe bestehen und wird automatisch aufgehoben, wenn die Prüfungszeit für alle abgelaufen ist. Andere Moodle-Funktionen bleiben unverändert.

Genauer:

* Die Sperre beginnt für jede Person mit dem Start ihres Versuchs. Gesperrt ist das Senden in allen Moodle-Unterhaltungen, auch in Gruppen. Empfangen bleibt möglich.
* Prüfungszeit eines Versuchs: Versuchsbeginn plus Zeitbegrenzung (einschließlich Verlängerungen und Nachfrist). Alle Prüflinge, die begonnen haben, bleiben gesperrt, bis die späteste Prüfungszeit abgelaufen ist. Die Aufhebung erfolgt danach innerhalb von etwa einer Minute.
* Wer erst beginnt, nachdem alle Prüfungszeiten abgelaufen sind, wird allein gesperrt. Bereits Fertige werden nicht erneut gesperrt.
* Ohne Zeitbegrenzung gilt ab Versuchsbeginn eine von der Administration festgelegte Sperrfrist (standardmäßig 4 Stunden). Eine frühere Schließzeit verkürzt sie, ein noch offener Versuch verlängert sie bis zur Schließzeit. Empfohlen wird die Einstellung daher für Tests mit Zeitbegrenzung.
* Beim Duplizieren, Sichern und Wiederherstellen sowie beim Kursimport wird die Einstellung übernommen. Bestehende Sperren werden dabei nie mitkopiert.';
$string['pluginname'] = 'Zugriffsregel Nachrichtensperre';
$string['privacy:metadata'] = 'Das Plugin speichert nur Testeinstellungen. Nachrichtensperren sind Rollenzuweisungen und werden vom Rollensystem erfasst.';
$string['releasefailed'] = 'Aufheben der Nachrichtensperren fehlgeschlagen: {$a}';
$string['roledescription'] = 'Wird von der Nachrichtensperre für Tests automatisch zugewiesen, solange eine Prüfung läuft. Nicht manuell zuweisen oder bearbeiten.';
$string['rolename'] = 'Nachrichtensperre für Tests';
$string['studentinfo'] = 'Ab dem Start Ihres Versuchs können Sie keine Moodle-Nachrichten senden. Die Sperre bleibt nach Ihrer Abgabe bestehen, bis die Prüfung für alle beendet ist.';
$string['taskreleaselocks'] = 'Nachrichtensperren für Tests aufheben';
$string['taskreleaselocksdone'] = 'Nachrichtensperren von {$a} Test(s) aufgehoben.';
$string['taskreleaselocksfailed'] = 'Aufheben der Nachrichtensperren für einzelne Tests fehlgeschlagen, neuer Versuch beim nächsten Lauf: {$a}';
