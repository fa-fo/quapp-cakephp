<?php

require_once __DIR__ . './../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();

$mpdf->AddPage('P');
$mpdf->WriteHTML('<style type="text/css">
        table{border-collapse: collapse}
        td {border: solid 1px #000; font-size: 18px; }
        </style>');

$c = 0;

foreach ($teamYears as $ty) {
    if ($c % 32 == 0) {
        if ($c > 0) {
            $mpdf->AddPage('P');
        }
        $mpdf->WriteHTML('<h1>Endstand QuattFo</span></h1>');
    }
    if ($c % 16 == 0) {
        if ($c != 0 && $c != 32) {
            $mpdf->WriteHTML('<p>&nbsp;</p>');
        }
        $mpdf->WriteHTML('<table border="0"  cellspacing="0" cellpadding="2" align="center" width="100%">');
    }
    $c++;
    $mpdf->WriteHTML('<tr>');
    $mpdf->WriteHTML('<td>' . ($ty->endRanking ?? 0) . '</td>');
    $mpdf->WriteHTML('<td>' . $ty->team_name . '</td>');
    $mpdf->WriteHTML('</tr>');
    if ($c % 16 == 0) {
        $mpdf->WriteHTML('</table>');
    }

}

$mpdf->WriteHTML('</table>');
$mpdf->Output();


