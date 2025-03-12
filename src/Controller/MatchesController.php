<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Group;
use App\Model\Entity\GroupTeam;
use App\Model\Entity\Match4;
use App\Model\Entity\Match4schedulingPattern;
use Cake\I18n\DateTime;

/**
 * Matches Controller
 *
 * @property \App\Model\Table\MatchesTable $Matches
 */
class MatchesController extends AppController
{
    public function byId(string $id = ''): void
    {
        $conditionsArray = array('Matches.id' => (int)$id);
        $match = $this->getMatches($conditionsArray, 1);

        $this->apiReturn($match);
    }

    public function byTeam(string $team_id = '', string $year_id = '', string $day_id = '', string $adminView = ''): void
    {
        $settings = $this->getSettings();
        $year_id = (int)$year_id ?: $settings['currentYear_id'];
        $day_id = (int)$day_id ?: $settings['currentDay_id'];

        $return = $this->getMatchesByTeam((int)$team_id, $year_id, $day_id, (int)$adminView);

        $return['currentRoundId'] = $this->getCurrentRoundId($year_id, $day_id);

        $this->apiReturn($return, $year_id, $day_id);
    }


    public function byReferee(): void
    {
        $return['matches'] = array();
        $postData = $this->request->getData();

        if (isset($postData['refereeName'])) {
            $conditionsArray = array(
                'refereeName LIKE' => '%' . $postData['refereeName'] . '%',
                'Groups.year_id' => $this->getCurrentYearId(),
                'Groups.day_id' => $this->getCurrentDayId(),
            );

            $return['matches'] = $this->getMatches($conditionsArray, 0, 0, 1);
        }

        $this->apiReturn($return);
    }

    public function byGroup(string $group_id = '', string $adminView = ''): void
    {
        $group = $this->getPrevAndNextGroup((int)$group_id);
        /**
         * @var Group|null $group
         */
        if ($group) {
            $showTime = $this->getScheduleShowTime($group->year_id, $group->day_id, (int)$adminView);
            if ($showTime !== 0) {
                $group['showTime'] = $showTime;
            } else {
                $group['rounds'] = $this->getMatchesByGroup($group);
                $group['currentRoundId'] = $this->getCurrentRoundId($group->year_id, $group->day_id);
            }
            $this->apiReturn($group, $group->year_id, $group->day_id);
        }

        $this->apiReturn(array());
    }

    public function pdfMatchesByGroup(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->getSettings();

            $groups = $this->fetchTable('Groups')->find('all', array(
                'fields' => array('id', 'name', 'year_id', 'day_id'),
                'conditions' => array('year_id' => $settings['currentYear_id'], 'day_id' => $settings['currentDay_id'], 'name !=' => 'Endrunde'),
                'order' => array('name' => 'ASC')
            ));

            $year = $this->getCurrentYear()->toArray();
            $day = DateTime::createFromFormat('Y-m-d H:i:s', $year['day' . $settings['currentDay_id']]->i18nFormat('yyyy-MM-dd HH:mm:ss'));

            foreach ($groups as $group) {
                $group['rounds'] = $this->getMatchesByGroup($group);
                $group['day'] = $day;
            }

            $this->viewBuilder()->setTemplatePath('pdf');
            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('groups', $groups);
            $this->viewBuilder()->setVar('year', $year);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }

