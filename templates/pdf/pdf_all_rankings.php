<?php

use App\Model\Entity\Group;
use App\Model\Entity\GroupTeam;

require_once __DIR__ . '/../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();

try {
    $p = 0;
    $groups = $groups ?? array();
    $year = $year ?? array();

    foreach ($groups as $group) {
        /**
         * @var Group $group
         */
        $p++;
        $html = '';
        $mpdf->AddPage('L');
        $fontSize = $group->teamsCount > 16 ? 14 : 20;
        $padding = $group->teamsCount > 16 ? 4 : 6;

        if ($p == 1) {
            $html .= '<style>
            h1 {margin: 0; padding: 0; font-size: 22px}
            table {border-collapse: collapse}
            th {border: 0; padding: 2px; font-size: ' . $fontSize . 'px}
            td {border: solid 1px #000; padding: ' . $padding . 'px; font-size: ' . $fontSize . 'px}
            span {font-size: 16px}
            </style>';
        }

        $html .= '<h1>Tabelle ' . ($year['teamsCount'] > 24 ? 'Gruppe ' . $group->name : '') . ' <span>(' . $group->date->i18nFormat('EEEE, dd.MM.yyyy') . ')</span></h1>';

        $html .= '<table border="0"  cellspacing="0" cellpadding="8" align="center" width="100%">';
        $html .= '<tr>';
        $html .= '<th></th>';
        $html .= '<th></th>';
        $html .= '<th>Spiele</th>';
        $html .= '<th>Torverh.</th>';
        $html .= '<th>Tordiff.</th>';
        $html .= '<th>Punkte</th>';
        $html .= '</tr>';

        foreach ($group->groupTeams as $gT) {
            /**
             * @var GroupTeam $gT
             */
            $html .= '<tr>';
            $html .= '<td>' . ($gT->calcRanking ?? 0) . '</td>';
            $html .= '<td>' . $gT->team->name . '</td>';
            $html .= '<td>' . ($gT->calcCountMatches ?? 0) . '</td>';
            $html .= '<td>' . ($gT->calcGoalsScored ?? 0) . ':' . ($gT->calcGoalsReceived ?? 0) . '</td>';
            $html .= '<td>' . ($gT->calcGoalsDiff > 0 ? '+' : '') . ($gT->calcGoalsDiff ?? 0) . '</td>';
            $html .= '<td>' . ($gT->calcPointsPlus ?? 0) . ':' . ($gT->calcPointsMinus ?? 0) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $mpdf->WriteHTML($html);
    }

    $mpdf->Output();
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}


