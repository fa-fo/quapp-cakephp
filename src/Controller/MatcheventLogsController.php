<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\FrozenTime;

/**
 * MatcheventLogs Controller
 *
 * @property \App\Model\Table\MatcheventLogsTable $MatcheventLogs
 */
class MatcheventLogsController extends AppController
{
    public function login($match_id = false)
    {
        $matchReturn = false; // initial
        $match = $this->getMatchAndLogs($match_id, 1);
        if ($match) {
            $isLoggedIn = $match[0]['logsCalc']['isLoggedIn'] ?? 0;

            if ($isLoggedIn != 1) {
                $postData = $this->request->getData();

                if (isset($postData['matchEventCode'])) {
                    $this->loadModel('Matchevents');
                    $matchEvent = $this->Matchevents->find()->where(['code' => $postData['matchEventCode']])->first();
                    $postData['matchEvent_id'] = $matchEvent->get('id');

                    if ($this->checkPostData($match[0], $postData, $matchEvent)) {
                        $newLog = $this->MatcheventLogs->newEmptyEntity();
                        $newLog = $this->MatcheventLogs->patchEntity($newLog, $postData);
                        $newLog->set('match_id', $match_id);
                        $newLog->set('datetime', FrozenTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'));

                        if ($this->MatcheventLogs->save($newLog)) {
                            $logs = $this->getLogs($match_id);

                            $match[0]['logsCalc'] = $logs['calc'];

                            $matchReturn = $match;  // return full match infos
                        }
                    }
                }
            }
        }

        $this->apiReturn($matchReturn);
    }

    public function add($match_id = false)
    {
        $calc = false; // initial

        $match = $this->getMatchAndLogs($match_id, 1);
        if ($match) {
            $isLoggedIn = $match[0]['logsCalc']['isLoggedIn'] ?? 0;
            $isTime2login = $match[0]['isTime2login'] ?? 0;

            if ($isLoggedIn && $isTime2login) {
                $postData = $this->request->getData();

                if (isset($postData['matchEventCode'])) {
                    $this->loadModel('Matchevents');
                    $matchEvent = $this->Matchevents->find()->where(['code' => $postData['matchEventCode']])->first();

                    $postData['matchEvent_id'] = $matchEvent->get('id');
                    $postData['team_id'] = isset($postData['team_id']) ? (int)$postData['team_id'] : null;
                    $postData['playerNumber'] = isset($postData['playerNumber']) ? (int)$postData['playerNumber'] : null;

                    if ($this->checkPostData($match[0], $postData, $matchEvent)) {
                        $newLog = $this->MatcheventLogs->newEmptyEntity();
                        $newLog = $this->MatcheventLogs->patchEntity($newLog, $postData);
                        $newLog->set('match_id', $match_id);
                        $newLog->set('datetime', FrozenTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'));

                        if ($this->MatcheventLogs->save($newLog)) {
                            $logs = $this->getLogs($match_id);
                            $calc = $logs['calc'];

                            $isMatchLive = $match[0]['logsCalc']['isMatchLive'] ?? 0;
                            if ($isMatchLive) {
                                $lastLog = $this->getLastLogCancelable($match_id);
                                if ($lastLog) {
                                    $calc['inserted_id'] = $lastLog->id;
                                    if (in_array($calc['inserted_id'], ($calc['foulOutLogIds'] ?? array()))) {
                                        $ll = $this->getLastLog($match_id);
                                        $calc['showFoulOutModal'] = ($ll->id == $calc['inserted_id'] ? 1 : 0);
                                    } else if (in_array($calc['inserted_id'], ($calc['doubleYellowLogIds'] ?? array()))) {
                                        $ll = $this->getLastLog($match_id);
                                        $calc['showDoubleYellowModal'] = ($ll->id == $calc['inserted_id'] ? 1 : 0);
                                    }
                                }
                            }
                            unset($calc['foulOutLogIds']); // no need
                            unset($calc['doubleYellowLogIds']); // no need
                            $calc['isTime2confirm'] = $match[0]['isTime2confirm'] ?? 0;
                        }
                    }
                }
            } else {
                $calc['isLoggedIn'] = 0;
            }
        }

        $this->apiReturn($calc); // return only small infos to reduce bandwith
    }

    public function cancel($match_id = false, $id = false)
    {
        $calc = false; // initial
        $match = $this->getMatchAndLogs($match_id, 1);

        if ($match) {
            $isLoggedIn = $match[0]['logsCalc']['isLoggedIn'] ?? 0;
            $isTime2login = $match[0]['isTime2login'] ?? 0;

            if ($isLoggedIn && $isTime2login) {
                $postData = $this->request->getData();

                if (isset($postData['refereePIN'])) {
                    if ($this->checkPostData($match[0], $postData, false, true)) {
                        $log = false;

                        if ($id) {
                            $log = $this->MatcheventLogs->find('all', array(
                                'conditions' => array('match_id' => $match_id, 'id' => $id, 'canceled' => 0)
                            ))->first();
                        } else if ($postData['matchEventCode']) {
                            $log = $this->MatcheventLogs->find('all', array(
                                'contain' => array(
                                    'Matchevents' => array('fields' => array('code'))
                                ),
                                'conditions' => array('MatcheventLogs.match_id' => $match_id, 'Matchevents.code' => $postData['matchEventCode'], 'MatcheventLogs.canceled' => 0),
                                'order' => array('MatcheventLogs.id' => 'DESC')
                            ))->first();
                        }

                        if ($log) {
                            $log->set('canceled', 1);
                            $log->set('cancelTime', FrozenTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'));

                            if ($this->MatcheventLogs->save($log)) {
                                $logs = $this->getLogs($match_id);
                                $calc = $logs['calc'];

                                $isMatchLive = $match[0]['logsCalc']['isMatchLive'] ?? 0;
                                if ($isMatchLive) {
                                    $lastLog = $this->getLastLogCancelable($match_id);
                                    if ($lastLog) {
                                        $calc['inserted_id'] = $lastLog->id;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $calc['isLoggedIn'] = 0;
            }
        }
        $this->apiReturn($calc);
    }

    private function getLastLog($match_id = false)
    {
        return $this->MatcheventLogs->find('all', array(
            'conditions' => array('match_id' => $match_id, 'canceled' => 0),
            'contain' => array('Matchevents'),
            'order' => array('MatcheventLogs.id' => 'DESC')
        ))->first();
    }

    private function getLastLogCancelable($match_id = false)
    {
        return $this->MatcheventLogs->find('all', array(
            'conditions' => array('match_id' => $match_id, 'canceled' => 0, 'Matchevents.isCancelable' => 1),
            'contain' => array('Matchevents'),
            'order' => array('MatcheventLogs.id' => 'DESC')
        ))->first();
    }

    public
    function saveRemarks($match_id = false)
    {
        $this->loadModel('Matches');
        $match = $this->getMatchAndLogs($match_id, 0);

        if ($match) {
            $postData = $this->request->getData();

            if (isset($postData['matchEventCode'])) {
                $this->loadModel('Matchevents');
                $matchEvent = $this->Matchevents->find()->where(['code' => $postData['matchEventCode']])->first();
                $postData['matchEvent_id'] = $matchEvent->get('id');

                if ($this->checkPostData($match[0], $postData, $matchEvent)) {
                    $m = $match[0];
                    $m->set('remarks', (string)$postData['remarks']);
                    $this->Matches->save($m);
                }
            }
        }

        $this->apiReturn($match);
    }

    private function getMatchAndLogs($match_id = false, $includeLogs = false)
    {
        $conditionsArray = array('Matches.id' => $match_id);
        $this->loadModel('Matches');

        return $this->getMatches($conditionsArray, $includeLogs);
    }

    private function checkPostData($match, $postData, $matchEvent, $cancel = false): bool
    {
        if (!$match) {
            return false;
        }

        if (!$this->request->is('post')) {
            return false;
        }

        $this->loadModel('TeamYears');
        $refereePIN = $this->TeamYears->find()->where(['team_id' => $match['refereeTeam_id'], 'year_id' => $this->getCurrentYearId()])->first()->get('refereePIN'); // get it here cause of security reason nowhere else!

        $settings = $this->getSettings();

        if ($settings['isTest'] ?? 0) {
            if ($refereePIN === null || $refereePIN < 1 || (int)$postData['refereePIN'] !== 12345) {
                return false;
            }
        } else {
            if ($refereePIN === null || $refereePIN < 1) {
                return false;
            }
            if ((int)$postData['refereePIN'] != $refereePIN) { // check 2nd PIN (match pin)
                $this->loadModel('Matches');
                $refereePIN = $this->Matches->find()->where(['id' => $match['id']])->first()->get('refereePIN'); // get it here cause of security reason nowhere else!
                if ($refereePIN === null || $refereePIN < 1) {
                    return false;
                }

                if ((int)$postData['refereePIN'] != $refereePIN) {  // check 3nd PIN possiblity (ref subst team pin)
                    $refereePIN = $this->TeamYears->find()->where(['team_id' => $match['refereeTeamSubst_id'], 'year_id' => $this->getCurrentYearId()])->first()->get('refereePIN'); // get it here cause of security reason nowhere else!
                    if ($refereePIN === null || $refereePIN < 1) {
                        return false;
                    }

                    if ((int)$postData['refereePIN'] != $refereePIN) {
                        return false;
                    }
                }
            }
        }
        if (!isset($postData['matchEvent_id']) || !$matchEvent) {
            return (bool)$cancel; // allow cancellation without knowing event
        }

        if ($matchEvent->needsTeamAssoc == 1 && (!isset($postData['team_id']) || ($match['team1_id'] != (int)$postData['team_id'] && $match['team2_id'] != (int)$postData['team_id']))) {
            return false;
        }
        if ($matchEvent->needsPlayerAssoc == 1 && !isset($postData['playerNumber'])) {
            return false;
        }

        return true;
    }
}
