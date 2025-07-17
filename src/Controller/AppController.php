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
use App\Model\Entity\Login;
use App\Model\Entity\Match4;
use App\Model\Entity\Round;
use App\Model\Entity\Team;
use App\Model\Entity\TeamYear;
use App\Model\Entity\Year;
use App\View\PdfView;
use Cake\Controller\Controller;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
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
    }

    public function viewClasses(): array
    {
        return [JsonView::class, PdfView::class];
    }

    public function apiReturn(mixed $object, int $year_id = 0, int $day_id = 0): \Cake\Http\Response
    {
        if ($object) {
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

    protected function getSettings(): array
    {
        return $this->fetchTable('Settings')->find('list', [
            'keyField' => 'name',
            'valueField' => 'value'
        ])->toArray();
    }

    protected function getCurrentYear(): Year
    {
        $settings = $this->getSettings();
        // todo: put currentYear_id inside return
        $year = $this->fetchTable('Years')->find('all', array(
            'conditions' => array('id' => $settings['currentYear_id']),
        ))->first();
        /**
         * @var Year $year
         */
        return $year;
    }

    protected function getCurrentYearId(): int
    {
        return ($this->getSettings())['currentYear_id'];
    }

    protected function getCurrentDayId(): int
    {
        return ($this->getSettings())['currentDay_id'];
    }

    protected function getGroupByMatchId(int $id): Group
    {
        $match = $this->fetchTable('Matches')->find()->where(['id' => $id])->first();
        /**
         * @var Match4 $match
         */
        $group = $this->fetchTable('Groups')->find()->where(['id' => $match->get('group_id')])->first();
        /**
         * @var Group $group
         */
        return $group;
    }

    protected function getGroupByTeamId(int $team_id, int $year_id, int $day_id): Group
    {
        $groupteam = $this->fetchTable('GroupTeams')->find('all', array(
            'contain' => array('Groups' => array('fields' => array('id', 'year_id', 'day_id'))),
            'conditions' => array('team_id' => $team_id, 'Groups.year_id' => $year_id, 'Groups.day_id' => $day_id),
        ))->first();
        /**
         * @var GroupTeam $groupteam
         */
        $group = $this->fetchTable('Groups')->find()->where(['id' => $groupteam->get('group_id')])->first();
        /**
         * @var Group $group
         */
        return $group;
    }

    protected function getPrevAndNextGroup(int $group_id): array|EntityInterface|null
    {
        $group_id = $group_id ?: $this->getCurrentGroupId(0);

        if ($group_id) {
            $group = $this->fetchTable('Groups')->find()->where(['id' => $group_id])->first();
            /**
             * @var Group|null $group
             */
            if ($group) {
                $countGroups = $this->fetchTable('Groups')->find('all', array(
                    'conditions' => array('year_id' => $group->year_id, 'day_id' => $group->day_id)
                ))->count();

                $groupPosNumber = $this->getGroupPosNumber($group_id);
                if ($groupPosNumber + 1 > 1) {
                    $group['prevGroup'] = $this->fetchTable('Groups')->find('all', array(
                        'fields' => array('group_id' => 'id', 'group_name' => 'name', 'id', 'name'),
                        'conditions' => array('id' => $group_id - 1)
                    ))->first();
                }
                if ($groupPosNumber + 1 < $countGroups) {
                    $group['nextGroup'] = $this->fetchTable('Groups')->find('all', array(
                        'fields' => array('group_id' => 'id', 'group_name' => 'name', 'id', 'name'),
                        'conditions' => array('id' => $group_id + 1, 'name !=' => 'Endrunde')
                    ))->first();
                }
            }
        }

        return $group ?? null;
    }

    protected function getScheduleShowTime(int $year_id, int $day_id, int $adminView = 0): int|DateTime
    {
        $settings = $this->getSettings();

        if ($settings['showScheduleHoursBefore'] == 0) {
            return 0; // show Schedule
        }

        $currentYear = $this->getCurrentYear()->toArray();
        $stime = DateTime::createFromFormat('Y-m-d H:i:s', $currentYear['day' . $settings['currentDay_id']]->i18nFormat('yyyy-MM-dd HH:mm:ss'));
        $showTime = $stime->subHours($settings['showScheduleHoursBefore']);
        $now = DateTime::now();

        if ($now > $showTime || $currentYear['id'] != $year_id || $settings['currentDay_id'] != $day_id || $settings['isTest'] || $adminView) {
            return 0; // show Schedule
        }
        return $showTime; // do not show schedule
    }

    protected function getMatchesByTeam(int $team_id, int $year_id, int $day_id, int $adminView = 0): array
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

            $groupteam = $this->fetchTable('GroupTeams')->find('all', array(
                'contain' => array('Groups' => array('fields' => array('id', 'year_id', 'day_id'))),
                'conditions' => array('team_id' => $team_id, 'Groups.year_id' => $year_id, 'Groups.day_id' => $day_id),
            ))->first();

            if ($groupteam) {
                $group = $this->fetchTable('Groups')->find()->where(['id' => $groupteam['group_id']])->first();
                /**
                 * @var Group $group
                 */
                $return['group']['group_id'] = $group->get('id');
                $return['group']['group_name'] = $group->get('name');
                $refGroup = $this->getRefereeGroup($group);
                $return['referee_group_name'] = $refGroup ? $refGroup->get('name') : null;
            }
        }

        return $return;
    }

    protected function getMatches(array $conditionsArray, int $includeLogs = 0, int $sortBy = 1, int $adminView = 0): bool|array
    {
        $query = $this->fetchTable('Matches')->find('all', array(
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
            'order' => array('Rounds.id' => 'ASC', ($sortBy ? 'Sports.id' : 'Matches.id') => 'ASC', 'Matches.id' => 'ASC')
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
                        $row['resultAdmin'] = null;
                    } else {
                        $row['round']['autoUpdateResults'] = 1;
                    }

                    if ($row['group']['year']['id'] == $settings['currentYear_id']) {
                        $row['isTest'] = $settings['isTest'];
                    }

                    // no need
                    unset($row['group']['year']['teamsCount']);
                    unset($row['group']['year']['daysCount']);
                    unset($row['group']['year']['day1']);
                    unset($row['group']['year']['day2']);
                    unset($row['round']['timeStartDay1']);
                    unset($row['round']['timeStartDay2']);

                    unset($row['refereePIN']); // security issue

                    if ($adminView) {
                        $refereeTy = $this->fetchTable('TeamYears')->find('all', array('conditions' => array('team_id' => ($row['refereeTeam_id'] ?? 0), 'year_id' => $row['group']['year']['id'])))->first();
                        /**
                         * @var TeamYear|null $refereeTy
                         */
                        $row['isRefereeCanceled'] = $refereeTy ? $refereeTy->get('canceled') : 1;
                    } else {
                        unset($row['remarks']);
                    }

                    if (!$includeLogs) {
                        unset($row['sport']['goalFactor']); // no need
                    }

                    if ($row['matchStartTime']) {
                        $stime = DateTime::createFromFormat('Y-m-d H:i:s', $row['matchStartTime']);
                        $now = DateTime::now();
                        $row['isTime2login'] = $now > $stime->subMinutes($settings['time2LoginMinsBeforeFrom']) && $now < $stime->addMinutes($settings['time2LoginMinsAfterUntil']) ? 1 : 0;

                        if ($row['isTest']) {
                            $row['isTime2login'] = 1;

                            // set test time for isTime2matchEnd and isTime2confirm
                            $s3 = DateTime::now()->i18nFormat('yyyy-MM-dd HH:');
                            $s4 = DateTime::now()->i18nFormat('mm');
                            $mt = $s3 . ((int)$s4 > 29 ? '30' : '00') . ':00'; // set to full half hour
                            $stime = DateTime::createFromFormat('Y-m-d H:i:s', $mt);
                        }
                        if ($includeLogs) {
                            $row['isTime2matchEnd'] = ($now > $stime->addMinutes($settings['time2MatchEndMinAfterFrom'])) ? 1 : 0;
                            $row['isTime2confirm'] = ($now > $stime->addMinutes($settings['time2ConfirmMinsAfterFrom']) && $now < $stime->addMinutes($settings['time2ConfirmMinsAfterUntil'])) ? 1 : 0;
                        }
                        if (!$settings['useLiveScouting']) {
                            $row['isTime2login'] = 0;
                        }
                    }

                    if ((($conditionsArray['OR']['refereeTeam_id'] ?? 0) > 0
                            && $conditionsArray['OR']['refereeTeam_id'] == $row['refereeTeam_id'])
                        || (($conditionsArray['OR']['refereeTeamSubst_id'] ?? 0) > 0
                            && $conditionsArray['OR']['refereeTeamSubst_id'] == $row['refereeTeamSubst_id'])) {
                        $row['isRefereeJob'] = 1;

                        if ($row['isTime2login'] && !$includeLogs) {
                            $row['isRefereeJobLoginRequired'] = $this->wasLoggedIn($row['id']) ? 0 : 1;
                        }
                    }

                    if ($includeLogs) {
                        $logs = $this->getLogs($row['id']);
                        $row['logsCalc'] = $logs['calc'] ?? array();
                        $row['logsCalc']['isLoggedIn'] = $row['logsCalc']['isLoggedIn'] ?? 0;
                        $row['logsCalc']['isResultConfirmed'] = $row['logsCalc']['isResultConfirmed'] ?? ($row['resultTrend'] !== null ? 1 : 0);

                        if ($adminView) {
                            $row['isResultOk'] = $this->isResultOk($row->toArray()) ? 1 : 0;
                        }
                    }

                    if ($row['isPlayOff'] > 0) {
                        $row['playOffName'] = $this->getPlayOffName($row['isPlayOff']);
                    }

                    return $row;
                });
            })->toArray();

            if ($sortBy == 2) { // non-midday rounds first: for changeTeams function
                usort($matches, function ($a, $b) {
                    return abs($b['round']['id'] - 8.5) <=> abs($a['round']['id'] - 8.5);
                });
            } else if ($sortBy == 3) { // midday rounds first: for changeTeams function
                usort($matches, function ($a, $b) {
                    return abs($a['round']['id'] - 8.5) <=> abs($b['round']['id'] - 8.5);
                });
            } else if ($sortBy == 4) { // last rounds first: for admin remarks view
                usort($matches, function ($a, $b) {
                    return $b['matchStartTime'] <=> $a['matchStartTime'];
                });
            } else { // regular sort: matchStartTime
                usort($matches, function ($a, $b) {
                    return $a['matchStartTime'] <=> $b['matchStartTime'];
                });
            }
        } else {
            $matches = false;
        }

        return $matches;
    }

    private function isResultOk(array $row): bool
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

    private function getPlayOffName(int $isPlayOff): string
    {
        return match ($isPlayOff % 10) {
            4 => 'Halbfinale',
            3 => 'Spiel um Platz 3',
            2 => 'Finale',
            default => '',
        };
    }

    protected function getPlayOffRanking(Year $year): array
    {
        $return = array();
        $match2 = $this->fetchTable('Matches')->find()->where(['isPlayOff' => (int)($year->id . '2')])->first(); // Finale
        $match3 = $this->fetchTable('Matches')->find()->where(['isPlayOff' => (int)($year->id . '3')])->first(); // 3rd-Place-Match
        /**
         * @var Match4 $match2
         * @var Match4 $match3
         */
        if ($match2->resultTrend) {
            $return[1] = in_array($match2->resultTrend, array(1, 3)) ? $match2->team1_id : $match2->team2_id;
            $return[2] = in_array($match2->resultTrend, array(1, 3)) ? $match2->team2_id : $match2->team1_id;

            if ($match3->resultTrend) {
                $return[3] = in_array($match3->resultTrend, array(1, 3)) ? $match3->team1_id : $match3->team2_id;
                $return[4] = in_array($match3->resultTrend, array(1, 3)) ? $match3->team2_id : $match3->team1_id;
            }
        }

        return $return;
    }

    protected function getPlayOffWinLose(Year $year): array
    {
        $return = array();
        $matches = $this->getMatches(array('isPlayOff' => (int)($year->id . '4'))); // Semi-Finales

        if (is_array($matches)) {
            foreach ($matches as $m) {
                /**
                 * @var Match4 $m
                 */
                $return['winners'][] = in_array($m->resultTrend, array(1, 3)) ? $m->team1_id : (in_array($m->resultTrend, array(2, 4)) ? $m->team2_id : 0);
                $return['losers'][] = in_array($m->resultTrend, array(1, 3)) ? $m->team2_id : (in_array($m->resultTrend, array(2, 4)) ? $m->team1_id : 0);
            }
        }

        return $return;
    }

    private function wasLoggedIn(int $match_id): int
    {
        return $this->fetchTable('MatcheventLogs')->find('all', array(
            'conditions' => array('Matchevents.code' => 'LOGIN', 'match_id' => $match_id),
            'contain' => array('Matchevents')
        ))->count();
    }

    protected function getLogs(int $match_id): bool|array
    {
        $query = $this->fetchTable('MatcheventLogs')->find('all', array(
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


    protected function getCalcFromLogs(array $logs): array
    {
        $calc = array();
        $calc['maxOffset'] = 0;
        $calc['minOffset'] = 9999;
        $calc['photos'] = array();

        $offsetCount = 0;
        $allOffset = 0;
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
                            $rtime = DateTime::createFromFormat('Y-m-d H:i:s', $l['datetimeSent']->i18nFormat('yyyy-MM-dd HH:mm:ss'));
                            $rtime = $rtime->addMinutes($l['matchevent']->playerFoulSuspMinutes);

                            if ($rtime > DateTime::now()) {
                                $calc[$code][$l['team_id']][$l['playerNumber']]['reEntryTime'][$l['id']] = $rtime->diffInSeconds(DateTime::now());
                            }
                        }
                    }
                }

                switch ($code) {
                    case 'LOGIN':
                        $calc['isLoggedIn'] = 1;
                        $calc['wasLoggedIn'] = 1;
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
                        $calc['isMatchStarted'] = 1; // ! needed
                        $calc['isMatchLive'] = 0;
                        $calc['isMatchEnded'] = 1;
                        break;
                    case 'PHOTO_UPLOAD':
                        $calc['photos'][] = array('id' => $l['id'], 'checked' => $l['playerNumber']);
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
                    $lastAliveTime = $l['datetime'];
                }
            }
        }

        if ($lastAliveTime !== null) {
            $setting = $this->getSettings();
            $aliveDiff = $lastAliveTime->diffInSeconds(DateTime::now());
            $calc['isLoggedIn'] = (int)($aliveDiff < ($setting['autoLogoutSecsAfter'] ?? 60)) * (int)($calc['isLoggedIn'] ?? 0);
        }

        $calc['avgOffset'] = $offsetCount > 0 ? round($allOffset / $offsetCount, 2) : 0;

        return $calc;
    }

    public function reCalcRanking(string $team1_id = '', string $team2_id = ''): void
    {
        $return = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $return = $this->getCalcRanking((int)$team1_id, (int)$team2_id);
        }

        $this->apiReturn($return);
    }


    protected function getCalcRanking(int $team1_id = 0, int $team2_id = 0, bool $doSetRanking = true): array
    {
        $year = $this->getCurrentYear();
        /**
         * @var Year $year
         */
        $condGtArray = $team1_id ? ($team2_id ? array('GroupTeams.team_id IN' => array($team1_id, $team2_id)) : array('GroupTeams.team_id' => $team1_id)) : array();
        $groupTeams = $this->fetchTable('GroupTeams')->find('all', array(
            'contain' => array('Groups' => array('fields' => array('name', 'year_id', 'day_id'))),
            'conditions' => array_merge($condGtArray, array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId())),
            'order' => array('GroupTeams.id' => 'ASC')
        ));

        $countMatches = 0;
        $countGroupTeams = $groupTeams->count();
        if ($countGroupTeams > 0) {
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

                if (is_array($matches) && count($matches) > 0) {
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

                    $this->fetchTable('GroupTeams')->save($gt);
                }
            }
        }

        if ($doSetRanking) {
            $this->setRanking($year);
        }

        return array('countMatches' => $countMatches, 'countGroupTeams' => $countGroupTeams, 'doSetRanking' => $doSetRanking);
    }


    protected function setRanking(Year $year): void
    {
        $groupTeams = $this->fetchTable('GroupTeams')->find('all', array(
            'contain' => array('Groups' => array('fields' => array('id', 'year_id', 'day_id'))),
            'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId()),
            'order' => array('GroupTeams.group_id' => 'ASC', 'GroupTeams.canceled' => 'ASC', 'GroupTeams.calcPointsPlus' => 'DESC', 'GroupTeams.calcGoalsDiff' => 'DESC', 'GroupTeams.calcGoalsScored' => 'DESC')
        ));

        $groupId = 0;
        $countRanking = 0;
        if ($groupTeams->count() > 0) {
            foreach ($groupTeams as $gt) { // set temporarily null because of unique values
                $gt->set('calcRanking', null);
                $this->fetchTable('GroupTeams')->save($gt);
            }

            foreach ($groupTeams as $gt) { // set correct ranking
                /**
                 * @var GroupTeam $gt
                 */
                $countRanking = ($groupId == $gt->group_id ? $countRanking + 1 : 1);
                $groupId = $gt->group_id;

                $gt->set('calcRanking', $countRanking);

                $this->fetchTable('GroupTeams')->save($gt);
            }
        }

    }

    protected function getGroupPosNumber(int $group_id): int
    {
        $group = $this->fetchTable('Groups')->get($group_id);
        /**
         * @var Group $group
         */
        return ord(strtoupper($group->name)) - ord('A');
    }

    protected function getCurrentGroupId(int $number): int|false
    {
        $year = $this->getCurrentYear();
        /**
         * @var Year $year
         */
        $name = chr(ord('A') + $number);

        $group = $this->fetchTable('Groups')->find('all', array(
            'conditions' => array('name' => $name, 'year_id' => $year->id, 'day_id' => $this->getCurrentDayId()),
            'order' => array('id' => 'ASC')
        ))->first();
        /**
         * @var Group|null $group
         */

        return $group ? $group->id : false;
    }

    protected function getGroupName(int $number): string|bool
    {
        $alphabet = range('A', 'Z');

        return $alphabet[$number] ?? false;
    }

    protected function getMatchesByGroup(Group $group): array
    {
        $rounds = $this->fetchTable('Rounds')->find('all', array(
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

    public function clearTest(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->getSettings();

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
            $settings = $this->getSettings();

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

    protected function getFactorsLeastCommonMultiple(): \GMP|int
    {
        $sports = $this->fetchTable('Sports')->find()->all();

        $gmp = 1;
        foreach ($sports as $s) {
            $gmp = gmp_lcm($gmp, $s->goalFactor);
        }

        return $gmp;
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

    protected function getRefereeGroup(Group $playGroup): Group|null
    {
        // groupName: A->B, B->A, C->D, D->C, E->F, F->E, ...
        // groupPosNumber: 0->1, 1->0, 2->3, 3->2, 4->5, 5->4, ...
        $playGroupPosNumber = $this->getGroupPosNumber($playGroup->id);
        $refereeGroupPosNumber = $playGroupPosNumber % 2 ? $playGroupPosNumber - 1 : $playGroupPosNumber + 1;

        $name = $this->getGroupName($refereeGroupPosNumber);

        $refereeGroup = $this->fetchTable('Groups')->find('all', array(
            'conditions' => array('year_id' => $playGroup->year_id, 'day_id' => $playGroup->day_id, 'name' => $name)
        ))->first();
        /**
         * @var Group|null $refereeGroup
         */
        return $refereeGroup;
    }

    protected function getCurrentRoundId(int $yearId = 0, int $dayId = 0, int $offset = 0): float|int
    {
        $return = 0;
        $settings = $this->getSettings();
        $currentYear = $this->getCurrentYear()->toArray();
        $day = $currentYear['day' . $settings['currentDay_id']]->i18nFormat('yyyy-MM-dd');
        $now = DateTime::now()->i18nFormat('yyyy-MM-dd');

        if (($yearId == 0 || $yearId == $settings['currentYear_id'])
            && ($dayId == 0 || $dayId == $settings['currentDay_id'])
            && ($settings['isTest'] == 1 || $now == $day)
        ) {
            $time = DateTime::now();
            $time = $time->addMinutes($offset);

            $cRound = $this->fetchTable('Rounds')->find('all', array(
                'conditions' => array('timeStartDay' . $dayId . ' <=' => $time),
                'order' => array('id' => 'DESC')
            ))->first();

            $return = $cRound ? $cRound->id : 1;

            if ($settings['isTest'] == 1 && !$cRound) {
                $time = $time->subHours($dayId == 2 ? 1 : 2);
                $cycle = (int)floor($time->hour / 8);

                if ($cycle != 1) {
                    $return = ($time->hour % 8 * 2 + 1) + (int)floor($time->minute / 30);
                }
            }
        }

        return $return;
    }
}
