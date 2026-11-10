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
 * Italian strings for quizaccess_messaginglock.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['fallbackduration'] = 'Durata del blocco senza tempo massimo';
$string['fallbackduration_desc'] = 'Durata del blocco di un tentativo in un quiz senza tempo massimo, calcolata dall\'inizio del tentativo. Una data di chiusura anteriore la riduce. Finché il tentativo è ancora aperto, il blocco resta attivo fino alla data di chiusura.';
$string['lockfailed'] = 'Non è stato possibile bloccare la messaggistica di Moodle per il tuo tentativo, quindi al momento non puoi svolgere il quiz. Contatta il tuo docente.';
$string['messaginglockenabled'] = 'Blocca la messaggistica di Moodle durante l\'esame';
$string['messaginglockenabled_help'] = 'Se attivata, gli studenti non possono inviare messaggi Moodle durante il tempo d\'esame. Il blocco resta attivo anche dopo una consegna anticipata e viene rimosso automaticamente quando il tempo d\'esame è terminato per tutti. Le altre funzioni di Moodle non sono interessate.

In dettaglio:

* Il blocco inizia per ciascuno nel momento in cui avvia il proprio tentativo. L\'invio è bloccato in tutte le conversazioni di Moodle, comprese quelle di gruppo. La ricezione resta possibile.
* Tempo d\'esame di un tentativo: inizio del tentativo più il tempo massimo (incluse proroghe e periodo di tolleranza). Tutti gli studenti che hanno iniziato restano bloccati finché non è terminato l\'ultimo tempo d\'esame. Il blocco viene poi rimosso entro circa un minuto.
* Chi inizia solo dopo che tutti i tempi d\'esame sono terminati viene bloccato da solo. Chi ha terminato prima non viene bloccato di nuovo.
* Senza tempo massimo si applica, dall\'inizio del tentativo, una durata del blocco stabilita dall\'amministrazione (4 ore per impostazione predefinita). Una data di chiusura anteriore la riduce; un tentativo ancora aperto la prolunga fino alla data di chiusura. L\'impostazione è quindi consigliata per quiz con tempo massimo.
* L\'impostazione viene mantenuta quando il quiz viene duplicato, salvato con backup e ripristinato, oppure importato. I blocchi esistenti non vengono mai copiati.';
$string['pluginname'] = 'Regola di accesso: blocco della messaggistica';
$string['privacy:metadata'] = 'Il plugin memorizza solo impostazioni dei quiz. I blocchi della messaggistica sono assegnazioni di ruolo, gestite dal sottosistema dei ruoli.';
$string['releasefailed'] = 'Rimozione dei blocchi della messaggistica non riuscita: {$a}';
$string['roledescription'] = 'Assegnato automaticamente dal blocco della messaggistica dei quiz durante un esame in corso. Non assegnare né modificare manualmente.';
$string['rolename'] = 'Blocco della messaggistica dei quiz';
$string['studentinfo'] = 'Dall\'inizio del tuo tentativo non puoi inviare messaggi Moodle. Il blocco continua dopo la consegna, finché l\'esame non è terminato per tutti.';
$string['taskreleaselocks'] = 'Rimuovi i blocchi della messaggistica dei quiz';
$string['taskreleaselocksdone'] = 'Blocchi della messaggistica rimossi per {$a} quiz.';
$string['taskreleaselocksfailed'] = 'Rimozione dei blocchi della messaggistica non riuscita per alcuni quiz, nuovo tentativo alla prossima esecuzione: {$a}';
