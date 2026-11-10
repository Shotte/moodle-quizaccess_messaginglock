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
 * Portuguese strings for quizaccess_messaginglock.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['fallbackduration'] = 'Período de bloqueio sem tempo limite';
$string['fallbackduration_desc'] = 'Período de bloqueio de uma tentativa num teste sem tempo limite, contado a partir do início da tentativa. Uma data de fecho anterior encurta-o. Enquanto a tentativa estiver aberta, o bloqueio mantém-se até à data de fecho.';
$string['lockfailed'] = 'Não foi possível bloquear as mensagens do Moodle para a sua tentativa, pelo que de momento não pode responder ao teste. Contacte o seu professor.';
$string['messaginglockenabled'] = 'Bloquear as mensagens do Moodle durante o exame';
$string['messaginglockenabled_help'] = 'Se estiver ativado, os alunos não podem enviar mensagens do Moodle durante o tempo de exame. O bloqueio mantém-se após uma submissão antecipada e é levantado automaticamente quando o tempo de exame tiver terminado para todos. As restantes funcionalidades do Moodle não são afetadas.

Em detalhe:

* O bloqueio começa para cada pessoa quando inicia a sua tentativa. O envio é bloqueado em todas as conversas do Moodle, incluindo as de grupo. A receção continua a ser possível.
* Tempo de exame de uma tentativa: início da tentativa mais o tempo limite (incluindo prolongamentos e período de tolerância). Todos os alunos que começaram ficam bloqueados até terminar o último tempo de exame. O bloqueio é depois levantado em cerca de um minuto.
* Quem só começar depois de terminados todos os tempos de exame fica bloqueado sozinho. Quem terminou mais cedo não volta a ser bloqueado.
* Sem tempo limite, aplica-se desde o início da tentativa um período de bloqueio definido pela administração (4 horas por omissão). Uma data de fecho anterior encurta-o; uma tentativa ainda aberta prolonga-o até à data de fecho. Por isso, esta opção é recomendada para testes com tempo limite.
* A opção é mantida ao duplicar o teste, ao fazer cópia de segurança e restauro e ao importar. Os bloqueios existentes nunca são copiados.';
$string['pluginname'] = 'Regra de acesso: bloqueio de mensagens';
$string['privacy:metadata'] = 'O plugin guarda apenas configurações de testes. Os bloqueios de mensagens são atribuições de papéis, abrangidas pelo subsistema de papéis.';
$string['releasefailed'] = 'Falha ao levantar os bloqueios de mensagens: {$a}';
$string['roledescription'] = 'Atribuído automaticamente pelo bloqueio de mensagens dos testes enquanto decorre um exame. Não atribuir nem editar manualmente.';
$string['rolename'] = 'Bloqueio de mensagens dos testes';
$string['studentinfo'] = 'A partir do início da sua tentativa não pode enviar mensagens do Moodle. Isto continua após a submissão, até o exame ter terminado para todos.';
$string['taskreleaselocks'] = 'Levantar os bloqueios de mensagens dos testes';
$string['taskreleaselocksdone'] = 'Bloqueios de mensagens levantados em {$a} teste(s).';
$string['taskreleaselocksfailed'] = 'Falha ao levantar os bloqueios de mensagens em alguns testes; nova tentativa na próxima execução: {$a}';
