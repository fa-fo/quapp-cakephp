<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Group;
use App\Model\Entity\GroupTeam;
use App\Model\Entity\Match4schedulingPattern;
use App\Model\Entity\TeamYear;
use App\Model\Entity\Year;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\DateTime;

/**
 * GroupTeams Controller
 *
 * @property \App\Model\Table\GroupTeamsTable $GroupTeams
 * @property \App\Controller\Component\CacheComponent $Cache
 * @property \App\Controller\Component\GroupGetComponent $GroupGet
 * @property \App\Controller\Component\MatchGetComponent $MatchGet
 * @property \App\Controller\Component\RoundGetComponent $RoundGet
 * @property \App\Controller\Component\SecurityComponent $Security
 */
class GroupTeamsController extends AppController
{
    // getRanking
    public function all(string $group_id = '', string $adminView = ''): void
    {
        $group_id = (int)$group_id;
        $adminView = (int)$adminView;
        $group = $this->GroupGet->getPrevAndNextGroup($group_id);

        if ($group) {
            $group['showRanking'] = 1;
            $group['groupTeams'] = $this->getRanking($group);
            $settings = $this->Cache->getSettings();

            if (!$adminView && $group['year_id'] == $settings['currentYear_id']) {
                $group['isTest'] = $settings['isTest'];

                if ($group['day_id'] == 2
                    && $this->RoundGet->getCurrentRoundId($settings['currentYear_id'], 2, 10) > 12) {
                    if ($settings['showEndRanking'] == 0) {
                        $group['groupTeams'] = null;
                        $group['showRanking'] = 0;
                    }
                }

                if ($group['day_id'] == $settings['currentDay_id']) {
                    $group['currentRoundId'] = $this->RoundGet->getCurrentRoundId($group['year_id'], $group['day_id']);
                }
            }

            $this->apiReturn($group, $group['year_id'], $group['day_id']);
        }

        $this->apiReturn(array());
    }

    private function getRanking(array $group): array
    {
        return $this->GroupTeams->find('all', array(
            'contain' => array(
                'Groups' => array('fields' => array('group_id' => 'Groups.id', 'group_name' => 'Groups.name', 'year_id', 'day_id')),
                'Teams' => array('fields' => array('name'))
            ),
            'conditions' => array('GroupTeams.group_id' => $group['id'], 'Groups.year_id' => $group['year_id'], 'Groups.day_id' => $group['day_id'], 'Teams.hidden' => 0),
            'order' => array('Groups.id' => 'ASC', 'GroupTeams.canceled' => 'ASC', 'GroupTeams.calcRanking' => 'ASC', 'Teams.name' => 'ASC')
        ))->toArray();
    }

