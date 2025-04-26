<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();
$alphabet = range('A', 'Z');

try {
    $html = '';
    $mpdf->AddPage('P');

    $c = 0;
    $teamYears = $teamYears ?? array();
    $year = $year ?? array();
    $ptrRankingSingle = $ptrRankingSingle ?? array();
    $ptrRankingTeams = $ptrRankingTeams ?? array();

    $fontSize = count($ptrRankingSingle) > 0 ? 14 : 16;
    $padding = count($ptrRankingSingle) > 0 ? 3 : 4;

    $html .= '<style>
        h1 {margin: 0; padding: 0; font-size: 18px}
        table {border-collapse: collapse}
        td {border: solid 1px #000; padding: ' . $padding . 'px; font-size: ' . $fontSize . 'px}
        td.ptr {border: solid 1px #000; padding: 1px; font-size: 10px}
        td.b0 {border: 0; padding: 0}
        p {margin: 0; padding: 0; font-size: 10px}
        </style>';

    if ($year['teamsCount'] > 24) {
        $newPageArray = array(0, $year['teamsCount'] / 2);
        $endPageArray = array($year['teamsCount'] / 2, $year['teamsCount']);
        $breakArray = array($year['teamsCount'] / 4, $year['teamsCount'] / 4 * 3);
        $newTableArray = array(0, $year['teamsCount'] / 4, $year['teamsCount'] / 4 * 2, $year['teamsCount'] / 4 * 3);
        $endTableArray = array($year['teamsCount'] / 4, $year['teamsCount'] / 4 * 2, $year['teamsCount'] / 4 * 3, $year['teamsCount']);
    } else {
        $newPageArray = array(0);
        $endPageArray = array($year['teamsCount']);
        $breakArray = array();
        $newTableArray = array(0);
        $endTableArray = array($year['teamsCount']);
    }

    if (count($ptrRankingSingle) > 0 || count($ptrRankingTeams) > 0) {
        $html .= '<p>Die besten, fleißigsten Protokollierenden:</p>';
        $html .= '<table border="0"  cellspacing="0" cellpadding="0" align="center" width="90%">';
        $html .= '<tr><td class="b0">';
        $html .= '<p>Einzelwertung:</p>';
        $html .= '<table border="0"  cellspacing="0" cellpadding="1" align="left" width="90%">';
        foreach ($ptrRankingSingle as $ptr) {
            $html .= '<tr>';
            $html .= '<td width="20" align="right" class="ptr"><b>' . ($ptr->ptrRanking ?? 0) . '</b></td>';
            $html .= '<td class="ptr">' . $ptr->team_name . '</td>';
            $html .= '<td class="ptr" align="right">' . $ptr->ptrPoints . ' P.</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '</td><td class="b0">';

        $html .= '<p>Teamwertung:</p>';
        $html .= '<table border="0"  cellspacing="0" cellpadding="1" align="left" width="90%">';
        foreach ($ptrRankingTeams as $ptr) {
            $html .= '<tr>';
            $html .= '<td width="20" align="right" class="ptr"><b>' . ($ptr->ptrRanking ?? 0) . '</b></td>';
            $html .= '<td class="ptr">' . $ptr->team_name . '</td>';
            $html .= '<td class="ptr" align="right">' . $ptr->ptrPoints . ' P.</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '</td></tr>';
        $html .= '</table>';
        $html .= '<p>&nbsp;</p>';
    }

    foreach ($teamYears as $ty) {
        if (in_array($c, $newPageArray)) {
            if ($c > 0) {
                $html = '';
                $mpdf->AddPage('P');
            }
            $html .= '<h1>Endstand QuattFo ' . $year['name'] . '</span></h1>';
        }
        if (in_array($c, $breakArray)) {
            $html .= '<p>&nbsp;</p>';
        }
        if (in_array($c, $newTableArray)) {
            if ($year['teamsCount'] > 24) {
                $html .= '<p>Gruppe ' . $alphabet[(int)(($year['teamsCount'] - $c - 1) / 16)] . ':</p>';
            }
            $html .= '<table border="0"  cellspacing="0" cellpadding="2" align="center" width="100%">';
        }
        $c++;
        $html .= '<tr>';
        $html .= '<td width="50" align="right"><b>' . ($ty->endRanking ?? 0) . '</b></td>';
        $html .= '<td>' . ($ty->canceled ? '<s>' : '') . $ty->team_name . ($ty->canceled ? '</s>' : '') . '</td>';
        $html .= '<td width="50" align="right"><i>' . ($ty->ctRanking < 21 ? $ty->ctRanking . '.' : '') . '</i></td>';
        $html .= '</tr>';
        if (in_array($c, $endTableArray)) {
            $html .= '</table>';
        }
        if (in_array($c, $endPageArray)) {
            $mpdf->WriteHTML($html);
        }
    }

    $mpdf->Output();
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}



