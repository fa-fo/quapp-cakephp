<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\GroupTeam;
use App\Model\Entity\Year;

/**
 * Groups Controller
 *
 * @property \App\Model\Table\GroupsTable $Groups
 */
class GroupsController extends AppController
{
    public function all($year_id = false, $day_id = false)
    {
        $year = $this->getCurrentYear();
        /**
         * @var Year $year
         */

        $year_id = $year_id ?: $year->id;
        $day_id = $day_id ?: $this->getCurrentDayId();

        $year = array();
        $year['groups'] = $this->Groups->find('all', array(
            'fields' => array('group_id' => 'id', 'group_name' => 'name', 'id', 'name', 'year_id', 'day_id', 'teamsCount'),
            'conditions' => array('year_id' => $year_id, 'day_id' => $day_id),
            'order' => array('group_name' => 'ASC')
        ))->toArray();

        $this->apiReturn($year, $year_id, $day_id);
    }

    public function addAll($countGroups = false)
    {
        $groups = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year = $this->getCurrentYear();
            /**
             * @var Year $year
             */

            $existingGroups = $this->Groups->find('all', array(
                'conditions' => array('year_id' => $year->id, 'day_id' => $this->getCurrentDayId()),
                'order' => array('id' => 'ASC')
            ));

            if ($existingGroups->count() == 0) {
                $this->loadModel('TeamYears');
                $countTeams = $this->TeamYears->find('all', array(
                    'conditions' => array('year_id' => $year->id),
                    'order' => array('id' => 'ASC')
                ))->count();

                if ($countTeams == $year->teamsCount) {
                    $countGroups = $countGroups ?: (int)ceil($countTeams / 16);
                    $alphabet = range('A', 'Z');

                    for ($c = 0; $c < $countGroups; $c++) {
                        $group = $this->Groups->newEmptyEntity();
                        $group->set('year_id', $year->id);
                        $group->set('day_id', $this->getCurrentDayId());
                        $group->set('name', $alphabet[$c]);
                        $group->set('teamsCount', 16);

                        if ($this->Groups->save($group)) {
                            $groups[] = $group;
                        }
                    }
                }
            }
        }

        //$groups = count($groups) ? $groups : false;

