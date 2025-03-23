-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `quapp`
--

--
-- Tabellenstruktur für Tabelle `days`
--

CREATE TABLE `days` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Daten für Tabelle `days`
--

INSERT INTO `days` (`id`, `name`) VALUES
(1, 'Samstag'),
(2, 'Sonntag');

--
-- Tabellenstruktur für Tabelle `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `year_id` int(11) NOT NULL,
  `day_id` int(11) NOT NULL,
  `name` varchar(16) NOT NULL,
  `teamsCount` int(11) NOT NULL DEFAULT 16
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


--
-- Tabellenstruktur für Tabelle `group_teams`
--

CREATE TABLE `group_teams` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `placeNumber` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `calcRanking` int(11) DEFAULT NULL,
  `calcCountMatches` int(11) DEFAULT NULL,
  `calcGoalsScored` int(11) DEFAULT NULL,
  `calcGoalsReceived` int(11) DEFAULT NULL,
  `calcGoalsDiff` int(11) DEFAULT NULL,
  `calcPointsPlus` int(11) DEFAULT NULL,
  `calcPointsMinus` int(11) DEFAULT NULL,
  `canceled` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Tabellenstruktur für Tabelle `logins`
--

CREATE TABLE `logins` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `password` varchar(32) NOT NULL,
  `failedlogincount` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


--
-- Tabellenstruktur für Tabelle `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `round_id` int(11) NOT NULL,
  `sport_id` int(11) NOT NULL,
  `team1_id` int(11) DEFAULT NULL,
  `team2_id` int(11) DEFAULT NULL,
  `refereeTeam_id` int(11) DEFAULT NULL,
  `refereeTeamSubst_id` int(11) DEFAULT NULL,
  `refereeName` VARCHAR(32) DEFAULT NULL,
  `refereePIN` varchar(5) DEFAULT NULL,
  `resultTrend` int(11) DEFAULT NULL,
  `resultGoals1` int(11) DEFAULT NULL,
  `resultGoals2` int(11) DEFAULT NULL,
  `resultAdmin` int(11) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `isPlayOff` INT(11) NOT NULL DEFAULT 0,
  `canceled` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


--
-- Tabellenstruktur für Tabelle `matchevents`
--

