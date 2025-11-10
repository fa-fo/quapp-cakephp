<?php

namespace App\Controller\Component;

use App\Model\Entity\GroupTeam;
use App\Model\Entity\Match4;
use App\Model\Entity\Team;
use App\Model\Entity\Year;
use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;

/**
 * @property CacheComponent $Cache
 * @property MatchGetComponent $MatchGet
 */
class CalcComponent extends Component
{
    protected array $components = ['Cache', 'MatchGet'];

    public function getCalcRanking(int $team1_id = 0, int $team2_id = 0, bool $doSetRanking = true): array
    {
        $settings = $this->Cache->getSettings();
        $year = $this->Cache->getCurrentYear();
        /**
         * @var Year $year
         */

        $condGtArray = match (true) {
            $team1_id && $team2_id => ['GroupTeams.team_id IN' => [$team1_id, $team2_id]],
            $team1_id => ['GroupTeams.team_id' => $team1_id],
            default => [],
        };

        $groupTeams = FactoryLocator::get('Table')->get('GroupTeams')->find('all', array(
            'contain' => array('Groups' => array('fields' => array('name', 'year_id', 'day_id'))),
            'conditions' => array_merge($condGtArray, array('Groups.year_id' => $year->id, 'Groups.day_id' => $settings['currentDay_id'])),
            'order' => array('GroupTeams.id' => 'ASC')
        ))->all();

        $countMatches = 0;
        foreach ($groupTeams as $gt) {
            /**
             * @var GroupTeam $gt
             */

            $calcCountMatches = 0;
            $calcGoalsScored = 0;
            $calcGoalsReceived = 0;
            $calcPointsPlus = 0;
            $calcPointsMinus = 0;

            $conditionsArray = array(
                'Groups.id' => $gt->group_id,
                'resultTrend IS NOT' => null,
                'OR' => array(
                    'team1_id' => $gt->team_id,
                    'team2_id' => $gt->team_id,
                )
            );

            $matches = $this->MatchGet->getMatches($conditionsArray);

            if (is_array($matches) && count($matches) > 0) {
                foreach ($matches as $m) {
                    /**
                     * @var Match4 $m
                     */

                    if ($year->alwaysAutoUpdateResults || $m->round->autoUpdateResults) {
                        $countMatches++;
                        $calcCountMatches++;

                        if ($m->resultTrend == 5) { // X:X-Wertung
                            $calcGoalsReceived += $this->getFactorsLeastCommonMultiple();
                            $calcPointsMinus += 2;
                        } else {
                            $calcGoalsScored += $gt->team_id == $m->team1_id ? $m->resultGoals1 : $m->resultGoals2;
                            $calcGoalsReceived += $gt->team_id == $m->team1_id ? $m->resultGoals2 : $m->resultGoals1;
                            $calcPointsPlus += $gt->team_id == $m->team1_id ? ($m->resultGoals1 > $m->resultGoals2 ? 2 : ($m->resultGoals1 < $m->resultGoals2 ? 0 : 1)) : ($m->resultGoals1 > $m->resultGoals2 ? 0 : ($m->resultGoals1 < $m->resultGoals2 ? 2 : 1));
                            $calcPointsMinus += $gt->team_id == $m->team1_id ? ($m->resultGoals1 > $m->resultGoals2 ? 0 : ($m->resultGoals1 < $m->resultGoals2 ? 2 : 1)) : ($m->resultGoals1 > $m->resultGoals2 ? 2 : ($m->resultGoals1 < $m->resultGoals2 ? 0 : 1));
                        }
                    }
                }

                $gt->set('calcCountMatches', (int)$calcCountMatches);
                $gt->set('calcGoalsScored', (int)$calcGoalsScored);
                $gt->set('calcGoalsReceived', (int)$calcGoalsReceived);
                $gt->set('calcGoalsDiff', (int)($calcGoalsScored - $calcGoalsReceived));
                $gt->set('calcPointsPlus', (int)$calcPointsPlus);
                $gt->set('calcPointsMinus', (int)$calcPointsMinus);

                FactoryLocator::get('Table')->get('GroupTeams')->save($gt);
            }
        }

        if ($doSetRanking) {
            $this->setRanking($year);
        }

        return array('countMatches' => $countMatches, 'countGroupTeams' => $groupTeams->count(), 'doSetRanking' => $doSetRanking);
    }

