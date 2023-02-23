<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Group;
use App\Model\Entity\Match;
use App\Model\Entity\MatchschedulingPattern16;
use App\Model\Entity\Round;
use App\Model\Entity\Year;
use Cake\I18n\FrozenTime;

/**
 * Matches Controller
 *
 * @property \App\Model\Table\MatchesTable $Matches
 */
class MatchesController extends AppController
{
    public function index()
    {
        $matches = false;

        $this->apiReturn($matches);
    }

    public function byId($id = false)
    {
        $conditionsArray = array('Matches.id' => $id);
        $match = $this->getMatches($conditionsArray, 1);

        $this->apiReturn($match);
    }

    public function byTeam($team_id = false, $year_id = false, $day_id = false, $adminView = 0)
    {
        $year_id = $year_id ?: $this->getCurrentYearId();
        $day_id = $day_id ?: $this->getCurrentDayId();

        $return = $this->getMatchesByTeam($team_id, $year_id, $day_id, $adminView);

        $this->apiReturn($return, $year_id, $day_id);
    }

    public function byGroup($group_id = false, $adminView = 0)
    {
        $group = $this->getPrevAndNextGroup($group_id);

        if ($group) {
            $showTime = $this->getScheduleShowTime($group->year_id, $group->day_id, $adminView);
            if ($showTime !== 0) {
                $group['showTime'] = $showTime;
            } else {
                $group['rounds'] = $this->getMatchesByGroup($group);
            }
            $this->apiReturn($group, $group->year_id, $group->day_id);
        }

        $this->apiReturn(array());
    }

    private function getMatchesByGroup($group): array
    {
        $this->loadModel('Rounds');
        $rounds = $this->Rounds->find('all', array(
            'fields' => array('id', 'timeStartDay' . $group->day_id, 'autoUpdateResults'),
            'order' => array('id' => 'ASC')
        ))->toArray();

        if (count($rounds) > 0) {
            foreach ($rounds as $round) {
                /**
                 * @var Round $round
                 */
                $conditionsArray = array(
                    'group_id' => $group->id,
                    'round_id' => $round->id,
                );

                $round['matches'] = $this->getMatches($conditionsArray);
            }
        }

        return $rounds;
    }

    public function pdfMatchesByGroup()
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $this->loadModel('Groups');
            $groups = $this->Groups->find('all', array(
                'fields' => array('id', 'name', 'year_id', 'day_id'),
                'conditions' => array('year_id' => $this->getCurrentYearId(), 'day_id' => $this->getCurrentDayId()),
                'order' => array('name' => 'ASC')
            ));

            $settings = $this->getSettings();
            $currentYear = $this->getCurrentYear()->toArray();
            $day = FrozenTime::createFromFormat('Y-m-d H:i:s', $currentYear['day' . $settings['currentDay_id']]->i18nFormat('yyyy-MM-dd HH:mm:ss'));

