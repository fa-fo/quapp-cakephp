<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Query\SelectQuery;

class PtrRankingComponent extends Component
{
    public function getPtrRanking(string $mode, int $yearId, int $limit = null, string $orderDir = 'ASC'): array|false
    {
        $return = false;
        $query = FactoryLocator::get('Table')->get('PushTokens')->find('all', array(
            'fields' => array('ptrRanking', 'ptrPoints', 'team_name' => 'Teams.name'),
            'conditions' => array('ptrRanking IS NOT' => null, 'my_year_id' => $yearId),
            'contain' => array('Teams')
        ));

        /**
         * @var SelectQuery $query
         */
        if ($mode == 'single') {
            $return = $query->orderBy(array('ptrRanking' => 'ASC'))->toArray();
        } else if ($mode == 'teams') {
            $teams = $query->select([
                'count' => $query->func()->count('*'),
                'ptrPoints' => $query->func()->sum('ptrPoints')
            ])
                ->groupBy('my_team_id')
                ->orderBy(array('ptrPoints' => 'DESC'))
                ->toArray();

            $c = 0;
            foreach ($teams as $t) {
                $c++;
                $t['ptrRanking'] = $c;
            }

            $return = $teams;
        }

        if (is_array($return)) {
            if ($limit) {
                $return = array_slice($return, 0, $limit);
            }
            if ($orderDir == 'DESC') {
                $return = array_reverse($return);
            }
        }

        return $return;
    }
}
