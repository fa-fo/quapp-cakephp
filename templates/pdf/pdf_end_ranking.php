<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();

try {
    $html = '';
    $mpdf->AddPage('P');
    $html .= '<style>
        table{border-collapse: collapse}
        td {border: solid 1px #000; font-size: 18px; }
        </style>';

    $c = 0;
    $teamYears = $teamYears ?? array();
    $year = $year ?? array();

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
            $html .= '<table border="0"  cellspacing="0" cellpadding="2" align="center" width="100%">';
        }
        $c++;
        $html .= '<tr>';
        $html .= '<td>' . ($ty->endRanking ?? 0) . '</td>';
        $html .= '<td>' . ($ty->canceled ? '<s>' : '') . $ty->team_name . ($ty->canceled ? '</s>' : '') . '</td>';
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



