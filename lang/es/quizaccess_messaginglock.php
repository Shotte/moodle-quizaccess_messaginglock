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
 * Spanish strings for quizaccess_messaginglock.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['fallbackduration'] = 'Periodo de bloqueo sin límite de tiempo';
$string['fallbackduration_desc'] = 'Periodo de bloqueo de un intento en un cuestionario sin límite de tiempo, contado desde el inicio del intento. Una fecha de cierre anterior lo acorta. Mientras el intento siga abierto, el bloqueo se mantiene hasta la fecha de cierre.';
$string['lockfailed'] = 'No se ha podido bloquear la mensajería de Moodle para su intento, por lo que en este momento no puede realizar el cuestionario. Póngase en contacto con su profesor.';
$string['messaginglockenabled'] = 'Bloquear la mensajería de Moodle durante el examen';
$string['messaginglockenabled_help'] = 'Si se activa, los estudiantes no pueden enviar mensajes de Moodle durante el tiempo de examen. El bloqueo se mantiene tras un envío anticipado y se levanta automáticamente cuando el tiempo de examen ha terminado para todos. Las demás funciones de Moodle no se ven afectadas.

En detalle:

* El bloqueo comienza para cada persona cuando inicia su intento. Se bloquea el envío en todas las conversaciones de Moodle, incluidas las de grupo. La recepción sigue siendo posible.
* Tiempo de examen de un intento: inicio del intento más el límite de tiempo (incluidas las prórrogas y el periodo de gracia). Todos los estudiantes que han comenzado permanecen bloqueados hasta que termina el último tiempo de examen. Después, el bloqueo se levanta en aproximadamente un minuto.
* Quien empiece cuando ya hayan terminado todos los tiempos de examen se bloquea de forma individual. Quienes terminaron antes no vuelven a ser bloqueados.
* Sin límite de tiempo, se aplica desde el inicio del intento un periodo de bloqueo fijado por la administración (4 horas por defecto). Una fecha de cierre anterior lo acorta; un intento que sigue abierto lo prolonga hasta la fecha de cierre. Por ello se recomienda esta opción para cuestionarios con límite de tiempo.
* La opción se conserva al duplicar, hacer copia de seguridad y restaurar o importar el cuestionario. Los bloqueos existentes nunca se copian.';
$string['pluginname'] = 'Regla de acceso: bloqueo de mensajería';
$string['privacy:metadata'] = 'El plugin solo almacena configuraciones de cuestionarios. Los bloqueos de mensajería son asignaciones de roles, que gestiona el subsistema de roles.';
$string['releasefailed'] = 'Ha fallado la liberación de los bloqueos de mensajería: {$a}';
$string['roledescription'] = 'Asignado automáticamente por el bloqueo de mensajería de cuestionarios mientras hay un examen en curso. No asignar ni editar manualmente.';
$string['rolename'] = 'Bloqueo de mensajería de cuestionarios';
$string['studentinfo'] = 'Desde el inicio de su intento no puede enviar mensajes de Moodle. Esto continúa después de enviar el cuestionario, hasta que el examen haya terminado para todos.';
$string['taskreleaselocks'] = 'Liberar los bloqueos de mensajería de cuestionarios';
$string['taskreleaselocksdone'] = 'Se han liberado los bloqueos de mensajería de {$a} cuestionario(s).';
$string['taskreleaselocksfailed'] = 'Ha fallado la liberación de los bloqueos de mensajería en algunos cuestionarios; se reintentará en la próxima ejecución: {$a}';
