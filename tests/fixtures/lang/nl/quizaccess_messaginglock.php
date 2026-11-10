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
 * Dutch strings for quizaccess_messaginglock.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['fallbackduration'] = 'Blokkeerperiode zonder tijdslimiet';
$string['fallbackduration_desc'] = 'Blokkeerperiode van een poging in een test zonder tijdslimiet, gerekend vanaf het begin van de poging. Een eerdere sluitingsdatum verkort deze. Zolang de poging nog open is, blijft de blokkering tot de sluitingsdatum bestaan.';
$string['lockfailed'] = 'Moodle-berichten konden voor je poging niet worden geblokkeerd. Je kunt de test daarom op dit moment niet maken. Neem contact op met je leraar.';
$string['messaginglockenabled'] = 'Moodle-berichten blokkeren tijdens het examen';
$string['messaginglockenabled_help'] = 'Als deze instelling is ingeschakeld, kunnen studenten tijdens de examentijd geen Moodle-berichten versturen. De blokkering blijft ook na vroegtijdig indienen bestaan en wordt automatisch opgeheven zodra de examentijd voor iedereen is verstreken. Andere Moodle-functies worden niet beïnvloed.

In detail:

* De blokkering begint voor iedereen op het moment dat diegene de eigen poging start. Versturen is geblokkeerd in alle Moodle-gesprekken, ook in groepen. Ontvangen blijft mogelijk.
* Examentijd van een poging: begin van de poging plus de tijdslimiet (inclusief verlengingen en respijtperiode). Alle studenten die zijn begonnen, blijven geblokkeerd tot de laatste examentijd is verstreken. Daarna wordt de blokkering binnen ongeveer een minuut opgeheven.
* Wie pas begint nadat alle examentijden zijn verstreken, wordt als enige geblokkeerd. Wie eerder klaar was, wordt niet opnieuw geblokkeerd.
* Zonder tijdslimiet geldt vanaf het begin van de poging een door de beheerder ingestelde blokkeerperiode (standaard 4 uur). Een eerdere sluitingsdatum verkort deze; een poging die nog open is, verlengt deze tot de sluitingsdatum. De instelling wordt daarom aanbevolen voor tests met een tijdslimiet.
* De instelling blijft behouden wanneer de test wordt gedupliceerd, geback-upt en teruggezet of geïmporteerd. Bestaande blokkeringen worden nooit gekopieerd.';
$string['pluginname'] = 'Toegangsregel Berichtenblokkering';
$string['privacy:metadata'] = 'De plugin slaat alleen testinstellingen op. Berichtenblokkeringen zijn roltoewijzingen, die door het rollensubsysteem worden afgehandeld.';
$string['releasefailed'] = 'Opheffen van de berichtenblokkeringen mislukt: {$a}';
$string['roledescription'] = 'Wordt automatisch toegewezen door de berichtenblokkering voor tests zolang een examen loopt. Niet handmatig toewijzen of bewerken.';
$string['rolename'] = 'Berichtenblokkering voor tests';
$string['studentinfo'] = 'Vanaf het begin van je poging kun je geen Moodle-berichten versturen. Dit blijft zo na het indienen, totdat het examen voor iedereen is afgelopen.';
$string['taskreleaselocks'] = 'Berichtenblokkeringen voor tests opheffen';
$string['taskreleaselocksdone'] = 'Berichtenblokkeringen van {$a} test(s) opgeheven.';
$string['taskreleaselocksfailed'] = 'Opheffen van de berichtenblokkeringen mislukt voor sommige tests, nieuwe poging bij de volgende uitvoering: {$a}';
