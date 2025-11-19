<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;

use App\Model\Entity\Year;
use App\View\PdfView;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\View\JsonView;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/4/en/controllers.html#the-app-controller
 * @property \App\Controller\Component\CacheComponent $Cache
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * @return void
     * @throws \Exception
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Cache');
        $this->loadComponent('Calc');
        $this->loadComponent('GroupGet');
        $this->loadComponent('MatchGet');
        $this->loadComponent('PlayOff');
        $this->loadComponent('PtrRanking');
        $this->loadComponent('Security');
    }

    public function viewClasses(): array
    {
        return [JsonView::class, PdfView::class];
    }

    public function apiReturn(mixed $object, int $year_id = 0, int $day_id = 0): \Cake\Http\Response
    {
        if ($object) {
            $year = $this->Cache->getCurrentYear()->toArray();
            $year['settings'] = $this->Cache->getSettings();

            // todo: delete after V3.0 complete rollout:
            $year['isTest'] = $year['settings']['isTest'];
            $year['isCurrent'] = $year['settings']['currentYear_id'] == $year['id'] ? 1 : 0;
            $year['currentDay_id'] = $year['settings']['currentDay_id'];
            $year['alwaysAutoUpdateResults'] = $year['settings']['alwaysAutoUpdateResults'];
            // todo end

            $year['day'] = $year['day' . $year['settings']['currentDay_id']];

            $object_array = compact('object');
            $return = array_merge(array('status' => 'success'), array('year' => $year), $object_array);

            // only for archive
            if (($year_id && $year_id != $year['id']) || ($day_id && $day_id != $year['settings']['currentDay_id'])) {
                $yearSelected = $this->fetchTable('Years')->find('all', array(
                    'fields' => array('id', 'name', $day_id ? 'day' . $day_id : 'day1'),
                    'conditions' => array('id' => $year_id)
                ))->first()->toArray();

                if ($day_id) {
                    $yearSelected['day'] = $yearSelected['day' . $day_id];
                    unset($yearSelected['day' . $day_id]);
                } else {
                    $yearSelected['daysWithGroups'] = $this->fetchTable('Groups')->find('all', array(
                        'conditions' => array('year_id' => $yearSelected['id']),
                        'group' => 'day_id'
                    ))->count();
                }

                $return = array_merge($return, array('yearSelected' => $yearSelected));
            }

            if (is_array($return['object']) && ($return['object']['currentRoundId'] ?? 0) > 0) {
                $return['object']['secondsUntilReload'] = $this->getSecondsUntilReload($return['object']['currentRoundId'], $year['settings']);
            }

            $this->set($return);
        } else {
            $this->set(array());
        }

        $this->viewBuilder()->setClassName('Json');

        return $this->response;
    }

    public function pdfReturn(): \Cake\Http\Response
    {
        $this->viewBuilder()->setClassName('Pdf');

        return $this->response;
    }

    public function beforeRender(EventInterface $event): void
    {
        $this->viewBuilder()->setOption('serialize', true);
    }

    protected function getTeamsCountPerGroup(Year $year): int
    {
        return $year->teamsCount > 24 ? 16 : $year->teamsCount;
    }

    protected function getCurrentRoundId(int $yearId = 0, int $dayId = 0, int $offset = 0): int
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

            $cRound = $this->fetchTable('Rounds')->find('all', array(
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

    private function getSecondsUntilReload(int $currentRoundId, array $settings): array
    {
        $return = array(0, 0);

        if ($currentRoundId > 0) {
            $time = $this->getQTime($settings);
            $reloadOffset0 = $settings['time2ConfirmMinsAfterFrom'] + 1;
            $reloadOffset1 = 1;

            // next confirmation time
            $cRound = $this->fetchTable('Rounds')->find('all', array(
                'conditions' => array('timeStartDay' . $settings['currentDay_id'] . ' >=' => $time->subMinutes($reloadOffset0)),
                'order' => array('id' => 'ASC')
            ))->first();

            if ($cRound) {
                $rs = DateTime::createFromFormat('H:i:s', $cRound['timeStartDay' . $settings['currentDay_id']]->i18nFormat('HH:mm:ss'));
                $rTime0 = $rs->addMinutes($reloadOffset0);
                $return[0] = max($time->diffInSeconds($rTime0, false), 0);
            }

            // next round start time
            $nRound = $this->fetchTable('Rounds')->find()->where(['id' => $currentRoundId + 1])->first();
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
