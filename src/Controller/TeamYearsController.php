<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\GroupTeam;
use App\Model\Entity\Match;
use App\Model\Entity\TeamYear;
use App\Model\Entity\Year;
use Cake\Datasource\ConnectionManager;

/**
 * TeamYears Controller
 *
 * @property \App\Model\Table\TeamYearsTable $TeamYears
 */
class TeamYearsController extends AppController
{

    // getCurrentTeams
    public function all()
    {
        $year = $this->getCurrentYear();
        /**
         * @var Year $year
         */

        $teamYears = $this->TeamYears->find('all', array(
            'fields' => array('id', 'team_id', 'canceled'),
            'conditions' => array('year_id' => $year->id, 'OR' => array('Teams.hidden' => 0, 'canceled' => 0)),
            'contain' => array('Teams' => array('fields' => array('name'))),
            'order' => array('Teams.name' => 'ASC')
        ))->toArray();

        /*
        $this->loadModel('GroupTeams');

        foreach ($teamYears as $ty) {
            /**
             * @var TeamYear $ty
             */
        /*
            $groupteam = $this->GroupTeams->find('all', array(
                'contain' => array('Groups' => array('fields' => array('id', 'year_id', 'day_id', 'name'))),
                'conditions' => array('GroupTeams.team_id' => $ty->team_id, 'Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId()),
            ))->first();

            $ty['group_id'] = $groupteam->group->id;
            $ty['group_name'] = $groupteam->group->name;
        }
        */

        $this->apiReturn($teamYears);
    }

    public function allWithPushTokenCount()
    {
        $year = $this->getCurrentYear();
        /**
         * @var Year $year
         */

        $teamYears = $this->TeamYears->find('all', array(
            'fields' => array('id', 'team_id', 'canceled'),
            'conditions' => array('year_id' => $year->id, 'OR' => array('Teams.hidden' => 0, 'canceled' => 0)),
            'contain' => array('Teams' => array('fields' => array('name'))),
            'order' => array('Teams.name' => 'ASC')
        ))->toArray();

        $this->loadModel('PushTokens');
        foreach ($teamYears as $ty) {
            $ty['countPushTokens'] = $this->PushTokens->find()->where(['my_team_id' => $ty['team_id']])->count();
        }

        $this->apiReturn($teamYears);
    }

    public function pdfAllTeamsMatches()
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {

            $year = $this->getCurrentYear();
            /**
             * @var Year $year
             */

            $teamYears = $this->TeamYears->find('all', array(
                'fields' => array('id', 'team_id', 'year_id', 'refereePIN', 'canceled'),
                'conditions' => array('year_id' => $year->id),
                'contain' => array('Teams' => array('fields' => array('name'))),
                'order' => array('Teams.name' => 'ASC')
            ))->toArray();

            $this->loadModel('Matches');

            foreach ($teamYears as $ty) {
                $ty['infos'] = $this->getMatchesByTeam($ty['team_id'], $year->id, $this->getCurrentDayId(), 1);
            }

            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('teamYears', $teamYears);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }

    /*
    public function add()
    {
        $teamyear = $this->TeamYears->newEmptyEntity();
        if ($this->request->is('post')) {
            $teamyear = $this->TeamYears->patchEntity($teamyear, $this->request->getData());
            $teamyear->set('year_id', $this->getCurrentYearId());

            if (!$this->TeamYears->save($teamyear)) {
                $teamyear = false;
            }
        } else {
            $teamyear = false;
        }

        $this->apiReturn($teamyear);
    }

    public function edit($id = false)
    {
        $teamyear = $id ? $this->TeamYears->find()->where(['id' => $id])->first() : false;

        if ($teamyear) {
            if ($this->request->is(['patch', 'post', 'put'])) {
                $teamyear = $this->TeamYears->patchEntity($teamyear, $this->request->getData());
                $this->TeamYears->save($teamyear);
            } else {
                $teamyear = false;
            }
        }

        $this->apiReturn($teamyear);
    }
*/

    public function cancel($id = false, $undo = 0)
    {
        $teamYear = false;
        $postData = $this->request->getData();

        if ($id && isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year_id = $this->getCurrentYearId();
            $teamYear = $this->TeamYears->find('all', array(
                'conditions' => array('id' => $id, 'year_id' => $year_id)
            ))->first();

            if ($teamYear) {
                $teamYear->set('canceled', $undo ? 0 : 1);
                $this->TeamYears->save($teamYear);

                $day_id = $this->getCurrentDayId();

                $this->loadModel('GroupTeams');
                $groupTeam = $this->GroupTeams->find('all', array(
                    'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
                    'conditions' => array('team_id' => $teamYear->get('team_id'), 'Groups.year_id' => $year_id, 'Groups.day_id' => $day_id),
                ))->first();

                if ($groupTeam) {
                    /**
                     * @var GroupTeams $groupTeam
                     */
                    $groupTeam->set('canceled', $undo ? 0 : 1);
                    $this->GroupTeams->save($groupTeam);

                    $this->getCalcRanking($teamYear->get('team_id'));
                }

                // cancel matches:
                $conditionsArray = array(
                    'resultTrend IS' => null,
                    'Groups.year_id' => $teamYear->get('year_id'),
                    'Groups.day_id' => $day_id,
                    'OR' => array(
                        'team1_id' => $teamYear->get('team_id'),
                        'team2_id' => $teamYear->get('team_id'),
                    )
                );

                $this->loadModel('Matches');
                $matches = $this->getMatches($conditionsArray);
                if ($matches) {
                    foreach ($matches as $m) {
                        /**
                         * @var Match $m
                         */
                        $a = $m->team1_id == $teamYear->get('team_id') ? 1 : 2;
                        $m->set('canceled', $undo ? $m->canceled - $a : $a + $m->canceled);
                        $this->Matches->save($m);
                    }
                }
            }
        }

        $this->apiReturn($teamYear);
    }

