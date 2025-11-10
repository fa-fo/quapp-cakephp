<?php

namespace App\Controller\Component;

use App\Model\Entity\Match4;
use App\Model\Entity\Year;
use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;

class PlayOffComponent extends Component
{
    protected MatchGetComponent $MatchGet;

    public function getPlayOffName(int $isPlayOff): string
    {
        return match ($isPlayOff % 10) {
            4 => 'Halbfinale',
            3 => 'Spiel um Platz 3',
            2 => 'Finale',
            default => '',
        };
    }

    public function getPlayOffRanking(Year $year): array
    {
        $return = array();
        $match2 = FactoryLocator::get('Table')->get('Matches')->find()->where(['isPlayOff' => (int)($year->id . '2')])->first(); // Finale
        $match3 = FactoryLocator::get('Table')->get('Matches')->find()->where(['isPlayOff' => (int)($year->id . '3')])->first(); // 3rd-Place-Match
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

    public function getPlayOffWinLose(Year $year): array
    {
        $return = array();
        $matches = $this->MatchGet->getMatches(array('isPlayOff' => (int)($year->id . '4'))); // Semi-Finales

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

    public function getPlayOffNumber(int $i, int $use, int $year_id): int
    {
        $number = 0;

        if ($i < $use / 2) { // Quarter-Finale
            $number = $use; // 4
        }
        if ($i == $use - 2) { // Third-Place-Match
            $number = 3;
        }
        if ($i == $use - 1) { // Finale
            $number = 2;
        }

        return (int)($year_id . $number);
    }

}
