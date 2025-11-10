<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Group;
use App\Model\Entity\GroupTeam;
use App\Model\Entity\Match4;
use App\Model\Entity\Setting;
use App\Model\Entity\TeamYear;
use Cake\I18n\DateTime;

/**
 * TeamYears Controller
 *
 * @property \App\Model\Table\TeamYearsTable $TeamYears
 * @property \App\Controller\Component\MatchGetComponent $MatchGet
 * @property \App\Controller\Component\PtrRankingComponent $PtrRanking
 */
class TeamYearsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('MatchGet');
    }

    // getCurrentTeams
    public function all(): void
    {
        $year = $this->getCurrentYear();

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
        $year = $this->getCurrentYear();

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

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->getSettings();

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

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->getSettings();
            $year = $this->getCurrentYear()->toArray();

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
                $g = $this->getGroupByTeamId($ty->team_id, $settings['currentYear_id'], $settings['currentDay_id']);
                $gN = $this->getGroupPosNumber($g->id);

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

        if ($id && isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year_id = $this->getCurrentYearId();
            $day_id = $this->getCurrentDayId();

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

                    $this->getCalcRanking($teamYear->team_id);
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
        $return['matches'] = array();

        $teamYears = $this->TeamYears->find('all', array(
            'conditions' => array('canceled' => 1, 'year_id' => $this->getCurrentYearId())
        ))->toArray();

        if ($teamYears) {
            foreach ($teamYears as $ty) {
                $day_id = $this->getCurrentDayId();

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
        $year_id = (int)$year_id ?: $this->getCurrentYearId();
        $adminView = (int)$adminView;

        $teamYears = $this->TeamYears->find('all', array(
            'fields' => array('id', 'endRanking', 'team_id'),
            'contain' => array('Teams' => array('fields' => array('team_name' => 'name'))),
            'conditions' => array('year_id' => $year_id, 'Teams.hidden' => 0),
            'order' => array('endRanking' => 'ASC', 'team_name' => 'ASC')
        ))->toArray();

        if (!$adminView && $year_id == $this->getCurrentYearId()) {
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

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->getSettings();
            $year = $this->getCurrentYear()->toArray();
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

            if ($settings['usePushTokenRatings']) {
                $this->loadComponent('PtrRanking');

                $ptrRankingSingle = $this->PtrRanking->getPtrRanking('single', $year['id'], 3, 'DESC');
                $this->viewBuilder()->setVar('ptrRankingSingle', $ptrRankingSingle);

                $ptrRankingTeams = $this->PtrRanking->getPtrRanking('teams', $year['id'], 3, 'DESC');
                $this->viewBuilder()->setVar('ptrRankingTeams', $ptrRankingTeams);
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

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->getSettings();
            $year = $this->getCurrentYear();

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

                    $poArray = $settings['usePlayOff'] > 0 ? $this->getPlayOffRanking($year) : array();

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
                            $endRanking = $this->getGroupPosNumber($groupteam->group_id) * $groupCountTeams + $groupteam->calcRanking;
                        */

                        if ($endRanking) {
                            $ty->set('endRanking', $endRanking);
                            $this->TeamYears->save($ty);
                        }
                    }
                }
            }

            // update all-time ranking
            $rowsCount = $this->updateCalcTotal($settings['currentYear_id']);
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
        $settings = $this->getSettings();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
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

}
