<?php

use Cake\I18n\FrozenTime;

function ellipsis(string $input, int $maxLength = 25): string
{
    return strlen($input) > $maxLength ? mb_convert_encoding(substr($input, 0, $maxLength), 'UTF-8', 'UTF-8') . "..." : $input;
}

function getMatchHtml(string $html, mixed $teamYears): string
{
    $html = '';
    //$html .= '<img src="img/logo2024.png" style="float:left" width="150">';
    $html .= '<table border="0" cellspacing="0" cellpadding="6" align="center" width="70%">';
    $html .= '<tr>';
    $html .= '<th>Uhrzeit</th>';
    $html .= '<th>Mannschaft</th>';
    $html .= '<th>Sportart</th>';
    $html .= '<th>Spielfeld</th>';
    $html .= '<th>Team-PIN<br/>für SR</th>';
    $html .= '</tr>';

    foreach ($teamYears['infos']['matches'] as $match) {
        $tag1 = $match->canceled ? '<td><s>' : '<td>';
        $tag2 = $match->canceled ? '</s></td>' : '</td>';

        $refNote = ($match->isRefereeJob && $teamYears['infos']['referee_group_name'] != $match->group_name) ? '   !!' : '';

        $html .= '<tr>';
        $html .= $tag1 . FrozenTime::createFromFormat('Y-m-d H:i:s', $match->matchStartTime)->i18nFormat('HH:mm') . ' Uhr' . $tag2;
        $html .= $tag1 . $teamYears->team->name . $tag2;
        //$html .= $tag1 . $match->teams1->name . $tag2;
        //$html .= $tag1 . $match->teams2->name . $tag2;
        $html .= $tag1 . $match->sport->code . ($match->isRefereeJob ? 'SR' : '') . $tag2;
        $html .= $tag1 . $match->group_name . $refNote . $tag2;
        if ($match->isRefereeJob) {
            $html .= $tag1 . $teamYears->refereePIN . $tag2;
        }
        $html .= '</tr>';
    }

    $html .= '</table>';
    $html .= '<img src="img/qr-codes.png" style="margin:20px 0 0 150px" width="650">';

    return $html;
}
