<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\GroupTeam;
use App\Model\Entity\Match4;
use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;

/**
 * Years Controller
 *
 * @property \App\Model\Table\YearsTable $Years
 * @property \App\Controller\Component\CalcComponent $Calc
 * @property \App\Controller\Component\MatchGetComponent $MatchGet
 * @property \App\Controller\Component\SecurityComponent $Security
 */
class YearsController extends AppController
{
    public function getCurrent(): void
    {
        $year = $this->Cache->getCurrentYear()->toArray();

        $year['settings'] = $this->Cache->getSettings();

        $year['currentDay_id'] = $year['settings']['currentDay_id'];
        $year['day'] = $year['settings']['currentDay_id'] == 1 ? $year['day1'] : $year['day2'];
        $year['isStart'] = 1;

        $this->apiReturn($year);

        // todo: after V2.0 complete rollout: simplify: $this->apiReturn(array('isStart' => '1'));  // SIC!
        // todo: deprecated: after V2.0.1 complete rollout: function not needed anymore
    }

    public function all(): void
    {
        $settings = $this->Cache->getSettings();

        $years = $this->Years->find('all', array(
            'fields' => array('id', 'year_id' => 'id', 'year_name' => 'name'),
            'order' => array('id' => 'DESC')
        ))
            ->where(['id <=' => $settings['currentYear_id']])
            ->limit($settings['currentYear_id'])  // limit needed for offset!
            ->offset($settings['showEndRanking'] ? 0 : 1) // as an index not to show current without ranking
        ;

        $this->apiReturn($years);
    }

    public function setCurrentDayIncrement(): void
    {
        $settings = $this->Cache->getSettings();
        $year = $this->Cache->getCurrentYear();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {

            if ($settings['currentDay_id'] < $year->daysCount) {
                $this->Security->setSetting('currentDay_id', $settings['currentDay_id'] + 1);
                $this->Security->setSetting('alwaysAutoUpdateResults', 0);

                $settings = $this->Cache->getSettings();
            }
        }

        $this->apiReturn($settings);
    }

    public function setAlwaysAutoUpdateResults(): void
    {
        $return = $this->Security->setSetting('alwaysAutoUpdateResults', 1);
        $this->Calc->getCalcRanking();

        $this->apiReturn($return);
    }

    public function showEndRanking(string $show = ''): void
    {
        $return = $this->Security->setSetting('showEndRanking', (int)$show);
        $this->Calc->getCalcRanking();

        $this->apiReturn($return);
    }

    public function reCalcRanking(string $team1_id = '', string $team2_id = ''): void
    {
        $return = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $return = $this->Calc->getCalcRanking((int)$team1_id, (int)$team2_id);
        }

