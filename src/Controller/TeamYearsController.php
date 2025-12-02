<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Group;
use App\Model\Entity\GroupTeam;
use App\Model\Entity\Match4;
use App\Model\Entity\Match4eventLog;
use App\Model\Entity\Setting;
use App\Model\Entity\TeamYear;
use Cake\I18n\DateTime;

/**
 * TeamYears Controller
 *
 * @property \App\Model\Table\TeamYearsTable $TeamYears
 * @property \App\Controller\Component\CalcComponent $Calc
 * @property \App\Controller\Component\GroupGetComponent $GroupGet
 * @property \App\Controller\Component\MatchGetComponent $MatchGet
 * @property \App\Controller\Component\PlayOffComponent $PlayOff
 * @property \App\Controller\Component\ScrRankingComponent $ScrRanking
 * @property \App\Controller\Component\SecurityComponent $Security
 */
class TeamYearsController extends AppController
{
    // getCurrentTeams
    public function all(): void
    {
        $year = $this->Cache->getCurrentYear();

        $teamYears = $this->TeamYears->find('all', array(
            'fields' => array('id', 'team_id', 'canceled'),
            'conditions' => array('year_id' => $year->id, 'OR' => array('Teams.hidden' => 0, 'canceled' => 0)),
            'contain' => array('Teams' => array('fields' => array('name'))),
            'order' => array('Teams.name' => 'ASC')
        ))->toArray();

        $this->apiReturn($teamYears);
    }

    public function allWithPushTokenCount(): void
    {
        $year = $this->Cache->getCurrentYear();

        $teamYears = $this->TeamYears->find('all', array(
            'fields' => array('id', 'team_id', 'canceled'),
            'conditions' => array('year_id' => $year->id, 'OR' => array('Teams.hidden' => 0, 'canceled' => 0)),
            'contain' => array('Teams' => array('fields' => array('name'))),
            'order' => array('Teams.name' => 'ASC')
        ))->toArray();

        foreach ($teamYears as $ty) {
            $ty['countPushTokens'] = $this->fetchTable('PushTokens')->find()->where(['my_team_id' => $ty['team_id']])->count();
        }

        $this->apiReturn($teamYears);
    }

    public function pdfAllTeamsMatches(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();

            $teamYears = $this->TeamYears->find('all', array(
                'fields' => array('id', 'team_id', 'year_id', 'refereePIN', 'canceled'),
                'conditions' => array('year_id' => $settings['currentYear_id']),
                'contain' => array('Teams' => array('fields' => array('name'))),
                'order' => array('Teams.name' => 'ASC')
            ))->toArray();

            foreach ($teamYears as $ty) {
                $ty['infos'] = $this->MatchGet->getMatchesByTeam($ty['team_id'], $settings['currentYear_id'], $settings['currentDay_id'], 1);
            }

            $this->viewBuilder()->setTemplatePath('pdf');
            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('teamYears', $teamYears);
            $this->viewBuilder()->setVar('settings', $settings);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }

    public function pdfAllTeamsMatchesWithGroupMatches(string $offset = ''): void
    {
        $offset = (int)$offset;
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year = $this->Cache->getCurrentYear()->toArray();

            $groups = $this->fetchTable('Groups')->find('all', array(
                'fields' => array('id', 'name', 'year_id', 'day_id'),
                'conditions' => array('year_id' => $settings['currentYear_id'], 'day_id' => $settings['currentDay_id']),
                'order' => array('name' => 'ASC')
            ))->toArray();

            foreach ($groups as $group) {
                /**
                 * @var Group $group
                 */
                $group['rounds'] = $this->MatchGet->getMatchesByGroup($group->toArray());
            }

            $teamYears = $this->TeamYears->find('all', array(
                'fields' => array('id', 'team_id', 'year_id', 'refereePIN', 'canceled'),
                'conditions' => array('year_id' => $settings['currentYear_id']),
                'contain' => array('Teams' => array('fields' => array('name'))),
                'order' => array('Teams.name' => 'ASC')
            ))->offset($offset * 32)->limit(32); // set limit because of execution timeout

            foreach ($teamYears as $ty) {
                /**
                 * @var TeamYear $ty
                 */
                $g = $this->GroupGet->getGroupByTeamId($ty->team_id, $settings['currentYear_id'], $settings['currentDay_id']);
                $gN = $this->GroupGet->getGroupPosNumber($g->id);

                $ty['infos'] = $this->MatchGet->getMatchesByTeam($ty->team_id, $settings['currentYear_id'], $settings['currentDay_id'], 1);
                $ty['day'] = DateTime::createFromFormat('Y-m-d H:i:s', $year['day' . $settings['currentDay_id']]->i18nFormat('yyyy-MM-dd HH:mm:ss'));
                $ty['group'] = $groups[$gN];
            }

            $this->viewBuilder()->setTemplatePath('pdf');
            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('teamYears', $teamYears);
            $this->viewBuilder()->setVar('settings', $settings);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }

