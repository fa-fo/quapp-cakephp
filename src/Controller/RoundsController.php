<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Round;

/**
 * Rounds Controller
 *
 * @property \App\Model\Table\RoundsTable $Rounds
 */
class RoundsController extends AppController
{
    public function all(string $includeStats = '', string $year_id = '', string $day_id = ''): void
    {
        // only current day !!!
        $settings = $this->getSettings();
        $currentYear = $this->getCurrentYear()->toArray();
        $day = $currentYear['day' . $settings['currentDay_id']]->i18nFormat('yyyy-MM-dd');

        $includeStats = (int)$includeStats;
        $year_id = (int)$year_id ?: $settings['currentYear_id'];
        $day_id = (int)$day_id ?: $settings['currentDay_id'];

        $year = array();
        $year['rounds'] = $this->Rounds->find('all', array(
            'fields' => array('id', 'timeStartDay' . $day_id),
            'order' => array('id' => 'ASC')
        ))->toArray();

        foreach ($year['rounds'] as $r) {
            /**
             * @var Round $r
             */
            $conditionsArray = array(
                'Groups.year_id' => $year_id,
                'Groups.day_id' => $day_id,
                'round_id' => $r->id
            );

            $query1 = $this->fetchTable('Matches')->find('all', array(
                'contain' => array('Groups'),
                'conditions' => $conditionsArray
            ));

            $r['matchesCount'] = $query1->count();

            if ($includeStats) {
                $query2 = $this->fetchTable('Matches')->find('all', array(
                    'contain' => array('Groups'),
                    'conditions' => array_merge($conditionsArray, array('resultTrend IS NOT' => null))
                ));

                $r['matchesWithResult'] = $query2->count();
            }

            $r['timeStart'] = $day . ' ' . $r['timeStartDay' . $day_id]->i18nFormat('HH:mm:ss');
            unset($r['timeStartDay' . $day_id]); // no need
        }

        $this->apiReturn($year);
    }
}
