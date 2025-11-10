<?php

namespace App\Controller\Component;

use App\Model\Entity\Group;
use App\Model\Entity\Round;
use App\Model\Entity\TeamYear;
use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;

/**
 * @property CacheComponent $Cache
 * @property GroupGetComponent $GroupGet
 * @property PlayOffComponent $PlayOff
 */
class MatchGetComponent extends Component
{
    protected array $components = ['Cache', 'GroupGet', 'PlayOff'];

    public function getMatchesByGroup(array $group): array
    {
        $rounds = FactoryLocator::get('Table')->get('Rounds')->find('all', array(
            'fields' => array('id', 'timeStartDay' . $group['day_id'], 'autoUpdateResults'),
            'order' => array('id' => 'ASC')
        ))->toArray();

        if (count($rounds) > 0) {
            foreach ($rounds as $round) {
                /**
                 * @var Round $round
                 */
                $conditionsArray = array(
                    'group_id' => $group['id'],
                    'round_id' => $round['id'],
                );

                $round['matches'] = $this->getMatches($conditionsArray);
            }
        }

        return $rounds;
    }

    public function getMatchesByTeam(int $team_id, int $year_id, int $day_id, int $adminView = 0): array
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

            $groupteam = FactoryLocator::get('Table')->get('GroupTeams')->find('all', array(
                'contain' => array('Groups' => array('fields' => array('id', 'year_id', 'day_id'))),
                'conditions' => array('team_id' => $team_id, 'Groups.year_id' => $year_id, 'Groups.day_id' => $day_id),
            ))->first();