    public function pdfAllRankings(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();

            $groups = $this->fetchTable('Groups')->find('all', array(
                'fields' => array('id', 'name', 'year_id', 'day_id', 'teamsCount'),
                'conditions' => array('year_id' => $settings['currentYear_id'], 'day_id' => $settings['currentDay_id'], 'name !=' => 'Endrunde'),
                'order' => array('name' => 'ASC')
            ));

            $year = $this->Cache->getCurrentYear()->toArray();
            $day = DateTime::createFromFormat('Y-m-d H:i:s', $year['day' . $settings['currentDay_id']]->i18nFormat('yyyy-MM-dd HH:mm:ss'));

            foreach ($groups as $group) {
                /**
                 * @var Group $group
                 */
                $group['groupTeams'] = $this->getRanking($group->toArray());
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

    public function addAll(): void
    {
        $groupTeams = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year = $this->Cache->getCurrentYear();

            $oldGroupTeams = $this->GroupTeams->find('all', array(
                'contain' => array('Groups' => array('fields' => array('name', 'year_id', 'day_id'))),
                'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $settings['currentDay_id']),
                'order' => array('GroupTeams.id' => 'ASC')
            ));

            if ($oldGroupTeams->count() == 0) {
                $groups = $this->fetchTable('Groups')->find('all', array(
                    'conditions' => array('year_id' => $year->id, 'day_id' => $settings['currentDay_id']),
                    'order' => array('id' => 'ASC')
                ));

                if ($groups->count() > 0) {
                    $countGroup = 0;
                    foreach ($groups as $group) {
                        /**
                         * @var Group $group
                         */
                        if ($group->name != 'Endrunde') {
                            if ($settings['currentDay_id'] == 1) {
                                $groupTeams = array_merge($groupTeams, $this->addFromTeamYearsOrderById($year, $group, $countGroup));
                            } else {
                                $groupTeams = array_merge($groupTeams, $this->addFromPrevDayRanking($year, $group, $countGroup));
                            }
                        }
                        $countGroup++;
                    }
                }
            }
        }
        //$groupTeams = count($groupTeams) ? $groupTeams : false;

        $this->apiReturn(count($groupTeams));
    }

    private function addFromTeamYearsOrderById(Year $year, Group $group, int $countGroup): array
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
        $settings = $this->Cache->getSettings();
        $groupTeams = array();
        $orderArray = array('GroupTeams.calcRanking' => 'ASC', 'GroupTeams.group_id' => 'ASC'); // standard for 64 teams

        if ($year->teamsCount > 64) {
            $orderArray = array('GroupTeams.calcRanking' => 'ASC', 'GroupTeams.calcPointsPlus' => 'ASC', 'GroupTeams.calcGoalsDiff' => 'ASC', 'GroupTeams.calcGoalsScored' => 'ASC', 'GroupTeams.group_id' => 'ASC');
        }

        $prevGroupTeams = $this->GroupTeams->find('all', array(
            'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
            'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => ($settings['currentDay_id'] - 1)),
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


    public function sortPlaceNumberAfterAddAll(string $mode = 'none', string $rgMode = '1100'): void
    {
        $doCount = 0;
        $checkings = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year = $this->Cache->getCurrentYear();

            $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $settings['currentDay_id']);
            $existingMatches = $this->MatchGet->getMatches($conditionsArray);

            if (!$existingMatches) {
                $groups = $this->fetchTable('Groups')->find('all', array(
                    'conditions' => array('year_id' => $year->id, 'day_id' => $settings['currentDay_id']),
                    'order' => array('id' => 'ASC')
                ));

                $groupsCount = $groups->count();
                if ($groupsCount > 0) {
                    $teamsCountPerGroup = ($groups->toArray())[0]->teamsCount;

                    do {
                        $doCount++;
                        $rand4_array = array();

                        $ar = $checkings['countDoubleMatches']['countPrevLastYearMatchesSameSport'] ?? 0;
                        $rgMode = '';
                        if (is_array($ar)) {
                            foreach ($ar as $r) {
                                $rgMode .= ($r > 0 ? 1 : 0);
                            }
                        }

                        if ($mode == 'random4') {
                            $ra = range(0, 3);
                            for ($a = 0; $a < $groupsCount; $a++) { // each group separate random
                                for ($b = 0; $b < $teamsCountPerGroup / 4; $b++) { // each quartet separate random
                                    shuffle($ra);

                                    $c = 0;
                                    foreach ($ra as $v) { // need! because shuffle keeps combi key => value
                                        $ra[$c] = $v;
                                        $c++;
                                    }

                                    $rand4_array[$a][$b] = $ra; // for random placenumber sort
                                }
                            }
                        }

                        $rand2_array = array();

                        if ($mode == 'random2') {
                            $ra = range(0, $teamsCountPerGroup - 1);
                            for ($a = 0; $a < $groupsCount; $a++) { // each group separate random
                                shuffle($ra);

                                $c = 0;
                                foreach ($ra as $v) { // need! because shuffle keeps combi key => value
                                    $ra[$c] = $v;
                                    $c++;
                                }

                                $rand2_array[$a] = $ra; // for random placenumber sort
                            }
                        }

                        $options = array('sortmode' => $mode, 'rgMode' => $rgMode, 'year_id' => $year->id, 'currentDay_id' => $settings['currentDay_id'],
                            'rand4_array' => $rand4_array, 'rand2_array' => $rand2_array, 'groupsCount' => $groupsCount, 'teamsCountPerGroup' => $teamsCountPerGroup);

                        $groupTeams = $this->GroupTeams->find('all', array(
                            'contain' => array('Groups', 'Teams'),
                            'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $settings['currentDay_id']),
                            'order' => array('GroupTeams.group_id' => 'ASC', 'GroupTeams.id' => 'ASC')
                        ))->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($options) {
                            return $results->map(function ($row, $counter = 0) use ($options) {
                                //Adding Calculated Fields
                                // initial and for day 1: just some values to use switch options
                                $prevRankingInTeam = (($counter % 64) % $options['teamsCountPerGroup']) % 4;
                                $prevGroupPosNumber = (int)floor((($counter % ($options['teamsCountPerGroup'] * 4)) % $options['teamsCountPerGroup']) / 4);

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

                                    if ($options['groupsCount'] <= 4) { // not compatible with 96 teams modus
                                        $prevRankingInTeam = ($prevGroupteam->calcRanking - 1) % 4;
                                        $prevGroupPosNumber = $this->GroupGet->getGroupPosNumber($prevGroupteam->group_id) % 4;
                                    }
                                }

                                $groupPosNumber = $this->GroupGet->getGroupPosNumber($row['group_id']);
                                $row['groupPosNumber'] = $groupPosNumber;

                                switch ($options['sortmode']) {
                                    default:
                                    case 'none':  // no change
                                        $row['newPlaceNumber'] = $row['placeNumber'];
                                        break;
                                    case 'initial':
                                        $row['newPlaceNumber'] = ($counter % $options['teamsCountPerGroup']) + 1;
                                        break;
                                    case 'standard':  // group before ranking
                                        $row['newPlaceNumber'] = (int)($prevRankingInTeam + 1 + $prevGroupPosNumber * 4);
                                        break;
                                    case 'ranking': // ranking before group
                                        $row['newPlaceNumber'] = (int)(($prevRankingInTeam) * 4 + 1 + $prevGroupPosNumber);
                                        break;
                                    case 'random4':
                                        $row['newPlaceNumber'] = $this->getNewPlaceNumberRandom4($row['placeNumber'], $groupPosNumber, $options['rand4_array']);
                                        break;
                                    case 'random2':
                                        $row['newPlaceNumber'] = $this->getNewPlaceNumberRandom2($row['placeNumber'], $groupPosNumber, $options['rand2_array']);
                                        break;
                                }

                                if (substr($options['rgMode'], $groupPosNumber, 1) == '0') {
                                    $row['newPlaceNumber'] = $row['placeNumber'];
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
                        $checkings = $this->getCountCheckings($groupTeams, $groupsCount, $options['currentDay_id'], 0);

                    } while (($options['currentDay_id'] == 1 && array_sum($checkings['countDoubleMatches']['countPrevLastYearMatchesSameSport']) > 0 && $doCount < 100 && $options['sortmode'] == 'random2')
                    || ($options['currentDay_id'] == 2 && array_sum($checkings['countDoubleMatches']['countPrevLastYearMatchesSameSport']) > 0 && $doCount < 100 && $options['sortmode'] == 'random4'));
                }
            }
        }

        $checkings['doCount'] = $doCount;

        $this->apiReturn($checkings);
    }


    public function checkPlaceNumberAfterAddAll(): void
    {
        $checkings = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year = $this->Cache->getCurrentYear();

            $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $settings['currentDay_id']);
            $existingMatches = $this->MatchGet->getMatches($conditionsArray);

            if (!$existingMatches) {
                $groups = $this->fetchTable('Groups')->find('all', array(
                    'conditions' => array('year_id' => $year->id, 'day_id' => $settings['currentDay_id']),
                    'order' => array('id' => 'ASC')
                ));

                $groupsCount = $groups->count();
                if ($groupsCount > 0) {
                    $groupTeams = $this->GroupTeams->find('all', array(
                        'contain' => array('Groups', 'Teams'),
                        'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $settings['currentDay_id']),
                        'order' => array('GroupTeams.group_id' => 'ASC', 'GroupTeams.id' => 'ASC')
                    ))->all();

                    // check has to be after all set
                    $checkings = $this->getCountCheckings($groupTeams, $groupsCount, $settings['currentDay_id'], 1);
                }
            }
        }

        $this->apiReturn($checkings);
    }

    private function getCountCheckings(ResultSetInterface $groupTeams, int $groupsCount, int $currentDay_id, int $mode = 1): array
    {
        $year = $this->Cache->getCurrentYear();
        $countPrevYearsMatches = 0;
        $countPrevYearsMatchesSameSport = 0;
        $countPrevLastYearMatchesSameSport = array_pad(array(), $groupsCount, 0);
        $countPrevLastDayMatches = 0;

        $currentOpponentTeamRankingPointsPerYear = array();
        $currentOpponentTeamRankingPower = array();
        $currentOpponentTeamPrevRankings = array();

        $avgOpponentRankingPointsPerYear = array();
        $avgOpponentRankingPower = array();
        $avgOpponentPrevDayRanking = array();

        $teamsPerGroup = $this->getTeamsCountPerGroup($year);
        $tableName = 'MatchschedulingPattern' . $teamsPerGroup;
        $matchschedulings = $this->fetchTable($tableName)->find('all');

        $gtArray = array();
        foreach ($groupTeams as $gt) {
            $gtArray[$gt->group->name][$gt->placeNumber] = $gt;
        }

        for ($g = 0; $g < $groupsCount; $g++) {
            $groupName = $this->GroupGet->getGroupName($g);

            foreach ($matchschedulings as $msc) {
                /**
                 * @var Match4schedulingPattern $msc
                 */
                $conditionsArray = array(
                    'OR' => array(
                        'team1_id' => $gtArray[$groupName][$msc->placenumberTeam1]->team_id,
                        'team2_id' => $gtArray[$groupName][$msc->placenumberTeam1]->team_id
                    ),
                    'AND' => array(
                        'OR' => array(
                            'team1_id' => $gtArray[$groupName][$msc->placenumberTeam2]->team_id,
                            'team2_id' => $gtArray[$groupName][$msc->placenumberTeam2]->team_id
                        )));

                $countPrevLastYearMatchesSameSport[$g] += $this->fetchTable('Matches')
                    ->find('all', array('contain' => array('Groups'), 'conditions' => array_merge($conditionsArray, array('Groups.year_id' => $year->id - 1, 'sport_id' => $msc->sport_id))))
                    ->count();

                if ($mode == 1) {
                    $countPrevYearsMatches += $this->fetchTable('Matches')
                        ->find('all', array('contain' => array('Groups'), 'conditions' => array_merge($conditionsArray, array('Groups.year_id !=' => $year->id))))
                        ->count();
                    $countPrevYearsMatchesSameSport += $this->fetchTable('Matches')
                        ->find('all', array('contain' => array('Groups'), 'conditions' => array_merge($conditionsArray, array('Groups.year_id !=' => $year->id, 'sport_id' => $msc->sport_id))))
                        ->count();

                    $currentOpponentTeamRankingPointsPerYear[$groupName][$msc->placenumberTeam1][] = ($gtArray[$groupName][$msc->placenumberTeam2]->team)->calcTotalPointsPerYear;
                    $currentOpponentTeamRankingPointsPerYear[$groupName][$msc->placenumberTeam2][] = ($gtArray[$groupName][$msc->placenumberTeam1]->team)->calcTotalPointsPerYear;

                    $currentOpponentTeamRankingPower[$groupName][$msc->placenumberTeam1][] = ($gtArray[$groupName][$msc->placenumberTeam2]->team)->calcPowerRankingPoints;
                    $currentOpponentTeamRankingPower[$groupName][$msc->placenumberTeam2][] = ($gtArray[$groupName][$msc->placenumberTeam1]->team)->calcPowerRankingPoints;

                    if ($currentDay_id > 1) {
                        $countPrevLastDayMatches += $this->fetchTable('Matches')
                            ->find('all', array('contain' => array('Groups'), 'conditions' => array_merge($conditionsArray, array('Groups.year_id' => $year->id, 'Groups.day_id' => ($currentDay_id - 1)))))
                            ->count();

                        $currentOpponentPrevGroupteam1 = $this->GroupTeams->find('all', array(
                            'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
                            'conditions' => array('GroupTeams.team_id' => $gtArray[$groupName][$msc->placenumberTeam1]->team_id, 'Groups.year_id' => $year->id, 'Groups.day_id' => ($currentDay_id - 1)),
                        ))->first();
                        /**
                         * @var GroupTeam $currentOpponentPrevGroupteam1
                         */
                        $currentOpponentTeamPrevRankings[$groupName][$msc->placenumberTeam2][] = $this->getMode4Base1($currentOpponentPrevGroupteam1->calcRanking);

                        $currentOpponentPrevGroupteam2 = $this->GroupTeams->find('all', array(
                            'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
                            'conditions' => array('GroupTeams.team_id' => $gtArray[$groupName][$msc->placenumberTeam2]->team_id, 'Groups.year_id' => $year->id, 'Groups.day_id' => ($currentDay_id - 1)),
                        ))->first();
                        /**
                         * @var GroupTeam $currentOpponentPrevGroupteam2
                         */
                        $currentOpponentTeamPrevRankings[$groupName][$msc->placenumberTeam1][] = $this->getMode4Base1($currentOpponentPrevGroupteam2->calcRanking);
                    }
                }
            }

            if ($mode == 1) {
                foreach ($currentOpponentTeamRankingPointsPerYear[$groupName] as $placenumber => $opArray) {
                    $opArray = array_filter($opArray); // filter null
                    if (count($opArray) > 0) {
                        $avgOpponentRankingPointsPerYear[$groupName][$placenumber] = round(array_sum($opArray) / count($opArray), 2);
                    }
                }

                $avgOpponentRankingPointsPerYear[$groupName] = array('max' => max($avgOpponentRankingPointsPerYear[$groupName] ?? array(0)), 'min' => min($avgOpponentRankingPointsPerYear[$groupName] ?? array(0)));

                foreach ($currentOpponentTeamRankingPower[$groupName] as $placenumber => $prArray) {
                    $prArray = array_filter($prArray); // filter null
                    if (count($prArray) > 0) {
                        $avgOpponentRankingPower[$groupName][$placenumber] = round(array_sum($prArray) / count($prArray), 2);
                    }
                }

                $avgOpponentRankingPower[$groupName] = array('max' => max($avgOpponentRankingPower[$groupName] ?? array(0)), 'min' => min($avgOpponentRankingPower[$groupName] ?? array(0)));

                if ($currentDay_id > 1) {
                    foreach ($currentOpponentTeamPrevRankings[$groupName] as $placenumber => $orArray) {
                        if (count($orArray) > 0) {
                            $avgOpponentPrevDayRanking[$groupName][$placenumber] = array_sum($orArray) / count($orArray);
                        }
                    }

                    $avgOpponentPrevDayRanking[$groupName] = array('max' => max($avgOpponentPrevDayRanking[$groupName] ?? array(0)), 'min' => min($avgOpponentPrevDayRanking[$groupName] ?? array(0)));
                }
            }
        }

        $countDoubleMatches = array(
            'countPrevYearsMatches' => $countPrevYearsMatches,
            'countPrevYearsMatchesSameSport' => $countPrevYearsMatchesSameSport,
            'countPrevLastYearMatchesSameSport' => $countPrevLastYearMatchesSameSport,
            'countPrevLastDayMatches' => $countPrevLastDayMatches,
        );

        return array(
            'countDoubleMatches' => $countDoubleMatches,
            'avgOpponentRankingPointsPerYear' => $avgOpponentRankingPointsPerYear,
            'avgOpponentRankingPower' => $avgOpponentRankingPower,
            'avgOpponentPrevDayRanking' => $avgOpponentPrevDayRanking,
        );
    }

    private function getMode4Base1(int $ranking): int
    {
        return ($ranking - 1) % 4 + 1;
    }

    private function getCountCheckings2_deprecated(GroupTeam $groupteam, int $currentDay_id): array
    {
        $year = $this->Cache->getCurrentYear();
        $countPrevYearsMatches = 0;
        $countPrevYearsMatchesSameSport = 0;
        $countPrevLastYearMatchesSameSport = 0;
        $prevDayOpponentTeamIds = array();
        $currentOpponentTeamIds = array();
        $currentOpponentTeamPrevRankings = array();
        $currentOpponentTeamRankingPointsPerYear = array();

        $teamsPerGroup = $this->getTeamsCountPerGroup($year);
        $tableName = 'MatchschedulingPattern' . $teamsPerGroup;
        $matchschedulings = $this->fetchTable($tableName)->find('all');

        foreach ($matchschedulings as $msc) {
            /**
             * @var Match4schedulingPattern $msc
             */
            if (in_array($groupteam->placeNumber, array($msc->placenumberTeam1, $msc->placenumberTeam2))) {
                // check previous years for doublets
                $opponentGroupteam = $this->GroupTeams->find('all', array(
                    'conditions' => array('GroupTeams.group_id' => $groupteam->group_id, 'GroupTeams.placeNumber' => $msc->placenumberTeam1 == $groupteam->placeNumber ? $msc->placenumberTeam2 : $msc->placenumberTeam1),
                ))->first();
                /**
                 * @var GroupTeam|null $opponentGroupteam
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
                    $prevYearsMatches = $this->MatchGet->getMatches($conditionsArray);
                    $countPrevYearsMatches += (is_array($prevYearsMatches) ? count($prevYearsMatches) : 0);

                    $prevYearsMatchesSameSport = $this->MatchGet->getMatches(array_merge($conditionsArray, array('sport_id' => $msc->sport_id)));
                    $countPrevYearsMatchesSameSport += (is_array($prevYearsMatchesSameSport) ? count($prevYearsMatchesSameSport) : 0);

                    $prevLastYearMatchesSameSport = $this->MatchGet->getMatches(array_merge($conditionsArray, array('sport_id' => $msc->sport_id, 'Groups.year_id' => $year->id - 1)));
                    $countPrevLastYearMatchesSameSport += (is_array($prevLastYearMatchesSameSport) ? count($prevLastYearMatchesSameSport) : 0);
                }
            }

            if ($currentDay_id > 1 && in_array($groupteam->prevPlaceNumber, array($msc->placenumberTeam1, $msc->placenumberTeam2))) {
                // check Day 1 from same year for doublets
                $prevDayOpponentGroupteam = $this->GroupTeams->find('all', array(
                    'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
                    'conditions' => array('GroupTeams.group_id' => $groupteam->prevGroupId, 'GroupTeams.placeNumber' => $msc->placenumberTeam1 == $groupteam->prevPlaceNumber ? $msc->placenumberTeam2 : $msc->placenumberTeam1, 'Groups.year_id' => $year->id, 'Groups.day_id' => ($currentDay_id - 1)),
                ))->first();
                /**
                 * @var GroupTeam $prevDayOpponentGroupteam
                 */
                $prevDayOpponentTeamIds[$msc->sport_id] ??= array();
                $prevDayOpponentTeamIds[$msc->sport_id][] = $prevDayOpponentGroupteam->team_id;
            }

            if (in_array($groupteam->placeNumber, array($msc->placenumberTeam1, $msc->placenumberTeam2))) {
                $currentOpponentGroupteam = $this->GroupTeams->find('all', array(
                    'contain' => array('Groups', 'Teams'),
                    'conditions' => array('GroupTeams.group_id' => $groupteam->group_id, 'GroupTeams.placeNumber' => $msc->placenumberTeam1 == $groupteam->placeNumber ? $msc->placenumberTeam2 : $msc->placenumberTeam1, 'Groups.year_id' => $year->id, 'Groups.day_id' => $currentDay_id),
                ))->first();
                /**
                 * @var GroupTeam|null $currentOpponentGroupteam
                 */
                if ($currentOpponentGroupteam) {
                    $currentOpponentTeamIds[$msc->sport_id] = $currentOpponentTeamIds[$msc->sport_id] ?? array();
                    $currentOpponentTeamIds[$msc->sport_id][] = $currentOpponentGroupteam->team_id;

                    if (($currentOpponentGroupteam->team)->calcTotalPointsPerYear) {
                        $currentOpponentTeamRankingPointsPerYear[] = ($currentOpponentGroupteam->team)->calcTotalPointsPerYear;
                    }

                    if ($currentDay_id > 1) {
                        $currentOpponentPrevGroupteam = $this->GroupTeams->find('all', array(
                            'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
                            'conditions' => array('GroupTeams.team_id' => $currentOpponentGroupteam->team_id, 'Groups.year_id' => $year->id, 'Groups.day_id' => ($currentDay_id - 1)),
                        ))->first();
                        /**
                         * @var GroupTeam $currentOpponentPrevGroupteam
                         */
                        $currentOpponentTeamPrevRankings[] = $currentOpponentPrevGroupteam->calcRanking;
                    }
                }
            }
        }

        $countPrevYearsDuplicates = array('countSameMatch' => $countPrevYearsMatches / 2, 'countSameMatchSameSport' => $countPrevYearsMatchesSameSport / 2, 'countPrevLastYearMatchesSameSport' => $countPrevLastYearMatchesSameSport / 2);
        $countPrevDayDuplicates = $this->countDuplicates_deprecated($prevDayOpponentTeamIds, $currentOpponentTeamIds);
        $avgOpponentPrevDayRanking = count($currentOpponentTeamPrevRankings) > 0 ? $this->getAvgOpponentPrevDayRanking_deprecated($currentOpponentTeamPrevRankings) : null;
        $avgOpponentRankingPointsPerYear = count($currentOpponentTeamRankingPointsPerYear) > 0 ? round(array_sum($currentOpponentTeamRankingPointsPerYear) / count($currentOpponentTeamRankingPointsPerYear), 2) : null;

        return array(
            'countPrevYearsDuplicates' => $countPrevYearsDuplicates,
            'countPrevDayDuplicates' => $countPrevDayDuplicates,
            'avgOpponentPrevDayRanking' => $avgOpponentPrevDayRanking,
            'avgOpponentRankingPointsPerYear' => $avgOpponentRankingPointsPerYear,
        );
    }


    private function countDuplicates_deprecated(array $array1, array $array2): array
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


    private function getAvgOpponentPrevDayRanking_deprecated(array $array): float|int
    {
        $c = 0;
        $sum = 0;

        foreach ($array as $value) {
            $value = ($value - 1) % 4 + 1;
            $sum += $value;
            $c++;
        }

        return $c ? $sum / $c : 0;
    }

    private function getNewPlaceNumberRandom4(int $placeNumber, int $groupPosNumber, array $rand4_array): int
    {
        // change only within a quartet
        $quNumber = (int)floor(($placeNumber % 100 - 1) / 4); // use $placeNumber % 100 because of temp value 1000 + x
        $quPosNumber = ($placeNumber % 100 - 1) % 4;

        return $quNumber * 4 + $rand4_array[$groupPosNumber][$quNumber][$quPosNumber] + 1;
    }

    private function getNewPlaceNumberRandom2(int $placeNumber, int $groupPosNumber, array $rand2_array): int
    {
        return $rand2_array[$groupPosNumber][$placeNumber % 100 - 1] + 1; // use $placeNumber % 100 because of temp value 1000 + x
    }

}
