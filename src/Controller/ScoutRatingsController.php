<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Match4;
use App\Model\Entity\Match4event;
use App\Model\Entity\Match4eventLog;
use App\Model\Entity\ScoutRating;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;

/**
 * ScoutRatings Controller
 *
 * @property \App\Model\Table\ScoutRatingsTable $ScoutRatings
 * @property \App\Controller\Component\CacheComponent $Cache
 * @property \App\Controller\Component\MatchGetComponent $MatchGet
 * @property \App\Controller\Component\ScrRankingComponent $ScrRanking
 * @property \App\Controller\Component\SecurityComponent $Security
 */
class ScoutRatingsController extends AppController
{
    public function checkAll(): void
    {
        $rowCount = 0;
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();

            $conditionsArray = array(
                'Groups.year_id' => $settings['currentYear_id'],
                'Groups.day_id' => $settings['currentDay_id'],
            );

            $matches = $this->MatchGet->getMatches($conditionsArray, 1, 0, 1);

            if (is_array($matches)) {
                foreach ($matches as $match) {
                    $this->checkScrPoints($match);
                }
            }

            $rowCount = $this->setScrRanking($settings['currentYear_id']);
        }

        $this->apiReturn($rowCount);
    }

    private function checkScrPoints(\Cake\ORM\Entity $match): void
    {
        $ratings = $this->ScoutRatings->find('all', array(
            'contain' => array(
                'MatcheventLogs',
                'MatcheventLogs.Matchevents',
            ),
            'conditions' => array('MatcheventLogs.match_id' => $match->id),
            'order' => array('ScoutRatings.id' => 'ASC')
        ))->all();

        $wasLoggedIn = 0;

        foreach ($ratings as $rating) {
            $ok = 0;
            $factor = 1;
            $log = $rating->matchevent_log;
            $event = $log->matchevent;
            /**
             * @var ScoutRating $rating
             * @var Match4eventLog $log
             * @var Match4event $event
             * @var Match4 $match
             */

            if ($event->code == 'LOGIN') {
                $mt = DateTime::createFromFormat('Y-m-d H:i:s', $match->matchStartTime);
                $ok = $log->datetime < $mt;
                if ($ok && $wasLoggedIn == 0) {
                    $dateDiff = $mt->diffInMinutes($log->datetime);
                    $factor = $dateDiff > 4 ? $factor : $factor * $dateDiff * .2;
                    $wasLoggedIn = 1;
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

            $scr = $this->ScoutRatings->find()->where(['id' => $rating->id])->first();

            if ($scr) {
                /**
                 * @var ScoutRating $scr
                 */
                $scr->set('confirmed', (int)$ok * $factor);
                $this->ScoutRatings->save($scr);
            }
        }
    }

    private function setScrRanking(int $year_id): int
    {
        $conn = ConnectionManager::get('default');
        /**
         * @var \Cake\Database\Connection $conn
         */
        $conn->execute("UPDATE team_years SET scrRanking=NULL WHERE year_id=" . $year_id);
        $conn->execute("UPDATE team_years SET scrPoints=NULL WHERE year_id=" . $year_id);

        return $conn->execute(file_get_contents(__DIR__ . "/sql/update_teamYears_scrRanking.sql"), ['year_id' => $year_id])->rowCount();
    }

    /**
     * @throws \Exception
     */
    public function getScrRanking(): void
    {
        $settings = $this->Cache->getSettings();
        $return = $this->ScrRanking->getScrRanking($settings['currentYear_id']);

        $this->apiReturn($return);
    }
}
