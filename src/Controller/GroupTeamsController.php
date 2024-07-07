<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Group;
use App\Model\Entity\GroupTeam;
use App\Model\Entity\Match4schedulingPattern16;
use App\Model\Entity\TeamYear;
use App\Model\Entity\Year;
use Cake\I18n\FrozenTime;

/**
 * GroupTeams Controller
 *
 * @property \App\Model\Table\GroupTeamsTable $GroupTeams
 */
class GroupTeamsController extends AppController
{
    // getRanking
    public function all(string $group_id = '', string $adminView = ''): void
    {
        $group_id = (int)$group_id;
        $adminView = (int)$adminView;
        $group = $group_id ? $this->getPrevAndNextGroup($group_id) : false;
        /**
         * @var Group $group
         */

        if ($group) {
            $group['showRanking'] = 1;
            $group['groupTeams'] = $this->getRanking($group);
            $settings = $this->getSettings();

            if (!$adminView && $group->year_id == $settings['currentYear_id']) {
                $group['isTest'] = $settings['isTest'];

                if ($group->day_id == 2 // was: && $settings['alwaysAutoUpdateResults'] == 1
                    && $this->getCurrentRoundId($settings['currentYear_id'], $settings['currentDay_id'], 10) > 12) {
                    if ($settings['showEndRanking'] == 0) {
                        $group['groupTeams'] = null;
                        $group['showRanking'] = 0;
                    }
                }
            }

            $this->apiReturn($group, $group->year_id, $group->day_id);
        }

        $this->apiReturn(array());
    }

    private function getRanking(Group $group): array
    {
        return $this->GroupTeams->find('all', array(
            'contain' => array(
                'Groups' => array('fields' => array('group_id' => 'Groups.id', 'group_name' => 'Groups.name', 'year_id', 'day_id')),
                'Teams' => array('fields' => array('name'))
            ),
            'conditions' => array('GroupTeams.group_id' => $group->id, 'GroupTeams.canceled' => 0, 'Groups.year_id' => $group->year_id, 'Groups.day_id' => $group->day_id),
            'order' => array('Groups.id' => 'ASC', 'GroupTeams.canceled' => 'ASC', 'GroupTeams.calcRanking' => 'ASC')
        ))->toArray();
    }

    public function pdfAllRankings(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $groups = $this->fetchTable('Groups')->find('all', array(
                'fields' => array('id', 'name', 'year_id', 'day_id'),
                'conditions' => array('year_id' => $this->getCurrentYearId(), 'day_id' => $this->getCurrentDayId()),
                'order' => array('name' => 'ASC')
            ));

            $settings = $this->getSettings();
            $currentYear = $this->getCurrentYear()->toArray();
            $day = FrozenTime::createFromFormat('Y-m-d H:i:s', $currentYear['day' . $settings['currentDay_id']]->i18nFormat('yyyy-MM-dd HH:mm:ss'));

            foreach ($groups as $group) {
                $group['groupTeams'] = $this->getRanking($group);
                $group['day'] = $day;
            }

            $this->viewBuilder()->setTemplatePath('pdf');
            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('groups', $groups);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }

    public function addAll(): void
    {
        $groupTeams = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year = $this->getCurrentYear();

            $oldGroupTeams = $this->GroupTeams->find('all', array(
                'contain' => array('Groups' => array('fields' => array('name', 'year_id', 'day_id'))),
                'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId()),
                'order' => array('GroupTeams.id' => 'ASC')
            ));

            if ($oldGroupTeams->count() == 0) {
                $groups = $this->fetchTable('Groups')->find('all', array(
                    'conditions' => array('year_id' => $year->id, 'day_id' => $this->getCurrentDayId()),
                    'order' => array('id' => 'ASC')
                ));

                if ($groups->count() > 0) {
                    $countGroup = 0;
                    foreach ($groups as $group) {
                        /**
                         * @var Group $group
                         */

                        if ($this->getCurrentDayId() == 1) {
                            $groupTeams = array_merge($groupTeams, $this->addFromTeamYearsOrderById($year, $group, $countGroup));
                        } else {
                            $groupTeams = array_merge($groupTeams, $this->addFromPrevDayRanking($year, $group, $countGroup));
                        }

                        $countGroup++;
                    }
                }
            }
        }
        //$groupTeams = count($groupTeams) ? $groupTeams : false;

