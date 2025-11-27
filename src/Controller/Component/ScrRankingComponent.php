<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;

class ScrRankingComponent extends Component
{
    public function getScrRanking(int $yearId, int $limit = null, string $orderDir = 'ASC'): array|false
    {
        $return = FactoryLocator::get('Table')->get('TeamYears')->find('all', array(
            'fields' => array('scrRanking', 'scrPoints', 'team_name' => 'Teams.name'),
            'conditions' => array('scrRanking IS NOT' => null, 'year_id' => $yearId),
            'contain' => array('Teams')
        ))->orderBy(array('scrRanking' => 'ASC'))->toArray();

        if ($limit) {
            $return = array_slice($return, 0, $limit);
        }
        if ($orderDir == 'DESC') {
            $return = array_reverse($return);
        }

        return $return;
    }
}
