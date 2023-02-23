<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Round;
use Cake\I18n\FrozenTime;

/**
 * Rounds Controller
 *
 * @property \App\Model\Table\RoundsTable $Rounds
 */
class RoundsController extends AppController
{
    public function all($includeStats = false, $year_id = false, $day_id = false)
    {
        $settings = $this->getSettings();
        $year = $this->getCurrentYear();

        $year_id = $year_id ?: $year->id;
        $day_id = $day_id ?: $this->getCurrentDayId();

        $year = array();
        $year['rounds'] = $this->Rounds->find('all', array(
            'fields' => array('id', 'timeStartDay' . $day_id),
            'order' => array('id' => 'ASC')
        ))->toArray();

        $this->loadModel('Matches');

        foreach ($year['rounds'] as $r) {
            /**
             * @var Round $r
             */
            $conditionsArray = array(
                'Groups.year_id' => $year_id,
                'Groups.day_id' => $day_id,
                'round_id' => $r->id
            );

            $query1 = $this->Matches->find('all', array(
                'contain' => array('Groups'),
                'conditions' => $conditionsArray
            ));

            $r['matchesCount'] = $query1->count();

            if ($includeStats) {
                $query2 = $this->Matches->find('all', array(
                    'contain' => array('Groups'),
                    'conditions' => array_merge($conditionsArray, array('resultTrend IS NOT' => null))
                ));

                $r['matchesWithResult'] = $query2->count();
            }

            $r['timeStart'] = $r['timeStartDay' . $day_id];
            unset($r['timeStartDay' . $day_id]); // no need
        }

        $this->apiReturn($year);
    }
}