        $this->apiReturn(count($groups));
    }

    // not needed???????
    /*
    public function getRanking($id = false, $year_id = false, $day_id = false)
    {
        $this->loadModel('GroupTeams');
        $year = $this->getCurrentYear();
        $year_id = $year_id ?: $year->id;
        $day_id = $day_id ?: $this->getCurrentDayId();

        $condGtArray = $id ? array('id' => $id) : array();

        $groups = $this->Groups->find('all', array(
            'conditions' => array_merge($condGtArray, array('year_id' => $year_id, 'day_id' => $day_id)),
            'order' => array('id' => 'ASC')
        ));

        $countGroups = $groups->count();

        $countGroupTeams = 0;
        if ($countGroups > 0) {
            foreach ($groups as $group) {
                $options = array('countGroupTeams' => $countGroupTeams, 'day_id' => $day_id);

                $groupTeams = $this->GroupTeams->find('all', array(
                    'contain' => array(
                        'Teams' => array('fields' => array('name'))
                    ),
                    'conditions' => array('GroupTeams.group_id' => $group->id),
                    'order' => array('GroupTeams.calcRanking IS NOT' => null, 'GroupTeams.calcRanking' => 'ASC', 'GroupTeams.placeNumber' => 'ASC')
                ))->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($options) {
                    return $results->map(function ($row) use ($options) {
                        //Adding Calculated Fields
                        if ($options['day_id'] > 1) {
                            $row['overallRanking'] = $options['countGroupTeams'] + $row['calcRanking'];
                        }
                        return $row;
                    });
                })->toArray();

                $group['countGroupTeams'] = count($groupTeams);
                $countGroupTeams += $group['countGroupTeams'];

                $group['groupTeams'] = $groupTeams;
            }
        }

        $return = array('countGroups' => $countGroups, 'groups' => $groups);
        $this->apiReturn($return);
    }
*/

    // order by points per year (day 1 only)
    public function sortAfterAddAllGroupTeams($mode = 'standard')
    {
        $return = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year = $this->getCurrentYear();
            /**
             * @var Year $year
             */

            if ($this->getCurrentDayId() == 1) {
                $this->loadModel('Matches');

                $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId());
                $existingMatches = $this->getMatches($conditionsArray);

                if (!$existingMatches) {
                    $groups = $this->Groups->find('all', array(
                        'conditions' => array('year_id' => $year->id, 'day_id' => $this->getCurrentDayId()),
                        'order' => array('id' => 'ASC')
                    ));

                    $groupsCount = $groups->count();

                    if ($groupsCount > 0) {
                        $avgRankingPointsPerYear = array();
                        $teamsCountPerGroup = ($groups->toArray())[0]->teamsCount;

                        switch ($mode) {
                            case 'random':
                                $orderArray = array('Teams.calcTotalPointsPerYear' => 'DESC');
                                break;
                            case 'standard':
                            default:
                                $orderArray = array('GroupTeams.id' => 'ASC'); // like originally inserted
                                break;
                        }

                        $this->loadModel('GroupTeams');
                        $groupTeams = $this->GroupTeams->find('all', array(
                            'fields' => array(
                                'GroupTeams.id',
                                'Teams.calcTotalPointsPerYear',
                            ),
                            'contain' => array(
                                'Groups' => array('fields' => array('name', 'year_id', 'day_id')),
                                'Teams' => array('fields' => array('calcTotalPointsPerYear')),
                            ),
                            'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId()),
                            'order' => $orderArray
                        ))->all();

                        $c = 0;
                        $groupFillArray = array_fill(0, $groupsCount, 0); // temp array for 4 groups

                        foreach ($groupTeams as $gt) {
                            if ($mode == 'random') { // numbered by draw
                                $newPlacenumber = floor($c / $groupsCount) + 1; // teamsCountPerGroup cups for draw, countGroups (4 or 6) teams in each cup

                                do {
                                    $number = random_int(0, $groupsCount - 1); // groups A...D..F
                                } while ($groupFillArray[$number] >= $newPlacenumber);

                            } else if ($mode == 'standard') { // simply numbered by counter
                                $newPlacenumber = $c % $teamsCountPerGroup + 1;
                                $number = floor($c / $teamsCountPerGroup);
                            }

                            $groupFillArray[$number]++;
                            $newGroupId = $this->getCurrentGroupId((int)$number);

                            $gt->set('group_id', $newGroupId);
                            $gt->set('calcRanking', null); // because of unique value per group
                            $gt->set('placeNumber', (int)(3000 + $newPlacenumber)); // temp because of unique values
                            $this->GroupTeams->save($gt);
                            $c++;
                        }

                        // set right new placeNumber
                        foreach ($groupTeams as $gt) {
                            /**
                             * @var GroupTeam $gt
                             */
                            $gt->set('placeNumber', (int)($gt->placeNumber - 3000));
                            $this->GroupTeams->save($gt);
                        }

                        foreach ($groups as $group) {
                            $avgRankingPointsPerYear[$group->name] = $this->getAvgRankingPointsPerYear($group->id);
                        }

                        //$return = array_merge(array('avgRankingPointsPerYear' => $avgRankingPointsPerYear), $groupTeams->toArray());
                        $return = array('avgRankingPointsPerYear' => $avgRankingPointsPerYear);
                    }
                }
            }
        }

        $this->apiReturn($return);
    }


    public function getRankingPointsPerYear($id = false, $year_id = false, $day_id = false)
    {
        $this->loadModel('GroupTeams');
        $year = $this->getCurrentYear();
        /**
         * @var Year $year
         */

        $year_id = $year_id ?: $year->id;
        $day_id = $day_id ?: $this->getCurrentDayId();

        $condGtArray = $id ? array('id' => $id) : array();

        $groups = $this->Groups->find('all', array(
            'conditions' => array_merge($condGtArray, array('year_id' => $year_id, 'day_id' => $day_id)),
            'order' => array('id' => 'ASC')
        ));

        $countGroups = $groups->count();

        if ($countGroups > 0) {
            foreach ($groups as $group) {
                $avgRankingPointsPerYear[$group->name] = $this->getAvgRankingPointsPerYear($group->id);
            }
        }

        $this->apiReturn(array('avgRankingPointsPerYear' => $avgRankingPointsPerYear));
    }


    private function getAvgRankingPointsPerYear($id)
    {
        $groupTeams = $this->GroupTeams->find('all', array(
            'contain' => array(
                'Teams' => array('fields' => array('name', 'calcTotalPointsPerYear'))
            ),
            'conditions' => array('GroupTeams.group_id' => $id),
            'order' => array('GroupTeams.id' => 'ASC')
        ))->toArray();

        $countGroupTeams = count($groupTeams);
        $rankingPointsPerYear = 0;

        if ($countGroupTeams > 0) {
            foreach ($groupTeams as $gt) {
                if (($gt->team) && ($gt->team)->calcTotalPointsPerYear) {
                    $rankingPointsPerYear += ($gt->team)->calcTotalPointsPerYear;
                }
            }

            return round($rankingPointsPerYear / $countGroupTeams, 2);
        }

        return null;
    }
}
