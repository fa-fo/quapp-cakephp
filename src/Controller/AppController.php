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

use App\Model\Entity\GroupTeam;
use App\Model\Entity\Login;
use App\Model\Entity\Match4;
use App\Model\Entity\Team;
use App\Model\Entity\Year;
use App\View\PdfView;
use Cake\Controller\Controller;
use Cake\Datasource\ConnectionManager;
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
 * @property \App\Controller\Component\CalcComponent $Calc
 * @property \App\Controller\Component\MatchGetComponent $MatchGet
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

    public function clearTest(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();

            if ($settings['isTest'] ?? 0) {
                $rc = 0;

                $conn = ConnectionManager::get('default');
                /**
                 * @var \Cake\Database\Connection $conn
                 */
                $rc += $conn->execute("DELETE ptr FROM push_token_ratings ptr WHERE 1")->rowCount();
                $rc += $conn->execute("DELETE ml FROM matchevent_logs ml LEFT JOIN `matches` m ON ml.match_id=m.id LEFT JOIN `groups` g ON m.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE m FROM `matches` m LEFT JOIN `groups` g ON m.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE gt FROM group_teams gt LEFT JOIN `groups` g ON gt.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE g FROM `groups` g LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE ty FROM team_years ty LEFT JOIN years y ON ty.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE FROM teams WHERE testTeam=1")->rowCount();
                $rc += $conn->execute("UPDATE settings SET value=1 WHERE name = 'currentDay_id'")->rowCount();
                $rc += $conn->execute("UPDATE settings SET value=0 WHERE name = 'showEndRanking'")->rowCount();
                $rc += $conn->execute("UPDATE push_tokens SET ptrPoints=0 WHERE 1")->rowCount();
                $rc += $conn->execute("UPDATE push_tokens SET ptrRanking=NULL WHERE 1")->rowCount();
                if ($settings['usePlayOff'] == 0) {
                    $rc += $conn->execute("UPDATE settings SET value=0 WHERE name = 'alwaysAutoUpdateResults'")->rowCount();
                }

                if ($_SERVER['SERVER_NAME'] == 'localhost') {
                    //$conn->execute("CALL reset_autoincrement('matchevent_logs')");
                    //$conn->execute("CALL reset_autoincrement('matches')");
                    //$conn->execute("CALL reset_autoincrement('group_teams')");
                    //$conn->execute("CALL reset_autoincrement('groups')");
                    //$conn->execute("CALL reset_autoincrement('team_years')");
                    //$conn->execute("CALL reset_autoincrement('years')");
                }

                // reset all time stats
                $this->updateCalcTotal($settings['currentYear_id'] - 1);

                $this->apiReturn(array('rows affected' => $rc));
            }
        }
    }

    public function clearMatchesAndLogs(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();

            if ($settings['isTest'] ?? 0) {
                $rc = 0;
                $conn = ConnectionManager::get('default');
                /**
                 * @var \Cake\Database\Connection $conn
                 */
                $rc += $conn->execute("DELETE ptr FROM push_token_ratings ptr WHERE 1")->rowCount();
                $rc += $conn->execute("DELETE ml FROM matchevent_logs ml LEFT JOIN `matches` m ON ml.match_id=m.id LEFT JOIN `groups` g ON m.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE m FROM `matches` m LEFT JOIN `groups` g ON m.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("UPDATE push_tokens SET ptrPoints=0 WHERE 1")->rowCount();
                $rc += $conn->execute("UPDATE push_tokens SET ptrRanking=NULL WHERE 1")->rowCount();
                $this->apiReturn(array('rows affected' => $rc));
            }
        }
    }

    protected function updateCalcTotal(int $yearId): int
    {
        $conn = ConnectionManager::get('default');
        /**
         * @var \Cake\Database\Connection $conn
         */
        $conn->execute(file_get_contents(__DIR__ . "/sql/setnull_team_calcTotal.sql"));
        $conn->execute(file_get_contents(__DIR__ . "/sql/update_team_calcTotal.sql"));
        $conn->execute(file_get_contents(__DIR__ . "/sql/update_team_calcPower.sql"), ['year_id' => $yearId]);

        // Add prev team names points:
        $conditionsArray = array('Teams.calcTotalRankingPoints IS NOT' => null, 'Teams.hidden' => 0);

        $teams = $this->getTeams($conditionsArray, array(
            'PrevTeams' => array('fields' => array('id', 'name', 'calcTotalYears', 'calcTotalRankingPoints', 'calcTotalChampionships', 'prevTeam_id')),
            'PrevTeams.PrevTeams' => array('fields' => array('id', 'name', 'calcTotalYears', 'calcTotalRankingPoints', 'calcTotalChampionships')),
        ))->toArray();

        $prevTeamIds = array();
        foreach ($teams as $team) {
            $prevTeamIds[] = $this->addFromPrevNames($team, $team['prev_team']);
        }

        usort($teams, function ($a, $b) {
            return $b['calcTotalRankingPoints'] <=> $a['calcTotalRankingPoints'];
        });

        $c = 0;
        // set new ranking with points from prev team_names
        foreach ($teams as $team) {
            $t = $this->fetchTable('Teams')->find()->where(['id' => $team['id']])->first();
            /**
             * @var Team $t
             */
            if (in_array($team['team_id'], $prevTeamIds)) {
                $t->set('calcTotalRanking', null);
            } else {
                $c++;
                $t->set('calcTotalRanking', $c);
                if ($team['prev_team']) {
                    $t->set('calcTotalYears', $team['calcTotalYears']);
                    $t->set('calcTotalRankingPoints', $team['calcTotalRankingPoints']);
                    $t->set('calcTotalChampionships', $team['calcTotalChampionships']);
                    $t->set('calcTotalPointsPerYear', floor(100 * ($team['calcTotalRankingPoints'] / $team['calcTotalYears'])) / 100);
                }
            }

            $this->fetchTable('Teams')->save($t);
        }

        return $c;
    }

    private function addFromPrevNames(Team $team, Team|null $prevTeam): bool|int
    {
        $oldNameId = false;
        if ($prevTeam) {
            $oldNameId = $prevTeam['id'];
            $team['calcTotalYears'] += $prevTeam['calcTotalYears'];
            $team['calcTotalRankingPoints'] += $prevTeam['calcTotalRankingPoints'];
            $team['calcTotalChampionships'] += $prevTeam['calcTotalChampionships'];

            $this->addFromPrevNames($team, $prevTeam['prev_team']);
        }
        return $oldNameId;
    }

    protected function getTeams(array $conditionsArray, array $containArray = array()): \Cake\ORM\Query
    {
        return $this->fetchTable('Teams')->find('all', array(
            'fields' => array('id', 'team_id' => 'Teams.id', 'team_name' => 'Teams.name', 'calcTotalYears', 'calcTotalRankingPoints', 'calcTotalPointsPerYear', 'calcTotalChampionships', 'calcTotalRanking'),
            'contain' => $containArray,
            'conditions' => $conditionsArray,
            'order' => array('Teams.calcTotalRanking' => 'ASC')
        ));
    }

    protected function checkUsernamePassword(string $name, string $password): int|bool
    {
        $return = false;

        if ($this->request->is('post')) {
            $login = $this->fetchTable('Logins')->find('all', array(
                'conditions' => array('name' => $name),
            ))->first();
            /**
             * @var Login|null $login
             */
            if ($login && ($login->id ?? 0) > 0) {
                if ($login->failedlogincount < 100 && md5($password) == $login->password) {
                    $return = $login->id;
                    $login->set('failedlogincount', 0);
                } else {
                    $login->set('failedlogincount', $login->failedlogincount + 1);
                }
                $this->fetchTable('Logins')->save($login);
            }
        }

        return $return;
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
