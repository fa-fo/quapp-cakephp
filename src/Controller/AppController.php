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

use App\Model\Entity\Group;
use App\Model\Entity\GroupTeam;
use App\Model\Entity\Match4;
use App\Model\Entity\Round;
use App\Model\Entity\Year;
use Cake\Controller\Controller;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/4/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        //$this->loadComponent('Flash');


        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/4/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    public function apiReturn($object, $year_id = false, $day_id = false)
    {
        $this->RequestHandler->renderAs($this, 'json');

        if ($object) {
            $this->loadModel('Years');
            $year = $this->getCurrentYear()->toArray();

            $year['settings'] = $this->getSettings();

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
                $yearSelected = $this->Years->find('all', array(
                    'fields' => array('id', 'name', $day_id ? 'day' . $day_id : 'day1'),
                    'conditions' => array('id' => $year_id)
                ))->first()->toArray();

                if ($day_id) {
                    $yearSelected['day'] = $yearSelected['day' . $day_id];
                    unset($yearSelected['day' . $day_id]);
                } else {
                    $this->loadModel('Groups');
                    $yearSelected['daysWithGroups'] = $this->Groups->find('all', array(
                        'conditions' => array('year_id' => $yearSelected['id']),
                        'group' => 'day_id'
                    ))->count();
                }

                $return = array_merge($return, array('yearSelected' => $yearSelected));
            }

            $this->set($return);
        } else {
            $this->set($object);
        }
    }

    public function pdfReturn()
    {
        $this->RequestHandler->renderAs($this, 'pdf');
    }

    public function beforeRender(EventInterface $event)
    {
        $this->viewBuilder()->setOption('serialize', true);
        //$this->RequestHandler->renderAs($this, 'json');
    }

    protected function getSettings()
    {
        $this->loadModel('Settings');

        return $this->Settings->find('list', [
            'keyField' => 'name',
            'valueField' => 'value'
        ])->toArray();
    }

    protected function getCurrentYear()
    {
        $settings = $this->getSettings();
        $this->loadModel('Years');
        // todo: put currentYear_id inside return

        return $this->Years->find('all', array(
            'conditions' => array('id' => $settings['currentYear_id']),
        ))->first();
    }

    protected function getCurrentYearId()
    {
        return $this->getCurrentYear()->get('id');
    }

    protected function getCurrentDayId()
    {
        return ($this->getSettings())['currentDay_id'];
    }

    protected function getGroupByMatchId($id)
    {
        $this->loadModel('Matches');
        $this->loadModel('Groups');

        $match = $this->Matches->find()->where(['id' => $id])->first();
        $group = $match ? $this->Groups->find()->where(['id' => $match->get('group_id')])->first() : false;

        return $group ?: false;
    }

    protected function getGroupByTeamId($team_id, $year_id, $day_id)
    {
        $this->loadModel('Groups');
        $this->loadModel('GroupTeams');
        $groupteam = $this->GroupTeams->find('all', array(
            'contain' => array('Groups' => array('fields' => array('id', 'year_id', 'day_id'))),
            'conditions' => array('team_id' => $team_id, 'Groups.year_id' => $year_id, 'Groups.day_id' => $day_id),
        ))->first();

        $group = $groupteam ? $this->Groups->find()->where(['id' => $groupteam->get('group_id')])->first() : false;

        return $group ?: false;
    }

    protected function getPrevAndNextGroup($group_id)
    {
        $this->loadModel('Groups');
        $group = $this->Groups->find()->where(['id' => $group_id])->first();

        if ($group) {
            $countGroups = $this->Groups->find('all', array(
                'conditions' => array('year_id' => $group->year_id, 'day_id' => $group->day_id)
            ))->count();

            $groupPosNumber = $this->getGroupPosNumber($group_id);
            if ($groupPosNumber + 1 > 1) {
                $group['prevGroup'] = $this->Groups->find('all', array(
                    'fields' => array('group_id' => 'id', 'group_name' => 'name', 'id', 'name'),
                    'conditions' => array('id' => $group_id - 1)
                ))->first();
            }
            if ($groupPosNumber + 1 < $countGroups) {
                $group['nextGroup'] = $this->Groups->find('all', array(
                    'fields' => array('group_id' => 'id', 'group_name' => 'name', 'id', 'name'),
                    'conditions' => array('id' => $group_id + 1)
                ))->first();
            }
        }

        return $group;
    }

    protected function getScheduleShowTime($year_id, $day_id, $adminView = 0)
    {
        $settings = $this->getSettings();
        $currentYear = $this->getCurrentYear()->toArray();
        $stime = FrozenTime::createFromFormat('Y-m-d H:i:s', $currentYear['day' . $settings['currentDay_id']]->i18nFormat('yyyy-MM-dd HH:mm:ss'));
        $showTime = $stime->subHours($settings['showScheduleHoursBefore']);
        $now = FrozenTime::now();

        if ($settings['isTest'] || $adminView || $currentYear['id'] != $year_id || $settings['currentDay_id'] != $day_id || $now > $showTime) {
            return 0; // show Schedule
        }
        return $showTime; // do not show schedule
    }

    protected function getMatchesByTeam($team_id, $year_id, $day_id, $adminView = 0)
    {
        $showTime = $this->getScheduleShowTime($year_id, $day_id, $adminView);
        if ($showTime !== 0) {
            $return['showTime'] = $showTime;
        } else {
            $conditionsArray = array(
                'Groups.year_id' => $year_id,
                'Groups.day_id' => $day_id,
                'OR' => array(
                    'team1_id' => $team_id,
                    'team2_id' => $team_id,
                    'refereeTeam_id' => $team_id,
                    'refereeTeamSubst_id' => $team_id,
                )
            );

            $return['matches'] = $team_id ? $this->getMatches($conditionsArray) : false;

            $this->loadModel('GroupTeams');
            $groupteam = $this->GroupTeams->find('all', array(
                'contain' => array('Groups' => array('fields' => array('id', 'year_id', 'day_id'))),
                'conditions' => array('team_id' => $team_id, 'Groups.year_id' => $year_id, 'Groups.day_id' => $day_id),
            ))->first();

            if ($groupteam) {
                $this->loadModel('Groups');
                $group = $this->Groups->find()->where(['id' => $groupteam['group_id']])->first();
                $return['group']['group_id'] = $group->get('id');
                $return['group']['group_name'] = $group->get('name');
                $return['referee_group_name'] = $this->getRefereeGroup($group)->get('name');
            }
        }

        return $return;
    }

    protected function getMatches($conditionsArray, $includeLogs = 0, $sortBySportId = 1, $adminView = 0)
    {
        $query = $this->Matches->find('all', array(
            'contain' => array(
                'Rounds',
                'Groups' => array('fields' => array('group_name' => 'Groups.name', 'day_id')),
                'Groups.Years',
                'Sports' => array('fields' => array('id', 'name', 'code', 'goalFactor')),
                'Teams1' => array('fields' => array('name')),
                'Teams2' => array('fields' => array('name')),
                'Teams3' => array('fields' => array('name')),
                'Teams4' => array('fields' => array('name')),
            ),
            'conditions' => $conditionsArray,
            'order' => array('Rounds.id' => 'ASC', ($sortBySportId ? 'Sports.id' : 'Matches.id') => 'ASC', 'Matches.id' => 'ASC')
        ));

        $count = $query->count();

        if ($count > 0) {
            $settings = $this->getSettings();
            $matches = $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($conditionsArray, $includeLogs, $adminView, $settings) {
                return $results->map(function ($row) use ($conditionsArray, $includeLogs, $adminView, $settings) {
                    //Adding Calculated Fields
                    if (!isset($row['teams3'])) {
                        $row['teams3'] = array('name' => '-'); // needed!
                    }

                    if (isset($conditionsArray['OR']['refereeTeam_id']) && $conditionsArray['OR']['refereeTeam_id'] == $row['refereeTeam_id']) {
                        $row['isRefereeJob'] = 1;
                    }

                    // Start time
                    if ($row['group']['year']['day' . $row['group']['day_id']]) {
                        $s1 = ($row['group']['year']['day' . $row['group']['day_id']])->i18nFormat('yyyy-MM-dd');
                        $s2 = ($row['round']['timeStartDay' . $row['group']['day_id']])->i18nFormat('HH:mm:ss');
                        $row['matchStartTime'] = $s1 . ' ' . $s2;
                    }

                    if (!$row['round']['autoUpdateResults']
                        && $row['group']['year']['id'] == $settings['currentYear_id']
                        && !$adminView
                        && !$settings['alwaysAutoUpdateResults']
                        && $row['group']['day_id'] == $settings['currentDay_id']) {

                        $row['resultGoals1'] = null;
                        $row['resultGoals2'] = null;
                        $row['resultTrend'] = null;
                    } else {
                        $row['round']['autoUpdateResults'] = 1;
                    }

                    // no need
                    unset($row['group']['year']['teamsCount']);
                    unset($row['group']['year']['daysCount']);
                    unset($row['group']['year']['day1']);
                    unset($row['group']['year']['day2']);
                    unset($row['round']['timeStartDay1']);
                    unset($row['round']['timeStartDay2']);

                    if ($adminView) {
                        $this->loadModel('TeamYears');
                        $refereeTy = $this->TeamYears->find('all', array('conditions' => array('team_id' => ($row['refereeTeam_id'] ?? 0), 'year_id' => $row['group']['year']['id'])))->first();
                        $row['isRefereeCanceled'] = $refereeTy ? $refereeTy->get('canceled') : 1;
                    } else {
                        unset($row['refereePIN']); // security issue
                    }

                    if (!$includeLogs) {
                        unset($row['sport']['goalFactor']); // no need
                    }

                    if ($row['matchStartTime']) {
                        $stime = FrozenTime::createFromFormat('Y-m-d H:i:s', $row['matchStartTime']);
                        $now = FrozenTime::now();
                        $row['isTime2login'] = $settings['isTest'] || ($now > $stime->subMinutes($settings['time2LoginMinsBeforeFrom']) && $now < $stime->addMinutes($settings['time2LoginMinsAfterUntil'])) ? 1 : 0;
                        $row['isTime2confirm'] = $settings['isTest'] || ($now > $stime->addMinutes($settings['time2ConfirmMinsAfterFrom']) && $now < $stime->addMinutes($settings['time2ConfirmMinsAfterUntil'])) ? 1 : 0;
                    }

                    if ($includeLogs) {
                        $logs = $this->getLogs($row['id']);
                        $row['logsCalc'] = $logs['calc'] ?? array();
                        $row['logsCalc']['isLoggedIn'] = $row['logsCalc']['isLoggedIn'] ?? 0;

                        if ($adminView) {
                            $row['isResultOk'] = $this->isResultOk($row) ? 1 : 0;
                        }
                    }

                    return $row;
                });
            })->toArray();

            usort($matches, function ($a, $b) {
                return $a['matchStartTime'] <=> $b['matchStartTime'];
            });
        } else {
            $matches = false;
        }

        return $matches;
    }

    private function isResultOk($row): bool
    {
        return !!(
            isset($row['logsCalc']['teamWon']) &&
            (($row['logsCalc']['teamWon'] === 0 &&
                    (!isset($row['logsCalc']['score']) ||
                        (int)($row['logsCalc']['score'][$row['team1_id']] ?? 0) == (int)($row['logsCalc']['score'][$row['team2_id']] ?? 0)))
                ||
                ($row['logsCalc']['teamWon'] === 1 && isset($row['logsCalc']['score']) &&
                    (int)($row['logsCalc']['score'][$row['team1_id']] ?? 0) > (int)($row['logsCalc']['score'][$row['team2_id']] ?? 0))
                ||
                ($row['logsCalc']['teamWon'] === 2 && isset($row['logsCalc']['score']) &&
                    (int)($row['logsCalc']['score'][$row['team1_id']] ?? 0) < (int)($row['logsCalc']['score'][$row['team2_id']] ?? 0)
                )
            ));
    }

    protected function getLogs($match_id)
    {
        $this->loadModel('MatcheventLogs');
        $query = $this->MatcheventLogs->find('all', array(
            'contain' => array(
                'Matchevents' => array('fields' => array('code', 'name', 'playerFouledOutAfter', 'playerFoulSuspMinutes', 'showOnSportsOnly'))
            ),
            'conditions' => array('MatcheventLogs.match_id' => $match_id, 'MatcheventLogs.canceled' => 0),
            'order' => array('MatcheventLogs.id' => 'ASC')
        ));

        $count = $query->count();

        if ($count > 0) {
            $logs = $query->toArray();
            $logs['calc'] = $this->getCalcFromLogs($logs);
        } else {
            $logs = false;
        }

        return $logs;
    }


    protected function getCalcFromLogs($logs)
    {
        $this->loadModel('Matchevents');
        $calc = array();

        if ($logs && count($logs) > 0) {
            $offsetCount = 0;
            $allOffset = 0;
            $calc['maxOffset'] = 0;
            $calc['minOffset'] = 9999;
            $lastAliveTime = null;
            $matcheventFoulPersonal = false;

            foreach ($logs as $l) {
                if (isset($l['matchevent'])) {
                    $code = $l['matchevent']->code;

                    //Adding Calculated Fields
                    if (isset($l['team_id'])) {
                        $calc['score'][$l['team_id']] ??= 0;

                        if (strstr($code, 'FOUL_')) { // Card and Foul score
                            $calc['foulOutLogIds'] ??= array();
                            $calc['doubleYellowLogIds'] ??= array();
                            $calc[$code][$l['team_id']] ??= array();

                            // todo: delete after V2.0 complete rollout:
                            $calc[$code][$l['team_id']][] = $l['playerNumber'];
                            // todo: delete end

                            $code .= '_V2';
                            $calc[$code][$l['team_id']][$l['playerNumber']]['count'] ??= 0;
                            $calc[$code]['name'] = $l['matchevent']->name;

                            if ($l['matchevent']->showOnSportsOnly == 1) { // BB only
                                $calc['FOUL_PERSONAL_V2'][$l['team_id']][$l['playerNumber']]['count'] ??= 0; // needed for later increment

                                if (substr($code, 0, 13) == 'FOUL_PERSONAL') {
                                    $matcheventFoulPersonal = $l['matchevent'];
                                }
                                if (substr($code, 0, 15) == 'FOUL_TECH_FLAGR') { // add tech. foul to pers. foul count
                                    if ($calc['FOUL_PERSONAL_V2'][$l['team_id']][$l['playerNumber']]['count'] >= 0) { // add only if not foul-out yet
                                        $calc['FOUL_PERSONAL_V2'][$l['team_id']][$l['playerNumber']]['count']++;
                                    }
                                }
                            }

                            if ($calc[$code][$l['team_id']][$l['playerNumber']]['count'] >= 0) { // add only if not foul-out yet
                                $calc[$code][$l['team_id']][$l['playerNumber']]['count']++;
                            }

                            if ($l['matchevent']->playerFouledOutAfter
                                && $calc[$code][$l['team_id']][$l['playerNumber']]['count'] >= $l['matchevent']->playerFouledOutAfter) {
                                // set negative value as marker to foul-out players
                                $calc[$code][$l['team_id']][$l['playerNumber']]['count'] *= -1;
                                $calc['foulOutLogIds'][] = $l['id'];
                            }

                            if ($l['matchevent']->showOnSportsOnly == 1) { // BB only
                                // needed for case PF+PF+TF: set negative value as marker to foul-out players
                                if ($matcheventFoulPersonal
                                    && $calc['FOUL_PERSONAL_V2'][$l['team_id']][$l['playerNumber']]['count'] >= $matcheventFoulPersonal->playerFouledOutAfter) {
                                    $calc['FOUL_PERSONAL_V2'][$l['team_id']][$l['playerNumber']]['count'] *= -1;
                                    $calc['foulOutLogIds'][] = $l['id'];
                                }
                            }

                            if (substr($code, 0, 16) == 'FOUL_CARD_YELLOW' && $calc[$code][$l['team_id']][$l['playerNumber']]['count'] > 1) {
                                $calc['doubleYellowLogIds'][] = $l['id'];
                            }

                            if ($l['matchevent']->playerFoulSuspMinutes) {  // Fouls with suspension
                                $rtime = FrozenTime::createFromFormat('Y-m-d H:i:s', $l['datetimeSent']->i18nFormat('yyyy-MM-dd HH:mm:ss'));
                                $rtime = $rtime->addMinutes($l['matchevent']->playerFoulSuspMinutes);

                                if ($rtime > FrozenTime::now()) {
                                    $calc[$code][$l['team_id']][$l['playerNumber']]['reEntryTime'][$l['id']] = $rtime->diffInSeconds(FrozenTime::now());
                                }
                            }
                        }
                    }

                    switch ($code) {
                        case 'LOGIN':
                            $calc['isLoggedIn'] = 1;
                            break;
                        case 'ON_PLACE_REF':
                            $calc['isRefereeOnPlace'] = 1;
                            break;
                        case 'ON_PLACE_TEAM1':
                            $calc['isTeam1OnPlace'] = 1;
                            break;
                        case 'ON_PLACE_TEAM2':
                            $calc['isTeam2OnPlace'] = 1;
                            break;
                        case 'MATCH_START':
                            $calc['isMatchStarted'] = 1;
                            $calc['isMatchLive'] = 1;
                            break;
                        case 'MATCH_END':
                            $calc['isMatchLive'] = 0;
                            $calc['isMatchEnded'] = 1;
                            break;
                        case 'RESULT_WIN_NONE':
                            $calc['teamWon'] = 0; // Remis
                            break;
                        case 'RESULT_WIN_TEAM1':
                            $calc['teamWon'] = 1;
                            break;
                        case 'RESULT_WIN_TEAM2':
                            $calc['teamWon'] = 2;
                            break;
                        case 'MATCH_CONCLUDE':
                            $calc['isMatchConcluded'] = 1;
                            $calc['isMatchLive'] = 0;
                            $calc['isMatchEnded'] = 1;
                            break;
                        case 'RESULT_CONFIRM':
                            $calc['isResultConfirmed'] = 1;
                            $calc['isMatchConcluded'] = 1;
                            $calc['isMatchLive'] = 0;
                            $calc['isMatchEnded'] = 1;
                            break;
                        case 'LOGOUT':
                            $calc['isLoggedIn'] = 0; // sic!
                            break;

                        case 'GOAL_1POINT':
                            $calc['score'][$l['team_id']] += 1;
                            break;
                        case 'GOAL_2POINT':
                            $calc['score'][$l['team_id']] += 2;
                            break;
                        case 'GOAL_3POINT':
                            $calc['score'][$l['team_id']] += 3;
                            break;
                    }

                    $calc['isMatchReadyToStart'] = (int)($calc['isRefereeOnPlace'] ?? 0) * (int)($calc['isTeam1OnPlace'] ?? 0) * (int)($calc['isTeam2OnPlace'] ?? 0);

                    if ($l['datetimeSent']) {
                        $offset = $l['datetime']->diffInSeconds($l['datetimeSent']) % 3600; // clear timezone difference by modulus
                        if ($offset < 1000) { // ignore offsets like 3599
                            $offsetCount++;
                            $calc['maxOffset'] = $calc['maxOffset'] > $offset ? $calc['maxOffset'] : $offset;
                            $calc['minOffset'] = $calc['minOffset'] < $offset ? $calc['minOffset'] : $offset;
                            $allOffset += $offset;
                        }
                        $lastAliveTime = $l['datetimeSent'];
                    }
                }
            }

            if ($lastAliveTime !== null) {
                $setting = $this->getSettings();
                $aliveDiff = $lastAliveTime->diffInSeconds(FrozenTime::now());
                $calc['isLoggedIn'] = (int)($aliveDiff < ($setting['autoLogoutSecsAfter'] ?? 60)) * ($calc['isLoggedIn'] ?? 0);
            }

            $calc['avgOffset'] = $offsetCount > 0 ? round($allOffset / $offsetCount, 2) : 0;
        }

        return $calc;
    }

    public function reCalcRanking($team1_id = false, $team2_id = false)
    {
        $return = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $return = $this->getCalcRanking($team1_id, $team2_id);
        }

        $this->apiReturn($return);
    }


    protected function getCalcRanking($team1_id = false, $team2_id = false, $doSetRanking = true)
    {
        $year = $this->getCurrentYear();
        /**
         * @var Year $year
         */
        $this->loadModel('GroupTeams');

        $condGtArray = $team1_id ? ($team2_id ? array('GroupTeams.team_id IN' => array($team1_id, $team2_id)) : array('GroupTeams.team_id' => $team1_id)) : array();
        $groupTeams = $this->GroupTeams->find('all', array(
            'contain' => array('Groups' => array('fields' => array('name', 'year_id', 'day_id'))),
            'conditions' => array_merge($condGtArray, array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId())),
            'order' => array('GroupTeams.id' => 'ASC')
        ));

        $countMatches = 0;
        $countGroupTeams = $groupTeams ? $groupTeams->count() : 0;
        if ($countGroupTeams > 0) {
            $this->loadModel('Matches');

            foreach ($groupTeams as $gt) {
                /**
                 * @var GroupTeam $gt
                 */

                $calcCountMatches = 0;
                $calcGoalsScored = 0;
                $calcGoalsReceived = 0;
                $calcPointsPlus = 0;
                $calcPointsMinus = 0;

                $conditionsArray = array(
                    'Groups.id' => $gt->group_id,
                    'resultTrend IS NOT' => null,
                    'OR' => array(
                        'team1_id' => $gt->team_id,
                        'team2_id' => $gt->team_id,
                    )
                );

                $matches = $this->getMatches($conditionsArray);

                if ($matches && count($matches) > 0) {
                    foreach ($matches as $m) {
                        /**
                         * @var Match4 $m
                         */

                        if ($year->alwaysAutoUpdateResults || $m->round->autoUpdateResults) {
                            $countMatches++;
                            $calcCountMatches++;

                            if ($m->resultTrend == 5) { // X:X-Wertung
                                $calcGoalsReceived += $this->getFactorsLeastCommonMultiple();
                                $calcPointsMinus += 2;
                            } else {
                                $calcGoalsScored += $gt->team_id == $m->team1_id ? $m->resultGoals1 : $m->resultGoals2;
                                $calcGoalsReceived += $gt->team_id == $m->team1_id ? $m->resultGoals2 : $m->resultGoals1;
                                $calcPointsPlus += $gt->team_id == $m->team1_id ? ($m->resultGoals1 > $m->resultGoals2 ? 2 : ($m->resultGoals1 < $m->resultGoals2 ? 0 : 1)) : ($m->resultGoals1 > $m->resultGoals2 ? 0 : ($m->resultGoals1 < $m->resultGoals2 ? 2 : 1));
                                $calcPointsMinus += $gt->team_id == $m->team1_id ? ($m->resultGoals1 > $m->resultGoals2 ? 0 : ($m->resultGoals1 < $m->resultGoals2 ? 2 : 1)) : ($m->resultGoals1 > $m->resultGoals2 ? 2 : ($m->resultGoals1 < $m->resultGoals2 ? 0 : 1));
                            }
                        }
                    }

                    $gt->set('calcCountMatches', (int)$calcCountMatches);
                    $gt->set('calcGoalsScored', (int)$calcGoalsScored);
                    $gt->set('calcGoalsReceived', (int)$calcGoalsReceived);
                    $gt->set('calcGoalsDiff', (int)($calcGoalsScored - $calcGoalsReceived));
                    $gt->set('calcPointsPlus', (int)$calcPointsPlus);
                    $gt->set('calcPointsMinus', (int)$calcPointsMinus);

                    $this->GroupTeams->save($gt);
                }
            }
        }

        if ($doSetRanking) {
            $this->setRanking($year);
        }

        return array('countMatches' => $countMatches, 'countGroupTeams' => $countGroupTeams, 'doSetRanking' => $doSetRanking);
    }


    protected function setRanking($year)
    {
        $groupTeams = $this->GroupTeams->find('all', array(
            'contain' => array('Groups' => array('fields' => array('id', 'year_id', 'day_id'))),
            'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId()),
            'order' => array('GroupTeams.group_id' => 'ASC', 'GroupTeams.canceled' => 'ASC', 'GroupTeams.calcPointsPlus' => 'DESC', 'GroupTeams.calcGoalsDiff' => 'DESC', 'GroupTeams.calcGoalsScored' => 'DESC')
        ));

        $groupId = 0;
        $countRanking = 0;
        if ($groupTeams->count() > 0) {
            foreach ($groupTeams as $gt) { // set temporarily null because of unique values
                $gt->set('calcRanking', null);
                $this->GroupTeams->save($gt);
            }

            foreach ($groupTeams as $gt) { // set correct ranking
                /**
                 * @var GroupTeam $gt
                 */
                $countRanking = ($groupId == $gt->group_id ? $countRanking + 1 : 1);
                $groupId = $gt->group_id;

                $gt->set('calcRanking', $countRanking);

                $this->GroupTeams->save($gt);
            }
        }

        return;
    }

    protected function getGroupPosNumber($group_id): int
    {
        $this->loadModel('Groups');
        $group = $this->Groups->get($group_id);

        return ord(strtoupper($group->name)) - ord('A');
    }

    protected function getCurrentGroupId($number)
    {
        /**
         * @var Year $year
         */
        $year = $this->getCurrentYear();
        $name = chr(ord('A') + $number);

        $this->loadModel('Groups');
        $group = $this->Groups->find('all', array(
            'conditions' => array('name' => $name, 'year_id' => $year->id, 'day_id' => $this->getCurrentDayId()),
            'order' => array('id' => 'ASC')
        ))->first();

        return $group->id;
    }

    protected function getGroupName($number)
    {
        $alphabet = range('A', 'Z');

        return $alphabet[$number] ?? false;
    }

    protected function getMatchesByGroup($group): array
    {
        $this->loadModel('Rounds');
        $rounds = $this->Rounds->find('all', array(
            'fields' => array('id', 'timeStartDay' . $group->day_id, 'autoUpdateResults'),
            'order' => array('id' => 'ASC')
        ))->toArray();

        if (count($rounds) > 0) {
            foreach ($rounds as $round) {
                /**
                 * @var Round $round
                 */
                $conditionsArray = array(
                    'group_id' => $group->id,
                    'round_id' => $round->id,
                );

                $round['matches'] = $this->getMatches($conditionsArray);
            }
        }

        return $rounds;
    }

    public function clearTest()
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->getSettings();

            if ($settings['isTest'] ?? 0) {
                $rc = 0;

                $conn = ConnectionManager::get('default');

                $rc += $conn->execute("DELETE ml FROM matchevent_logs ml LEFT JOIN matches m ON ml.match_id=m.id LEFT JOIN groups g ON m.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE m FROM matches m LEFT JOIN groups g ON m.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE gt FROM group_teams gt LEFT JOIN groups g ON gt.group_id=g.id LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE g FROM groups g LEFT JOIN years y ON g.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("DELETE ty FROM team_years ty LEFT JOIN years y ON ty.year_id=y.id WHERE y.id = " . $settings['currentYear_id'])->rowCount();
                $rc += $conn->execute("UPDATE settings SET value=1 WHERE name = 'currentDay_id'")->rowCount();
                $rc += $conn->execute("UPDATE settings SET value=0 WHERE name = 'alwaysAutoUpdateResults'")->rowCount();
                $rc += $conn->execute("UPDATE settings SET value=0 WHERE name = 'showEndRanking'")->rowCount();

                if ($_SERVER['SERVER_NAME'] == 'localhost') {
                    //$conn->execute("CALL reset_autoincrement('matchevent_logs')");
                    //$conn->execute("CALL reset_autoincrement('matches')");
                    //$conn->execute("CALL reset_autoincrement('group_teams')");
                    //$conn->execute("CALL reset_autoincrement('groups')");
                    //$conn->execute("CALL reset_autoincrement('team_years')");
                    //$conn->execute("CALL reset_autoincrement('years')");
                }

                // reset all time stats
                $this->updateCalcTotal();

                $this->apiReturn(array('rows affected' => $rc));
            }
        }
    }

    protected function updateCalcTotal()
    {
        $conn = ConnectionManager::get('default');
        $stmt1 = $conn->execute(file_get_contents(__DIR__ . "/sql/setnull_team_calcTotal.sql"));
        $stmt2 = $conn->execute(file_get_contents(__DIR__ . "/sql/update_team_calcTotal.sql"));

        return $stmt2->rowCount();
    }

    protected function getFactorsLeastCommonMultiple()
    {
        $this->loadModel('Sports');
        $sports = $this->Sports->find()->all();

        $gmp = 1;
        foreach ($sports as $s) {
            $gmp = gmp_lcm($gmp, $s->goalFactor);
        }

        return $gmp;
    }

    protected function checkUsernamePassword($name, $password)
    {
        $return = false;

        if ($this->request->is('post')) {
            $this->loadModel('Logins');
            $login = $this->Logins->find('all', array(
                'conditions' => array('name' => $name, 'password' => md5($password)),
            ))->first();

            if ($login && ($login->id ?? 0) > 0) {
                $return = $login->id;
            }
        }

        return $return;
    }


    protected
    function getRefereeGroup(Group $playGroup)
    {
        // groupName: A->B, B->A, C->D, D->C, E->F, F->E, ...
        // groupPosNumber: 0->1, 1->0, 2->3, 3->2, 4->5, 5->4, ...
        $playGroupPosNumber = $this->getGroupPosNumber($playGroup->id);
        $refereeGroupPosNumber = $playGroupPosNumber % 2 ? $playGroupPosNumber - 1 : $playGroupPosNumber + 1;

        $name = $this->getGroupName($refereeGroupPosNumber);

        $refereeGroup = $this->Groups->find('all', array(
            'conditions' => array('year_id' => $playGroup->year_id, 'day_id' => $playGroup->day_id, 'name' => $name)
        ))->first();

        return $refereeGroup ?: false;
    }

    protected function getCurrentRoundId($offset = 0)
    {
        $time = FrozenTime::now();
        $time = $time->addMinutes($offset);
        $time = $time->subHours($this->getCurrentDayId() == 2 ? 1 : 2);
        $cycle = (int)floor($time->hour / 8);

        if ($cycle != 1) {
            return 1;
        }

        return ($time->hour % 8 * 2 + 1) + (int)floor($time->minute / 30);
    }
}
