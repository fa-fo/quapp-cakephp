<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Group;
use App\Model\Entity\GroupTeam;

/**
 * Groups Controller
 *
 * @property \App\Model\Table\GroupsTable $Groups
 * @property \App\Controller\Component\CalcComponent $Calc
 * @property \App\Controller\Component\GroupGetComponent $GroupGet
 * @property \App\Controller\Component\MatchGetComponent $MatchGet
 * @property \App\Controller\Component\SecurityComponent $Security
 */
class GroupsController extends AppController
{
    public function all(string $year_id = '', string $day_id = ''): void
    {
        $settings = $this->Cache->getSettings();
        $year_id = (int)$year_id ?: $settings['currentYear_id'];
        $day_id = (int)$day_id ?: $settings['currentDay_id'];

        $year = array();
        $year['groups'] = $this->Groups->find('all', array(
            'fields' => array('group_id' => 'id', 'group_name' => 'name', 'id', 'name', 'year_id', 'day_id', 'teamsCount'),
            'conditions' => array('year_id' => $year_id, 'day_id' => $day_id, 'name !=' => 'Endrunde'),
            'order' => array('group_name' => 'ASC')
        ))->toArray();

        $this->apiReturn($year, $year_id, $day_id);
    }

    public function addAll(string $countGroups = ''): void
    {
        $countGroups = (int)$countGroups;
        $groups = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year = $this->Cache->getCurrentYear();

            $existingGroups = $this->Groups->find('all', array(
                'conditions' => array('year_id' => $year->id, 'day_id' => $settings['currentDay_id']),
                'order' => array('id' => 'ASC')
            ));

            if ($existingGroups->count() == 0) {
                $countTeams = $this->fetchTable('TeamYears')->find('all', array(
                    'conditions' => array('year_id' => $year->id),
                    'order' => array('id' => 'ASC')
                ))->count();

                if ($countTeams == $year->teamsCount) {
                    $teamsPerGroup = $this->getTeamsCountPerGroup($year);
                    $countGroups = $countGroups ?: (int)ceil($countTeams / $teamsPerGroup);
                    $alphabet = range('A', 'Z');

                    for ($c = 0; $c < $countGroups; $c++) {
                        $group = $this->Groups->newEmptyEntity();
                        $group->set('year_id', $year->id);
                        $group->set('day_id', $settings['currentDay_id']);
                        $group->set('name', $alphabet[$c]);
                        $group->set('teamsCount', $teamsPerGroup);

                        if ($this->Groups->save($group)) {
                            $groups[] = $group;
                        }
                    }

                    if ($settings['usePlayOff'] > 0) {
                        $group = $this->Groups->newEmptyEntity();
                        $group->set('year_id', $year->id);
                        $group->set('day_id', $settings['currentDay_id']);
                        $group->set('name', 'Endrunde');
                        $group->set('teamsCount', $settings['usePlayOff']);

                        if ($this->Groups->save($group)) {
                            $groups[] = $group;
                        }
                    }
                }
            }
        }

