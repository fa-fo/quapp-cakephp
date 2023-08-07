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

    foreach ($teamYears as $ty) {
        if ($c % 32 == 0) {
            if ($c > 0) {
                $html = '';
                $mpdf->AddPage('P');
            }
            $html .= '<h1>Endstand QuattFo</span></h1>';
        }
        if ($c % 16 == 0) {
            if ($c != 0 && $c != 32) {
                $html .= '<p>&nbsp;</p>';
            }
            $html .= '<table border="0"  cellspacing="0" cellpadding="2" align="center" width="100%">';
        }
        $c++;
        $html .= '<tr>';
        $html .= '<td>' . ($ty->endRanking ?? 0) . '</td>';
        $html .= '<td>' . ($ty->canceled ? '<s>' : '') . $ty->team_name . ($ty->canceled ? '</s>' : '') . '</td>';
        $html .= '</tr>';
        if ($c % 16 == 0) {
            $html .= '</table>';
        }
        if ($c % 32 == 0) {
            $mpdf->WriteHTML($html);
        }
    }

    $mpdf->Output();
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}