    public function cancel(string $id = '', string $undo = ''): void
    {
        $id = (int)$id;
        $undo = (int)$undo;
        $teamYear = false;
        $postData = $this->request->getData();

        if ($id && isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year_id = $settings['currentYear_id'];
            $day_id = $settings['currentDay_id'];

            $teamYear = $this->TeamYears->find('all', array(
                'conditions' => array('id' => $id, 'year_id' => $year_id)
            ))->first();

            if ($teamYear) {
                /**
                 * @var TeamYear $teamYear
                 */
                $teamYear->set('canceled', $undo ? 0 : 1);
                $this->TeamYears->save($teamYear);

                $groupTeam = $this->fetchTable('GroupTeams')->find('all', array(
                    'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
                    'conditions' => array('team_id' => $teamYear->team_id, 'Groups.year_id' => $year_id, 'Groups.day_id' => $day_id),
                ))->first();

                if ($groupTeam) {
                    /**
                     * @var GroupTeam $groupTeam
                     */
                    $groupTeam->set('canceled', $undo ? 0 : 1);
                    $this->fetchTable('GroupTeams')->save($groupTeam);

                    $this->Calc->getCalcRanking($teamYear->team_id);
                }

                // cancel matches:
                $conditionsArray = array(
                    'resultTrend IS' => null,
                    'Groups.year_id' => $year_id,
                    'Groups.day_id' => $day_id,
                    'OR' => array(
                        'team1_id' => $teamYear->team_id,
                        'team2_id' => $teamYear->team_id,
                    )
                );
                $matches = $this->fetchTable('Matches')->find('all', array(
                    'contain' => array('Groups'),
                    'conditions' => $conditionsArray
                ))->all();

                foreach ($matches as $m) {
                    /**
                     * @var Match4 $m
                     */
                    $a = $m->team1_id == $teamYear->team_id ? 1 : 2;
                    $canceled = $undo && $m->canceled ? $m->canceled - $a : (!$undo ? $a + $m->canceled : $m->canceled);

                    $m->set('canceled', $canceled);
                    $this->fetchTable('Matches')->save($m);
                }
            }
        }

        $this->apiReturn($teamYear);
    }

    // deprecated! use instead:  matches/refereeCanceledMatches
    public function refereeCanceledTeamsMatches(): void
    {
        $settings = $this->Cache->getSettings();
        $return['matches'] = array();

        $teamYears = $this->TeamYears->find('all', array(
            'conditions' => array('canceled' => 1, 'year_id' => $settings['currentYear_id'])
        ))->toArray();

        if ($teamYears) {
            foreach ($teamYears as $ty) {
                $day_id = $settings['currentDay_id'];

                $conditionsArray = array(
                    'resultTrend IS' => null,
                    'canceled' => 0,
                    'Groups.year_id' => $ty->get('year_id'),
                    'Groups.day_id' => $day_id,
                    'OR' => array(
                        'refereeTeam_id' => $ty->get('team_id'),
                        'refereeTeam_id IS' => null,
                    )
                );

                $matches = $this->MatchGet->getMatches($conditionsArray, 0, 1, 1);

                if (is_array($matches)) {
                    $return['matches'] = array_merge($return['matches'], $matches);
                }
            }

            usort($return['matches'], function ($a, $b) {
                return $a['matchStartTime'] <=> $b['matchStartTime'];
            });
        }

        $this->apiReturn($return);
    }

    public function getEndRanking(string $year_id = '', string $adminView = ''): void
    {
        $settings = $this->Cache->getSettings();
        $year_id = (int)$year_id ?: $settings['currentYear_id'];
        $adminView = (int)$adminView;

        $teamYears = $this->TeamYears->find('all', array(
            'fields' => array('id', 'endRanking', 'team_id'),
            'contain' => array('Teams' => array('fields' => array('team_name' => 'name'))),
            'conditions' => array('year_id' => $year_id, 'Teams.hidden' => 0),
            'order' => array('endRanking' => 'ASC', 'team_name' => 'ASC')
        ))->toArray();

        if (!$adminView && $year_id == $settings['currentYear_id']) {
            $showEndRanking = $this->fetchTable('Settings')->find('all')->where(['name' => 'showEndRanking'])->first();
            /**
             * @var Setting $showEndRanking
             */
            if ($showEndRanking->value == 0) {
                $teamYears = null;
                $teamYears['showRanking'] = 0;
            }
        }

        $this->apiReturn($teamYears, $year_id);
    }