    private function setRanking(Year $year): void
    {
        $settings = $this->Cache->getSettings();
        $groupTeams = FactoryLocator::get('Table')->get('GroupTeams')->find('all', array(
            'contain' => array('Groups' => array('fields' => array('id', 'year_id', 'day_id'))),
            'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $settings['currentDay_id']),
            'order' => array('GroupTeams.group_id' => 'ASC', 'GroupTeams.canceled' => 'ASC', 'GroupTeams.calcPointsPlus' => 'DESC', 'GroupTeams.calcGoalsDiff' => 'DESC', 'GroupTeams.calcGoalsScored' => 'DESC')
        ))->all();

        $groupId = 0;
        $countRanking = 0;

        foreach ($groupTeams as $gt) { // set temporarily null because of unique values
            /**
             * @var GroupTeam $gt
             */
            $gt->set('calcRanking', null);
            FactoryLocator::get('Table')->get('GroupTeams')->save($gt);
        }

        foreach ($groupTeams as $gt) { // set correct ranking
            /**
             * @var GroupTeam $gt
             */
            $countRanking = ($groupId == $gt->group_id ? $countRanking + 1 : 1);
            $groupId = $gt->group_id;

            $gt->set('calcRanking', $countRanking);

            FactoryLocator::get('Table')->get('GroupTeams')->save($gt);
        }
    }

    public function getFactorsLeastCommonMultiple(): \GMP|int
    {
        $sports = FactoryLocator::get('Table')->get('Sports')->find()->all();

        $gmp = 1;
        foreach ($sports as $s) {
            $gmp = gmp_lcm($gmp, $s->goalFactor);
        }

        return $gmp;
    }

    public function updateCalcTotal(int $yearId): int
    {
        $conn = ConnectionManager::get('default');
        /**
         * @var \Cake\Database\Connection $conn
         */
        $conn->execute(file_get_contents(__DIR__ . "/sql/setnull_team_calcTotal.sql"));
        $conn->execute(file_get_contents(__DIR__ . "/sql/update_team_calcTotal.sql"));
        $conn->execute(file_get_contents(__DIR__ . "/sql/update_team_calcPower.sql"), ['year_id' => $yearId]);

        // Add prev team names points:
        $conditionsArray = array('Teams.calcTotalRankingPoints IS NOT' => null, 'Teams.hidden' => 0);

        $teams = $this->Cache->getTeams($conditionsArray, array(
            'PrevTeams' => array('fields' => array('id', 'name', 'calcTotalYears', 'calcTotalRankingPoints', 'calcTotalChampionships', 'prevTeam_id')),
            'PrevTeams.PrevTeams' => array('fields' => array('id', 'name', 'calcTotalYears', 'calcTotalRankingPoints', 'calcTotalChampionships')),
        ))->toArray();

        $prevTeamIds = array();
        foreach ($teams as $team) {
            $prevTeamIds[] = $this->addFromPrevNames($team, $team['prev_team']);
        }

        usort($teams, function ($a, $b) {
            return $b['calcTotalRankingPoints'] <=> $a['calcTotalRankingPoints'];
        });

        $c = 0;
        // set new ranking with points from prev team_names
        foreach ($teams as $team) {
            $t = FactoryLocator::get('Table')->get('Teams')->find()->where(['id' => $team['id']])->first();
            /**
             * @var Team $t
             */
            if (in_array($team['team_id'], $prevTeamIds)) {
                $t->set('calcTotalRanking', null);
            } else {
                $c++;
                $t->set('calcTotalRanking', $c);
                if ($team['prev_team']) {
                    $t->set('calcTotalYears', $team['calcTotalYears']);
                    $t->set('calcTotalRankingPoints', $team['calcTotalRankingPoints']);
                    $t->set('calcTotalChampionships', $team['calcTotalChampionships']);
                    $t->set('calcTotalPointsPerYear', floor(100 * ($team['calcTotalRankingPoints'] / $team['calcTotalYears'])) / 100);
                }
            }

            FactoryLocator::get('Table')->get('Teams')->save($t);
        }

        Cache::delete('app_calc_total');

        return $c;
    }

    private function addFromPrevNames(Team $team, Team|null $prevTeam): bool|int
    {
        $oldNameId = false;
        if ($prevTeam) {
            $oldNameId = $prevTeam['id'];
            $team['calcTotalYears'] += $prevTeam['calcTotalYears'];
            $team['calcTotalRankingPoints'] += $prevTeam['calcTotalRankingPoints'];
            $team['calcTotalChampionships'] += $prevTeam['calcTotalChampionships'];

            $this->addFromPrevNames($team, $prevTeam['prev_team']);
        }
        return $oldNameId;
    }

}