        $this->apiReturn(count($groupTeams));
    }

    private function addFromTeamYearsOrderById($year, Group $group, $countGroup): array
    {
        $groupTeams = array();

        $teamYears = $this->fetchTable('TeamYears')->find('all', array(
            'conditions' => array('year_id' => $year->id),
            'order' => array('id' => 'ASC')
        ))->offset($group->teamsCount * $countGroup)->limit($group->teamsCount);

        if ($teamYears->count() > 0) {
            $placeNumberCounter = 0;
            foreach ($teamYears as $teamyear) {
                /**
                 * @var TeamYear $teamyear
                 */
                $placeNumberCounter++;
                $groupteam = $this->GroupTeams->newEmptyEntity();
                $groupteam->set('group_id', $group->id);
                $groupteam->set('team_id', $teamyear->team_id);
                $groupteam->set('placeNumber', $placeNumberCounter);
                $groupteam->set('canceled', $teamyear->canceled);

                if ($this->GroupTeams->save($groupteam)) {
                    $groupTeams[] = $groupteam;
                }
            }
        }

        return $groupTeams;
    }


    private function addFromPrevDayRanking(Year $year, Group $group, int $countGroup): array
    {
        $groupTeams = array();
        $orderArray = array('GroupTeams.calcRanking' => 'ASC', 'GroupTeams.group_id' => 'ASC'); // standard for 64 teams

        if ($year->teamsCount > 64) {
            $orderArray = array('GroupTeams.calcRanking' => 'ASC', 'GroupTeams.calcPointsPlus' => 'ASC', 'GroupTeams.calcGoalsDiff' => 'ASC', 'GroupTeams.calcGoalsScored' => 'ASC', 'GroupTeams.group_id' => 'ASC');
        }

        $prevGroupTeams = $this->GroupTeams->find('all', array(
            'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
            'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => ($this->getCurrentDayId() - 1)),
            'order' => $orderArray
        ))->offset($group->teamsCount * $countGroup)->limit($group->teamsCount);

        if ($prevGroupTeams->count() > 0) {
            $placeNumberCounter = 0;
            foreach ($prevGroupTeams as $pgt) {
                /**
                 * @var GroupTeam $pgt
                 */
                $placeNumberCounter++;
                $groupteam = $this->GroupTeams->newEmptyEntity();
                $groupteam->set('group_id', $group->id);
                $groupteam->set('team_id', $pgt->team_id);
                $groupteam->set('placeNumber', $placeNumberCounter);
                $groupteam->set('canceled', $pgt->canceled);

                if ($this->GroupTeams->save($groupteam)) {
                    $groupTeams[] = $groupteam;
                }
            }
        }

        return $groupTeams;
    }

    public function sortPlaceNumberAfterAddAll(string $mode = 'none'): void
    {
        $countDoubleMatches['countPrevYearsSameMatch'] = 0;
        $countDoubleMatches['countPrevYearsSameMatchSameSport'] = 0;
        $countDoubleMatches['countPrevDaySameMatch'] = 0;
        $countDoubleMatches['countPrevDaySameMatchSameSport'] = 0;
        $avgOpponentPrevDayRanking = array();
        $avgOpponentRankingPointsPerYear = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year = $this->getCurrentYear();
            $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId());
            $existingMatches = $this->getMatches($conditionsArray);

            if (!$existingMatches) {
                $groups = $this->fetchTable('Groups')->find('all', array(
                    'conditions' => array('year_id' => $year->id, 'day_id' => $this->getCurrentDayId()),
                    'order' => array('id' => 'ASC')
                ));

                $groupsCount = $groups->count();
                if ($groupsCount > 0) {
                    $teamsCountPerGroup = ($groups->toArray())[0]->teamsCount;

                    if ($teamsCountPerGroup == 16) {
                        $rand_array = array();

                        if ($mode == 'random') {
                            $ra = range(0, 3);
                            for ($a = 0; $a < $groupsCount; $a++) { // each group seperate random
                                shuffle($ra);
                                $rand_array[$a] = array(random_int(0, 1), random_int(0, 1), $ra); // for random placenumber sort
                            }
                        }

                        $options = array('sortmode' => $mode, 'year_id' => $year->id, 'currentDay_id' => $this->getCurrentDayId(), 'rand_array' => $rand_array,
                            'groupsCount' => $groupsCount);

                        $groupTeams = $this->GroupTeams->find('all', array(
                            'contain' => array('Groups' => array('fields' => array('name', 'year_id', 'day_id'))),
                            'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId()),
                            'order' => array('GroupTeams.group_id' => 'ASC', 'GroupTeams.id' => 'ASC')
                        ))->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($options) {
                            return $results->map(function ($row, $counter = 0) use ($options) {
                                //Adding Calculated Fields
                                // initial and for day 1: just some values to use switch options
                                $prevRankingInTeam = (($counter % 64) % 16) % 4;
                                $prevGroupPosNumber = (int)floor((($counter % 64) % 16) / 4);

                                if ($options['currentDay_id'] > 1) {
                                    $prevGroupteam = $this->GroupTeams->find('all', array(
                                        'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
                                        'conditions' => array('GroupTeams.team_id' => $row['team_id'], 'Groups.year_id' => $options['year_id'], 'Groups.day_id' => ($options['currentDay_id'] - 1)),
                                    ))->first();
                                    /**
                                     * @var GroupTeam $prevGroupteam
                                     */
                                    $row['prevPlaceNumber'] = $prevGroupteam->placeNumber;
                                    $row['prevGroupId'] = $prevGroupteam->group_id;

                                    if ($options['groupsCount'] == 4) { // not compatible with 96 teams modus
                                        $prevRankingInTeam = ($prevGroupteam->calcRanking - 1) % 4;
                                        $prevGroupPosNumber = $this->getGroupPosNumber($prevGroupteam->group_id) % 4;
                                    }
                                }

                                $groupPosNumber = $this->getGroupPosNumber($row['group_id']);
                                $row['groupPosNumber'] = $groupPosNumber;

                                switch ($options['sortmode']) {
                                    default:
                                    case 'none':  // no change
                                        $row['newPlaceNumber'] = $row['placeNumber'];
                                        break;
                                    case 'initial':
                                        $row['newPlaceNumber'] = ($counter % 16) + 1;
                                        break;
                                    case 'standard':  // group before ranking
                                        $row['newPlaceNumber'] = (int)($prevRankingInTeam + 1 + $prevGroupPosNumber * 4);
                                        break;
                                    case 'ranking': // ranking before group
                                        $row['newPlaceNumber'] = (int)(($prevRankingInTeam) * 4 + 1 + $prevGroupPosNumber);
                                        break;
                                    case 'random':
                                        $row['newPlaceNumber'] = (int)($this->getNewPlaceNumberRandom($prevRankingInTeam, $prevGroupPosNumber, $groupPosNumber, $options['rand_array']));
                                        break;
                                }

                                $counter++; // sic! is used
                                return $row;
                            });
                        })->all();

                        // temp set because of unique values
                        foreach ($groupTeams as $gt) {
                            if ($gt->newPlaceNumber != $gt->placeNumber) {
                                $gt->set('placeNumber', (int)(1000 + $gt->newPlaceNumber));
                                $this->GroupTeams->save($gt);
                            }
                        }

                        foreach ($groupTeams as $gt) {
                            if ($gt->newPlaceNumber != $gt->placeNumber) {
                                $gt->set('placeNumber', $gt->newPlaceNumber);
                                $this->GroupTeams->save($gt);
                            }
                        }

                        // check has to be after all set
                        foreach ($groupTeams as $gt) {
                            $checks = $this->getCountCheckings($gt, $options['currentDay_id']);

                            $countDoubleMatches['countPrevYearsSameMatch'] += $checks['countPrevYearsDuplicates']['countSameMatch'];
                            $countDoubleMatches['countPrevYearsSameMatchSameSport'] += $checks['countPrevYearsDuplicates']['countSameMatchSameSport'];
                            $countDoubleMatches['countPrevDaySameMatch'] += $checks['countPrevDayDuplicates']['countSameMatch'];
                            $countDoubleMatches['countPrevDaySameMatchSameSport'] += $checks['countPrevDayDuplicates']['countSameMatchSameSport'];
                            $avgOpponentPrevDayRanking[$gt->groupPosNumber][$gt->team_id] = $checks['avgOpponentPrevDayRanking'];
                            $avgOpponentRankingPointsPerYear[$gt->groupPosNumber][$gt->team_id] = $checks['avgOpponentRankingPointsPerYear'];
                        }
                    }
                }
            }
        }

        $checkings2 = array();
        for ($i = 0; $i < count($avgOpponentPrevDayRanking); $i++) {
            $checkings2[$this->getGroupName($i)] = array('max' => max($avgOpponentPrevDayRanking[$i]), 'min' => min($avgOpponentPrevDayRanking[$i]));
        }

        $checkings3 = array();
        for ($i = 0; $i < count($avgOpponentRankingPointsPerYear); $i++) {
            $checkings3[$this->getGroupName($i)] = array('max' => max($avgOpponentRankingPointsPerYear[$i]), 'min' => min($avgOpponentRankingPointsPerYear[$i]));
        }

        $checkings = array(
            'countDoubleMatches' => $countDoubleMatches,
            'avgOpponentPrevDayRanking' => $checkings2,
            'avgOpponentRankingPointsPerYear' => $checkings3,
        );

        $this->apiReturn($checkings);
    }


    private function getCountCheckings(GroupTeam $groupteam, int $currentDay_id): array
    {
        $year = $this->getCurrentYear();
        $countPrevYearsMatches = 0;
        $countPrevYearsMatchesSameSport = 0;
        $prevDayOpponentTeamIds = array();
        $currentOpponentTeamIds = array();
        $currentOpponentTeamPrevRankings = array();
        $currentOpponentTeamRankingPointsPerYear = array();

        $matchschedulings = $this->fetchTable('MatchschedulingPattern16')->find('all');

        foreach ($matchschedulings as $msc) {
            /**
             * @var Match4schedulingPattern16 $msc
             */
            if (in_array($groupteam->placeNumber, array($msc->placenumberTeam1, $msc->placenumberTeam2))) {
                // check previous years for doublets
                $opponentGroupteam = $this->GroupTeams->find('all', array(
                    'conditions' => array('GroupTeams.group_id' => $groupteam->group_id, 'GroupTeams.placeNumber' => $msc->placenumberTeam1 == $groupteam->placeNumber ? $msc->placenumberTeam2 : $msc->placenumberTeam1),
                ))->first();
                /**
                 * @var GroupTeam $opponentGroupteam
                 */

                if ($opponentGroupteam) {
                    $conditionsArray = array('Groups.year_id !=' => $year->id,
                        'OR' => array(
                            'team1_id' => $groupteam->team_id,
                            'team2_id' => $groupteam->team_id
                        ),
                        'AND' => array(
                            'OR' => array(
                                'team1_id' => $opponentGroupteam->team_id,
                                'team2_id' => $opponentGroupteam->team_id
                            )));
                    $prevYearsMatches = $this->getMatches($conditionsArray);
                    $countPrevYearsMatches += (is_array($prevYearsMatches) ? count($prevYearsMatches) : 0);

                    $prevYearsMatchesSameSport = $this->getMatches(array_merge($conditionsArray, array('sport_id' => $msc->sport_id)));
                    $countPrevYearsMatchesSameSport += (is_array($prevYearsMatchesSameSport) ? count($prevYearsMatchesSameSport) : 0);
                }
            }

            if ($currentDay_id > 1 && in_array($groupteam->prevPlaceNumber, array($msc->placenumberTeam1, $msc->placenumberTeam2))) {
                // check Day 1 from same year for doublets
                $prevDayOpponentGroupteam = $this->GroupTeams->find('all', array(
                    'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
                    'conditions' => array('GroupTeams.group_id' => $groupteam->prevGroupId, 'GroupTeams.placeNumber' => $msc->placenumberTeam1 == $groupteam->prevPlaceNumber ? $msc->placenumberTeam2 : $msc->placenumberTeam1, 'Groups.year_id' => $year->id, 'Groups.day_id' => ($this->getCurrentDayId() - 1)),
                ))->first();
                /**
                 * @var GroupTeam $prevDayOpponentGroupteam
                 */
                $prevDayOpponentTeamIds[$msc->sport_id] ??= array();
                $prevDayOpponentTeamIds[$msc->sport_id][] = $prevDayOpponentGroupteam->team_id;
            }

            if (in_array($groupteam->placeNumber, array($msc->placenumberTeam1, $msc->placenumberTeam2))) {
                $currentOpponentGroupteam = $this->GroupTeams->find('all', array(
                    'fields' => array(
                        'GroupTeams.id',
                        'GroupTeams.team_id',
                        'Teams.calcTotalPointsPerYear',
                    ),
                    'contain' => array(
                        'Groups' => array('fields' => array('year_id', 'day_id')),
                        'Teams' => array('fields' => array('calcTotalPointsPerYear')),
                    ),
                    'conditions' => array('GroupTeams.group_id' => $groupteam->group_id, 'GroupTeams.placeNumber' => $msc->placenumberTeam1 == $groupteam->placeNumber ? $msc->placenumberTeam2 : $msc->placenumberTeam1, 'Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId()),
                ))->first();
                /**
                 * @var GroupTeam $currentOpponentGroupteam
                 */
                $currentOpponentTeamIds[$msc->sport_id] = $currentOpponentTeamIds[$msc->sport_id] ?? array();
                $currentOpponentTeamIds[$msc->sport_id][] = $currentOpponentGroupteam->team_id;

                if ($currentOpponentGroupteam->team && ($currentOpponentGroupteam->team)->calcTotalPointsPerYear) {
                    $currentOpponentTeamRankingPointsPerYear[] = ($currentOpponentGroupteam->team)->calcTotalPointsPerYear;
                }

                if ($currentDay_id > 1) {
                    $currentOpponentPrevGroupteam = $this->GroupTeams->find('all', array(
                        'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
                        'conditions' => array('GroupTeams.team_id' => $currentOpponentGroupteam->team_id, 'Groups.year_id' => $year->id, 'Groups.day_id' => ($this->getCurrentDayId() - 1)),
                    ))->first();
                    /**
                     * @var GroupTeam $currentOpponentPrevGroupteam
                     */
                    $currentOpponentTeamPrevRankings[] = $currentOpponentPrevGroupteam->calcRanking;
                }
            }
        }

        $countPrevYearsDuplicates = array('countSameMatch' => $countPrevYearsMatches / 2, 'countSameMatchSameSport' => $countPrevYearsMatchesSameSport / 2);
        $countPrevDayDuplicates = $this->countDuplicates($prevDayOpponentTeamIds, $currentOpponentTeamIds);
        $avgOpponentPrevDayRanking = count($currentOpponentTeamPrevRankings) ? $this->getAvgOpponentPrevDayRanking($currentOpponentTeamPrevRankings) : null;
        $avgOpponentRankingPointsPerYear = count($currentOpponentTeamRankingPointsPerYear) ? round(array_sum($currentOpponentTeamRankingPointsPerYear) / count($currentOpponentTeamRankingPointsPerYear), 2) : null;

        return array(
            'countPrevYearsDuplicates' => $countPrevYearsDuplicates,
            'countPrevDayDuplicates' => $countPrevDayDuplicates,
            'avgOpponentPrevDayRanking' => $avgOpponentPrevDayRanking,
            'avgOpponentRankingPointsPerYear' => $avgOpponentRankingPointsPerYear,
        );
    }

    private function countDuplicates(array $array1, array $array2): array
    {
        $countSameMatch = 0;
        $countSameMatchSameSport = 0;

        foreach ($array1 as $sport_key1 => $ar1) {
            foreach ($ar1 as $value) {
                foreach ($array2 as $sport_key2 => $ar2) {
                    foreach ($ar2 as $value2) {
                        if ($value == $value2) {
                            $countSameMatch++;
                            if ($sport_key1 == $sport_key2) {
                                $countSameMatchSameSport++;
                                continue;
                            }
                        }
                    }
                }
            }
        }

        return array('countSameMatch' => $countSameMatch / 2, 'countSameMatchSameSport' => $countSameMatchSameSport / 2);  // only half because of double count for both opponents
    }


    private function getAvgOpponentPrevDayRanking(array $array): float|int
    {
        $c = 0;
        $sum = 0;

        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $value = ($value - 1) % 4 + 1;
                $sum += $value;
                $c++;
            }
        }

        return $sum / $c;
    }


    private function getNewPlaceNumberRandom(int $prevRankingInTeam, int $prevGroupPosNumber, int $groupPosNumber, array $rand_array): int
    {
        // first 3 parameters range 0..3
        $prevGroupPosNumber = $rand_array[$groupPosNumber][0] ? (3 - $prevGroupPosNumber) : $prevGroupPosNumber;
        $prevRankingInTeam = $rand_array[$groupPosNumber][1] ? (3 - $prevRankingInTeam) : $prevRankingInTeam;

        switch ($prevRankingInTeam) {
            case $rand_array[$groupPosNumber][2][0]:
                return 4 - $prevGroupPosNumber;
            case $rand_array[$groupPosNumber][2][1]:
                return 8 - $prevGroupPosNumber;
            case $rand_array[$groupPosNumber][2][2]:
                return 12 - $prevGroupPosNumber;
            case $rand_array[$groupPosNumber][2][3]:
                return 16 - $prevGroupPosNumber;
        }

        return 0; // unreachable
    }

}