    /**
     * @throws \Exception
     */
    public function pdfEndRanking(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year = $this->Cache->getCurrentYear()->toArray();
            $teamYears = $this->TeamYears->find('all', array(
                'contain' => array('Teams'),
                'conditions' => array('year_id' => $year['id']),
                'order' => array('endRanking' => 'DESC')
            ))->toArray();

            foreach ($teamYears as $ty) {
                $ty['group_team'] = $this->fetchTable('GroupTeams')->find('all', array(
                    'contain' => array('Groups' => array('fields' => array('name', 'year_id', 'day_id'))),
                    'conditions' => array('team_id' => $ty['team_id'], 'Groups.year_id' => $year['id'], 'Groups.day_id' => $settings['currentDay_id']),
                    'order' => array('GroupTeams.id' => 'DESC')
                ))->first()->toArray();
            }

            $this->viewBuilder()->setTemplatePath('pdf');
            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('teamYears', $teamYears);
            $this->viewBuilder()->setVar('year', $year);

            if ($settings['useScoutRatings']) {
                $scrRanking = $this->ScrRanking->getScrRanking($year['id'], 3, 'DESC');
                $this->viewBuilder()->setVar('scrRankingTeams', $scrRanking['teamYears']);
            }

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }

    public function setEndRanking(): void
    {
        $postData = $this->request->getData();
        $rowsCount = 0;

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year = $this->Cache->getCurrentYear();

            if ($settings['currentDay_id'] === $year->daysCount) {
                $teamYears = $this->TeamYears->find('all', array(
                    'conditions' => array('year_id' => $year->id)
                ));

                if ($teamYears->count() > 0) {
                    foreach ($teamYears as $ty) { // set null because of unique values
                        /**
                         * @var TeamYear $ty
                         */
                        $ty->set('endRanking', null);
                        $this->TeamYears->save($ty);
                    }

                    $poArray = $settings['usePlayOff'] > 0 ? $this->PlayOff->getPlayOffRanking($year) : array();

                    $gtArray = $this->fetchTable('GroupTeams')->find('all', array(
                        'contain' => array('Groups' => array('fields' => array('id', 'year_id', 'day_id'))),
                        'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $settings['currentDay_id'], 'team_id NOT IN' => $poArray ?: array(0)),
                        'order' => array('GroupTeams.group_id' => 'ASC', 'GroupTeams.canceled' => 'ASC', 'GroupTeams.calcRanking' => 'ASC')
                    ))->toArray();
                    foreach ($gtArray as $k => $v) {
                        $gtArray[$k] = $v['team_id'];
                    }

                    foreach ($teamYears as $ty) {
                        /**
                         * @var TeamYear $ty
                         */
                        $key1 = array_search($ty->team_id, $poArray) ?: 0;
                        $key2 = array_search($ty->team_id, $gtArray) ?: 0;

                        $endRanking = $key1 ?: (count($poArray) + (int)$key2 + 1);

                        /*  was:
                            $groupCountTeams = ($groupteam->group)->teamsCount;
                            $endRanking = $this->GroupGet->getGroupPosNumber($groupteam->group_id) * $groupCountTeams + $groupteam->calcRanking;
                        */

                        if ($endRanking) {
                            $ty->set('endRanking', $endRanking);
                            $this->TeamYears->save($ty);
                        }
                    }
                }
            }

            // update all-time ranking
            $rowsCount = $this->Calc->updateCalcTotal($settings['currentYear_id']);
        }