        $this->apiReturn($return);
    }

    public function new(): void
    {
        $postData = $this->request->getData();
        $year = $this->Cache->getCurrentYear()->toArray();
        $newYear = false;

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $ytime = DateTime::createFromFormat('Y-m-d H:i:s', $year['day' . $year['daysCount']]->i18nFormat('yyyy-MM-dd HH:mm:ss'));
            $ytime = $ytime->addHours(24); // start of next day after last day of the tournament
            $now = DateTime::now();

            if ($settings['currentDay_id'] == $year['daysCount'] && ($settings['isTest'] || $now > $ytime)) {
                $newYear = $this->Years->newEmptyEntity();
                $newYear->set('name', $postData['name']);
                $newYear->set('day1', $postData['day1']);
                $newYear->set('day2', $postData['day2'] != '' ? $postData['day2'] : null);
                $newYear->set('daysCount', $postData['daysCount']);
                $newYear->set('teamsCount', $postData['teamsCount']);
                $this->Years->save($newYear);

                $this->Security->setSetting('currentYear_id', $newYear->id);
                $this->Security->setSetting('currentDay_id', 1);
                $this->Security->setSetting('showEndRanking', 0);

                if ($settings['usePlayOff'] == 0) {
                    $this->Security->setSetting('alwaysAutoUpdateResults', 0);
                }

                Cache::delete('app_year');
            }
        }

        $this->apiReturn($newYear);
    }

    // get Status of current Day
    public function getStatus(): void
    {
        $settings = $this->Cache->getSettings();
        $conditionsArray = array('Groups.year_id' => $settings['currentYear_id'], 'Groups.day_id' => $settings['currentDay_id']);

        $teamYears = $this->fetchTable('TeamYears')->find('all', array(
            'conditions' => array('year_id' => $settings['currentYear_id']),
        ))->toArray();
        $teamYearsEndRanking = $this->fetchTable('TeamYears')->find('all', array(
            'conditions' => array('year_id' => $settings['currentYear_id'], 'endRanking IS NOT' => null),
        ))->toArray();
        $teamYearsPins = $this->fetchTable('TeamYears')->find('all', array(
            'conditions' => array('year_id' => $settings['currentYear_id'], 'refereePIN IS NOT' => null),
        ))->toArray();

        $scrRanking = $this->fetchTable('TeamYears')->find('all', array(
            'conditions' => array('year_id' => $settings['currentYear_id'], 'scrRanking IS NOT' => null),
        ));

        $groups = $this->fetchTable('Groups')->find('all', array(
            'conditions' => $conditionsArray,
        ))->toArray();

        $groupTeams = $this->fetchTable('GroupTeams')->find('all', array(
            'contain' => array(
                'Groups' => array('fields' => array('year_id', 'day_id')),
                'Teams'
            ),
            'conditions' => $conditionsArray,
        ))->toArray();

        $matches = $this->MatchGet->getMatches($conditionsArray, 0, 2, 1); // sortBy 2: get non-midday matches first
        $matchesPins = $this->MatchGet->getMatches(array_merge($conditionsArray, array('refereePIN IS NOT' => null)), 0, 0, 0);
        $matchesPlayOff = $this->fetchTable('Matches')->find('all', conditions: ['isPlayOff >' => (int)($settings['currentYear_id'] . '0')]);

        $sumCalcMatches = 0;
        $sumMatchesByTeam = array();
        $sumMatchesByTeamPerOpponent = array();
        $maxMatchesByTeamPerOpponent = array();
        $sumMatchesByTeamPerSport = array();
        $maxMatchesByTeamPerSport = array();
        $minMatchesByTeamPerSport = array();
        $sumJobsByTeamPerRound = array();
        $maxJobsByTeamPerRound = array();

        $matchesRefChangeable = array();
        $matchesTeamsChangeable = array();
        $missingRefereesCount = 0;
        $matchesWith1CanceledCount = 0;
        $matchResultCount = 0;

        if (is_array($matches)) {
            foreach ($groupTeams as $gt) {
                /**
                 * @var GroupTeam $gt
                 */
                if ($gt->team->hidden == 0) {
                    $sumCalcMatches += $gt->calcCountMatches;
                }
            }

            foreach ($matches as $m) {
                /**
                 * @var Match4 $m
                 */
                // search for minimize missing referees
                if ($m->isRefereeCanceled && !$m->refereeName && !$m->canceled && $m->resultTrend === null) {
                    $missingRefereesCount++;

                    // search for available refs from the same sport with canceled match
                    $conditionsArray = array('Groups.year_id' => $settings['currentYear_id'], 'Groups.day_id' => $settings['currentDay_id'], 'sport_id' => $m->sport_id, 'Matches.canceled >' => 0);
                    $matches1 = $this->MatchGet->getMatches($conditionsArray, 0, 3, 1); // sortBy 3: get midday matches first
                    if (is_array($matches1)) {
                        foreach ($matches1 as $m1) {
                            /**
                             * @var Match4 $m1
                             */
                            if (!$m1->isRefereeCanceled) {
                                // check if ref's team is already in play in same round with non-canceled match
                                $conditionsArray = array('Groups.year_id' => $settings['currentYear_id'], 'Groups.day_id' => $settings['currentDay_id'], 'round_id' => $m->round_id, 'Matches.canceled' => 0,
                                    'OR' => array(
                                        'team1_id' => $m1->refereeTeam_id,
                                        'team2_id' => $m1->refereeTeam_id,
                                        'refereeTeam_id' => $m1->refereeTeam_id,
                                        'refereeTeamSubst_id' => $m1->refereeTeam_id,
                                    )
                                );
                                $matches2 = $this->MatchGet->getMatches($conditionsArray, 0, 0, 0);
                                if (!$matches2) {
                                    $matchesRefChangeable[] = array($m, $m1);
                                    break;
                                }
                            }
                        }
                    }
                }

                // search for minimize canceled matches
                if ($settings['currentDay_id'] == 2 && ($m->canceled == 1 || $m->canceled == 2) && $m->resultTrend === null) {
                    $matchesWith1CanceledCount++;

                    // search for available teams from the same group and the same sport with canceled match
                    $conditionsArray = array('Groups.year_id' => $settings['currentYear_id'], 'Groups.day_id' => $settings['currentDay_id'], 'group_id' => $m->group_id, 'sport_id' => $m->sport_id, 'Matches.canceled >' => 0, 'Matches.canceled <' => 3, 'Matches.id !=' => $m->id);
                    $matches1 = $this->MatchGet->getMatches($conditionsArray, 0, 0, 1);
                    if (is_array($matches1)) {
                        foreach ($matches1 as $m1) {
                            /**
                             * @var Match4 $m1
                             */
                            // check if other team is already in play in the same round with non-canceled match
                            $otherTeam = $m1->canceled == 1 ? $m1->team2_id : $m1->team1_id;
                            $conditionsArray = array('Groups.year_id' => $settings['currentYear_id'], 'Groups.day_id' => $settings['currentDay_id'], 'round_id' => $m->round_id, 'Matches.canceled' => 0,
                                'OR' => array(
                                    'team1_id' => $otherTeam,
                                    'team2_id' => $otherTeam,
                                    'refereeTeam_id' => $otherTeam,
                                    'refereeTeamSubst_id' => $otherTeam,
                                )
                            );
                            $matches2 = $this->MatchGet->getMatches($conditionsArray, 0, 0, 0);
                            if (!$matches2) {
                                $matchesTeamsChangeable[] = array($m, $m1);
                                break;
                            }
                        }
                    }
                }

                $matchResultCount += ($m->resultTrend !== null ? 1 : 0);

                if ($m->team1_id && $m->team2_id && !$m->isPlayOff) {
                    $tArray = array($m->team1_id, $m->team2_id);
                    $sumMatchesByTeamPerOpponent[min($tArray)][max($tArray)] ??= 0;
                    $sumMatchesByTeamPerOpponent[min($tArray)][max($tArray)]++;

                    $acv1 = array_count_values($tArray);
                    foreach ($acv1 as $k => $v) {
                        $sumMatchesByTeam[$k] ??= 0;
                        $sumMatchesByTeam[$k] += $v;
                        $sumMatchesByTeamPerSport[$m->sport_id][$k] ??= 0;
                        $sumMatchesByTeamPerSport[$m->sport_id][$k] += $v;
                    }
                    $acv2 = array_count_values(array_filter(array($m->team1_id, $m->team2_id, $m->refereeTeam_id, $m->refereeTeamSubst_id)));
                    foreach ($acv2 as $k => $v) {
                        $sumJobsByTeamPerRound[$m->round_id][$k] ??= 0;
                        $sumJobsByTeamPerRound[$m->round_id][$k] += $v;
                    }
                }
            }
        }

        foreach ($sumMatchesByTeamPerOpponent as $k => $v) {
            $maxMatchesByTeamPerOpponent[$k] = max($v);
        }
        foreach ($sumMatchesByTeamPerSport as $k => $v) {
            $maxMatchesByTeamPerSport[$k] = max($v);
            $minMatchesByTeamPerSport[$k] = min($v);
        }
        foreach ($sumJobsByTeamPerRound as $k => $v) {
            $maxJobsByTeamPerRound[$k] = max($v);
        }

        // roundsWithPossibleLogsDelete: select for possible logs delete
        $query2 = $this->fetchTable('MatcheventLogs')->find('all', array(
            'contain' => array('Matches', 'Matches.Groups', 'Matches.Rounds', 'Matchevents'),
            'conditions' => array_merge($conditionsArray, array('Matchevents.code' => 'LOGIN', 'Matches.resultTrend IS' => null))
        ));
        $roundsWithPossibleLogsDelete = $query2->select(array('round_id' => 'Matches.round_id'))
            ->groupBy('Matches.round_id')
            ->orderBy(array('Matches.round_id' => 'ASC'))
            ->toArray();

        $status['teamYearsCount'] = count($teamYears);
        $status['teamYearsEndRankingCount'] = count($teamYearsEndRanking);
        $status['teamYearsPins'] = count($teamYearsPins);
        $status['groupsCount'] = count($groups);
        $status['groupTeamsCount'] = count($groupTeams);
        $status['sumCalcMatchesGroupTeams'] = $sumCalcMatches / 2;
        $status['matchesCount'] = is_array($matches) ? count($matches) : 0;
        $status['matchesPins'] = is_array($matchesPins) ? count($matchesPins) : 0;
        $status['matchesPlayOff'] = $matchesPlayOff->count();
        $status['matchResultCount'] = $matchResultCount;
        $status['minMatchesByTeam'] = !empty($sumMatchesByTeam) ? min($sumMatchesByTeam) : 0;
        $status['maxMatchesByTeam'] = !empty($sumMatchesByTeam) ? max($sumMatchesByTeam) : 0;
        $status['maxMatchesByTeamPerOpponent'] = !empty($maxMatchesByTeamPerOpponent) ? max($maxMatchesByTeamPerOpponent) : 0;
        $status['maxMatchesByTeamPerSport'] = !empty($maxMatchesByTeamPerSport) ? max($maxMatchesByTeamPerSport) : 0;
        $status['minMatchesByTeamPerSport'] = !empty($minMatchesByTeamPerSport) ? min($minMatchesByTeamPerSport) : 0;
        $status['maxJobsByTeamPerRound'] = !empty($maxJobsByTeamPerRound) ? max($maxJobsByTeamPerRound) : 0;

        $status['missingRefereesCount'] = $missingRefereesCount;
        $status['matchesRefChangeable'] = $matchesRefChangeable;

        $status['matchesWith1CanceledCount'] = $matchesWith1CanceledCount;
        $status['matchesTeamsChangeable'] = $matchesTeamsChangeable;

        $status['roundsWithPossibleLogsDelete'] = array_column($roundsWithPossibleLogsDelete, 'round_id');

        $status['scrRanking'] = $scrRanking->count();

        $this->apiReturn($status);
    }

    public function clearTest(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();

            if ($settings['isTest'] ?? 0) {
                $rc = 0;
                $conn = ConnectionManager::get('default');
                /**
                 * @var \Cake\Database\Connection $conn
                 */
                $rc += $conn->execute("DELETE ml FROM matchevent_logs ml LEFT JOIN `matches` m ON ml.match_id=m.id LEFT JOIN `groups` g ON m.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE m FROM `matches` m LEFT JOIN `groups` g ON m.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE gt FROM group_teams gt LEFT JOIN `groups` g ON gt.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE g FROM `groups` g LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE ty FROM team_years ty LEFT JOIN years y ON ty.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE FROM teams WHERE testTeam=1")->rowCount();
                $rc += $conn->execute("UPDATE settings SET value=1 WHERE name = 'currentDay_id'")->rowCount();
                $rc += $conn->execute("UPDATE settings SET value=0 WHERE name = 'showEndRanking'")->rowCount();
                if ($settings['usePlayOff'] == 0) {
                    $rc += $conn->execute("UPDATE settings SET value=0 WHERE name = 'alwaysAutoUpdateResults'")->rowCount();
                }

                Cache::delete('app_settings');
                Cache::delete('app_year');

                // reset all-time stats
                $this->Calc->updateCalcTotal($settings['currentYear_id'] - 1);

                $this->apiReturn(array('rows affected' => $rc));
            }
        }
    }

    public function clearMatchesAndLogs(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();

            if ($settings['isTest'] ?? 0) {
                $rc = 0;
                $conn = ConnectionManager::get('default');
                /**
                 * @var \Cake\Database\Connection $conn
                 */
                $rc += $conn->execute("DELETE ml FROM matchevent_logs ml LEFT JOIN `matches` m ON ml.match_id=m.id LEFT JOIN `groups` g ON m.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE m FROM `matches` m LEFT JOIN `groups` g ON m.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $this->apiReturn(array('rows affected' => $rc));
            }
        }
    }

    public function shufflePattern24toAvoidTripples(): void
    {
        $return = array();
        $query = $this->fetchTable('MatchschedulingPattern24')->find('all');

        $result = $query->select(['round_id', 'field1' => 'group_concat(placenumberTeam1)', 'field2' => 'group_concat(placenumberTeam2)'])
            ->groupBy('round_id')->toArray();

        do {
            shuffle($result);
            $c = 0;
            $ra = array();
            foreach ($result as $v) { // need! because shuffle keeps combi key => value
                $ra[$c] = $v;
                $c++;
            }
            $result = $ra;

            $pn_array = array();
            foreach ($result as $newRoundId => $r) {
                $pn_array[$newRoundId] = array_merge(explode(',', $r['field1']), explode(',', $r['field2']));
            }

            $found = array();
            for ($i = 1; $i <= 24; $i++) {
                foreach ($pn_array as $newRoundId => $a) {
                    if (in_array($i, $a)) {
                        $found[$i][] = $newRoundId;
                    }
                }
            }

            $doubles = 0;
            $tripples = 0;
            foreach ($found as $pn => $a) {
                for ($j = 1; $j < count($a); $j++) {
                    if ($a[$j] == $a[$j - 1] + 1) {
                        $doubles++;
                    }
                    if ($j >= 2 && $a[$j] == $a[$j - 1] + 1 && $a[$j] == $a[$j - 2] + 2) {
                        $tripples++;
                    }
                }
            }
            //$return[] = $doubles;

            if ($tripples == 0) {
                //$return[] = $found;

                $a1 = array();
                foreach ($result as $r) {
                    $a1[] = $r['round_id'];
                }
                $return[] = $a1;
                break;
            }
        } while ($tripples > 0);

        $this->apiReturn($return);
    }

    public function checkPattern24(): void
    {
        $return = array();
        $query = $this->fetchTable('MatchschedulingPattern24')->find('all');

        $result = $query->select(['round_id', 'field1' => 'group_concat(placenumberTeam1)', 'field2' => 'group_concat(placenumberTeam2)'])
            ->groupBy('round_id')->toArray();

        $pn_array = array();
        foreach ($result as $r) {
            $pn_array[$r['round_id']] = array_merge(explode(',', $r['field1']), explode(',', $r['field2']));
        }

        $found = array();
        for ($i = 1; $i <= 24; $i++) {
            foreach ($pn_array as $roundId => $a) {
                if (in_array($i, $a)) {
                    $found[$i][] = $roundId;
                }
            }
        }

        $doubles = 0;
        $tripples = 0;
        foreach ($found as $pn => $a) {
            for ($j = 1; $j < count($a); $j++) {
                if ($a[$j] == $a[$j - 1] + 1) {
                    $doubles++;
                }
                if ($j >= 2 && $a[$j] == $a[$j - 1] + 1 && $a[$j] == $a[$j - 2] + 2) {
                    $tripples++;
                }
            }
        }
        $return[] = $tripples;

        $this->apiReturn($found);
    }
}
