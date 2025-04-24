<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query\SelectQuery;

class PtrRankingComponent extends Component
{
    /**
     * @param string $mode
     * @param int $yearId
     * @return ResultSetInterface|false
     */
    public function getPtrRanking(string $mode, int $yearId): ResultSetInterface|false
    {
        $return = false;
        $query = FactoryLocator::get('Table')->get('PushTokens')->find('all', array(
            'fields' => array('ptrRanking', 'ptrPoints', 'Teams.name'),
            'conditions' => array('ptrRanking IS NOT' => null, 'my_year_id' => $yearId),
            'contain' => array('Teams')
        ));

        /**
         * @var SelectQuery $query
         */
        if ($mode == 'single') {
            $return = $query->orderBy(array('ptrRanking' => 'ASC'))->all();
        } else if ($mode == 'teams') {
            $teams = $query->select([
                'count' => $query->func()->count('*'),
                'ptrPoints' => $query->func()->sum('ptrPoints')
            ])
                ->groupBy('my_team_id')
                ->orderBy(array('ptrPoints' => 'DESC'))
                ->all();

            $c = 0;
            foreach ($teams as $t) {
                $c++;
                $t['ptrRanking'] = $c;
            }

            $return = $teams;
        }

        return $return;
    }
}