        $this->apiReturn($rowsCount);
    }

    /**
     * @throws \Exception
     */
    public function insert(): void
    {
        $return = array();
        $postData = $this->request->getData();
        $settings = $this->Cache->getSettings();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $teamNamesSplit = json_decode($postData['teamNamesSplit'], true);
            foreach ($teamNamesSplit as $team) {
                $newTy = $this->TeamYears->newEmptyEntity();
                $newTy->set('team_id', $team['team_id']);
                $newTy->set('year_id', $settings['currentYear_id']);
                $newTy->set('refereePIN', $this->createUniquePIN($settings['currentYear_id'], $team['team_id']));
                $this->TeamYears->save($newTy);

                $return[] = $newTy;
            }
        }

        $this->apiReturn($return);
    }

    /**
     * @throws \Exception
     */
    private function createUniquePIN(int $yearId, int $teamId): int
    {
        $str123 = str_pad((string)(($teamId + 107) % 1000), 3, "0", STR_PAD_LEFT);
        $str45 = $yearId * random_int(1, 3) % 100;

        return (int)($str123 . $str45);
    }

    public function setScrRanking(int $year_id = 0): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year_id = $year_id ?: $settings['currentYear_id'];

            $teamYears = $this->fetchTable('TeamYears')->find('all', array(
                'contain' => array('Teams'),
                'conditions' => array('TeamYears.year_id' => $year_id, 'Teams.hidden' => 0),
            ))->toArray();

            foreach ($teamYears as $teamYear) {
                $scrData = $this->getScrData($teamYear, $year_id);

                $teamYear->set('scrPoints', $scrData['scrPoints']);
                $teamYear->set('scrMatchCount', $scrData['scrMatchCount']);
                $this->fetchTable('TeamYears')->save($teamYear);
            }

            usort($teamYears, function ($a, $b) {
                return $b->scrPoints <=> $a->scrPoints;
            });

            $c = 0;

            foreach ($teamYears as $teamYear) {
                $c++;
                $teamYear->set('scrRanking', $c);
                $this->fetchTable('TeamYears')->save($teamYear);
            }

            $this->apiReturn(count($teamYears));
        }
    }

    public function getScrRanking(int $yearId = 0): void
    {
        $settings = $this->Cache->getSettings();
        $return = $this->ScrRanking->getScrRanking($yearId ?: $settings['currentYear_id']);

        $this->apiReturn($return);
    }

    public function getScrTeamLogs(int $team_id, int $year_id = 0): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year_id = $year_id ?: $settings['currentYear_id'];

            $teamYear = $this->fetchTable('TeamYears')->find('all', array(
                'contain' => array('Teams'),
                'conditions' => array('TeamYears.team_id' => $team_id, 'TeamYears.year_id' => $year_id),
            ))->first();

            $scrData = $this->getScrData($teamYear, $year_id);

            $this->apiReturn($scrData);
        }
    }

    private function getScrData(TeamYear $teamYear, int $year_id): array
    {
        /**
         * @var TeamYear $teamYear
         */
        $scrLogs = array();
        $sumPoints = 0;
        $prevRoundId = 0;
        $return = array();

        $conditionsArray = array(
            'Groups.year_id' => $year_id,
            'Matches.canceled' => 0,
            'OR' => array(
                'Matches.refereeTeamSubst_id' => $teamYear->team_id,
                'AND' => array('Matches.refereeTeamSubst_id IS' => null, 'Matches.refereeTeam_id' => $teamYear->team_id)));

        $matches = $this->MatchGet->getMatches($conditionsArray, 1, 0, 1);

        if (is_array($matches)) {
            foreach ($matches as $match) {
                /**
                 * @var Match4 $match
                 */
                $logs = $this->fetchTable('MatcheventLogs')->find('all', array(
                    'contain' => array('Matchevents'),
                    'conditions' => array('match_id' => $match->id, 'matchEvent_id IN' => array(1, 90, 98)),
                ))->orderBy(array('MatcheventLogs.id' => 'ASC'))->all();

                $wasLoggedIn = 0;
                foreach ($logs as $log) {
                    /**
                     * @var Match4eventLog $log
                     */
                    $points = 0;
                    $factor = 1;

                    if ($log->matchevent->code == 'LOGIN') {
                        $points = 50;
                        $mt = DateTime::createFromFormat('Y-m-d H:i:s', $match->matchStartTime);
                        $dateDiff = $log->datetime->diffInMinutes($mt, false);
                        if ($dateDiff > 0 && $wasLoggedIn == 0) {
                            $factor = $dateDiff > 4 ? $factor : $factor * $dateDiff * .2;
                            $wasLoggedIn = 1;
                        } else {
                            $factor = 0;
                        }
                    } elseif ($log->matchevent->code == 'MATCH_CONCLUDE') {
                        $points = 40;
                        $factor = $match->isResultOk ? $factor : $factor * .5;
                        $factor = $match->resultAdmin == 0 ? $factor : $factor * .5;

                        // remarks
                        $lengthSteps = [7, 14];
                        $remarksLength = strlen((string)$match->remarks);
                        foreach ($lengthSteps as $l) {
                            if ($remarksLength > $l) {
                                $factor += 0.1;
                            }
                        }
                    } elseif ($log->matchevent->code == 'PHOTO_UPLOAD') {
                        $points = 20;
                        $factor = $log->playerNumber; // 1 or 0
                    }

                    if ($prevRoundId != $match->round_id) {
                        $scrLogs[] = '';
                    }
                    $prevRoundId = $match->round_id;

                    $sumPoints += (int)($points * $factor);
                    $scrLogs[] = $log->datetime . ' -> Runde ' . $match->round_id . ' -> ' . $log->matchevent->code . ' -> ' . $points . ' * ' . $factor . ' = ' . (int)($points * $factor);
                }
            }

            $countMatches = count($matches);
            $return = array(
                'scrMatchCount' => $countMatches,
                'scrPoints' => $countMatches > 0 ? round($sumPoints / $countMatches, 1) : 0,
                'scrLogs' => $scrLogs
            );
        }

        return $return;
    }

}
