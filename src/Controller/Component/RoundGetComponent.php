<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;

/**
 * @property CacheComponent $Cache
 */
class RoundGetComponent extends Component
{
    protected array $components = ['Cache'];

    public function getCurrentRoundId(int $yearId = 0, int $dayId = 0, int $offset = 0): int
    {
        $return = 0;
        $settings = $this->Cache->getSettings();
        $currentYear = $this->Cache->getCurrentYear()->toArray();
        $day = $currentYear['day' . $settings['currentDay_id']];
        $time = $this->getQTime($settings);

        if (($yearId == 0 || $yearId == $settings['currentYear_id'])
            && ($dayId == 0 || $dayId == $settings['currentDay_id'])
            && ($settings['isTest'] == 1 || $time->i18nFormat('yyyy-MM-dd') == $day->i18nFormat('yyyy-MM-dd'))
        ) {
            $time = $time->addMinutes($offset);

            $cRound = FactoryLocator::get('Table')->get('Rounds')->find('all', array(
                'conditions' => array('OR' => array('timeStartDay' . $dayId . ' <=' => $time, 'id' => 1)),
                'order' => array('id' => 'DESC')
            ))->first()->toArray();

            if ($cRound) {
                $return = $cRound['id'];
                $time = $time->subMinutes($offset); // return to orig
                $ct = DateTime::createFromFormat('H:i:s', $cRound['timeStartDay' . $dayId]->i18nFormat('HH:mm:ss'));
                if ($ct->diffInMinutes($time) > 40) {
                    $return = 0;
                }
            }
        }

        return $return;
    }

    public function getSecondsUntilReload(int $currentRoundId, array $settings): array
    {
        $return = array(0, 0);

        if ($currentRoundId > 0) {
            $time = $this->getQTime($settings);
            $reloadOffset0 = $settings['time2ConfirmMinsAfterFrom'] + 1;
            $reloadOffset1 = 1;

            // next confirmation time
            $cRound = FactoryLocator::get('Table')->get('Rounds')->find('all', array(
                'conditions' => array('timeStartDay' . $settings['currentDay_id'] . ' >=' => $time->subMinutes($reloadOffset0)),
                'order' => array('id' => 'ASC')
            ))->first();

            if ($cRound) {
                $rs = DateTime::createFromFormat('H:i:s', $cRound['timeStartDay' . $settings['currentDay_id']]->i18nFormat('HH:mm:ss'));
                $rTime0 = $rs->addMinutes($reloadOffset0);
                $return[0] = max($time->diffInSeconds($rTime0, false), 0);
            }

            // next round start time
            $nRound = FactoryLocator::get('Table')->get('Rounds')->find()->where(['id' => $currentRoundId + 1])->first();
            if ($nRound) {
                $ns = DateTime::createFromFormat('H:i:s', $nRound['timeStartDay' . $settings['currentDay_id']]->i18nFormat('HH:mm:ss'));
                $rTime1 = $ns->addMinutes($reloadOffset1);
                $return[1] = max($time->diffInSeconds($rTime1, false), 0);
            }
        }

        return $return;
    }

    private function getQTime(array $settings): DateTime
    {
        $qTime = DateTime::now();

        if ($settings['isTest'] == 1) {
            $qTime = $qTime->subHours($settings['currentDay_id'] == 2 ? 1 : 2);

            $cycle = 1 - (int)floor($qTime->hour / 8);
            // cycle => -1 or 0 or +1
            $qTime = $qTime->addHours($cycle * 8);

            $qTime = $qTime->addHours($settings['currentDay_id'] == 2 ? 1 : 2);

            $now = DateTime::now();
            $qTime = $qTime->setDate($now->year, $now->month, $now->day); // if day-1 change: go back to today
        }

        return $qTime;
    }
}
