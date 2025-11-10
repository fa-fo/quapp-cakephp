<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Match4;
use App\Model\Entity\Match4event;
use App\Model\Entity\Match4eventLog;
use App\Model\Entity\PushTokenRating;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;

/**
 * PushTokenRatings Controller
 *
 * @property \App\Model\Table\PushTokenRatingsTable $PushTokenRatings
 * @property \App\Controller\Component\CacheComponent $Cache
 * @property \App\Controller\Component\MatchGetComponent $MatchGet
 * @property \App\Controller\Component\PtrRankingComponent $PtrRanking
 */
class PushTokenRatingsController extends AppController
{
    public function checkAll(): void
    {
        $rowCount = 0;
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();

            $conditionsArray = array(
                'Groups.year_id' => $settings['currentYear_id'],
                'Groups.day_id' => $settings['currentDay_id'],
            );

            $matches = $this->MatchGet->getMatches($conditionsArray, 1, 0, 1);

            if (is_array($matches)) {
                foreach ($matches as $match) {
                    $this->checkPtrPoints($match);
                }
            }

            $rowCount = $this->setPtrRanking($settings['currentYear_id']);
        }
        $this->apiReturn($rowCount);
    }

    private function checkPtrPoints(\Cake\ORM\Entity $match): void
    {
        $ratings = $this->PushTokenRatings->find('all', array(
            'contain' => array(
                'MatcheventLogs',
                'MatcheventLogs.Matchevents',
            ),
            'conditions' => array('MatcheventLogs.match_id' => $match->id)
        ))->all();

        foreach ($ratings as $rating) {
            $ok = 0;
            $factor = 1;
            $log = $rating->matchevent_log;
            $event = $log->matchevent;
            /**
             * @var PushTokenRating $rating
             * @var Match4eventLog $log
             * @var Match4event $event
             * @var Match4 $match
             */

            if ($event->code == 'LOGIN') {
                $mt = DateTime::createFromFormat('Y-m-d H:i:s', $match->matchStartTime);
                $ok = $log->datetime < $mt;
                if ($ok) {
                    $dateDiff = $mt->diffInMinutes($log->datetime);
                    $factor = $dateDiff > 4 ? $factor : $factor * $dateDiff * .2;
                } else {
                    $factor = 0;
                }
            } elseif ($event->code == 'MATCH_CONCLUDE') {
                $ok = 1;
                $factor = $match->isResultOk ? $factor : $factor * .8;
                $factor = $match->resultAdmin == 0 ? $factor : $factor * .5;
                $factor = strlen((string)$match->remarks) > 7 ? $factor + .1 : $factor;
            } elseif ($event->code == 'PHOTO_UPLOAD') {
                $ok = $log->playerNumber;
            }

            $ptr = $this->PushTokenRatings->find()->where(['id' => $rating->id])->first();

            if ($ptr) {
                /**
                 * @var PushTokenRating $ptr
                 */
                $ptr->set('confirmed', (int)$ok * $factor);
                $this->PushTokenRatings->save($ptr);
            }
        }
    }

    private function setPtrRanking(int $year_id): int
    {
        $conn = ConnectionManager::get('default');
        /**
         * @var \Cake\Database\Connection $conn
         */
        $conn->execute("UPDATE push_tokens SET ptrPoints=0 WHERE 1");
        $conn->execute("UPDATE push_tokens SET ptrRanking=NULL WHERE 1");

        $sql = "
            UPDATE push_tokens pt
                LEFT JOIN (
                    SELECT q.*, (@rownum := @rownum + 1) AS rr
                    FROM (
                             SELECT max(pt2.id) as id, sum(ptr.points * ptr.confirmed) AS pp
                             FROM push_tokens pt2
                             LEFT JOIN push_token_ratings ptr ON ptr.push_token_id = pt2.id
                             WHERE pt2.my_year_id=" . $year_id . "
                             GROUP BY ptr.push_token_id
                             ORDER BY pp DESC
                         ) q
                             CROSS JOIN (SELECT @rownum := 0) ff
                ) p ON pt.id = p.id

            SET pt.ptrPoints = p.pp,
                pt.ptrRanking = p.rr
            WHERE p.pp IS NOT NULL";

        $stmt = $conn->execute($sql);

        return $stmt->rowCount();
    }

    /**
     * @throws \Exception
     */
    public function getPtrRanking(string $mode = 'single'): void
    {
        $settings = $this->Cache->getSettings();
        $return = $this->PtrRanking->getPtrRanking($mode, $settings['currentYear_id']);

        $this->apiReturn($return);
    }
}
