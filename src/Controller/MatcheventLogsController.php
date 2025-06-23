<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Match4;
use App\Model\Entity\Match4event;
use App\Model\Entity\Match4eventLog;
use App\Model\Entity\PushToken;
use App\Model\Entity\PushTokenRating;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;

/**
 * MatcheventLogs Controller
 *
 * @property \App\Model\Table\MatcheventLogsTable $MatcheventLogs
 */
class MatcheventLogsController extends AppController
{

    public function login(string $match_id = ''): void
    {
        $match_id = (int)$match_id;
        $matchReturn = false; // initial
        $match = $this->getMatchAndLogs($match_id, 1);

        if (!is_null($match)) {
            $isLoggedIn = $match['logsCalc']['isLoggedIn'] ?? 0;

            if ($isLoggedIn != 1) {
                $postData = $this->request->getData();

                if (isset($postData['matchEventCode'])) {
                    $matchEvent = $this->fetchTable('Matchevents')->find()->where(['code' => $postData['matchEventCode']])->first();
                    /**
                     * @var Match4event $matchEvent
                     */
                    $postData['matchEvent_id'] = $matchEvent->id;

                    if ($this->checkPostData($match, $postData, $matchEvent)) {
                        $newLog = $this->MatcheventLogs->newEmptyEntity();
                        $newLog = $this->MatcheventLogs->patchEntity($newLog, $postData);
                        $newLog->set('match_id', $match_id);
                        $newLog->set('datetime', DateTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'));

                        if ($this->MatcheventLogs->save($newLog)) {
                            if (isset($postData['expoPushToken'])) {
                                $this->setPushTokenRating($newLog->id, $postData['matchEventCode'], $postData['expoPushToken']);
                            }

                            $logs = $this->getLogs($match_id);

                            if (is_array($logs)) {
                                $match['logsCalc'] = $logs['calc'];
                            }

                            $matchReturn = array($match);  // return full match infos
                        }
                    }
                }
            }
        }

        $this->apiReturn($matchReturn);
    }

    public function add(string $match_id = ''): void
    {
        $match_id = (int)$match_id;
        $calc = array(); // initial

        $match = $this->getMatchAndLogs($match_id, 1);
        if (!is_null($match)) {
            $isLoggedIn = $match['logsCalc']['isLoggedIn'] ?? 0;
            $isTime2login = $match['isTime2login'] ?? 0;

            if ($isLoggedIn && $isTime2login) {
                $postData = $this->request->getData();

                if (isset($postData['matchEventCode'])) {
                    $matchEvent = $this->fetchTable('Matchevents')->find()->where(['code' => $postData['matchEventCode']])->first();
                    /**
                     * @var Match4event $matchEvent
                     */
                    $postData['matchEvent_id'] = $matchEvent->get('id');
                    $postData['team_id'] = isset($postData['team_id']) ? (int)$postData['team_id'] : null;
                    $postData['playerNumber'] = isset($postData['playerNumber']) ? (int)$postData['playerNumber'] : null;

                    if ($this->checkPostData($match, $postData, $matchEvent)) {
                        $newLog = $this->MatcheventLogs->newEmptyEntity();
                        $newLog = $this->MatcheventLogs->patchEntity($newLog, $postData);
                        $newLog->set('match_id', $match_id);
                        $newLog->set('datetime', DateTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'));

                        if ($this->MatcheventLogs->save($newLog)) {
                            if ($postData['matchEventCode'] == 'PHOTO_UPLOAD') {
                                $this->savePhoto($postData['photo'], $match_id, $newLog->id);
                            }

                            if (isset($postData['expoPushToken'])) {
                                $this->setPushTokenRating($newLog->id, $postData['matchEventCode'], $postData['expoPushToken']);
                            }

                            $isMatchLive = $match['logsCalc']['isMatchLive'] ?? 0;

                            $logs = $this->getLogs($match_id);
                            if (is_array($logs)) {
                                $calc = $logs['calc'];

                                if ($isMatchLive) {
                                    $lastLog = $this->getLastLogCancelable($match_id);
                                    if ($lastLog) {
                                        /**
                                         * @var Match4eventLog $lastLog
                                         */
                                        $calc['inserted_id'] = $lastLog->id;
                                        if (in_array($calc['inserted_id'], ($calc['foulOutLogIds'] ?? array()))) {
                                            $ll = $this->getLastLog($match_id);
                                            /**
                                             * @var Match4eventLog $ll
                                             */
                                            $calc['showFoulOutModal'] = ($ll->id == $calc['inserted_id'] ? 1 : 0);

                                        } else if (in_array($calc['inserted_id'], ($calc['doubleYellowLogIds'] ?? array()))) {
                                            $ll = $this->getLastLog($match_id);
                                            /**
                                             * @var Match4eventLog $ll
                                             */
                                            $calc['showDoubleYellowModal'] = ($ll->id == $calc['inserted_id'] ? 1 : 0);
                                        }
                                    }
                                }
                                unset($calc['foulOutLogIds']); // no need
                                unset($calc['doubleYellowLogIds']); // no need
                                $calc['isTime2matchEnd'] = $match['isTime2matchEnd'] ?? 0;
                                $calc['isTime2confirm'] = $match['isTime2confirm'] ?? 0;
                            }
                        }
                    }
                }
            } else {
                $calc['isLoggedIn'] = 0;
            }
        }

        $this->apiReturn($calc); // return only small infos to reduce bandwith
    }

    public function cancel(string $match_id = '', string $id = ''): void
    {
        $match_id = (int)$match_id;
        $id = (int)$id;
        $calc = array(); // initial
        $match = $this->getMatchAndLogs($match_id, 1);

        if (!is_null($match)) {
            $isLoggedIn = $match['logsCalc']['isLoggedIn'] ?? 0;
            $isTime2login = $match['isTime2login'] ?? 0;

            if ($isLoggedIn && $isTime2login) {
                $postData = $this->request->getData();

                if (isset($postData['refereePIN'])) {
                    if ($this->checkPostData($match, $postData, null, true)) {
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
                            /**
                             * @var Match4eventLog $log
                             */
                            $log->set('canceled', 1);
                            $log->set('cancelTime', DateTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'));

                            if ($this->MatcheventLogs->save($log)) {
                                $isMatchLive = $match['logsCalc']['isMatchLive'] ?? 0;
                                $logs = $this->getLogs($match_id);

                                if (is_array($logs)) {
                                    $calc = $logs['calc'];

                                    if ($isMatchLive) {
                                        $lastLog = $this->getLastLogCancelable($match_id);
                                        if ($lastLog) {
                                            /**
                                             * @var Match4eventLog $lastLog
                                             */
                                            $calc['inserted_id'] = $lastLog->id;
                                        }
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

    private function getLastLog(int $match_id): array|\Cake\Datasource\EntityInterface|null
    {
        return $this->MatcheventLogs->find('all', array(
            'conditions' => array('match_id' => $match_id, 'canceled' => 0),
            'contain' => array('Matchevents'),
            'order' => array('MatcheventLogs.id' => 'DESC')
        ))->first();
    }

    private function getLastLogCancelable(int $match_id): array|\Cake\Datasource\EntityInterface|null
    {
        return $this->MatcheventLogs->find('all', array(
            'conditions' => array('match_id' => $match_id, 'canceled' => 0, 'Matchevents.isCancelable' => 1),
            'contain' => array('Matchevents'),
            'order' => array('MatcheventLogs.id' => 'DESC')
        ))->first();
    }

    public function saveRemarks(string $match_id = ''): void
    {
        $match_id = (int)$match_id;
        $match = $this->getMatchAndLogs($match_id, 0);

        if (!is_null($match)) {
            $postData = $this->request->getData();

            if (isset($postData['matchEventCode']) && trim($postData['matchEventCode']) != '') {
                $matchEvent = $this->fetchTable('Matchevents')->find()->where(['code' => $postData['matchEventCode']])->first();
                /**
                 * @var Match4event $matchEvent
                 */
                $postData['matchEvent_id'] = $matchEvent->get('id');

                if ($this->checkPostData($match, $postData, $matchEvent)) {
                    $m = $this->fetchTable('Matches')->find()->where(['id' => $match_id])->first();
                    /**
                     * @var Match4 $m
                     */
                    $m->set('remarks', (string)$postData['remarks']);
                    $this->fetchTable('Matches')->save($m);
                }
            }
        }

        $this->apiReturn($match);
    }

    private function getMatchAndLogs(int $match_id, int $includeLogs): array|null
    {
        $match = null;
        $conditionsArray = array('Matches.id' => $match_id);
        $match_array = $this->getMatches($conditionsArray, $includeLogs);

        if (is_array($match_array)) {
            $match = $match_array[0]->toArray();
        }
        return $match;
    }

    private function checkPostData(array $match, array $postData, \Cake\ORM\Entity|null $matchEvent, bool $cancel = false): bool
    {
        if (!$this->request->is('post')) {
            return false;
        }

        $refereePIN = $this->fetchTable('TeamYears')->find()->where(['team_id' => $match['refereeTeam_id'], 'year_id' => $this->getCurrentYearId()])->first()->get('refereePIN'); // get it here cause of security reason nowhere else!
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
                $refereePIN = $this->fetchTable('Matches')->find()->where(['id' => $match['id']])->first()->get('refereePIN'); // get it here cause of security reason nowhere else!
                if ($refereePIN === null || $refereePIN < 1) {
                    return false;
                }

                if ((int)$postData['refereePIN'] != $refereePIN) {  // check 3nd PIN possiblity (ref subst team pin)
                    if ($match['refereeTeamSubst_id'] === null) {
                        return false;
                    }

                    $refereePIN = $this->fetchTable('TeamYears')->find()->where(['team_id' => $match['refereeTeamSubst_id'], 'year_id' => $this->getCurrentYearId()])->first()->get('refereePIN'); // get it here cause of security reason nowhere else!
                    if ($refereePIN === null || $refereePIN < 1) {
                        return false;
                    }

                    if ((int)$postData['refereePIN'] != $refereePIN) {
                        return false;
                    }
                }
            }
        }
        if (!isset($postData['matchEvent_id']) || is_null($matchEvent)) {
            return (bool)$cancel; // allow cancellation without knowing event
        }

        /**
         * @var Match4event $matchEvent
         */
        if ($matchEvent->needsTeamAssoc == 1 && (!isset($postData['team_id']) || ($match['team1_id'] != (int)$postData['team_id'] && $match['team2_id'] != (int)$postData['team_id']))) {
            return false;
        }
        if ($matchEvent->needsPlayerAssoc == 1 && !isset($postData['playerNumber'])) {
            return false;
        }

        return true;
    }

    /**
     * @throws \ErrorException
     */
    private function savePhoto(string $photoDataBase64, int $match_id, int $id): void
    {
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photoDataBase64));

        $dir = $this->getPhotoDir();
        if (!file_exists($dir . '/original')) {
            mkdir($dir . '/original', 0755, true);
        }
        if (!file_exists($dir . '/web')) {
            mkdir($dir . '/web', 0755, true);
        }
        if (!file_exists($dir . '/thumbs')) {
            mkdir($dir . '/thumbs', 0755, true);
        }

        $filename0 = $this->getPhotoFilename($dir . '/original', $match_id, $id);
        file_put_contents($filename0, $data);

        // create thumbnails
        $filename1 = $this->getPhotoFilename($dir . '/web', $match_id, $id);
        $filename2 = $this->getPhotoFilename($dir . '/thumbs', $match_id, $id);

        $this->makeThumb($filename0, $filename1, 1200);
        $this->makeThumb($filename0, $filename2, 120);
    }

    private function makeThumb(string $src, string $dest, int $desired_width): void
    {
        /* read the source image */
        $source_image = imagecreatefromjpeg($src);

        if ($source_image) {
            $width = imagesx($source_image);
            $height = imagesy($source_image);

            /* find the "desired height" of this thumbnail, relative to the desired width  */
            $desired_height = (int)floor($height * ($desired_width / $width));

            if ($desired_height > 0) {
                /* create a new, "virtual" image */
                $virtual_image = imagecreatetruecolor($desired_width, $desired_height);

                /* copy source image at a resized size */
                imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);

                /* create the physical thumbnail image to its destination */
                imagejpeg($virtual_image, $dest);
            }
        }
    }

    public function getPhotosToCheck(): void
    {
        $photos = array(); // initial
        $postData = $this->request->getData();
        $settings = $this->getSettings();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $conditionsArray = array('Matchevents.code' => 'PHOTO_UPLOAD', 'year_id' => $settings['currentYear_id']);
            $containArray = array('Matchevents', 'Matches', 'Matches.Groups');

            $photos['toCheck'] = $this->MatcheventLogs->find('all', array(
                'conditions' => array_merge($conditionsArray, array('playerNumber IS' => null)),
                'fields' => array('id', 'match_id'),
                'contain' => $containArray,
                'order' => array('MatcheventLogs.id' => 'ASC')
            ));

            $photos['okCount'] = $this->MatcheventLogs->find('all', array(
                'conditions' => array_merge($conditionsArray, array('playerNumber IS' => '1')),
                'contain' => $containArray,
            ))->count();

            $photos['notOkCount'] = $this->MatcheventLogs->find('all', array(
                'conditions' => array_merge($conditionsArray, array('playerNumber IS' => '0')),
                'contain' => $containArray,
            ))->count();

            $photos['lastChecked'] = $this->MatcheventLogs->find('all', array(
                'conditions' => array_merge($conditionsArray, array('playerNumber IS NOT' => null)),
                'fields' => array('id', 'match_id', 'playerNumber'),
                'contain' => $containArray,
                'order' => array('MatcheventLogs.id' => 'DESC')
            ))->limit(32);

            foreach ($photos['lastChecked'] as $log) {
                $conditionsArray = array(
                    'Matches.id' => $log->match_id,
                );

                $a = $this->getMatches($conditionsArray);
                $log['match'] = is_array($a) ? $a[0] : null;
            }
        }

        $this->apiReturn($photos);
    }

    public function setPhotoCheck(string $id = '', string $isOk = ''): void
    {
        $return = array(); // initial
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $id = (int)$id;
            if ($id) {
                $isOk = (int)$isOk;
                $log = $this->MatcheventLogs->find()->where(['id' => $id])->first();

                if ($log) {
                    /**
                     * @var Match4eventLog $log
                     */
                    $log->set('playerNumber', $isOk); // sic! playerNumber as isOk-Field

                    if ($this->MatcheventLogs->save($log)) {
                        $return = $log;
                    }
                }
            }
        }
        $this->apiReturn($return);
    }

    public function getPhotosAll(string $myTeamId = '', string $year_id = ''): void
    {
        $return = array();
        $myPhotos = array();
        $myTeamId = (int)$myTeamId;
        $settings = $this->getSettings();
        $year_id = (int)$year_id ?: $settings['currentYear_id'];

        $photos = $this->MatcheventLogs->find('all', array(
            'conditions' => array(
                'Matchevents.code' => 'PHOTO_UPLOAD',
                'playerNumber' => '1',
                'year_id' => $year_id,
            ),
            'fields' => array('id', 'match_id'),
            'contain' => array(
                'Matchevents',
                'Matches' => array('fields' => array('resultGoals1' => 'resultGoals1', 'resultGoals2' => 'resultGoals2')),
                'Matches.Rounds' => array('fields' => array('round_id' => 'Rounds.id')),
                'Matches.Groups' => array('fields' => array('group_name' => 'Groups.name')),
                'Matches.Sports' => array('fields' => array('sport_name' => 'Sports.name')),
                'Matches.Teams1' => array('fields' => array('team1_name' => 'Teams1.name', 'team1_id' => 'Teams1.id')),
                'Matches.Teams2' => array('fields' => array('team2_name' => 'Teams2.name', 'team2_id' => 'Teams2.id')),
                'Matches.Teams3' => array('fields' => array('team3_name' => 'Teams3.name')),
            ),
            'order' => array('MatcheventLogs.id' => 'DESC')
        ));

        $c = -1;
        foreach ($photos as $ph) {
            $c++;
            if ($myTeamId == $ph->team1_id || $myTeamId == $ph->team2_id) {
                $myPhotos[] = $c;
            }
        }

        $return['photos'] = $photos;
        $return['myPhotos'] = $myPhotos;

        $this->apiReturn($return, $year_id);
    }


    private function getPhotoDir(): string
    {
        $year = $this->getCurrentYear();
        return __DIR__ . '/../../webroot/img/' . $year->name;
    }

    private function getPhotoFilename(string $dir, int $match_id, int $id): string
    {
        return $dir . '/' . $match_id . '_' . $id . '.jpg';
    }

    private function setPushTokenRating(int $id, string $matchEventCode, string $expoPushToken): void
    {
        $settings = $this->getSettings();

        if ($settings['usePushTokenRatings'] && $expoPushToken != '') {
            $pt = $this->fetchTable('PushTokens')->find()->where(['expoPushToken' => $expoPushToken])->first();

            if ($pt) {
                /**
                 * @var PushToken $pt
                 */
                $ptr = $this->fetchTable('PushTokenRatings')->newEmptyEntity();
                /**
                 * @var PushTokenRating $ptr
                 */
                $ptr->set('push_token_id', $pt->id);
                $ptr->set('matchevent_log_id', $id);
                $ptr->set('points', $this->getPushTokenRatingPoints($matchEventCode));
                $this->fetchTable('PushTokenRatings')->save($ptr);
            }
        }
    }

    private function getPushTokenRatingPoints(string $matchEventCode): int
    {
        return match ($matchEventCode) {
            'LOGIN' => 50,
            'MATCH_CONCLUDE' => 40,
            'PHOTO_UPLOAD' => 20,
            default => 0,
        };
    }

    public function insertTestLogs(): void
    {
        $matches = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->getSettings();

            if ($settings['isTest'] ?? 0) {
                $conditionsArray = array(
                    'Groups.year_id' => $settings['currentYear_id'],
                    'Groups.day_id' => $settings['currentDay_id'],
                );

                $matches = $this->getMatches($conditionsArray, 1);
                if (is_array($matches) && count($matches) > 0) {
                    foreach ($matches as $m) {
                        /**
                         * @var Match4 $m
                         */
                        if ($m->resultTrend === null) {
                            $pt = $this->fetchTable('PushTokens')->find('all')
                                ->where(['my_year_id >=' => $settings['currentYear_id'] - 1])
                                ->orderBy('rand()')->first();

                            // LOGIN
                            $newLog = $this->MatcheventLogs->newEmptyEntity();
                            $event = $this->fetchTable('Matchevents')->find()->where(['code' => 'LOGIN'])->first();
                            $newLog->set('matchEvent_id', $event->get('id'));
                            $newLog->set('match_id', $m->id);
                            $this->MatcheventLogs->save($newLog);

                            $ptr = $this->fetchTable('PushTokenRatings')->newEmptyEntity();
                            $ptr->set('push_token_id', $pt->id);
                            $ptr->set('matchevent_log_id', $newLog->id);
                            $ptr->set('points', $this->getPushTokenRatingPoints('LOGIN'));
                            $this->fetchTable('PushTokenRatings')->save($ptr);


                            // MATCH_START
                            $newLog = $this->MatcheventLogs->newEmptyEntity();
                            $event = $this->fetchTable('Matchevents')->find()->where(['code' => 'MATCH_START'])->first();
                            $newLog->set('matchEvent_id', $event->get('id'));
                            $newLog->set('match_id', $m->id);
                            $this->MatcheventLogs->save($newLog);

                            for ($i = 0; $i < 2; $i++) {
                                // GOAL_1POINT
                                $newLog = $this->MatcheventLogs->newEmptyEntity();
                                $event = $this->fetchTable('Matchevents')->find()->where(['code' => 'GOAL_1POINT'])->first();
                                $newLog->set('matchEvent_id', $event->get('id'));
                                $newLog->set('match_id', $m->id);
                                $newLog->set('team_id', random_int(0, 1) ? $m->team1_id : $m->team2_id);
                                $this->MatcheventLogs->save($newLog);
                            }

                            // MATCH_END
                            $newLog = $this->MatcheventLogs->newEmptyEntity();
                            $event = $this->fetchTable('Matchevents')->find()->where(['code' => 'MATCH_END'])->first();
                            $newLog->set('matchEvent_id', $event->get('id'));
                            $newLog->set('match_id', $m->id);
                            $this->MatcheventLogs->save($newLog);

                            // RESULT_WIN_?
                            $newLog = $this->MatcheventLogs->newEmptyEntity();
                            $event = $this->fetchTable('Matchevents')->find()->where(['code' => 'RESULT_WIN_TEAM1'])->first();
                            $newLog->set('matchEvent_id', $event->get('id'));
                            $newLog->set('match_id', $m->id);
                            $this->MatcheventLogs->save($newLog);

                            // MATCH_CONCLUDE
                            $newLog = $this->MatcheventLogs->newEmptyEntity();
                            $event = $this->fetchTable('Matchevents')->find()->where(['code' => 'MATCH_CONCLUDE'])->first();
                            $newLog->set('matchEvent_id', $event->get('id'));
                            $newLog->set('match_id', $m->id);
                            $this->MatcheventLogs->save($newLog);

                            $ptr = $this->fetchTable('PushTokenRatings')->newEmptyEntity();
                            $ptr->set('push_token_id', $pt->id);
                            $ptr->set('matchevent_log_id', $newLog->id);
                            $ptr->set('points', $this->getPushTokenRatingPoints('MATCH_CONCLUDE'));
                            $this->fetchTable('PushTokenRatings')->save($ptr);

                            // LOGOUT
                            $newLog = $this->MatcheventLogs->newEmptyEntity();
                            $event = $this->fetchTable('Matchevents')->find()->where(['code' => 'LOGOUT'])->first();
                            $newLog->set('matchEvent_id', $event->get('id'));
                            $newLog->set('match_id', $m->id);
                            $this->MatcheventLogs->save($newLog);
                        }
                    }
                }
            }
        }

        $matchesCount = is_array($matches) ? count($matches) : false;
        $this->apiReturn($matchesCount);
    }

    public function clearByRound(string $round_id = ''): void
    {
        $rc = 0;
        $round_id = (int)$round_id;
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            if ($round_id > 0) {
                $settings = $this->getSettings();
                $conn = ConnectionManager::get('default');
                /**
                 * @var \Cake\Database\Connection $conn
                 */
                $sql = "DELETE ptr FROM push_token_ratings ptr
                        LEFT JOIN matchevent_logs ml ON ml.id = ptr.matchevent_log_id
                        LEFT JOIN `matches` m ON ml.match_id=m.id
                        LEFT JOIN `groups` g ON m.group_id=g.id
                        WHERE m.round_id = " . $round_id . "
                        AND g.year_id = " . $settings['currentYear_id'] . " AND g.day_id = " . $settings['currentDay_id'];
                $rc += $conn->execute($sql)->rowCount();

                $sql = "DELETE ml FROM matchevent_logs ml
                        LEFT JOIN `matches` m ON ml.match_id=m.id
                        LEFT JOIN `groups` g ON m.group_id=g.id
                        WHERE m.round_id = " . $round_id . "
                        AND g.year_id = " . $settings['currentYear_id'] . " AND g.day_id = " . $settings['currentDay_id'];

                $rc += $conn->execute($sql)->rowCount();
            }
        }

        $this->apiReturn(array('rows affected' => $rc));
    }
}
