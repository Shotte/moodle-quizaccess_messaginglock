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
 * French strings for quizaccess_messaginglock.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['fallbackduration'] = 'Durée du blocage sans limite de temps';
$string['fallbackduration_desc'] = 'Durée du blocage d\'une tentative dans un test sans limite de temps, comptée à partir du début de la tentative. Une date de fermeture antérieure la raccourcit. Tant que la tentative est encore ouverte, le blocage reste actif jusqu\'à la date de fermeture.';
$string['lockfailed'] = 'La messagerie Moodle n\'a pas pu être bloquée pour votre tentative ; vous ne pouvez donc pas travailler sur le test pour le moment. Veuillez contacter votre enseignant.';
$string['messaginglockenabled'] = 'Bloquer la messagerie Moodle pendant l\'examen';
$string['messaginglockenabled_help'] = 'Si ce réglage est activé, les étudiants ne peuvent pas envoyer de messages Moodle pendant la durée de l\'examen. Le blocage est maintenu après une remise anticipée et levé automatiquement lorsque la durée de l\'examen est terminée pour tous. Les autres fonctionnalités de Moodle ne sont pas concernées.

En détail :

* Le blocage commence pour chaque personne au début de sa tentative. L\'envoi est bloqué dans toutes les conversations Moodle, y compris les conversations de groupe. La réception reste possible.
* Durée d\'examen d\'une tentative : début de la tentative plus la limite de temps (prolongations et période de grâce comprises). Tous les étudiants qui ont commencé restent bloqués jusqu\'à la fin de la dernière durée d\'examen. Le blocage est ensuite levé en une minute environ.
* Une personne qui ne commence qu\'après la fin de toutes les durées d\'examen est bloquée seule. Les personnes ayant terminé plus tôt ne sont pas bloquées à nouveau.
* Sans limite de temps, une durée de blocage fixée par l\'administration s\'applique à partir du début de la tentative (4 heures par défaut). Une date de fermeture antérieure la raccourcit ; une tentative encore ouverte la prolonge jusqu\'à la date de fermeture. Ce réglage est donc recommandé pour les tests avec limite de temps.
* Le réglage est conservé lorsque le test est dupliqué, sauvegardé et restauré, ou importé. Les blocages existants ne sont jamais copiés.';
$string['pluginname'] = 'Règle d\'accès Blocage de la messagerie';
$string['privacy:metadata'] = 'Le plugin enregistre uniquement des réglages de test. Les blocages de messagerie sont des attributions de rôles, gérées par le sous-système des rôles.';
$string['releasefailed'] = 'Échec de la levée des blocages de messagerie : {$a}';
$string['roledescription'] = 'Attribué automatiquement par le blocage de messagerie des tests pendant un examen. Ne pas attribuer ni modifier manuellement.';
$string['rolename'] = 'Blocage de messagerie des tests';
$string['studentinfo'] = 'Dès le début de votre tentative, vous ne pouvez plus envoyer de messages Moodle. Cela continue après votre remise, jusqu\'à ce que l\'examen soit terminé pour tous.';
$string['taskreleaselocks'] = 'Lever les blocages de messagerie des tests';
$string['taskreleaselocksdone'] = 'Blocages de messagerie levés pour {$a} test(s).';
$string['taskreleaselocksfailed'] = 'Échec de la levée des blocages de messagerie pour certains tests, nouvelle tentative lors de la prochaine exécution : {$a}';
