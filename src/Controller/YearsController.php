<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Setting;
use Cake\Datasource\ConnectionManager;

/**
 * Years Controller
 *
 * @property \App\Model\Table\YearsTable $Years
 */
class YearsController extends AppController
{
    public function getCurrent(): void
    {
        $year = $this->getCurrentYear()->toArray();

        $year['settings'] = $this->getSettings();

        $year['currentDay_id'] = $year['settings']['currentDay_id'];
        $year['day'] = $year['settings']['currentDay_id'] == 1 ? $year['day1'] : $year['day2'];
        $year['isStart'] = 1;

        $this->apiReturn($year);

        // todo: after V2.0 complete rollout: simplify: $this->apiReturn(array('isStart' => '1'));  // SIC!
        // todo: deprecated: after V2.0.1 complete rollout: function not needed anymore
    }

    public function updateTeamsCount(): void
    {
        $conn = ConnectionManager::get('default');
        /**
         * @var \Cake\Database\Connection $conn
         */
        $stmt = $conn->execute(file_get_contents(__DIR__ . "/sql/update_years_teamsCount.sql"));

        $this->apiReturn($stmt->rowCount());
    }

    public function all(): void
    {
        $settings = $this->getSettings();

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
        $year = $this->getCurrentYear();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            if ($this->getCurrentDayId() < $year->daysCount) {
                $currentDay_id = $this->fetchTable('Settings')->find('all')->where(['name' => 'currentDay_id'])->first();
                /**
                 * @var Setting $currentDay_id
                 */
                $currentDay_id->set('value', $this->getCurrentDayId() + 1);
                $this->fetchTable('Settings')->save($currentDay_id);

                $alwaysAutoUpdateResults = $this->fetchTable('Settings')->find('all')->where(['name' => 'alwaysAutoUpdateResults'])->first();
                /**
                 * @var Setting $alwaysAutoUpdateResults
                 */
                $alwaysAutoUpdateResults->set('value', 0);
                $this->fetchTable('Settings')->save($alwaysAutoUpdateResults);
            }
        }