            foreach ($groups as $group) {
                $group['rounds'] = $this->getMatchesByGroup($group);
                $group['day'] = $day;
            }

            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('groups', $groups);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }

    public function byRound($round_id = false, $includeLogs = 0, $year_id = false, $day_id = false)
    {
        $adminView = $includeLogs; // sic! for admin and supervisor
        $year_id = $year_id ?: $this->getCurrentYearId();
        $day_id = $day_id ?: $this->getCurrentDayId();

        $showTime = $this->getScheduleShowTime($year_id, $day_id, $adminView);
        if ($showTime !== 0) {
            $return['showTime'] = $showTime;
        } else {

            $this->loadModel('Groups');
            $return['groups'] = $this->Groups->find('all', array(
                'fields' => array('group_id' => 'id', 'group_name' => 'name', 'id', 'name'),
                'conditions' => array('year_id' => $year_id, 'day_id' => $day_id),
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

                    $group['matches'] = $this->getMatches($conditionsArray, $includeLogs, !$includeLogs, $includeLogs);
                }

                $this->loadModel('Rounds');
                $return['round'] = $this->Rounds->find()->where(['id' => $round_id])->first();
            }
        }

        $this->apiReturn($return);
    }

    public
    function saveRefereeTeamSubst($id = false)
    {
        $match = false;
        $postData = $this->request->getData();

        if (isset($postData['refereeTeamSubst_id']) && isset($postData['password']) && $this->checkUsernamePassword('supervisor', $postData['password'])) {
            $conditionsArray = array('Matches.id' => $id);
            $match = $id ? ($this->getMatches($conditionsArray, 1))[0] : false;

            if ($match) {
                $match->set('refereeTeamSubst_id', (int)$postData['refereeTeamSubst_id']);
                $this->Matches->save($match);
            }
        }

        $this->apiReturn($match);
    }

    public
    function getRankingRefereeSubst()
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
            ->group('refereeTeamSubst_id')
            ->order(array('count' => 'DESC'))
            ->toArray();

        $c = 0;
        foreach ($teams as $t) {
            $c++;
            $t['key'] = $c;
        }

        $return['teams'] = $teams;
        $this->apiReturn($return);
    }

    public
    function forceLogout($id = false)
    {
        $match = false;
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('supervisor', $postData['password'])) {
            $conditionsArray = array('Matches.id' => $id);
            $match = $id ? ($this->getMatches($conditionsArray, 1))[0] : false;

            if ($match) {
                $this->loadModel('Matchevents');
                $newLog = $this->MatcheventLogs->newEmptyEntity();
                $newLog->set('match_id', $match->id);
                $newLog->set('datetime', FrozenTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'));
                $newLog->set('matchEvent_id', $this->Matchevents->find()->where(['code' => 'LOGOUT'])->first()->get('id'));
                $this->MatcheventLogs->save($newLog);
            }
        }

        $this->apiReturn($match);
    }


    public
    function confirm($id = false)
    {
        $match = false;
        $postData = $this->request->getData();

        if (isset($postData['mode']) && isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $mode = $postData['mode'];

            $conditionsArray = array('Matches.id' => $id);
            $match = $id ? ($this->getMatches($conditionsArray, 1))[0] : false;

            if ($match && $match->isTime2confirm && $this->isConfirmable($match, $match->logsCalc, $mode)) {
                if ($mode == 1 && isset($postData['goals1']) && isset($postData['goals2'])) {
                    $score1 = $postData['goals1'];
                    $score2 = $postData['goals2'];
                } else {
                    $score1 = $match->logsCalc['score'][$match->team1_id] ?? 0;
                    $score2 = $match->logsCalc['score'][$match->team2_id] ?? 0;
                }
                // Goal factor
                $this->loadModel('Sports');
                $factor = $this->Sports->find()->where(['id' => $match->sport_id])->first()->get('goalFactor');

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

                $this->Matches->save($match);

                // create event_log 'result confirmed'
                $this->loadModel('Matchevents');
                $newLog = $this->MatcheventLogs->newEmptyEntity();
                $newLog->set('match_id', $match->id);
                $newLog->set('datetime', FrozenTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'));
                $newLog->set('matchEvent_id', $this->Matchevents->find()->where(['code' => 'RESULT_CONFIRM'])->first()->get('id'));
                $this->MatcheventLogs->save($newLog);

                if ($match->round->autoUpdateResults) {
                    $calcRanking = $this->getCalcRanking($match->team1_id, $match->team2_id);
                    $match = $match->toArray();
                    $match['calcRanking'] = $calcRanking;
                }
            }
        }

        $this->apiReturn($match);
    }


    private
    function isConfirmable($match, $logsCalc, $mode)
    {
        /**
         * @var Group $group
         * @var Match $match
         * @var Year $year
         */

        $year = $this->getCurrentYear();
        $group = $this->getGroupByMatchId($match->id);

        // only current Year
        if ($group && $year->id == $group->year_id) {
            if ($mode == 0 && isset($logsCalc['teamWon'])) {
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


    public
    function addAllFromSchedulingPattern()
    {
        $matches = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year = $this->getCurrentYear();
            /**
             * @var Year $year
             */

            $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId());
            $existingMatches = $this->getMatches($conditionsArray);

            if (!$existingMatches) {
                $this->loadModel('Groups');
                $this->loadModel('GroupTeams');
                $this->loadModel('MatchschedulingPattern16');

                $this->loadModel('TeamYears');
                $teamYears = $this->TeamYears->find('all', array(
                    'conditions' => array('year_id' => $year->id)
                ))->toArray();
                $teamYearsCanceled = $this->TeamYears->find('all', array(
                    'conditions' => array('year_id' => $year->id, 'canceled' => 1)
                ))->toArray();

                if (count($teamYears) == $year->teamsCount) {
                    $canceledTeamsArray = array();
                    if ($teamYearsCanceled) {
                        foreach ($teamYearsCanceled as $ty) {
                            $canceledTeamsArray[] = $ty->team_id;
                        }
                    }

                    $groups = $this->Groups->find('all', array(
                        'conditions' => array('year_id' => $year->id, 'day_id' => $this->getCurrentDayId()),
                        'order' => array('id' => 'ASC')
                    ));

                    $matchschedulings = $this->MatchschedulingPattern16->find('all', array(
                        'order' => array('id' => 'ASC')
                    ));

                    foreach ($groups as $group) {
                        /**
                         * @var Group $group
                         */
                        foreach ($matchschedulings as $matchscheduling) {
                            /**
                             * @var MatchschedulingPattern16 $matchscheduling
                             */
                            $groupteam1 = $this->GroupTeams->find('all', array(
                                'conditions' => array('group_id' => $group->id, 'placeNumber' => $matchscheduling->placenumberTeam1)
                            ))->first();

                            $groupteam2 = $this->GroupTeams->find('all', array(
                                'conditions' => array('group_id' => $group->id, 'placeNumber' => $matchscheduling->placenumberTeam2)
                            ))->first();

                            $groupteam3 = $this->GroupTeams->find('all', array(
                                'conditions' => array('group_id' => $this->getRefereeGroup($group)->id, 'placeNumber' => $matchscheduling->placenumberRefereeTeam)
                            ))->first();

                            $match = $this->Matches->newEmptyEntity();
                            $match->set('group_id', $group->id);
                            $match->set('round_id', $matchscheduling->round_id);
                            $match->set('sport_id', $matchscheduling->sport_id);
                            $match->set('team1_id', $groupteam1->team_id);
                            $match->set('team2_id', $groupteam2->team_id);
                            $match->set('refereeTeam_id', $groupteam3->team_id);
                            $match->set('refereePIN', $this->createUniquePIN($matchscheduling->sport_id, $group->id, $matchscheduling->round_id));

                            $canceled = 0;
                            $canceled = in_array($groupteam1->team_id, $canceledTeamsArray) ? $canceled + 1 : $canceled;
                            $canceled = in_array($groupteam2->team_id, $canceledTeamsArray) ? $canceled + 2 : $canceled;
                            $match->set('canceled', $canceled);

                            if ($this->Matches->save($match)) {
                                $matches[] = $match;
                            }
                        }
                    }
                }
            }
        }

        $matchesCount = count($matches) ? count($matches) : false;

        $this->apiReturn($matchesCount);
    }

    private
    function createUniquePIN($sportId, $groupId, $roundId)
    {
        $str1 = (9 - $sportId) - 4 * random_int(0, 1);
        $str2 = 9 - $this->getGroupPosNumber($groupId);
        $str34 = str_pad((string)($roundId + 16 * random_int(0, 5)), 2, "0", STR_PAD_LEFT);
        $str5 = ($this->getCurrentDayId() - 1) + 2 * random_int(0, 4);

        return (int)($str1 . $str2 . $str34 . $str5);
    }

    public function getPIN($id)
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('supervisor', $postData['password'])) {
            $match = $this->Matches->find()->where(['id' => $id])->first();
            $this->apiReturn($match);
        }
    }


    public function refereeCanceledMatches()
    {
        $return['matches'] = array();

        $conditionsArray = array(
            'resultTrend IS' => null,
            'canceled' => 0,
            'Groups.year_id' => $this->getCurrentYearId(),
            'Groups.day_id' => $this->getCurrentDayId(),
        );

        $this->loadModel('Matches');
        $matches = $this->getMatches($conditionsArray, 0, 0, 1);

        foreach ($matches as $m) {
            if ($m->isRefereeCanceled) {
                $return['matches'][] = $m;
            }
        }

        $this->apiReturn($return);
    }

    // change ref from canceled team (match_id1) with ref from non-canceled team (match_id2)
    public function changeReferees($id1, $id2)
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $conditionsArray = array(
                'Matches.id' => $id1,
                'resultTrend IS' => null,
                'canceled' => 0,
                'Groups.year_id' => $this->getCurrentYearId(),
                'Groups.day_id' => $this->getCurrentDayId(),
            );

            $this->loadModel('Matches');
            $match1 = ($this->getMatches($conditionsArray, 0, 0, 1))[0];
            $match2 = $this->Matches->find()->where(['id' => $id2])->first();

            /**
             * @var Match $match1
             * @var Match $match2
             */
            if ($match1 && $match1->isRefereeCanceled && $match2) {
                $ref2 = $match2->refereeTeam_id;
                $match1->set('refereeTeam_id', $ref2);
                $match2->set('refereeTeam_id', null); // referee is from canceled team -> better not to set (unique key)
                $this->Matches->save($match1);
                $this->Matches->save($match2);
            }
            $this->apiReturn($match1);
        }
    }

    // change canceled team (match_id1) with non-canceled team (match_id2)
    public function changeTeams($id1, $id2)
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $match1 = $this->Matches->find()->where(['id' => $id1])->first();
            $match2 = $this->Matches->find()->where(['id' => $id2])->first();

            /**
             * @var Match $match1
             * @var Match $match2
             */
            if ($match1 && $match2 && in_array($match1->canceled, array(1, 2)) && in_array($match2->canceled, array(1, 2))) {
                $t1 = $match1->canceled == 1 ? $match1->team1_id : $match1->team2_id;
                $t2 = $match2->canceled == 1 ? $match2->team2_id : $match2->team1_id;
                $match1->set($match1->canceled == 1 ? 'team1_id' : 'team2_id', $t2);
                $match2->set($match2->canceled == 1 ? 'team2_id' : 'team1_id', $t1);
                $match1->set('canceled', 0);
                $match2->set('canceled', 3);
                $match2->set('refereeTeam_id', null); // referee not needed anymore: open round for this ref for another match
                $this->Matches->save($match1);
                $this->Matches->save($match2);
            }
            $this->apiReturn($match1);
        }
    }

    public
    function insertTestResults()
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
                if ($matches && count($matches) > 0) {
                    foreach ($matches as $m) {
                        /**
                         * @var Match $m
                         */
                        if ($m->resultTrend === null || $m->resultGoals1 === null || $m->resultGoals1 === null) {
                            $factor1 = ($m->team1) && ($m->team1)->calcTotalPointsPerYear ? ($m->team1)->calcTotalPointsPerYear / 7 : 1;
                            $factor2 = ($m->team2) && ($m->team2)->calcTotalPointsPerYear ? ($m->team2)->calcTotalPointsPerYear / 7 : 1;
                            $sportsFactor = $m->sport->goalFactor;

                            $m->set('resultGoals1', (int)round(random_int(0, 44) / $sportsFactor * $factor1) * $sportsFactor);
                            $m->set('resultGoals2', (int)round(random_int(0, 44) / $sportsFactor * $factor2) * $sportsFactor);

                            $diff = (int)($m->resultGoals1 - $m->resultGoals2);
                            $m->set('resultTrend', $diff > 0 ? 1 : ($diff < 0 ? 2 : 0));
                            $this->Matches->save($m);
                        }
                    }

                    $this->getCalcRanking();
                }
            }
        }

        $matchesCount = count($matches) ?: false;
        $this->apiReturn($matchesCount);
    }

    private
    function getDayIdByGroupId($group_id)
    {
        $this->loadModel('Groups');

        $group = $group_id ? $this->Groups->find()->where(['id' => $group_id])->first() : false;

        return $group ? $group->get('day_id') : false;
    }

    private
    function getYearIdByGroupId($group_id)
    {
        $this->loadModel('Groups');

        $group = $group_id ? $this->Groups->find()->where(['id' => $group_id])->first() : false;

        return $group ? $group->get('year_id') : false;
    }

}

