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
    public function all(string $adminView = '', string $offset = '0'): void
    {
        // only current day !!!
        $settings = $this->Cache->getSettings();
        $currentYear = $this->Cache->getCurrentYear()->toArray();
        $day = $currentYear['day' . $settings['currentDay_id']]->i18nFormat('yyyy-MM-dd');
        $adminView = (int)$adminView;

        $year = array();
        $year['rounds'] = $this->Rounds->find('all', array(
            'fields' => array('id', 'timeStartDay' . $settings['currentDay_id']),
            'order' => array('id' => 'ASC')
        ))->toArray();

        foreach ($year['rounds'] as $r) {
            /**
             * @var Round $r
             */
            if ($adminView) {
                $conditionsArray = array(
                    'Groups.year_id' => $settings['currentYear_id'],
                    'Groups.day_id' => $settings['currentDay_id'],
                    'round_id' => $r->id,
                );

                $r->matchesCount = $this->fetchTable('Matches')->find('all', array(
                    'contain' => array('Groups'),
                    'conditions' => $conditionsArray
                ))->count();

                $r->matchesConfirmed = $this->fetchTable('Matches')->find('all', array(
                    'contain' => array('Groups'),
                    'conditions' => array_merge($conditionsArray, array('resultTrend IS NOT' => null))
                ))->count();

                $r->matchesWithoutReferee = $this->fetchTable('Matches')->find('all', array(
                    'contain' => array('Groups'),
                    'conditions' => array_merge($conditionsArray, array('canceled' => 0, 'refereeTeam_id IS' => null, 'OR' => array('refereeName IS' => null, 'refereeName' => '')))
                ))->count();
            }

            $property = 'timeStartDay' . $settings['currentDay_id'];
            $r->timeStart = $day . ' ' . $r->{$property}->i18nFormat('HH:mm:ss');
            unset($r->{$property}); // no need
        }

        $year['currentRoundId'] = $this->RoundGet->getCurrentRoundId($settings['currentYear_id'], $settings['currentDay_id'], (int)$offset);
        $this->apiReturn($year);
    }
}
