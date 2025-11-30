<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Match4;
use App\Model\Entity\Match4eventLog;
use App\Model\Entity\TeamYear;
use Cake\I18n\DateTime;

/**
 * ScoutRatings Controller
 *
 * @property \App\Controller\Component\CacheComponent $Cache
 * @property \App\Controller\Component\MatchGetComponent $MatchGet
 * @property \App\Controller\Component\ScrRankingComponent $ScrRanking
 * @property \App\Controller\Component\SecurityComponent $Security
 */
class ScoutRatingsController extends AppController
{
    public function setScrRanking(int $year_id = 0): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            //$array = array();
            $settings = $this->Cache->getSettings();
            $year_id = $year_id ?: $settings['currentYear_id'];

            $teamYears = $this->fetchTable('TeamYears')->find('all', array(
                'contain' => array('Teams'),
                'conditions' => array('TeamYears.year_id' => $year_id, 'Teams.hidden' => 0),
            ))->toArray();

            foreach ($teamYears as $teamYear) {
                /**
                 * @var TeamYear $teamYear
                 */
                $sumPoints = 0;

                $conditionsArray = array(
                    'Groups.year_id' => $year_id,
                    'Matches.canceled' => 0,
                    'OR' => array(
                        'Matches.refereeTeamSubst_id' => $teamYear->team_id,
                        'AND' => array('Matches.refereeTeamSubst_id IS' => null, 'Matches.refereeTeam_id' => $teamYear->team_id)));

                $matches = $this->MatchGet->getMatches($conditionsArray, 1, 0, 1);

                if (is_array($matches)) {
                    foreach ($matches as $match) {
                        /**
                         * @var Match4 $match
                         */
                        $logs = $this->fetchTable('MatcheventLogs')->find('all', array(
                            'contain' => array('Matchevents'),
                            'conditions' => array('match_id' => $match->id, 'matchEvent_id IN' => array(1, 90, 98)),
                        ))->orderBy(array('MatcheventLogs.id' => 'ASC'))->all();

                        $wasLoggedIn = 0;
                        foreach ($logs as $log) {
                            /**
                             * @var Match4eventLog $log
                             */
                            $points = 0;
                            $factor = 1;

                            if ($log->matchevent->code == 'LOGIN') {
                                $points = 50;
                                $mt = DateTime::createFromFormat('Y-m-d H:i:s', $match->matchStartTime);
                                $dateDiff = $log->datetime->diffInMinutes($mt, false);
                                if ($dateDiff > 0 && $wasLoggedIn == 0) {
                                    $factor = $dateDiff > 4 ? $factor : $factor * $dateDiff * .2;
                                    $wasLoggedIn = 1;
                                } else {
                                    $factor = 0;
                                }
                            } elseif ($log->matchevent->code == 'MATCH_CONCLUDE') {
                                $points = 40;
                                $factor = $match->isResultOk ? $factor : $factor * .5;
                                $factor = $match->resultAdmin == 0 ? $factor : $factor * .5;

                                // remarks
                                $thresholds = [7, 14];
                                $remarksLength = strlen((string)$match->remarks);
                                foreach ($thresholds as $t) {
                                    if ($remarksLength > $t) {
                                        $factor += 0.1;
                                    }
                                }
                            } elseif ($log->matchevent->code == 'PHOTO_UPLOAD') {
                                $points = 20;
                                $factor = $log->playerNumber; // 1 or 0
                            }

                            $sumPoints += (int)($points * $factor);
                            //$array[$teamYear->team_id][] = $log->matchevent->code.' -> '.(int)($points * $factor);
                        }
                    }

                    $teamYear->set('scrPoints', $sumPoints);
                    $teamYear->set('scrMatchCount', count($matches));
                    $this->fetchTable('TeamYears')->save($teamYear);
                }
            }

            usort($teamYears, function ($a, $b) {
                return $b->scrPoints <=> $a->scrPoints;
            });

            $c = 0;

            foreach ($teamYears as $teamYear) {
                $c++;
                $teamYear->set('scrRanking', $c);
                $this->fetchTable('TeamYears')->save($teamYear);
            }
        }

        $this->apiReturn(null);
    }


    public function getScrRanking(): void
    {
        $settings = $this->Cache->getSettings();
        $return = $this->ScrRanking->getScrRanking($settings['currentYear_id']);

        $this->apiReturn($return);
    }
}
