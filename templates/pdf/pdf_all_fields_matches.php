<?php

use Cake\I18n\FrozenTime;

require_once __DIR__ . './../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();

$p = 0;
foreach ($sports as $sport) {
    foreach ($sport['fields'] as $fields) {
        $p++;
        $mpdf->AddPage('L');
        if ($p == 1) {
            $mpdf->WriteHTML('<style type="text/css">
            table{border-collapse: collapse}
            th {border: 0}
            td {border: solid 2px #000}
            </style>');
        }

        $mpdf->WriteHTML('<h1>Feldspielplan am  ' . FrozenTime::createFromFormat('Y-m-d H:i:s', $fields['matches'][0]->matchStartTime)->i18nFormat('d.MM.Y') . '</h1>');

        $mpdf->WriteHTML('<table border="0"  cellspacing="0" cellpadding="8" align="center" width="100%">');
        $mpdf->WriteHTML('<tr>');
        $mpdf->WriteHTML('<th>Uhrzeit</th>');
        $mpdf->WriteHTML('<th>Mannschaft 1</th>');
        $mpdf->WriteHTML('<th>Mannschaft 2</th>');
        $mpdf->WriteHTML('<th>Schiedsrichter</th>');
        $mpdf->WriteHTML('<th>Sportart</th>');
        $mpdf->WriteHTML('<th>Spielfeld</th>');
        $mpdf->WriteHTML('</tr>');

        foreach ($fields['matches'] as $match) {
            $mpdf->WriteHTML('<tr>');
            $mpdf->WriteHTML('<td>' . FrozenTime::createFromFormat('Y-m-d H:i:s', $match->matchStartTime)->i18nFormat('HH:mm') . ' Uhr</td>');
            $mpdf->WriteHTML('<td>' . $match->teams1->name . '</td>');
            $mpdf->WriteHTML('<td>' . $match->teams2->name . '</td>');
            $mpdf->WriteHTML('<td>' . $match->teams3->name . '</td>');
            $mpdf->WriteHTML('<td>' . $match->sport->code . '</td>');
            $mpdf->WriteHTML('<td>' . $match->group_name . '</td>');
            $mpdf->WriteHTML('</tr>');
        }

        $mpdf->WriteHTML('</table>');
    }
}

$mpdf->Output();


