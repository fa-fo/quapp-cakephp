<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\ConnectionManager;

/**
 * Years Controller
 *
 * @property \App\Model\Table\YearsTable $Years
 */
class YearsController extends AppController
{
    public function getCurrent()
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

    public function updateTeamsCount()
    {
        $conn = ConnectionManager::get('default');
        $stmt = $conn->execute(file_get_contents(__DIR__ . "/sql/update_years_teamsCount.sql"));

        $this->apiReturn($stmt->rowCount());
    }

    public function all()
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

    /*
    public function add()
    {
        $year = $this->Years->newEmptyEntity();
        if ($this->request->is('post')) {
            $year = $this->Years->patchEntity($year, $this->request->getData());
            if (!$this->Years->save($year)) {
                $year = false;
            }
        } else {
            $year = false;
        }

        $this->setCurrentYear($year->id);
        $this->apiReturn($year);
    }

    public function edit($id = false)
    {
        $year = $id ? $this->Years->find()->where(['id' => $id])->first() : false;

        if ($year) {
            if ($this->request->is(['patch', 'post', 'put'])) {
                $year = $this->Years->patchEntity($year, $this->request->getData());
                $this->Years->save($year);
            } else {
                $year = false;
            }
        }

        $this->setCurrentYear();
        $this->apiReturn($year);
    }

    public function setCurrent($id = false)
    {
        $year = $this->setCurrentYear($id);

        $this->apiReturn($year);
    }
*/
    public function setCurrentDayIncrement()
    {
        $year = $this->getCurrentYear();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            if ($year && $this->getCurrentDayId() < $year->daysCount) {
                $this->loadModel('Settings');

                $currentDay_id = $this->Settings->find('all')->where(['name' => 'currentDay_id'])->first();
                $currentDay_id->set('value', $this->getCurrentDayId() + 1);
                $this->Settings->save($currentDay_id);

                $alwaysAutoUpdateResults = $this->Settings->find('all')->where(['name' => 'alwaysAutoUpdateResults'])->first();
                $alwaysAutoUpdateResults->set('value', 0);
                $this->Settings->save($alwaysAutoUpdateResults);
            }
        }

        $this->apiReturn($year);
    }

    public function setAlwaysAutoUpdateResults()
    {
        $postData = $this->request->getData();
        $alwaysAutoUpdateResults = false;

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $this->loadModel('Settings');

            $alwaysAutoUpdateResults = $this->Settings->find('all')->where(['name' => 'alwaysAutoUpdateResults'])->first();
            $alwaysAutoUpdateResults->set('value', 1);
            $this->Settings->save($alwaysAutoUpdateResults);

            $this->getCalcRanking();
        }

        $this->apiReturn($alwaysAutoUpdateResults);
    }

    public function showEndRanking($show = 0)
    {
        $postData = $this->request->getData();
        $showEndRanking = false;

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $this->loadModel('Settings');
            $showEndRanking = $this->Settings->find('all')->where(['name' => 'showEndRanking'])->first();
            $showEndRanking->set('value', (int)$show);
            $this->Settings->save($showEndRanking);

            $this->getCalcRanking();
        }

        $this->apiReturn($showEndRanking);
    }


    // get Status of current Day
    public function getStatus()
    {
        $year = $this->getCurrentYear();

        $this->loadModel('TeamYears');
        $teamYears = $this->TeamYears->find('all', array(
            'conditions' => array('year_id' => $year->id),
        ))->toArray();
        $teamYearsEndRanking = $this->TeamYears->find('all', array(
            'conditions' => array('year_id' => $year->id, 'endRanking IS NOT' => null),
        ))->toArray();
        $teamYearsPins = $this->TeamYears->find('all', array(
            'conditions' => array('year_id' => $year->id, 'refereePIN IS NOT' => null),
        ))->toArray();

        $this->loadModel('Groups');
        $groups = $this->Groups->find('all', array(
            'conditions' => array('year_id' => $year->id, 'day_id' => $this->getCurrentDayId()),
        ))->toArray();

        $this->loadModel('GroupTeams');
        $groupTeams = $this->GroupTeams->find('all', array(
            'contain' => array(
                'Groups' => array('fields' => array('year_id', 'day_id')),
            ),
            'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId()),
        ))->toArray();

        $sumCalcMatches = 0;
        foreach ($groupTeams as $gt) {
            $sumCalcMatches += $gt->calcCountMatches;
        }

        $this->loadModel('Matches');
        $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId());
        $matches = $this->getMatches($conditionsArray, 0, 0, 1);

        $matchesRefChangeable = array();
        $matchesTeamsChangeable = array();
        $missingRefereesCount = 0;
        $matchesWith1CanceledCount = 0;
        $matchesPins = 0;
        $matchResultCount = 0;
        if ($matches) {
            foreach ($matches as $m) {
                // search for minimize missing referees
                if ($m->isRefereeCanceled && !$m->canceled && $m->resultTrend === null) {
                    $missingRefereesCount++;

                    // search for available refs from same sport with canceled match
                    $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId(), 'sport_id' => $m->sport_id, 'Matches.canceled >' => 0);
                    $matches1 = $this->getMatches($conditionsArray, 0, 0, 1);
                    if ($matches1) {
                        foreach ($matches1 as $m1) {
                            if (!$m1->isRefereeCanceled) {
                                // check if ref's team is already in play in same round with non-canceled match
                                $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId(), 'round_id' => $m->round_id, 'Matches.canceled' => 0,
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
                if ($this->getCurrentDayId() == 2 && ($m->canceled == 1 || $m->canceled == 2) && $m->resultTrend === null) {
                    $matchesWith1CanceledCount++;

                    // search for available teams from same group and same sport with canceled match
                    $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId(), 'group_id' => $m->group_id, 'sport_id' => $m->sport_id, 'Matches.canceled >' => 0, 'Matches.canceled <' => 3, 'Matches.id !=' => $m->id);
                    $matches1 = $this->getMatches($conditionsArray, 0, 0, 1);
                    if ($matches1) {
                        foreach ($matches1 as $m1) {
                            // check if other team is already in play in same round with non-canceled match
                            $otherTeam = $m1->canceled == 1 ? $m1->team2_id : $m1->team1_id;
                            $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId(), 'round_id' => $m->round_id, 'Matches.canceled' => 0,
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

                $matchesPins += ($m->refereePIN !== null ? 1 : 0);
                $matchResultCount += ($m->resultTrend !== null ? 1 : 0);
            }
        }

        $status['teamYearsCount'] = count($teamYears);
        $status['teamYearsEndRankingCount'] = count($teamYearsEndRanking);
        $status['teamYearsPins'] = count($teamYearsPins);
        $status['groupsCount'] = count($groups);
        $status['groupTeamsCount'] = count($groupTeams);
        $status['sumCalcMatchesGroupTeams'] = $sumCalcMatches / 2;
        $status['matchesCount'] = $matches ? count($matches) : 0;
        $status['matchesPins'] = $matchesPins;
        $status['matchResultCount'] = $matchResultCount;

        $status['missingRefereesCount'] = $missingRefereesCount;
        $status['matchesRefChangeable'] = $matchesRefChangeable;

        $status['matchesWith1CanceledCount'] = $matchesWith1CanceledCount;
        $status['matchesTeamsChangeable'] = $matchesTeamsChangeable;

        $this->apiReturn($status);
    }
}
