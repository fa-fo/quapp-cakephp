<?php

require_once __DIR__ . './../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();

$p = 0;
foreach ($groups as $group) {
    $p++;
    $mpdf->AddPage('L');
    if ($p == 1) {
        $mpdf->WriteHTML('<style type="text/css">
            table{border-collapse: collapse}
            th {border: 0}
            td {border: solid 2px #000}
            span{font-size: 16px}
            </style>');
    }

    $mpdf->WriteHTML('<h1>Tabelle Gruppe ' . $group->name . ' <span>(' . $group->day->i18nFormat('EEEE, dd.MM.yyyy') . ')</span></h1>');

    $mpdf->WriteHTML('<table border="0"  cellspacing="0" cellpadding="8" align="center" width="100%">');
    $mpdf->WriteHTML('<tr>');
    $mpdf->WriteHTML('<th></th>');
    $mpdf->WriteHTML('<th></th>');
    $mpdf->WriteHTML('<th>Spiele</th>');
    $mpdf->WriteHTML('<th>Torverh.</th>');
    $mpdf->WriteHTML('<th>Tordiff.</th>');
    $mpdf->WriteHTML('<th>Punkte</th>');
    $mpdf->WriteHTML('</tr>');

    foreach ($group['groupTeams'] as $gT) {
        $mpdf->WriteHTML('<tr>');
        $mpdf->WriteHTML('<td>' . ($gT->calcRanking ?? 0) . '</td>');
        $mpdf->WriteHTML('<td>' . $gT->team->name . '</td>');
        $mpdf->WriteHTML('<td>' . ($gT->calcCountMatches ?? 0) . '</td>');
        $mpdf->WriteHTML('<td>' . ($gT->calcGoalsScored ?? 0) . ':' . ($gT->calcGoalsReceived ?? 0) . '</td>');
        $mpdf->WriteHTML('<td>' . ($gT->calcGoalsDiff > 0 ? '+' : '') . ($gT->calcGoalsDiff ?? 0) . '</td>');
        $mpdf->WriteHTML('<td>' . ($gT->calcPointsPlus ?? 0) . ':' . ($gT->calcPointsMinus ?? 0) . '</td>');
        $mpdf->WriteHTML('</tr>');
    }

    $mpdf->WriteHTML('</table>');

}

$mpdf->Output();


