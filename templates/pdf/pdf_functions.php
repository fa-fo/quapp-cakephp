<?php

use App\Model\Entity\Match4;
use Cake\I18n\DateTime;

function ellipsis(string $input, int $maxLength = 24): string
{
    return strlen($input) > $maxLength ? mb_convert_encoding(substr($input, 0, $maxLength), 'UTF-8', 'UTF-8') . "..." : $input;
}

function getSportsMatch(array $matches, int $sportId): Cake\ORM\Entity|false
{
    foreach ($matches as $match) {
        /**
         * @var Match4 $match
         */
        if ($match->sport->id == $sportId) {
            return $match;
        }
    }
    return false;
}

function getMatchHtml(mixed $teamYears, array $settings): string
{
    $html = '';
    //$html .= '<img src="img/logo2025.png" style="float:left" width="150">';
    $html .= '<table border="0" cellspacing="0" cellpadding="6" align="center" width="70%">';
    $html .= '<tr>';
    $html .= '<th>Uhrzeit</th>';
    $html .= '<th>Mannschaft</th>';
    $html .= '<th>Sportart</th>';
    $html .= '<th>Spielfeld</th>';
    $html .= $settings['useLiveScouting'] ? '<th>Team-PIN<br/>für SR</th>' : '';
    $html .= '</tr>';

    foreach ($teamYears['infos']['matches'] as $match) {
        $tag1 = $match->canceled ? '<td><s>' : '<td>';
        $tag2 = $match->canceled ? '</s></td>' : '</td>';

        $refNote = ($match->isRefereeJob && $teamYears['infos']['referee_group_name'] != $match->group_name) ? '   !!' : '';

        $html .= '<tr>';
        $html .= $tag1 . DateTime::createFromFormat('Y-m-d H:i:s', $match->matchStartTime)->i18nFormat('HH:mm') . ' Uhr' . $tag2;
        $html .= $tag1 . $teamYears->team->name . $tag2;
        //$html .= $tag1 . $match->teams1->name . $tag2;
        //$html .= $tag1 . $match->teams2->name . $tag2;
        $html .= $tag1 . $match->sport->code . ($match->isRefereeJob ? 'SR' : '') . $tag2;
        $html .= $tag1 . $match->group_name . $refNote . $tag2;
        if ($settings['useLiveScouting'] && $match->isRefereeJob) {
            $html .= $tag1 . $teamYears->refereePIN . $tag2;
        }
        $html .= '</tr>';
    }

    $html .= '</table>';
    $html .= $settings['useLiveScouting'] ? '<img src="img/qr-codes.png" style="margin:20px 0 0 150px" width="650">' : '';

    return $html;
}