    public function byRound(string $round_id = '', string $includeLogs = '', string $year_id = '', string $day_id = '', string $offset = ''): void
    {
        $round_id = (int)$round_id;
        $includeLogs = (int)$includeLogs;

        $settings = $this->getSettings();
        $year_id = (int)$year_id ?: $settings['currentYear_id'];
        $day_id = (int)$day_id ?: $settings['currentDay_id'];

        $adminView = $includeLogs; // sic! for admin and supervisor

        $showTime = $this->getScheduleShowTime($year_id, $day_id, $adminView);
        if ($showTime !== 0) {
            $return['showTime'] = $showTime;
        } else {
            $return['currentRoundId'] = $this->getCurrentRoundId($year_id, $day_id, (int)$offset);
            $round_id = $round_id > 0 ? $round_id : $return['currentRoundId'];
            $isPlayOffRound = $settings['usePlayOff'] > 0 && $round_id == $this->fetchTable('Rounds')->find('all')->count();

            $return['groups'] = $this->fetchTable('Groups')->find('all', array(
                'fields' => array('group_id' => 'id', 'group_name' => 'name', 'id', 'name'),
                'conditions' => array('year_id' => $year_id, 'day_id' => $day_id, 'name IN' => $isPlayOffRound ? array('Endrunde') : range('A', 'Z')),
                'order' => array('Groups.name' => 'ASC')
            ))->toArray();

            if ($round_id && count($return['groups']) > 0) {
                foreach ($return['groups'] as $group) {
                    /**
                     * @var Group $group
                     */
                    $conditionsArray = array(
                        'Groups.year_id' => $year_id,
                        'Groups.day_id' => $day_id,
                        'round_id' => $round_id,
                        'group_id' => $group['id'],
                    );

                    // use parameter for same sort as Excel: $sortBySportId=!$includeLogs
                    $group['matches'] = $this->getMatches($conditionsArray, $includeLogs, 1, $adminView);

                    if ($isPlayOffRound) {
                        $group['playOffTeams'] = $this->fetchTable('GroupTeams')->find('all', array(
                            'contain' => array(
                                'Groups' => array('fields' => array('year_id', 'day_id')),
                                'Teams' => array('fields' => array('id', 'name'))
                            ),
                            'conditions' => array('GroupTeams.canceled' => 0, 'Groups.year_id' => $settings['currentYear_id'], 'Groups.day_id' => $settings['currentDay_id']),
                            'order' => array('Groups.id' => 'ASC', 'GroupTeams.calcRanking' => 'ASC')
                        ))->limit(8);
                    }
                }

                $return['round'] = $this->fetchTable('Rounds')->find()->where(['id' => $round_id])->first();
            }

            if ($adminView) {
                $conditionsArray = array( // get last remarks
                    'Groups.year_id' => $year_id,
                    'Groups.day_id' => $day_id,
                    'round_id >' => $round_id - 2,
                    'remarks IS NOT' => null,
                );

                $return['remarks'] = $this->getMatches($conditionsArray, 0, 4, 1); // sortBy 4: get last matches first
            }
        }

        $this->apiReturn($return);
    }

    public function saveMatchTeamIds(string $id = ''): void
    {
        $id = (int)$id;
        $match = false;
        $postData = $this->request->getData();

        if (isset($postData['team1_id']) && isset($postData['team2_id']) && isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $conditionsArray = array('Matches.id' => $id);
            $matches = $this->getMatches($conditionsArray, 1);
            if (is_array($matches)) {
                $match = $matches[0];
                /**
                 * @var Match4 $match
                 */
                $match->set('team1_id', $postData['team1_id']);
                $match->set('team2_id', $postData['team2_id']);
                $this->Matches->save($match);
            }
        }

        $this->apiReturn($match);
    }

    public function saveRefereeName(string $id = ''): void
    {
        $id = (int)$id;
        $match = false;
        $postData = $this->request->getData();

        if (isset($postData['refereeName']) && isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $conditionsArray = array('Matches.id' => $id);
            $matches = $this->getMatches($conditionsArray, 1);
            if (is_array($matches)) {
                $match = $matches[0];
                /**
                 * @var Match4 $match
                 */
                $match->set('refereeName', $postData['refereeName']);
                $this->Matches->save($match);
            }
        }

        $this->apiReturn($match);
    }

    public function saveRefereeTeamSubst(string $id = ''): void
    {
        $id = (int)$id;
        $match = false;
        $postData = $this->request->getData();

        if (isset($postData['refereeTeamSubst_id']) && isset($postData['password']) && $this->checkUsernamePassword('supervisor', $postData['password'])) {
            $conditionsArray = array('Matches.id' => $id);
            $matches = $this->getMatches($conditionsArray, 1);
            if (is_array($matches)) {
                $match = $matches[0];
                /**
                 * @var Match4 $match
                 */
                $match->set('refereeTeamSubst_id', (int)$postData['refereeTeamSubst_id']);
                $this->Matches->save($match);
            }
        }

        $this->apiReturn($match);
    }