            if ($groupteam) {
                $group = FactoryLocator::get('Table')->get('Groups')->find()->where(['id' => $groupteam['group_id']])->first();
                /**
                 * @var Group $group
                 */
                $return['group']['group_id'] = $group->get('id');
                $return['group']['group_name'] = $group->get('name');
                $refGroup = $this->GroupGet->getRefereeGroup($group);
                $return['referee_group_name'] = $refGroup ? $refGroup->get('name') : null;
            }
        }

        return $return;
    }

    public function getMatches(array $conditionsArray, int $includeLogs = 0, int $sortBy = 1, int $adminView = 0): bool|array
    {
        $query = FactoryLocator::get('Table')->get('Matches')->find('all', array(
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
            $settings = $this->Cache->getSettings();
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
                        $refereeTy = FactoryLocator::get('Table')->get('TeamYears')->find('all', array('conditions' => array('team_id' => ($row['refereeTeam_id'] ?? 0), 'year_id' => $row['group']['year']['id'])))->first();
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
                        $row['playOffName'] = $this->PlayOff->getPlayOffName($row['isPlayOff']);
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

    public function getLogs(int $match_id): bool|array
    {
        $query = FactoryLocator::get('Table')->get('MatcheventLogs')->find('all', array(
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

    private function getCalcFromLogs(array $logs): array
    {
        /**
         * @var array{
         *   maxOffset: int,
         *   minOffset: int,
         *   photos: array<int, array<string, int>>,
         *   score: array<int, int>
         * } $calc
         */
        $calc = array();
        $calc['maxOffset'] = 0;
        $calc['minOffset'] = 9999;
        $calc['photos'] = array();
        $calc['score'] = array();

        $offsetCount = 0;
        $allOffset = 0;
        $lastAliveTime = null;
        $matcheventFoulPersonal = false;

        foreach ($logs as $l) {
            if (isset($l['matchevent'])) {
                $code = (string)($l['matchevent']->code);
                $teamId = (int)($l['team_id'] ?? 0);

                //Adding Calculated Fields
                if ($teamId > 0) {
                    $calc['score'][$teamId] ??= 0;

                    if (str_contains($code, 'FOUL_')) { // Card and Foul score
                        $calc['foulOutLogIds'] ??= array();
                        $calc['doubleYellowLogIds'] ??= array();
                        $calc[$code] ??= array();
                        $calc[$code][$teamId] ??= array();

                        $code .= '_V2';
                        $calc[$code][$teamId][$l['playerNumber']]['count'] ??= 0;
                        $calc[$code]['name'] = $l['matchevent']->name;

                        if ($l['matchevent']->showOnSportsOnly == 1) { // BB only
                            $calc['FOUL_PERSONAL_V2'][$teamId][$l['playerNumber']]['count'] ??= 0; // needed for later increment

                            if (str_starts_with($code, 'FOUL_PERSONAL')) {
                                $matcheventFoulPersonal = $l['matchevent'];
                            }
                            if (str_starts_with($code, 'FOUL_TECH_FLAGR')) { // add tech. foul to pers. foul count
                                if ($calc['FOUL_PERSONAL_V2'][$teamId][$l['playerNumber']]['count'] >= 0) { // add only if not foul-out yet
                                    $calc['FOUL_PERSONAL_V2'][$teamId][$l['playerNumber']]['count']++;
                                }
                            }
                        }

                        if ($calc[$code][$teamId][$l['playerNumber']]['count'] >= 0) { // add only if not foul-out yet
                            $calc[$code][$teamId][$l['playerNumber']]['count']++;
                        }

                        if ($l['matchevent']->playerFouledOutAfter
                            && $calc[$code][$teamId][$l['playerNumber']]['count'] >= $l['matchevent']->playerFouledOutAfter) {
                            // set negative value as marker to foul-out players
                            $calc[$code][$teamId][$l['playerNumber']]['count'] *= -1;
                            $calc['foulOutLogIds'][] = $l['id'];
                        }

                        if ($l['matchevent']->showOnSportsOnly == 1) { // BB only
                            // needed for case PF+PF+TF: set negative value as marker to foul-out players
                            if ($matcheventFoulPersonal
                                && $calc['FOUL_PERSONAL_V2'][$teamId][$l['playerNumber']]['count'] >= $matcheventFoulPersonal->playerFouledOutAfter) {
                                $calc['FOUL_PERSONAL_V2'][$teamId][$l['playerNumber']]['count'] *= -1;
                                $calc['foulOutLogIds'][] = $l['id'];
                            }
                        }

                        if (str_starts_with($code, 'FOUL_CARD_YELLOW') && $calc[$code][$teamId][$l['playerNumber']]['count'] > 1) {
                            $calc['doubleYellowLogIds'][] = $l['id'];
                        }

                        if ($l['matchevent']->playerFoulSuspMinutes) {  // Fouls with suspension
                            $rtime = DateTime::createFromFormat('Y-m-d H:i:s', $l['datetimeSent']->i18nFormat('yyyy-MM-dd HH:mm:ss'));
                            $rtime = $rtime->addMinutes($l['matchevent']->playerFoulSuspMinutes);

                            if ($rtime > DateTime::now()) {
                                $calc[$code][$teamId][$l['playerNumber']]['reEntryTime'][$l['id']] = $rtime->diffInSeconds(DateTime::now());
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
                        $calc['score'][$teamId] += 1;
                        break;
                    case 'GOAL_2POINT':
                        $calc['score'][$teamId] += 2;
                        break;
                    case 'GOAL_3POINT':
                        $calc['score'][$teamId] += 3;
                        break;
                }

                $calc['isMatchReadyToStart'] = (int)($calc['isRefereeOnPlace'] ?? 0) * (int)($calc['isTeam1OnPlace'] ?? 0) * (int)($calc['isTeam2OnPlace'] ?? 0);

                if ($l['datetimeSent']) {
                    $offset = $l['datetime']->diffInSeconds($l['datetimeSent']) % 3600; // clear timezone difference by modulus
                    if ($offset < 1000) { // ignore offsets like 3599
                        $offsetCount++;
                        $calc['maxOffset'] = max($calc['maxOffset'], $offset);
                        $calc['minOffset'] = min($calc['minOffset'], $offset);
                        $allOffset += $offset;
                    }
                    $lastAliveTime = $l['datetime'];
                }
            }
        }

        if ($lastAliveTime !== null) {
            $setting = $this->Cache->getSettings();
            $aliveDiff = $lastAliveTime->diffInSeconds(DateTime::now());
            $calc['isLoggedIn'] = (int)($aliveDiff < ($setting['autoLogoutSecsAfter'] ?? 60)) * (int)($calc['isLoggedIn'] ?? 0);
        }

        $calc['avgOffset'] = $offsetCount > 0 ? round($allOffset / $offsetCount, 2) : 0;

        return $calc;
    }

    private function wasLoggedIn(int $match_id): int
    {
        return FactoryLocator::get('Table')->get('MatcheventLogs')->find('all', array(
            'conditions' => array('Matchevents.code' => 'LOGIN', 'match_id' => $match_id),
            'contain' => array('Matchevents')
        ))->count();
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

    public function getScheduleShowTime(int $year_id, int $day_id, int $adminView = 0): int|DateTime
    {
        $settings = $this->Cache->getSettings();

        if ($settings['showScheduleHoursBefore'] == 0) {
            return 0; // show Schedule
        }

        $currentYear = $this->Cache->getCurrentYear()->toArray();
        $stime = DateTime::createFromFormat('Y-m-d H:i:s', $currentYear['day' . $settings['currentDay_id']]->i18nFormat('yyyy-MM-dd HH:mm:ss'));
        $showTime = $stime->subHours($settings['showScheduleHoursBefore']);
        $now = DateTime::now();

        if ($now > $showTime || $currentYear['id'] != $year_id || $settings['currentDay_id'] != $day_id || $settings['isTest'] || $adminView) {
            return 0; // show Schedule
        }
        return $showTime; // do not show schedule
    }
}