        $this->apiReturn($year);
    }

    public function setAlwaysAutoUpdateResults(): void
    {
        $postData = $this->request->getData();
        $alwaysAutoUpdateResults = false;

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $alwaysAutoUpdateResults = $this->fetchTable('Settings')->find('all')->where(['name' => 'alwaysAutoUpdateResults'])->first();
            /**
             * @var Setting $alwaysAutoUpdateResults
             */
            $alwaysAutoUpdateResults->set('value', 1);
            $this->fetchTable('Settings')->save($alwaysAutoUpdateResults);

            $this->getCalcRanking();
        }

        $this->apiReturn($alwaysAutoUpdateResults);
    }

    public function showEndRanking(string $show = ''): void
    {
        $postData = $this->request->getData();
        $showEndRanking = false;

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $showEndRanking = $this->fetchTable('Settings')->find('all')->where(['name' => 'showEndRanking'])->first();
            /**
             * @var Setting $showEndRanking
             */
            $showEndRanking->set('value', (int)$show);
            $this->fetchTable('Settings')->save($showEndRanking);

            $this->getCalcRanking();
        }

        $this->apiReturn($showEndRanking);
    }

    // get Status of current Day
    public function getStatus(): void
    {
        $settings = $this->getSettings();
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

        $groups = $this->fetchTable('Groups')->find('all', array(
            'conditions' => $conditionsArray,
        ))->toArray();

        $groupTeams = $this->fetchTable('GroupTeams')->find('all', array(
            'contain' => array(
                'Groups' => array('fields' => array('year_id', 'day_id')),
            ),
            'conditions' => $conditionsArray,
        ))->toArray();

        $matches = $this->getMatches($conditionsArray, 0, 2, 1); // sortBy 2: get non-midday matches first
        $matchesPins = $this->getMatches(array_merge($conditionsArray, array('refereePIN IS NOT' => null)), 0, 0, 0);

        $sumCalcMatches = 0;
        $sumMatchesByTeam = array();
        $sumJobsByTeamByRound = array();
        $maxJobsByTeamPerRound = array(0);
        $sumJobsByTeamBy3Rounds = array();
        $maxJobsByTeamPer3Rounds = array(0);

        $matchesRefChangeable = array();
        $matchesTeamsChangeable = array();
        $missingRefereesCount = 0;
        $matchesWith1CanceledCount = 0;
        $matchResultCount = 0;

        if (is_array($matches)) {
            foreach ($groupTeams as $gt) {
                if ($gt->canceled == 0) {
                    $sumCalcMatches += $gt->calcCountMatches;
                }
            }

            foreach ($matches as $m) {
                // search for minimize missing referees
                if ($m->isRefereeCanceled && $m->refereeName == '' && !$m->canceled && $m->resultTrend === null) {
                    $missingRefereesCount++;

                    // search for available refs from same sport with canceled match
                    $conditionsArray = array('Groups.year_id' => $settings['currentYear_id'], 'Groups.day_id' => $settings['currentDay_id'], 'sport_id' => $m->sport_id, 'Matches.canceled >' => 0);
                    $matches1 = $this->getMatches($conditionsArray, 0, 3, 1); // sortBy 3: get midday matches first
                    if (is_array($matches1)) {
                        foreach ($matches1 as $m1) {
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
                                $matches2 = $this->getMatches($conditionsArray, 0, 0, 0);
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

                    // search for available teams from same group and same sport with canceled match
                    $conditionsArray = array('Groups.year_id' => $settings['currentYear_id'], 'Groups.day_id' => $settings['currentDay_id'], 'group_id' => $m->group_id, 'sport_id' => $m->sport_id, 'Matches.canceled >' => 0, 'Matches.canceled <' => 3, 'Matches.id !=' => $m->id);
                    $matches1 = $this->getMatches($conditionsArray, 0, 0, 1);
                    if (is_array($matches1)) {
                        foreach ($matches1 as $m1) {
                            // check if other team is already in play in same round with non-canceled match
                            $otherTeam = $m1->canceled == 1 ? $m1->team2_id : $m1->team1_id;
                            $conditionsArray = array('Groups.year_id' => $settings['currentYear_id'], 'Groups.day_id' => $settings['currentDay_id'], 'round_id' => $m->round_id, 'Matches.canceled' => 0,
                                'OR' => array(
                                    'team1_id' => $otherTeam,
                                    'team2_id' => $otherTeam,
                                    'refereeTeam_id' => $otherTeam,
                                    'refereeTeamSubst_id' => $otherTeam,
                                )
                            );
                            $matches2 = $this->getMatches($conditionsArray, 0, 0, 0);
                            if (!$matches2) {
                                $matchesTeamsChangeable[] = array($m, $m1);
                                break;
                            }
                        }
                    }
                }

                $matchResultCount += ($m->resultTrend !== null ? 1 : 0);

                $acv1 = array_count_values(array($m->team1_id, $m->team2_id));
                foreach ($acv1 as $k => $v) {
                    $sumMatchesByTeam[$k] ??= 0;
                    $sumMatchesByTeam[$k] += $v;
                }
                $acv2 = array_count_values(array_filter(array($m->team1_id, $m->team2_id, $m->refereeTeam_id, $m->refereeTeamSubst_id)));
                foreach ($acv2 as $k => $v) {
                    $sumJobsByTeamByRound[$m->round_id][$k] ??= 0;
                    $sumJobsByTeamByRound[$m->round_id][$k] += $v;

                    $sumJobsByTeamBy3Rounds[floor(($m->round_id - 1) / 3)][$k] ??= 0;
                    $sumJobsByTeamBy3Rounds[floor(($m->round_id - 1) / 3)][$k] += $v;
                }
            }
        }

        foreach ($sumJobsByTeamByRound as $k => $v) {
            $maxJobsByTeamPerRound[$k] = max($v);
        }
        foreach ($sumJobsByTeamBy3Rounds as $k => $v) {
            $maxJobsByTeamPer3Rounds[$k] = max($v);
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
        $status['matchResultCount'] = $matchResultCount;
        $status['minMatchesByTeam'] = !empty($sumMatchesByTeam) ? min($sumMatchesByTeam) : 0;
        $status['maxMatchesByTeam'] = !empty($sumMatchesByTeam) ? max($sumMatchesByTeam) : 0;
        $status['maxJobsByTeamPerRound'] = max($maxJobsByTeamPerRound);
        $status['maxJobsByTeamPer3Rounds'] = max($maxJobsByTeamPer3Rounds);

        $status['missingRefereesCount'] = $missingRefereesCount;
        $status['matchesRefChangeable'] = $matchesRefChangeable;

        $status['matchesWith1CanceledCount'] = $matchesWith1CanceledCount;
        $status['matchesTeamsChangeable'] = $matchesTeamsChangeable;

        $status['roundsWithPossibleLogsDelete'] = array_column($roundsWithPossibleLogsDelete, 'round_id');

        $this->apiReturn($status);
    }
}