    // deprecated! use instead:  matches/refereeCanceledMatches
    public function refereeCanceledTeamsMatches()
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

                $this->loadModel('Matches');
                $matches = $this->getMatches($conditionsArray, 0, 1, 1);

                if ($matches) {
                    $return['matches'] = array_merge($return['matches'], $matches);
                }
            }

            usort($return['matches'], function ($a, $b) {
                return $a['matchStartTime'] <=> $b['matchStartTime'];
            });
        }

        $this->apiReturn($return);
    }

    public function getEndRanking($year_id = false, $adminView = 0)
    {
        $year_id = $year_id ?: $this->getCurrentYearId();
        $teamYears = $this->TeamYears->find('all', array(
            'fields' => array('id', 'endRanking', 'team_id'),
            'conditions' => array('year_id' => $year_id, 'canceled' => 0),
            'contain' => array('Teams' => array('fields' => array('team_name' => 'name'))),
            'order' => array('endRanking' => 'ASC', 'team_name' => 'ASC')
        ))->toArray();

        if (!$adminView && $year_id == $this->getCurrentYearId()) {
            $this->loadModel('Settings');
            $showEndRanking = $this->Settings->find('all')->where(['name' => 'showEndRanking'])->first();
            if ($showEndRanking->value == 0) {
                $teamYears = null;
                $teamYears['showRanking'] = 0;
            }
        }

        $this->apiReturn($teamYears, $year_id);
    }

    public function pdfEndRanking()
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $teamYears = $this->TeamYears->find('all', array(
                'fields' => array('id', 'endRanking', 'team_id'),
                'conditions' => array('year_id' => $this->getCurrentYearId(), 'canceled' => 0),
                'contain' => array('Teams' => array('fields' => array('team_name' => 'name'))),
                'order' => array('endRanking' => 'ASC', 'team_name' => 'ASC')
            ))->toArray();

            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('teamYears', $teamYears);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }

    public function setEndRanking()
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            /**
             * @var GroupTeam $groupteam
             * @var TeamYear $ty
             * @var Year $year
             */
            $year = $this->getCurrentYear();

            if ($this->getCurrentDayId() === $year->daysCount) {
                $teamYears = $this->TeamYears->find('all', array(
                    'conditions' => array('year_id' => $year->id)
                ));

                if ($teamYears->count() > 0) {
                    $this->loadModel('GroupTeams');

                    foreach ($teamYears as $ty) { // set null because of unique values
                        $ty->set('endRanking', null);
                        $this->TeamYears->save($ty);
                    }

                    foreach ($teamYears as $ty) {
                        $groupteam = $this->GroupTeams->find('all', array(
                            'contain' => array('Groups' => array('fields' => array('year_id', 'day_id', 'teamsCount'))),
                            'conditions' => array('GroupTeams.team_id' => $ty->team_id, 'Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId()),
                        ))->first();

                        if ($groupteam !== null && $groupteam->group) {
                            $groupCountTeams = ($groupteam->group)->teamsCount;

                            $ty->set('endRanking', $this->getGroupPosNumber($groupteam->group_id) * $groupCountTeams + $groupteam->calcRanking);
                            $this->TeamYears->save($ty);
                        }
                    }
                }
            }

            // update all-time ranking
            $rowsCount = $this->updateCalcTotal();
        }

        $this->apiReturn($rowsCount);
    }


    public function insertTestValues()
    {
        $postData = $this->request->getData();
        $settings = $this->getSettings();

        if (($settings['isTest'] ?? 0) && isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $conn = ConnectionManager::get('default');
            $rc = $conn->execute(file_get_contents(__DIR__ . "/sql/insert_team_years2023.sql"))->rowCount();

            $teamYears = $this->TeamYears->find('all', array(
                'conditions' => array('year_id' => $this->getCurrentYearId()),
            ));

            foreach ($teamYears as $ty) {
                $ty->set('refereePIN', $this->createUniquePIN($ty->year_id, $ty->team_id));
                $this->TeamYears->save($ty);
            }

            $this->apiReturn($rc);
        }

        $this->apiReturn(array());
    }


    private
    function createUniquePIN($yearId, $teamId)
    {
        $str123 = str_pad((string)(($teamId + 107) % 1000), 3, "0", STR_PAD_LEFT);
        $str45 = $yearId * random_int(1, 3) % 100;

        return (int)($str123 . $str45);
    }

}
