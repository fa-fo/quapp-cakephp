<?php

use Cake\I18n\FrozenTime;

require_once __DIR__ . './../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();
$mpdf->showImageErrors = true;

$p = 0;
foreach ($teamYears as $ty) {
    $p++;
    $mpdf->AddPage('L');
    if ($p == 1) {
        $mpdf->WriteHTML('<style type="text/css">
            table{border-collapse: collapse}
            th {border: 0}
            td {border: solid 2px #000}
            </style>');
    }

    if(isset($ty['infos']['matches'][0])) {
        $mpdf->WriteHTML('<h2>Mannschaftsspielplan am  ' . FrozenTime::createFromFormat('Y-m-d H:i:s', $ty['infos']['matches'][0]->matchStartTime)->i18nFormat('d.MM.Y') . '</h2>');

        $mpdf->WriteHTML('<img src="img/logo2023.png" style="float:left" width="150">');
        $mpdf->WriteHTML('<table border="0"  cellspacing="0" cellpadding="6" align="center" width="70%">');
        $mpdf->WriteHTML('<tr>');
        $mpdf->WriteHTML('<th>Uhrzeit</th>');
        $mpdf->WriteHTML('<th>Mannschaft</th>');
        $mpdf->WriteHTML('<th>Sportart</th>');
        $mpdf->WriteHTML('<th>Spielfeld</th>');
        $mpdf->WriteHTML('<th>Team-PIN<br/>für SR</th>');
        $mpdf->WriteHTML('</tr>');

        foreach ($ty['infos']['matches'] as $match) {
            $mpdf->WriteHTML('<tr>');
            $mpdf->WriteHTML('<td>' . FrozenTime::createFromFormat('Y-m-d H:i:s', $match->matchStartTime)->i18nFormat('HH:mm') . ' Uhr</td>');
            $mpdf->WriteHTML('<td>' . $ty->team->name . '</td>');
            //$mpdf->WriteHTML('<td>' . $match->teams1->name . '</td>');
            //$mpdf->WriteHTML('<td>' . $match->teams2->name . '</td>');
            $mpdf->WriteHTML('<td>' . $match->sport->code . ($match->isRefereeJob ? 'SR' : '') . '</td>');
            $mpdf->WriteHTML('<td>' . $match->group_name . '</td>');
            if ($match->isRefereeJob) {
                $mpdf->WriteHTML('<td>' . $ty->refereePIN . '</td>');
            }
            $mpdf->WriteHTML('</tr>');
        }

        $mpdf->WriteHTML('</table>');
        $mpdf->WriteHTML('<img src="img/qr-codes.png" style="margin:20px 0 0 150px" width="650">');

    }
}


$mpdf->Output();


