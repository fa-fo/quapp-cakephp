<?php

namespace App\Controller\Component;

use App\Model\Entity\Year;
use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;

class CacheComponent extends Component
{
    public function getSettings(): array
    {
        return Cache::remember('app_settings', function () {
            $settings = FactoryLocator::get('Table')->get('Settings')->find('list', [
                'keyField' => 'name', 'valueField' => 'value'
            ])->toArray();

            $settings['roundsCount'] = FactoryLocator::get('Table')->get('Rounds')->find('all')->count();

            $settings['groupsCount'] = FactoryLocator::get('Table')->get('Groups')->find('all', array(
                'conditions' => array('year_id' => $settings['currentYear_id'], 'day_id' => $settings['currentDay_id'], 'name !=' => 'Endrunde'),
            ))->count();

            return $settings;
        });
    }

    public function getCurrentYear(): Year
    {
        $year = Cache::remember('app_year', function () {
            $settings = $this->getSettings();

            return FactoryLocator::get('Table')->get('Years')->find('all', [
                'conditions' => array('id' => $settings['currentYear_id']),
            ])->first();
        });

        /**
         * @var Year $year
         */
        return $year;
    }

    public function getTeams(array $conditionsArray, array $containArray = array(), string $cacheKey = ''): \Cake\Datasource\ResultSetInterface
    {
        if ($cacheKey != '') {
            $teams = Cache::remember($cacheKey, function () use ($conditionsArray, $containArray) {
                return $this->getTeamsResultSet($conditionsArray, $containArray);
            });
        } else {
            $teams = $this->getTeamsResultSet($conditionsArray, $containArray);
        }
        return $teams;
    }

    private function getTeamsResultSet(array $conditionsArray, array $containArray): \Cake\Datasource\ResultSetInterface
    {
        return FactoryLocator::get('Table')->get('Teams')->find('all', array(
            'fields' => array('id', 'team_id' => 'Teams.id', 'team_name' => 'Teams.name', 'calcTotalYears', 'calcTotalRankingPoints', 'calcTotalPointsPerYear', 'calcTotalChampionships', 'calcTotalRanking'),
            'conditions' => $conditionsArray,
            'contain' => $containArray,
            'order' => array('Teams.calcTotalRanking' => 'ASC')
        ))->all();
    }
}
