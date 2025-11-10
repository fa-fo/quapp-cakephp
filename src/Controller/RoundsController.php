<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Round;

/**
 * Rounds Controller
 *
 * @property \App\Model\Table\RoundsTable $Rounds
 * @property \App\Controller\Component\CacheComponent $Cache
 */
class RoundsController extends AppController
{
    public function all(string $adminView = '', string $offset = '0'): void
    {
        // only current day !!!
        $settings = $this->Cache->getSettings();
        $year_id = $settings['currentYear_id'];
        $day_id = $settings['currentDay_id'];

        $currentYear = $this->Cache->getCurrentYear()->toArray();
        $day = $currentYear['day' . $day_id]->i18nFormat('yyyy-MM-dd');
        $adminView = (int)$adminView;

        $year = array();
        $year['rounds'] = $this->Rounds->find('all', array(
            'fields' => array('id', 'timeStartDay' . $day_id),
            'order' => array('id' => 'ASC')
        ))->toArray();

        foreach ($year['rounds'] as $r) {
            /**
             * @var Round $r
             */
            if ($adminView) {
                $conditionsArray = array(
                    'Groups.year_id' => $year_id,
                    'Groups.day_id' => $day_id,
                    'round_id' => $r->id,
                );

                $query1 = $this->fetchTable('Matches')->find('all', array(
                    'contain' => array('Groups'),
                    'conditions' => $conditionsArray
                ));

                $r['matchesCount'] = $query1->count();

                $query2 = $this->fetchTable('Matches')->find('all', array(
                    'contain' => array('Groups'),
                    'conditions' => array_merge($conditionsArray, array('resultTrend IS NOT' => null))
                ));

                $r['matchesConfirmed'] = $query2->count();

                $query3 = $this->fetchTable('Matches')->find('all', array(
                    'contain' => array('Groups'),
                    'conditions' => array_merge($conditionsArray, array('canceled' => 0, 'refereeTeam_id IS' => null, 'OR' => array('refereeName IS' => null, 'refereeName' => '')))
                ));

                $r['matchesWithoutReferee'] = $query3->count();
            }

            $r['timeStart'] = $day . ' ' . $r['timeStartDay' . $day_id]->i18nFormat('HH:mm:ss');
            unset($r['timeStartDay' . $day_id]); // no need
        }

        $year['currentRoundId'] = $this->getCurrentRoundId($year_id, $day_id, (int)$offset);
        $this->apiReturn($year);
    }
}
