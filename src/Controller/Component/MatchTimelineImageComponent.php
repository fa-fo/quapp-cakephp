<?php

namespace App\Controller\Component;

use App\Model\Entity\Match4;
use App\Model\Entity\Match4eventLog;
use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;

class MatchTimelineImageComponent extends Component
{
    public function createMatchTimelineImage(\Cake\ORM\Entity $match, int $year_id): void
    {
        /**
         * @var Match4 $match
         */
        $width = 800;
        $height = 250;
        $image = imagecreatetruecolor($width, $height);

        $black = (int)imagecolorallocate($image, 0, 0, 0);
        $grey = (int)imagecolorallocate($image, 200, 200, 200);
        $white = (int)imagecolorallocate($image, 255, 255, 255);
        $red = (int)imagecolorallocate($image, 255, 0, 0);
        $green = (int)imagecolorallocate($image, 0, 255, 0);
        $blue = (int)imagecolorallocate($image, 0, 0, 255);

        imagefill($image, 0, 0, $white);

        $logs = FactoryLocator::get('Table')->get('MatcheventLogs')->find('all', array(
            'conditions' => array('match_id' => $match->id, 'canceled' => 0),
            'contain' => array('Matchevents'),
            'order' => array('MatcheventLogs.id' => 'ASC')
        ))->all();

        $goals1 = 0;
        $goals2 = 0;
        $maxDiff = 1;
        $x0 = 10;
        $x20 = $width - 50;
        $xh = $x0 + ($x20 - $x0) / 2; // half-time
        $y1 = $height / 2;

        // team names
        imagestring($image, 5, $x0, 10, stripslashes(mb_convert_encoding($match->teams1->name, 'ISO-8859-1', 'UTF-8')), $black);
        imagestring($image, 5, $x0, $height - 20, stripslashes(mb_convert_encoding($match->teams2->name, 'ISO-8859-1', 'UTF-8')), $black);

        // score
        imagestring($image, 5, $width - 50, 10, (string)($match->resultGoals1 / $match->sport->goalFactor), $green);
        imagestring($image, 5, $width - 50, $height - 20, (string)($match->resultGoals2 / $match->sport->goalFactor), $green);

        // result
        imagestring($image, 5, $width - 25, 10, (string)$match->resultGoals1, $black);
        imagestring($image, 5, $width - 25, $height - 20, (string)$match->resultGoals2, $black);

        // ball image
        $widthBall = 12;
        if ($match->sport->code != '') {
            $imgBall = imagecreatefrompng(__DIR__ . '/../../../webroot/img/' . strtolower($match->sport->code) . '.png');
        }

        $matchStartTime = false; // temp
        $matchTimeInSeconds = 20 * 60; // temp

        // first iteration run to get match duration and maxDiff
        foreach ($logs as $log) {
            /**
             * @var Match4eventLog $log
             */
            if ($log->matchevent->code == 'MATCH_START') {
                $matchStartTime = $log->datetimeSent;
            }
            if ($log->matchevent->code == 'MATCH_END' && $matchStartTime) {
                $matchTimeInSeconds = ($log->datetimeSent)->diffInSeconds($matchStartTime);
            }
            $this->updateScore($goals1, $goals2, $log, $match);
            $maxDiff = max(abs($goals1 - $goals2), $maxDiff);
        }

        $yFactor = ($height - 90) / 2 / $maxDiff;
        $goals1 = 0;
        $goals2 = 0;
        $x1 = $x0;

        // baseline
        imageline($image, $x0, $y1, $x20, $y1, $black);

        // orientation lines
        $y10 = (int)($height / 2 - $maxDiff * $yFactor);
        imageline($image, $x0, $y10, $x20, $y10, $grey);
        $y20 = (int)($height / 2 + $maxDiff * $yFactor);
        imageline($image, $x0, $y20, $x20, $y20, $grey);

        // small vertical lines:
        imagesetthickness($image, 3);
        imageline($image, $x0, $y1 - 2, $x1, $y1 + 2, $black); // match start
        imageline($image, $xh, $y1 - 2, $xh, $y1 + 2, $black); // half-time
        imageline($image, $x20, $y1 - 2, $x20, $y1 + 2, $black); // match end

        if ($matchStartTime) {
            $x1Sec = ($x20 - $x0) / $matchTimeInSeconds;

            // match end time
            $x2 = $x0 + (int)($matchTimeInSeconds * $x1Sec);
            $y2 = (int)($height / 2);

            // match duration
            imagestring($image, 2, $x2 / 2, $y2 - 15, 'HZ', $black);
            imagestring($image, 2, $x2 - 4, $y2 - 15, 'Spielzeit', $black);
            imagestring($image, 2, $x2 + 6, $y2 - 6, ((int)($matchTimeInSeconds / 60) . ':' . str_pad((string)($matchTimeInSeconds % 60), 2, '0', STR_PAD_LEFT)), $black);

            // second iteration run: draw line
            foreach ($logs as $log) {
                /**
                 * @var Match4eventLog $log
                 */
                $points = $this->updateScore($goals1, $goals2, $log, $match);

                if (str_starts_with($log->matchevent->code, 'GOAL_') || $log->matchevent->code == 'MATCH_END') {
                    $diff = $goals1 - $goals2;
                    $x2 = $x0 + (int)(($log->datetimeSent)->diffInSeconds($matchStartTime) * $x1Sec);
                    $y2 = (int)($height / 2 - $diff * $yFactor);

                    imageline($image, $x1, $y1, $x2, $y2, $green);

                    if ($points > 0 && $imgBall) {
                        $this->drawBalls($image, $imgBall, $x2, $y2, $widthBall, $points, $log->team_id == $match->get('team1_id'));
                    }

                    // save coordinates for next loop
                    $x1 = $x2;
                    $y1 = $y2;
                }
            }
        }

        $dest = $this->getPhotoDir($year_id) . '/' . $match->id . '.jpg';
        imagejpeg($image, $dest);
        imagedestroy($image);
    }

    private function getPhotoDir(int $year_id): string
    {
        $dir = __DIR__ . '/../../../webroot/img/timelines/year' . $year_id;

        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function updateScore(int &$goals1, int &$goals2, mixed $log, mixed $match): int
    {
        /**
         * @var Match4eventLog $log
         * @var Match4 $match
         */
        $points = 0;
        if (str_starts_with($log->matchevent->code, 'GOAL_')) {
            $points = (int)substr($log->matchevent->code, 5, 1);

            if ($log->team_id == $match->get('team1_id')) {
                $goals1 += $points;
            } elseif ($log->team_id == $match->get('team2_id')) {
                $goals2 += $points;
            }
        }
        return $points;
    }

    private function drawBalls(\GdImage $image, \GdImage $imgBall, int $x, int $y, int $widthBall, int $points, bool $isTeam1): void
    {
        for ($p = 1; $p <= $points; $p++) {
            $yt = $y - $widthBall / 2 + 10 * $p * ($isTeam1 ? -1 : 1);
            imagecopyresampled($image, $imgBall, $x - $widthBall / 2, $yt, 0, 0, $widthBall, $widthBall, 16, 16);
        }
    }
}
