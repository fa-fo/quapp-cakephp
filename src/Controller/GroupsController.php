<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Group;
use App\Model\Entity\GroupTeam;
use App\Model\Entity\Year;

/**
 * Groups Controller
 *
 * @property \App\Model\Table\GroupsTable $Groups
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

    // order by points per year (day 1 only)
    public function sortAfterAddAllGroupTeams(string $mode = 'standard'): void
    {
        $return = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year = $this->Cache->getCurrentYear();
            /**
             * @var Year $year
             */

            if ($settings['currentDay_id'] == 1) {
                $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $settings['currentDay_id']);
                $existingMatches = $this->MatchGet->getMatches($conditionsArray);

                if (!$existingMatches) {
                    $groups = $this->Groups->find('all', array(
                        'conditions' => array('year_id' => $year->id, 'day_id' => $settings['currentDay_id']),
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
                            case 'power':
                                $orderArray = array('Teams.calcPowerRankingPoints' => 'DESC');
                                break;
                            case 'standard':
                            default:
                                $orderArray = array('GroupTeams.id' => 'ASC'); // like originally inserted
                                break;
                        }

                        $groupTeams = $this->fetchTable('GroupTeams')->find('all', array(
                            'fields' => array(
                                'GroupTeams.id',
                                'Teams.calcTotalPointsPerYear',
                            ),
                            'contain' => array(
                                'Groups' => array('fields' => array('name', 'year_id', 'day_id')),
                                'Teams' => array('fields' => array('calcTotalPointsPerYear')),
                            ),
                            'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $settings['currentDay_id']),
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

                            } else if ($mode == 'power') {
                                if ($c % 8 < 4) {
                                    $number = $c % 4;
                                } else {
                                    $number = 3 - $c % 4;
                                }
                                $newPlacenumber = floor($c / 4) + 1;

                            } else { // $mode == 'standard' -> simply numbered by counter
                                $newPlacenumber = $c % $teamsCountPerGroup + 1;
                                $number = floor($c / $teamsCountPerGroup);
                            }
                            /**
                             * @var int $number
                             */
                            $groupFillArray[$number]++;
                            $newGroupId = $this->GroupGet->getCurrentGroupId((int)$number);

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

                        foreach ($groups as $group) {
                            /**
                             * @var Group $group
                             */
                            $avgRankingPointsPerYear[$group->name] = $this->getAvgRankingPointsPerYear($group->id);
                        }

                        $return = array('avgRankingPointsPerYear' => $avgRankingPointsPerYear);
                    }
                }
            }
        }

        $this->apiReturn($return);
    }


    /* not used?
    public function getRankingPointsPerYear(string $id = '', string $year_id = '', string $day_id = ''): void
    {
        $id = (int)$id;
        $settings = $this->Cache->getSettings();
        $year_id = (int)$year_id ?: $settings['currentYear_id'];
        $day_id = (int)$day_id ?: $settings['currentDay_id'];

        $avgRankingPointsPerYear = array();
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
*/

    private function getAvgRankingPointsPerYear(int $id): ?float
    {
        $groupTeams = $this->fetchTable('GroupTeams')->find('all', array(
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