        $this->apiReturn(count($groups));
    }

    // assign groupTeams to groups
    public function sortAfterAddAllGroupTeams(string $mode = 'standard'): void
    {
        $return = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();

            if ($settings['currentDay_id'] == 1) {
                $conditionsArray = array('Groups.year_id' => $settings['currentYear_id'], 'Groups.day_id' => $settings['currentDay_id']);
                $existingMatches = $this->MatchGet->getMatches($conditionsArray);

                if (!$existingMatches) {
                    $groups = $this->Groups->find('all', array(
                        'conditions' => array('year_id' => $settings['currentYear_id'], 'day_id' => $settings['currentDay_id']),
                        'order' => array('id' => 'ASC')
                    ));

                    $groupsCount = $groups->count();

                    if ($groupsCount > 0) {
                        $teamsCountPerGroup = ($groups->toArray())[0]->teamsCount;

                        $orderArray = match ($mode) {
                            'random' => array('Teams.calcTotalPointsPerYear' => 'DESC'),
                            'power' => array('Teams.calcPowerRankingPoints' => 'DESC'),
                            default => array('GroupTeams.id' => 'ASC'), // like originally inserted
                        };

                        $groupTeams = $this->fetchTable('GroupTeams')->find('all', array(
                            'fields' => array(
                                'GroupTeams.id',
                                'Teams.calcTotalPointsPerYear',
                            ),
                            'contain' => array(
                                'Groups' => array('fields' => array('name', 'year_id', 'day_id')),
                                'Teams' => array('fields' => array('calcTotalPointsPerYear')),
                            ),
                            'conditions' => array('Groups.year_id' => $settings['currentYear_id'], 'Groups.day_id' => $settings['currentDay_id']),
                            'order' => $orderArray
                        ))->all();

                        $c = 0;
                        $groupFillArray = array_fill(0, $groupsCount, 0); // temp array for ($groupsCount, e.g. 4 or 6) groups

                        foreach ($groupTeams as $gt) {
                            if ($mode == 'random') { // numbered by draw
                                $newPlacenumber = floor($c / $groupsCount) + 1; // teamsCountPerGroup cups for draw, $groupsCount teams in each cup

                                do {
                                    $gNumber = random_int(0, $groupsCount - 1); // groups A...D..F
                                } while ($groupFillArray[$gNumber] >= $newPlacenumber);

                            } else if ($mode == 'power') {
                                if ($c % ($groupsCount * 2) < $groupsCount) { // even cup: gNumber left to right
                                    $gNumber = $c % $groupsCount;
                                } else { // odd cup: gNumber right to left
                                    $gNumber = ($groupsCount - 1) - ($c % $groupsCount);
                                }
                                $gNumber = ($gNumber + 2) % $groupsCount; // group offset
                                $newPlacenumber = floor($c / $groupsCount) + 1;

                            } else { // $mode == 'standard' -> simply numbered by counter
                                $newPlacenumber = $c % $teamsCountPerGroup + 1;
                                $gNumber = floor($c / $teamsCountPerGroup);
                            }
                            /**
                             * @var int $gNumber
                             */
                            $groupFillArray[$gNumber]++;
                            $newGroupId = $this->GroupGet->getCurrentGroupId($gNumber);

                            $gt->set('group_id', $newGroupId);
                            $gt->set('calcRanking', null); // because of unique value per group
                            $gt->set('placeNumber', (int)(3000 + $newPlacenumber)); // temp because of unique values
                            $this->fetchTable('GroupTeams')->save($gt);
                            $c++;
                        }

                        // set right new placeNumber
                        foreach ($groupTeams as $gt) {
                            /**
                             * @var GroupTeam $gt
                             */
                            $gt->set('placeNumber', (int)($gt->placeNumber - 3000));
                            $this->fetchTable('GroupTeams')->save($gt);
                        }

                        $return = array('avgRankingPoints' => $this->getAvgRanking($groups));
                    }
                }
            }
        }

        $this->apiReturn($return);
    }

    public function checkAfterAddAllGroupTeams(): void
    {
        $settings = $this->Cache->getSettings();

        $groups = $this->Groups->find('all', array(
            'conditions' => array('year_id' => $settings['currentYear_id'], 'day_id' => $settings['currentDay_id']),
            'order' => array('id' => 'ASC')
        ));

        $return = array('avgRankingPoints' => $this->getAvgRanking($groups));

        $this->apiReturn($return);
    }

    private function getAvgRanking(\Cake\ORM\Query\SelectQuery $groups): array
    {
        $avgRankingPoints = array();

        foreach ($groups as $group) {
            /**
             * @var Group $group
             */
            $avgRankingPoints[$group->name] = $this->getAvgRankingPoints($group->id);
        }
        return $avgRankingPoints;
    }

    private function getAvgRankingPoints(int $id): array
    {
        $groupTeams = $this->fetchTable('GroupTeams')->find('all', array(
            'contain' => array(
                'Teams' => array('fields' => array('name', 'calcTotalPointsPerYear', 'calcPowerRankingPoints'))
            ),
            'conditions' => array('GroupTeams.group_id' => $id),
            'order' => array('GroupTeams.id' => 'ASC')
        ))->toArray();

        $rankingPoints = array();
        $powerPoints = array();

        foreach ($groupTeams as $gt) {
            /**
             * @var GroupTeam $gt
             */
            if (($gt->team)->calcTotalPointsPerYear) {
                $rankingPoints[] = ($gt->team)->calcTotalPointsPerYear;
            }
            if (($gt->team)->calcPowerRankingPoints) {
                $powerPoints[] = ($gt->team)->calcPowerRankingPoints;
            }
        }

        $statArray1 = $this->getStatistics($rankingPoints, 'PerYear');
        $statArray2 = $this->getStatistics($powerPoints, 'Power');

        return array_merge($statArray1, $statArray2);
    }

    private function getStatistics(array $array, string $sName): array
    {
        $return = array();

        if (count($array) > 0) {
            $mean = array_sum($array) / count($array);

            $return = array(
                'c' . $sName => count($array),
                'avg' . $sName => number_format($mean, 1),
                'stdDev' . $sName => number_format($this->Calc->stdDeviation($array, $mean), 1)
            );
        }

        return $return;
    }
}
