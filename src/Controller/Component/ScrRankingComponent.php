<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;

class ScrRankingComponent extends Component
{
    public function getScrRanking(int $yearId, int $limit = null, string $orderDir = 'ASC'): array
    {
        $teamYears = FactoryLocator::get('Table')->get('TeamYears')->find('all', array(
            'fields' => array('scrRanking', 'scrPoints', 'scrMatchCount', 'team_id' => 'Teams.id', 'team_name' => 'Teams.name'),
            'conditions' => array('scrRanking IS NOT' => null, 'year_id' => $yearId),
            'contain' => array('Teams')
        ))->orderBy(array('scrRanking' => 'ASC'))->toArray();

        if ($limit) {
            $teamYears = array_slice($teamYears, 0, $limit);
        }
        if ($orderDir == 'DESC') {
            $teamYears = array_reverse($teamYears);
        }

        $years = FactoryLocator::get('Table')->get('Years')->find('all', array(
            'conditions' => array('id >=' => 25),
        ))->orderBy(array('id' => 'DESC'))->toArray();

        $y = FactoryLocator::get('Table')->get('Years')->find()->where(['id' => $yearId])->first();

        return array('teamYears' => $teamYears, 'years' => $years, 'year_name' => $y->name);
    }
}