    public function getRankingRefereeSubst(): void
    {
        $query = $this->Matches->find('all', array(
            'contain' => array(
                'Teams4' => array('fields' => array('name')),
                'Groups'
            ),
            'conditions' => array(
                'Groups.year_id' => $this->getCurrentYearId(),
                'Groups.day_id' => $this->getCurrentDayId(),
                'Matches.refereeTeamSubst_id IS NOT' => null
            ),
        ));

        $teams = $query->select([
            'count' => $query->func()->count('*')
        ])
            ->groupBy('refereeTeamSubst_id')
            ->orderBy(array('count' => 'DESC'))
            ->toArray();

        $c = 0;
        foreach ($teams as $t) {
            $c++;
            $t['key'] = $c;
        }

        $return['teams'] = $teams;
        $this->apiReturn($return);
    }

    public function forceLogout(string $id = ''): void
    {
        $id = (int)$id;
        $match = false;
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('supervisor', $postData['password'])) {
            $conditionsArray = array('Matches.id' => $id);
            $matches = $this->getMatches($conditionsArray, 1);
            if (is_array($matches)) {
                $match = $matches[0];
                /**
                 * @var Match4 $match
                 */
                $newLog = $this->fetchTable('MatcheventLogs')->newEmptyEntity();
                $newLog->set('match_id', $match->id);
                $newLog->set('datetime', DateTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'));
                $newLog->set('matchEvent_id', $this->fetchTable('Matchevents')->find()->where(['code' => 'LOGOUT'])->first()->get('id'));
                $this->fetchTable('MatcheventLogs')->save($newLog);
            }
        }

        $this->apiReturn($match);
    }