CREATE TABLE `matchevents` (
  `id` int(11) NOT NULL,
  `code` varchar(16) NOT NULL,
  `name` varchar(64) NOT NULL,
  `needsTeamAssoc` int(11) NOT NULL DEFAULT 0,
  `needsPlayerAssoc` int(11) NOT NULL DEFAULT 0,
  `playerFouledOutAfter` int(11) DEFAULT NULL,
  `playerFoulSuspMinutes` int(11) DEFAULT NULL,
  `logsAddableWithoutModal` int(11) NOT NULL DEFAULT 0,
  `logsAddableOnLoggedIn` int(11) NOT NULL DEFAULT 1,
  `showOnSportsOnly` int(11) DEFAULT NULL,
  `textConfirmHeader` varchar(128) DEFAULT NULL,
  `textHeaderBeforeButton` varchar(256) DEFAULT NULL,
  `isCancelable` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Daten für Tabelle `matchevents`
--

INSERT INTO `matchevents` (`id`, `code`, `name`, `needsTeamAssoc`, `needsPlayerAssoc`, `playerFouledOutAfter`, `playerFoulSuspMinutes`, `logsAddableWithoutModal`, `logsAddableOnLoggedIn`, `showOnSportsOnly`, `textConfirmHeader`, `textHeaderBeforeButton`, `isCancelable`) VALUES
(1, 'LOGIN', 'SR zum Spiel eingeloggt', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 0),
(2, 'ON_PLACE_REF', 'Anwesend: SR', 0, 0, NULL, NULL, 1, 1, NULL, NULL, 'Vor Spielbeginn bitte vor Ort die Anwesenheit (und Spielbereitschaft) der Teilnehmer*innen durch Drücken der Buttons bestätigen:', 0),
(3, 'ON_PLACE_TEAM1', 'Anwesend: Team 1', 0, 0, NULL, NULL, 1, 1, NULL, NULL, NULL, 0),
(4, 'ON_PLACE_TEAM2', 'Anwesend: Team 2', 0, 0, NULL, NULL, 1, 1, NULL, NULL, NULL, 0),
(10, 'IS_ALIVE', 'alive', 0, 0, NULL, NULL, 1, 0, NULL, NULL, NULL, 0),
(11, 'MATCH_START', 'Spielbeginn', 0, 0, NULL, NULL, 0, 1, NULL, 'Bitte bestätigen, dass das Spiel läuft:', 'Ok, prima! Dann kann es ja losgehen, sobald der zentrale Anpfiff erfolgt, dann hier drücken:', 0),
(21, 'GOAL_1POINT', 'Tor', 1, 0, NULL, NULL, 1, 1, NULL, 'Welches Team hat den Punkt bzw. das Tor erzielt? Bitte auswählen:', 'Jedes Spielereignis bitte durch Drücken der folgenden Buttons protokollieren:', 1),
(22, 'GOAL_2POINT', '2 Punkte', 1, 0, NULL, NULL, 1, 1, 1, 'Welches Team hat den 2-Punkte-Wurf erzielt? Bitte auswählen:', NULL, 1),
(23, 'GOAL_3POINT', '3 Punkte', 1, 0, NULL, NULL, 1, 1, 1, 'Welches Team hat den 3-Punkte-Wurf erzielt? Bitte auswählen:', NULL, 1),
(31, 'FOUL_CARD_YELLOW', 'Gelbe Karte', 1, 1, NULL, NULL, 0, 1, -1, 'Welche Nummer von welchem Team hat die gelbe Karte erhalten? Bitte auswählen:', NULL, 1),
(32, 'FOUL_SUSP_HB', '2-Minuten-Strafe', 1, 1, 3, 2, 0, 1, 3, 'Welche Nummer von welchem Team hat die Zeitstrafe erhalten? Bitte auswählen:', NULL, 1),
(33, 'FOUL_SUSP_FB', '2-Minuten-Strafe', 1, 1, 2, 2, 0, 1, 2, 'Welche Nummer von welchem Team hat die Zeitstrafe erhalten? Bitte auswählen:', NULL, 1),
(34, 'FOUL_CARD_RED_FB', 'Rote Karte', 1, 1, 1, NULL, 0, 1, 2, 'Welche Nummer von welchem Team hat die rote Karte erhalten? Bitte auswählen:', NULL, 1),
(35, 'FOUL_CARD_RED_HB', 'Rote Karte', 1, 1, 1, 2, 0, 1, 3, 'Welche Nummer von welchem Team hat die rote Karte erhalten? Bitte auswählen:', NULL, 1),
(36, 'FOUL_CARD_RED_VB', 'Rote Karte', 1, 1, NULL, NULL, 0, 1, 4, 'Welche Nummer von welchem Team hat die rote Karte erhalten? Bitte auswählen:', NULL, 1),
(41, 'FOUL_PERSONAL', 'Persönliches Foul', 1, 1, 3, NULL, 0, 1, 1, 'Welche Nummer von welchem Team hat das Foul begangen? Bitte auswählen:', NULL, 1),
(42, 'FOUL_TECH_FLAGR', 'Techn./ Unsportl. Foul', 1, 1, 2, NULL, 0, 1, 1, 'Welche Nummer von welchem Team hat das Foul begangen? Bitte auswählen:', NULL, 1),
(43, 'FOUL_DISQ', 'Disqualif. Foul', 1, 1, 1, NULL, 0, 1, 1, 'Welche Nummer von welchem Team hat das Foul begangen? Bitte auswählen:', NULL, 1),
(55, 'MATCH_END', 'Spielende', 0, 0, NULL, NULL, 0, 1, NULL, 'Ist das Spiel wirklich beendet und stimmen die Toranzahlen?', 'Wenn alles protokolliert wurde und das Spiel beendet ist, bitte hier drücken und anschließend noch abschließen:', 0),
(70, 'RESULT_WIN_NONE', 'kein Sieger: Unentschieden', 0, 0, NULL, NULL, 1, 1, NULL, NULL, 'Zum Abschluss bitte noch den Spielausgang durch Drücken eines der folgenden Buttons bestätigen:', 0),
(71, 'RESULT_WIN_TEAM1', 'Sieger: Team 1', 0, 0, NULL, NULL, 1, 1, NULL, NULL, NULL, 0),
(72, 'RESULT_WIN_TEAM2', 'Sieger: Team 2', 0, 0, NULL, NULL, 1, 1, NULL, NULL, NULL, 0),
(90, 'MATCH_CONCLUDE', 'Spiel abschließen', 0, 0, NULL, NULL, 0, 1, NULL, 'Wirklich abschließen? Danach kann am Spielprotokoll nichts mehr geändert werden!', 'Bitte von beiden Teams kurz prüfen lassen. Passt alles? Falls ja, hier drücken:', 0),
(95, 'RESULT_CONFIRM', 'Ergebnis bestätigt', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 0),
(97, 'PHOTO_ADD', 'Fotografieren und hochladen', 0, 0, NULL, NULL, 1, 1, NULL, '', 'Optional: Team-Fotos jetzt hier hochladen:', 0),
(98, 'PHOTO_UPLOAD', 'Foto hochladen', 0, 0, NULL, NULL, 1, 0, NULL, '', '', 0),
(99, 'LOGOUT', 'vom Spiel ausloggen', 0, 0, NULL, NULL, 1, 1, NULL, 'Wirklich vom Spiel ausloggen?', 'Das Spiel ist nun abgeschlossen und wird in Kürze von der spielleitenden Stelle mit entsprechendem Faktor gewertet.', 0);

--
-- Tabellenstruktur für Tabelle `matchevent_logs`
--

CREATE TABLE `matchevent_logs` (
  `id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `matchEvent_id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `playerNumber` int(11) DEFAULT NULL,
  `playerName` varchar(64) DEFAULT NULL,
  `datetimeSent` datetime DEFAULT NULL,
  `datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `canceled` int(11) NOT NULL DEFAULT 0,
  `cancelTime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


--
-- Tabellenstruktur für Tabelle `matchscheduling_pattern16`
--

CREATE TABLE `matchscheduling_pattern16` (
  `id` int(11) NOT NULL,
  `round_id` int(11) NOT NULL,
  `placenumberTeam1` int(11) NOT NULL,
  `placenumberTeam2` int(11) NOT NULL,
  `placenumberRefereeTeam` int(11) NOT NULL,
  `sport_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Daten für Tabelle `matchscheduling_pattern16`
--

INSERT INTO `matchscheduling_pattern16` (`id`, `round_id`, `placenumberTeam1`, `placenumberTeam2`, `placenumberRefereeTeam`, `sport_id`) VALUES
(1, 1, 1, 9, 5, 4),
(2, 1, 2, 10, 6, 3),
(3, 1, 3, 11, 7, 1),
(4, 1, 4, 12, 8, 2),
(5, 2, 5, 13, 1, 4),
(6, 2, 6, 14, 2, 3),
(7, 2, 7, 15, 3, 1),
(8, 2, 8, 16, 4, 2),
(9, 3, 1, 10, 13, 2),
(10, 3, 2, 11, 14, 4),
(11, 3, 3, 12, 15, 3),
(12, 3, 4, 9, 16, 1),
(13, 4, 5, 14, 9, 2),
(14, 4, 6, 15, 10, 4),
(15, 4, 7, 16, 11, 3),
(16, 4, 8, 13, 12, 1),
(17, 5, 1, 16, 5, 1),
(18, 5, 2, 15, 6, 2),
(19, 5, 3, 14, 7, 4),
(20, 5, 4, 13, 8, 3),
(21, 6, 5, 12, 1, 1),
(22, 6, 6, 11, 2, 2),
(23, 6, 7, 10, 3, 4),
(24, 6, 8, 9, 4, 3),
(25, 7, 1, 15, 5, 3),
(26, 7, 2, 14, 6, 1),
(27, 7, 3, 13, 7, 2),
(28, 7, 4, 16, 8, 4),
(29, 8, 5, 11, 13, 3),
(30, 8, 6, 10, 14, 1),
(31, 8, 7, 9, 15, 2),
(32, 8, 8, 12, 16, 4),
(33, 9, 1, 5, 9, 4),
(34, 9, 2, 6, 10, 3),
(35, 9, 3, 7, 11, 1),
(36, 9, 4, 8, 12, 2),
(37, 10, 9, 13, 4, 4),
(38, 10, 10, 14, 1, 3),
(39, 10, 11, 15, 2, 1),
(40, 10, 12, 16, 3, 2),
(41, 11, 1, 6, 10, 2),
(42, 11, 2, 7, 11, 4),
(43, 11, 3, 8, 12, 3),
(44, 11, 4, 5, 9, 1),
(45, 12, 9, 14, 1, 2),
(46, 12, 10, 15, 2, 4),
(47, 12, 11, 16, 3, 3),
(48, 12, 12, 13, 4, 1),
(49, 13, 1, 14, 8, 1),
(50, 13, 2, 13, 5, 2),
(51, 13, 3, 16, 6, 4),
(52, 13, 4, 15, 7, 3),
(53, 14, 9, 6, 13, 1),
(54, 14, 10, 5, 16, 2),
(55, 14, 11, 8, 15, 4),
(56, 14, 12, 7, 14, 3),
(57, 15, 1, 13, 9, 3),
(58, 15, 2, 16, 10, 1),
(59, 15, 3, 15, 11, 2),
(60, 15, 4, 14, 12, 4),
(61, 16, 9, 5, 16, 3),
(62, 16, 10, 8, 15, 1),
(63, 16, 11, 7, 14, 2),
(64, 16, 12, 6, 13, 4);

--
-- Tabellenstruktur für Tabelle `matchscheduling_pattern24`
--

CREATE TABLE `matchscheduling_pattern24` (
                                             `id` int(11) NOT NULL,
                                             `round_id` int(11) NOT NULL,
                                             `placenumberTeam1` int(11) NOT NULL,
                                             `placenumberTeam2` int(11) NOT NULL,
                                             `placenumberRefereeTeam` int(11) DEFAULT NULL,
                                             `sport_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Daten für Tabelle `matchscheduling_pattern24`
--

INSERT INTO `matchscheduling_pattern24` (`id`, `round_id`, `placenumberTeam1`, `placenumberTeam2`, `placenumberRefereeTeam`, `sport_id`) VALUES
(1, 1, 5, 22, NULL, 1),
(2, 2, 9, 18, NULL, 1),
(3, 3, 13, 14, NULL, 1),
(4, 4, 6, 23, NULL, 1),
(5, 5, 10, 19, NULL, 1),
(6, 6, 14, 15, NULL, 1),
(7, 7, 7, 24, NULL, 1),
(8, 8, 11, 20, NULL, 1),
(9, 9, 15, 16, NULL, 1),
(10, 10, 8, 1, NULL, 1),
(11, 11, 12, 21, NULL, 1),
(12, 12, 16, 17, NULL, 1),
(13, 13, 9, 2, NULL, 1),
(14, 14, 13, 22, NULL, 1),
(15, 15, 17, 18, NULL, 1),
(16, 16, 10, 3, NULL, 1),
(17, 17, 14, 23, NULL, 1),
(18, 18, 18, 19, NULL, 1),
(19, 19, 11, 4, NULL, 1),
(20, 20, 15, 24, NULL, 1),
(21, 21, 19, 20, NULL, 1),
(22, 22, 12, 5, NULL, 1),
(23, 23, 16, 1, NULL, 1),
(24, 24, 20, 21, NULL, 1),
(25, 1, 2, 1, NULL, 2),
(26, 2, 6, 21, NULL, 2),
(27, 3, 10, 17, NULL, 2),
(28, 4, 3, 2, NULL, 2),
(29, 5, 7, 22, NULL, 2),
(30, 6, 11, 18, NULL, 2),
(31, 7, 4, 3, NULL, 2),
(32, 8, 8, 23, NULL, 2),
(33, 9, 12, 19, NULL, 2),
(34, 10, 5, 4, NULL, 2),
(35, 11, 9, 24, NULL, 2),
(36, 12, 13, 20, NULL, 2),
(37, 13, 6, 5, NULL, 2),
(38, 14, 10, 1, NULL, 2),
(39, 15, 14, 21, NULL, 2),
(40, 16, 7, 6, NULL, 2),
(41, 17, 11, 2, NULL, 2),
(42, 18, 15, 22, NULL, 2),
(43, 19, 8, 7, NULL, 2),
(44, 20, 12, 3, NULL, 2),
(45, 21, 16, 23, NULL, 2),
(46, 22, 9, 8, NULL, 2),
(47, 23, 13, 4, NULL, 2),
(48, 24, 17, 24, NULL, 2),
(49, 1, 3, 24, NULL, 3),
(50, 2, 7, 20, NULL, 3),
(51, 3, 11, 16, NULL, 3),
(52, 4, 4, 1, NULL, 3),
(53, 5, 8, 21, NULL, 3),
(54, 6, 12, 17, NULL, 3),
(55, 7, 5, 2, NULL, 3),
(56, 8, 9, 22, NULL, 3),
(57, 9, 13, 18, NULL, 3),
(58, 10, 6, 3, NULL, 3),
(59, 11, 10, 23, NULL, 3),
(60, 12, 14, 19, NULL, 3),
(61, 13, 7, 4, NULL, 3),
(62, 14, 11, 24, NULL, 3),
(63, 15, 15, 20, NULL, 3),
(64, 16, 8, 5, NULL, 3),
(65, 17, 12, 1, NULL, 3),
(66, 18, 16, 21, NULL, 3),
(67, 19, 9, 6, NULL, 3),
(68, 20, 13, 2, NULL, 3),
(69, 21, 17, 22, NULL, 3),
(70, 22, 10, 7, NULL, 3),
(71, 23, 14, 3, NULL, 3),
(72, 24, 18, 23, NULL, 3),
(73, 1, 4, 23, NULL, 4),
(74, 2, 8, 19, NULL, 4),
(75, 3, 12, 15, NULL, 4),
(76, 4, 5, 24, NULL, 4),
(77, 5, 9, 20, NULL, 4),
(78, 6, 13, 16, NULL, 4),
(79, 7, 6, 1, NULL, 4),
(80, 8, 10, 21, NULL, 4),
(81, 9, 14, 17, NULL, 4),
(82, 10, 7, 2, NULL, 4),
(83, 11, 11, 22, NULL, 4),
(84, 12, 15, 18, NULL, 4),
(85, 13, 8, 3, NULL, 4),
(86, 14, 12, 23, NULL, 4),
(87, 15, 16, 19, NULL, 4),
(88, 16, 9, 4, NULL, 4),
(89, 17, 13, 24, NULL, 4),
(90, 18, 17, 20, NULL, 4),
(91, 19, 10, 5, NULL, 4),
(92, 20, 14, 1, NULL, 4),
(93, 21, 18, 21, NULL, 4),
(94, 22, 11, 6, NULL, 4),
(95, 23, 15, 2, NULL, 4),
(96, 24, 19, 22, NULL, 4);

--
-- Tabellenstruktur für Tabelle `push_tokens`
--

CREATE TABLE `push_tokens` (
  `id` int(11) NOT NULL,
  `expoPushToken` varchar(64) NOT NULL,
  `my_team_id` int(11) NOT NULL,
  `my_year_id` int(11) DEFAULT NULL,
  `edited` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8_general_ci;

--
-- Tabellenstruktur für Tabelle `rankingpoints`
--

CREATE TABLE `rankingpoints` (
  `id` int(11) NOT NULL,
  `endRanking` int(11) NOT NULL,
  `points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Daten für Tabelle `rankingpoints`
--

INSERT INTO `rankingpoints` (`id`, `endRanking`, `points`) VALUES
(1, 1, 64),
(2, 2, 63),
(3, 3, 62),
(4, 4, 61),
(5, 5, 60),
(6, 6, 59),
(7, 7, 58),
(8, 8, 57),
(9, 9, 56),
(10, 10, 55),
(11, 11, 54),
(12, 12, 53),
(13, 13, 52),
(14, 14, 51),
(15, 15, 50),
(16, 16, 49),
(17, 17, 48),
(18, 18, 47),
(19, 19, 46),
(20, 20, 45),
(21, 21, 44),
(22, 22, 43),
(23, 23, 42),
(24, 24, 41),
(25, 25, 40),
(26, 26, 39),
(27, 27, 38),
(28, 28, 37),
(29, 29, 36),
(30, 30, 35),
(31, 31, 34),
(32, 32, 33),
(33, 33, 32),
(34, 34, 31),
(35, 35, 30),
(36, 36, 29),
(37, 37, 28),
(38, 38, 27),
(39, 39, 26),
(40, 40, 25),
(41, 41, 24),
(42, 42, 23),
(43, 43, 22),
(44, 44, 21),
(45, 45, 20),
(46, 46, 19),
(47, 47, 18),
(48, 48, 17),
(49, 49, 16),
(50, 50, 15),
(51, 51, 14),
(52, 52, 13),
(53, 53, 12),
(54, 54, 11),
(55, 55, 10),
(56, 56, 9),
(57, 57, 8),
(58, 58, 7),
(59, 59, 6),
(60, 60, 5),
(61, 61, 4),
(62, 62, 3),
(63, 63, 2),
(64, 64, 1);

--
-- Tabellenstruktur für Tabelle `rounds`
--

CREATE TABLE `rounds` (
  `id` int(11) NOT NULL,
  `timeStartDay1` time NOT NULL,
  `timeStartDay2` time NOT NULL,
  `autoUpdateResults` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Daten für Tabelle `rounds`
--

INSERT INTO `rounds` (`id`, `timeStartDay1`, `timeStartDay2`, `autoUpdateResults`) VALUES
(1, '10:00:00', '09:00:00', 1),
(2, '10:30:00', '09:30:00', 1),
(3, '11:00:00', '10:00:00', 1),
(4, '11:30:00', '10:30:00', 1),
(5, '12:00:00', '11:00:00', 1),
(6, '12:30:00', '11:30:00', 1),
(7, '13:00:00', '12:00:00', 1),
(8, '13:30:00', '12:30:00', 1),
(9, '14:00:00', '13:00:00', 1),
(10, '14:30:00', '13:30:00', 1),
(11, '15:00:00', '14:00:00', 1),
(12, '15:30:00', '14:30:00', 1),
(13, '16:00:00', '15:00:00', 1),
(14, '16:30:00', '15:30:00', 1),
(15, '17:00:00', '16:00:00', 1),
(16, '17:30:00', '16:30:00', 1),
(17, '18:00:00', '17:00:00', 1),
(18, '18:30:00', '17:30:00', 1),
(19, '19:00:00', '18:00:00', 1),
(20, '19:30:00', '18:30:00', 1),
(21, '20:00:00', '19:00:00', 1),
(22, '20:30:00', '19:30:00', 1),
(23, '21:00:00', '20:00:00', 1),
(24, '21:30:00', '20:30:00', 1);

--
-- Tabellenstruktur für Tabelle `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `value` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Daten für Tabelle `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`) VALUES
(1, 'isTest', 1),
(2, 'currentYear_id', 27),
(3, 'currentDay_id', 1),
(4, 'alwaysAutoUpdateResults', 0),
(5, 'showScheduleHoursBefore', 3),
(6, 'time2LoginMinsBeforeFrom', 120),
(7, 'time2LoginMinsAfterUntil', 29),
(8, 'time2MatchEndMinAfterFrom', 20),
(9, 'time2ConfirmMinsAfterFrom', 23),
(10, 'time2ConfirmMinsAfterUntil', 120),
(11, 'showEndRanking', 0),
(12, 'showLocalStorageScore', 0),
(13, 'maxPhotos', 5),
(14, 'autoLogoutSecsAfter', 60),
(15, 'useLiveScouting', 1),
(16, 'usePlayOff', 0),
(17, 'usePush', 1),
(18, 'useRefereeName', 0),
(19, 'useResourceContentApi', 0),
(20, 'showArchieve', 1);

--
-- Tabellenstruktur für Tabelle `sports`
--

CREATE TABLE `sports` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `code` varchar(4) NOT NULL,
  `goalFactor` int(11) NOT NULL,
  `color` varchar(8) DEFAULT NULL,
  `icon` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Daten für Tabelle `sports`
--

INSERT INTO `sports` (`id`, `name`, `code`, `goalFactor`, `color`, `icon`) VALUES
(1, 'Basketball', 'BB', 1, NULL, NULL),
(2, 'Fußball', 'FB', 5, NULL, NULL),
(3, 'Handball', 'HB', 3, NULL, NULL),
(4, 'Volleyball', 'VB', 1, NULL, NULL),
(5, 'Multi', '', 1, NULL, NULL);

--
-- Tabellenstruktur für Tabelle `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `calcTotalYears` int(11) DEFAULT NULL,
  `calcTotalRankingPoints` int(11) DEFAULT NULL,
  `calcTotalPointsPerYear` decimal(4,2) DEFAULT NULL,
  `calcTotalChampionships` int(11) DEFAULT NULL,
  `calcTotalRanking` int(11) DEFAULT NULL,
  `prevTeam_id` INT NULL DEFAULT NULL,
  `hidden` int(11) NOT NULL DEFAULT 0,
  `testTeam` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Tabellenstruktur für Tabelle `team_years`
--

CREATE TABLE `team_years` (
  `id` int(11) NOT NULL,
  `year_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `refereePIN` varchar(5) DEFAULT NULL,
  `endRanking` int(11) DEFAULT NULL,
  `canceled` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Tabellenstruktur für Tabelle `years`
--

CREATE TABLE `years` (
  `id` int(11) NOT NULL,
  `name` int(11) NOT NULL,
  `day1` date NOT NULL,
  `day2` date DEFAULT NULL,
  `teamsCount` int(11) NOT NULL DEFAULT 64,
  `daysCount` int(11) NOT NULL DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


--
-- Daten für Tabelle `years`
--

INSERT INTO `years` (`id`, `name`, `day1`, `day2`, `teamsCount`, `daysCount`) VALUES
(1, 1995, '1995-07-15', '1995-07-16', 28, 2),
(2, 1996, '1996-07-13', '1996-07-14', 64, 2),
(3, 1997, '1997-07-19', '1997-07-20', 61, 2),
(4, 1998, '1998-07-18', '1998-07-19', 63, 2),
(5, 1999, '1999-07-17', '1999-07-18', 62, 2),
(6, 2000, '2000-07-15', '2000-07-16', 63, 2),
(7, 2001, '2001-07-14', '2001-07-15', 64, 2),
(8, 2002, '2002-07-13', '2002-07-14', 64, 2),
(9, 2004, '2004-07-17', '2004-07-18', 61, 2),
(10, 2005, '2005-07-16', '2005-07-17', 64, 2),
(11, 2006, '2006-07-15', '2006-07-16', 64, 2),
(12, 2007, '2007-07-14', '2007-07-15', 63, 2),
(13, 2008, '2008-07-19', '2008-07-20', 62, 2),
(14, 2009, '2009-07-18', '2009-07-19', 63, 2),
(15, 2010, '2010-07-17', '2010-07-18', 60, 2),
(16, 2011, '2011-07-16', '2011-07-17', 59, 2),
(17, 2012, '2012-07-14', '2012-07-15', 63, 2),
(18, 2013, '2013-07-13', '2013-07-14', 63, 2),
(19, 2014, '2014-07-19', '2014-07-20', 62, 2),
(20, 2015, '2015-07-18', '2015-07-19', 64, 2),
(21, 2016, '2016-07-16', '2016-07-17', 64, 2),
(22, 2017, '2017-07-15', '2017-07-16', 64, 2),
(23, 2018, '2018-07-14', '2018-07-15', 63, 2),
(24, 2019, '2019-07-20', '2019-07-21', 62, 2),
(25, 2022, '2022-07-16', '2022-07-17', 64, 2),
(26, 2023, '2023-07-15', '2023-07-16', 64, 2),
(27, 2024, '2024-07-20', '2024-07-21', 64, 2),
(28, 2025, '2025-07-19', '2025-07-20', 64, 2);

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `days`
--
ALTER TABLE `days`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indizes für die Tabelle `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_yearId_dayId_name` (`year_id`,`day_id`,`name`),
  ADD KEY `fk_day_id` (`day_id`);

--
-- Indizes für die Tabelle `group_teams`
--
ALTER TABLE `group_teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_groupId_placenumber` (`group_id`,`placeNumber`) USING BTREE,
  ADD UNIQUE KEY `unique_groupId_team_id` (`group_id`,`team_id`),
  ADD UNIQUE KEY `unique_groupId_ranking` (`group_id`,`calcRanking`),
  ADD KEY `fk_team_id` (`team_id`);

--
-- Indizes für die Tabelle `logins`
--
ALTER TABLE `logins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_unique` (`name`) USING BTREE;

--
-- Indizes für die Tabelle `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_groupId_roundId_sport_id` (`group_id`,`round_id`,`sport_id`) USING BTREE,
  ADD UNIQUE KEY `unique_groupId_roundId_referee` (`group_id`,`round_id`,`refereeTeam_id`),
  ADD KEY `fk_round_id` (`round_id`),
  ADD KEY `fk_sport_id` (`sport_id`),
  ADD KEY `fk_team1_id` (`team1_id`),
  ADD KEY `fk_team2_id` (`team2_id`),
  ADD KEY `fk_refereeTeam_id` (`refereeTeam_id`),
  ADD KEY `fk_refereeTeamSubst_id` (`refereeTeamSubst_id`);

--
-- Indizes für die Tabelle `matchevents`
--
ALTER TABLE `matchevents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_code` (`code`) USING BTREE;

--
-- Indizes für die Tabelle `matchevent_logs`
--
ALTER TABLE `matchevent_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_match_id` (`match_id`),
  ADD KEY `fk_matchEvent_id` (`matchEvent_id`),
  ADD KEY `fk_team3_id` (`team_id`);

--
-- Indizes für die Tabelle `matchscheduling_pattern16`
--
ALTER TABLE `matchscheduling_pattern16`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `unique_sportId_round_id` (`sport_id`,`round_id`),
    ADD UNIQUE KEY `unique_referee_round_id` (`placenumberRefereeTeam`,`round_id`),
    ADD KEY `fk_round_id2` (`round_id`);

--
-- Indizes für die Tabelle `matchscheduling_pattern24`
--
ALTER TABLE `matchscheduling_pattern24`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `unique_sportId_round_id` (`sport_id`,`round_id`),
    ADD UNIQUE KEY `unique_referee_round_id` (`placenumberRefereeTeam`,`round_id`),
    ADD KEY `fk_round_id2` (`round_id`);

--
-- Indizes für die Tabelle `push_tokens`
--
ALTER TABLE `push_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_my_team_id` (`my_team_id`);

--
-- Indizes für die Tabelle `rankingpoints`
--
ALTER TABLE `rankingpoints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_endRanking` (`endRanking`) USING BTREE;

--
-- Indizes für die Tabelle `rounds`
--
ALTER TABLE `rounds`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `sports`
--
ALTER TABLE `sports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`) USING BTREE;

--
-- Indizes für die Tabelle `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_unique` (`name`),
  ADD KEY `fk_prevTeam_id` (`prevTeam_id`);

--
-- Indizes für die Tabelle `team_years`
--
ALTER TABLE `team_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_yearId_team_id` (`year_id`,`team_id`) USING BTREE,
  ADD UNIQUE KEY `unique_yearId_endRanking` (`year_id`,`endRanking`),
  ADD UNIQUE KEY `unique_yearId_teamId_refereePIN` (`year_id`,`team_id`,`refereePIN`),
  ADD KEY `fk_team_id4` (`team_id`);

--
-- Indizes für die Tabelle `years`
--
ALTER TABLE `years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`) USING BTREE;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `fk_day_id` FOREIGN KEY (`day_id`) REFERENCES `days` (`id`),
  ADD CONSTRAINT `fk_year_id` FOREIGN KEY (`year_id`) REFERENCES `years` (`id`);

--
-- Constraints der Tabelle `group_teams`
--
ALTER TABLE `group_teams`
  ADD CONSTRAINT `fk_group_id` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  ADD CONSTRAINT `fk_team_id` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`);

--
-- Constraints der Tabelle `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `fk2_group_id` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  ADD CONSTRAINT `fk_refereeTeamSubst_id` FOREIGN KEY (`refereeTeamSubst_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `fk_refereeTeam_id` FOREIGN KEY (`refereeTeam_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `fk_round_id` FOREIGN KEY (`round_id`) REFERENCES `rounds` (`id`),
  ADD CONSTRAINT `fk_sport_id` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`),
  ADD CONSTRAINT `fk_team1_id` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `fk_team2_id` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`id`);

--
-- Constraints der Tabelle `matchevent_logs`
--
ALTER TABLE `matchevent_logs`
  ADD CONSTRAINT `fk_matchEvent_id` FOREIGN KEY (`matchEvent_id`) REFERENCES `matchevents` (`id`),
  ADD CONSTRAINT `fk_match_id` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`),
  ADD CONSTRAINT `fk_team3_id` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`);

--
-- Constraints der Tabelle `matchscheduling_pattern16`
--
ALTER TABLE `matchscheduling_pattern16`
    ADD CONSTRAINT `fk_round_id2` FOREIGN KEY (`round_id`) REFERENCES `rounds` (`id`),
    ADD CONSTRAINT `fk_sport_id2` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`);

--
-- Constraints der Tabelle `matchscheduling_pattern24`
--
ALTER TABLE `matchscheduling_pattern24`
    ADD CONSTRAINT `fk_round_id3` FOREIGN KEY (`round_id`) REFERENCES `rounds` (`id`),
  ADD CONSTRAINT `fk_sport_id3` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`);

--
-- Constraints der Tabelle `push_tokens`
--
ALTER TABLE `push_tokens`
  ADD CONSTRAINT `fk_my_team_id` FOREIGN KEY (`my_team_id`) REFERENCES `teams` (`id`);

--
-- Constraints der Tabelle `teams`
--
ALTER TABLE `teams`
    ADD CONSTRAINT `fk_prevTeam_id` FOREIGN KEY (`prevTeam_id`) REFERENCES `teams` (`id`);

--
-- Constraints der Tabelle `team_years`
--
ALTER TABLE `team_years`
  ADD CONSTRAINT `fk_team_id4` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `fk_year_id3` FOREIGN KEY (`year_id`) REFERENCES `years` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