    public function confirmMulti(): void
    {
        $return = array();
        $postData = $this->request->getData();

        if (isset($postData['matches']) && isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $matches = json_decode($postData['matches'], true);
            $count = count($matches);

            if ($count > 0) {
                $c = 0;
                foreach ($matches as $m) {
                    $c++;
                    $id = (int)$m['id'];
                    $mode = (int)$m['mode'];
                    $a = $id ? $this->getMatches(array('Matches.id' => $id), 1) : false;
                    $a = is_array($a) ? $a : false;
                    $match = $a ? $a[0] : false;
                    /**
                     * @var Match4|false $match
                     */

                    if ($match && $match->isTime2confirm && $this->isConfirmable($match, $match->logsCalc, $mode)) {
                        if ($mode == 1 && isset($postData['goals1']) && isset($postData['goals2']) && isset($postData['resultAdmin'])) {
                            $score1 = (int)$postData['goals1'];
                            $score2 = (int)$postData['goals2'];
                            $resultAdmin = (int)$postData['resultAdmin'];
                        } else {
                            $score1 = $match->logsCalc['score'][$match->team1_id] ?? 0;
                            $score2 = $match->logsCalc['score'][$match->team2_id] ?? 0;
                            $resultAdmin = 0;
                        }
                        // Goal factor
                        $factor = $this->fetchTable('Sports')->find()->where(['id' => $match->sport_id])->first()->get('goalFactor');

                        $rTrend = $match->logsCalc['teamWon'] ?? 0; // Mode 0: regular with same result (score and teamWon)

                        if ($mode == 1) { // like Score with not same result (score and teamWon)
                            $rTrend = $score1 - $score2 > 0 ? 1 : ($score1 - $score2 < 0 ? 2 : 0);
                        } else if ($mode == 2) { // like teamWon with not same result (score and teamWon)
                            $s1 = $rTrend == 1 ? max(array($score1, $score2)) : ($rTrend == 2 ? min(array($score1, $score2)) : $score1);
                            $s2 = $rTrend == 1 ? min(array($score1, $score2)) : ($rTrend == 2 ? max(array($score1, $score2)) : $score1);
                            $score1 = $s1;
                            $score2 = $s2;
                        } else if ($mode == 3) { // X:0-Wertung
                            $score1 = $this->getFactorsLeastCommonMultiple() / $factor;
                            $score2 = 0;
                            $rTrend = 3;
                        } else if ($mode == 4) { // 0:X-Wertung
                            $score1 = 0;
                            $score2 = $this->getFactorsLeastCommonMultiple() / $factor;
                            $rTrend = 4;
                        } else if ($mode == 5) { // X:X-Wertung
                            $score1 = 0;
                            $score2 = 0;
                            $rTrend = 5;
                        } else if ($mode == 6) { // 0:0-Wertung
                            $score1 = 0;
                            $score2 = 0;
                            $rTrend = 6;
                        }

                        $match->set('resultTrend', $rTrend);
                        $match->set('resultGoals1', (int)($score1 * $factor));
                        $match->set('resultGoals2', (int)($score2 * $factor));
                        $match->set('resultAdmin', (int)$resultAdmin);
                        $this->Matches->save($match);

                        // create event_log 'RESULT_CONFIRM'
                        $newLog = $this->fetchTable('MatcheventLogs')->newEmptyEntity();
                        $newLog->set('match_id', $match->id);
                        $newLog->set('datetime', DateTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'));
                        $newLog->set('matchEvent_id', $this->fetchTable('Matchevents')->find()->where(['code' => 'RESULT_CONFIRM'])->first()->get('id'));
                        $this->fetchTable('MatcheventLogs')->save($newLog);

                        if ($match->round->autoUpdateResults) {
                            $calcRanking = $this->getCalcRanking($match->team1_id, $match->team2_id, $c == $count);
                            $return[$c] = $match->toArray();
                            $return['calcRanking'][$c] = $calcRanking;
                        }
                    }
                }
            }
        }

        $this->apiReturn($return);
    }


    private function isConfirmable(\Cake\ORM\Entity $match, array $logsCalc, int $mode): bool
    {
        $settings = $this->getSettings();
        $group = $this->getGroupByMatchId($match->id);

        // only current Day
        if ($settings['currentYear_id'] == $group->year_id && $settings['currentDay_id'] == $group->day_id) {
            if ($mode == 0 && isset($logsCalc['teamWon'])) {
                /**
                 * @var Match4 $match
                 */
                // check plausibility
                if ($logsCalc['teamWon'] == 0 && ($logsCalc['score'][$match->team1_id] ?? 0) == ($logsCalc['score'][$match->team2_id] ?? 0)) {
                    return true;
                }
                if ($logsCalc['teamWon'] == 1 && ($logsCalc['score'][$match->team1_id] ?? 0) > ($logsCalc['score'][$match->team2_id] ?? 0)) {
                    return true;
                }
                if ($logsCalc['teamWon'] == 2 && ($logsCalc['score'][$match->team1_id] ?? 0) < ($logsCalc['score'][$match->team2_id] ?? 0)) {
                    return true;
                }
                return false;
            }

            // mode is not 0
            return true;
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function addAllFromSchedulingPattern(): void
    {
        $matches = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year = $this->getCurrentYear();

            $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId());
            $existingMatches = $this->getMatches($conditionsArray);

            if (!$existingMatches) {
                $teamYears = $this->fetchTable('TeamYears')->find('all', array(
                    'conditions' => array('year_id' => $year->id)
                ))->toArray();
                $teamYearsCanceled = $this->fetchTable('TeamYears')->find('all', array(
                    'conditions' => array('year_id' => $year->id, 'canceled' => 1)
                ))->toArray();

                if (count($teamYears) == $year->teamsCount) {
                    $settings = $this->getSettings();

                    $canceledTeamsArray = array();
                    if ($teamYearsCanceled) {
                        foreach ($teamYearsCanceled as $ty) {
                            $canceledTeamsArray[] = $ty->team_id;
                        }
                    }

                    $groups = $this->fetchTable('Groups')->find('all', array(
                        'conditions' => array('year_id' => $year->id, 'day_id' => $settings['currentDay_id']),
                        'order' => array('id' => 'ASC')
                    ));

                    $teamsPerGroup = $this->getTeamsCountPerGroup($year);
                    $tableName = 'MatchschedulingPattern' . $teamsPerGroup;
                    $matchschedulings = $this->fetchTable($tableName)->find('all', array(
                        'order' => array('id' => 'ASC')
                    ));

                    foreach ($groups as $group) {
                        if ($group->name != 'Endrunde') {
                            foreach ($matchschedulings as $matchscheduling) {
                                /**
                                 * @var Group $group
                                 * @var Match4schedulingPattern $matchscheduling
                                 */
                                $groupteam1 = $this->fetchTable('GroupTeams')->find('all', array(
                                    'conditions' => array('group_id' => $group->id, 'placeNumber' => $matchscheduling->placenumberTeam1)
                                ))->first();

                                $groupteam2 = $this->fetchTable('GroupTeams')->find('all', array(
                                    'conditions' => array('group_id' => $group->id, 'placeNumber' => $matchscheduling->placenumberTeam2)
                                ))->first();

                                $groupteam3 = $matchscheduling->placenumberRefereeTeam ? $this->fetchTable('GroupTeams')->find('all', array(
                                    'conditions' => array('group_id' => $this->getRefereeGroup($group)->id, 'placeNumber' => $matchscheduling->placenumberRefereeTeam)
                                ))->first() : null;

                                /**
                                 * @var GroupTeam $groupteam1
                                 * @var GroupTeam $groupteam2
                                 * @var GroupTeam|null $groupteam3
                                 */
                                $match = $this->Matches->newEmptyEntity();
                                $match->set('group_id', $group->id);
                                $match->set('round_id', $matchscheduling->round_id);
                                $match->set('sport_id', $matchscheduling->sport_id);
                                $match->set('team1_id', $groupteam1->team_id);
                                $match->set('team2_id', $groupteam2->team_id);
                                $match->set('refereeTeam_id', $groupteam3 ? $groupteam3->team_id : null);

                                if ($settings['useLiveScouting']) {
                                    $match->set('refereePIN', $this->createUniquePIN($matchscheduling->sport_id, $group->id, $matchscheduling->round_id));
                                }

                                $canceled = 0;
                                $canceled = in_array($groupteam1->team_id, $canceledTeamsArray) ? $canceled + 1 : $canceled;
                                $canceled = in_array($groupteam2->team_id, $canceledTeamsArray) ? $canceled + 2 : $canceled;
                                $match->set('canceled', $canceled);

                                if ($this->Matches->save($match)) {
                                    $matches[] = $match;
                                }
                            }
                        } else {
                            if ($settings['usePlayOff'] > 0) {
                                for ($i = 0; $i < $settings['usePlayOff']; $i++) {
                                    $match = $this->Matches->newEmptyEntity();
                                    $match->set('group_id', $group->id);
                                    $match->set('round_id', $this->fetchTable('Rounds')->find('all')->count()); // last Round
                                    $match->set('sport_id', 5); // sport=multi
                                    $match->set('isPlayOff', $this->getPlayOffNumber($i, $settings['usePlayOff'], $year->id));

                                    if ($this->Matches->save($match)) {
                                        $matches[] = $match;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $checkings = array(
            'matchesCount' => count($matches) ?: 0
        );

        $this->apiReturn($checkings);
    }

    private function getPlayOffNumber(int $i, int $use, int $year_id): int
    {
        $number = 0;

        if ($i < $use / 2) { // Quarter-Finale
            $number = $use; // 4
        }
        if ($i == $use - 2) { // Third-Place-Match
            $number = 3;
        }
        if ($i == $use - 1) { // Finale
            $number = 2;
        }

        return (int)($year_id . $number);
    }

    /**
     * @throws \Exception
     */
    private function createUniquePIN(int $sportId, int $groupId, int $roundId): int
    {
        $str1 = (9 - $sportId) - 4 * random_int(0, 1);
        $str2 = 9 - $this->getGroupPosNumber($groupId);
        $str34 = str_pad((string)($roundId + 16 * random_int(0, 5)), 2, "0", STR_PAD_LEFT);
        $str5 = ($this->getCurrentDayId() - 1) + 2 * random_int(0, 4);

        return (int)($str1 . $str2 . $str34 . $str5);
    }

    public function getPIN(int $id): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('supervisor', $postData['password'])) {
            $match = $this->Matches->find()->where(['id' => $id])->first();
            $this->apiReturn($match);
        }
    }


    public function refereeCanceledMatches(): void
    {
        $return['matches'] = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('supervisor', $postData['password'])) {
            $conditionsArray = array(
                'resultTrend IS' => null,
                'canceled' => 0,
                'Groups.year_id' => $this->getCurrentYearId(),
                'Groups.day_id' => $this->getCurrentDayId(),
            );

            $matches = $this->getMatches($conditionsArray, 0, 0, 1);

            if (is_array($matches)) {
                foreach ($matches as $m) {
                    if ($m->isRefereeCanceled) {
                        $return['matches'][] = $m;
                    }
                }
            }
        }

        $this->apiReturn($return);
    }

    // change ref from canceled team (match_id1) with ref from non-canceled team (match_id2)
    public function changeReferees(int $id1, int $id2): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $conditionsArray = array('Matches.id' => $id1);
            $m = $this->getMatches($conditionsArray, 0, 0, 1); // needed to access adminView field 'isRefereeCanceled'
            if (is_array($m)) {
                $match1 = $m[0];
                $match2 = $this->Matches->find()->where(['id' => $id2])->first();

                /**
                 * @var Match4 $match1
                 * @var Match4 $match2
                 */
                if ($match1->isRefereeCanceled) {
                    $ref2 = $match2->refereeTeam_id;
                    $match1->set('refereeTeam_id', $ref2);
                    $match2->set('refereeTeam_id', null); // referee is from canceled team -> better not to set (unique key)
                    $this->Matches->save($match1);
                    $this->Matches->save($match2);
                }
                $this->apiReturn($match1);
            }
        }
    }

    // change canceled team (match_id1) with non-canceled team (match_id2)
    public function changeTeams(int $id1, int $id2): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $match1 = $this->Matches->find()->where(['id' => $id1])->first();
            $match2 = $this->Matches->find()->where(['id' => $id2])->first();

            /**
             * @var Match4|null $match1
             * @var Match4|null $match2
             */
            if ($match1 && $match2 && in_array($match1->canceled, array(1, 2)) && in_array($match2->canceled, array(1, 2))) {
                $t1 = $match1->canceled == 1 ? $match1->team1_id : $match1->team2_id; // canceled team
                $t2 = $match2->canceled == 1 ? $match2->team2_id : $match2->team1_id; // non-canceled team
                $match1->set(($match1->canceled == 1 ? 'team1_id' : 'team2_id'), $t2); // set to non-canceled team
                $match2->set(($match2->canceled == 1 ? 'team2_id' : 'team1_id'), $t1); // set to canceled team
                $match1->set('canceled', 0);
                $match2->set('canceled', 3);
                $match2->set('refereeTeam_id', null); // referee not needed anymore: open round for this ref for another match
                $this->Matches->save($match1);
                $this->Matches->save($match2);
            }
            $this->apiReturn($match1);
        }
    }

    /**
     * @throws \Exception
     */
    public function insertTestResults(): void
    {
        $matches = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->getSettings();

            if ($settings['isTest'] ?? 0) {
                $conditionsArray = array(
                    'Groups.year_id' => $this->getCurrentYearId(),
                    'Groups.day_id' => $this->getCurrentDayId(),
                );

                $matches = $this->getMatches($conditionsArray, 1);
                if (is_array($matches) && count($matches) > 0) {
                    foreach ($matches as $m) {
                        /**
                         * @var Match4 $m
                         */
                        if ($m->team1_id && $m->team2_id && $m->team1 && $m->team2) {
                            if ($m->resultTrend === null || $m->resultGoals1 === null || $m->resultGoals1 === null) {
                                $factor1 = ($m->team1)->calcTotalPointsPerYear ? (int)($m->team1)->calcTotalPointsPerYear / 7 : 1;
                                $factor2 = ($m->team2)->calcTotalPointsPerYear ? (int)($m->team2)->calcTotalPointsPerYear / 7 : 1;
                                $sportsFactor = $m->sport->goalFactor;

                                $m->set('resultGoals1', (int)round(random_int(0, 44) / $sportsFactor * $factor1) * $sportsFactor);
                                $m->set('resultGoals2', (int)round(random_int(0, 44) / $sportsFactor * $factor2) * $sportsFactor);

                                $diff = (int)($m->resultGoals1 - $m->resultGoals2);
                                $m->set('resultTrend', $diff > 0 ? 1 : ($diff < 0 ? 2 : 0));
                                $this->Matches->save($m);
                            }
                        }
                    }

                    $this->getCalcRanking();
                }
            }
        }

        $matchesCount = is_array($matches) ? count($matches) : false;
        $this->apiReturn($matchesCount);
    }

    private function getDayIdByGroupId(int $group_id): int|bool
    {
        $group = $this->fetchTable('Groups')->find()->where(['id' => $group_id])->first();
        /**
         * @var Group $group
         */
        return $group->get('day_id');
    }

    private function getYearIdByGroupId(int|bool $group_id): int|bool
    {
        $group = $this->fetchTable('Groups')->find()->where(['id' => $group_id])->first();
        /**
         * @var Group $group
         */
        return $group->get('year_id');
    }
}

