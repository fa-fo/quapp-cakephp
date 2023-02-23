<?php

use Cake\I18n\FrozenTime;

require_once __DIR__ . './../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();

function ellipsis($input, $maxLength = 25)
{
    return strlen($input) > $maxLength ? mb_convert_encoding(substr($input, 0, $maxLength), 'UTF-8', 'UTF-8') . "..." : $input;
}

$p = 0;
foreach ($groups as $group) {
    $p++;
    $mpdf->AddPage('L');
    if ($p == 1) {
        $mpdf->WriteHTML('<style type="text/css">
            table{border-collapse: collapse}
            th {border: 0; font-size: 14px; vertical-align: top; text-align:left}
            td {border: 0; font-size: 14px; vertical-align: top}
            span.t {font-weight: bold}
            td.r {padding-bottom: 13px}
            td.m {border: solid 1px #aaa}
            td.g {text-align:right; font-weight: bold}
            td.sr {font-style: italic}
            span{font-size: 16px}
            </style>');
    }

    foreach ($group['rounds'] as $round) {
        if (($round->id - 1) % 8 == 0) {
            if ($round->id != 1) {
                $mpdf->AddPage('L');
            }
            $mpdf->WriteHTML('<h2>Spielplan Gruppe ' . $group->name . ' <span>(' . $group->day->i18nFormat('EEEE, dd.MM.yyyy') . ')</span></h2>');
            $mpdf->WriteHTML('<table border="0"  cellspacing="0" cellpadding="2" align="left" width="100%">');
            $mpdf->WriteHTML('<tr>');
            $mpdf->WriteHTML('<th>&nbsp;</th>');
            $mpdf->WriteHTML('<th><img src="img/bb.png" width="15">Basketball</th>');
            $mpdf->WriteHTML('<th><img src="img/fb.png" width="15">Fußball</th>');
            $mpdf->WriteHTML('<th><img src="img/hb.png" width="15">Handball</th>');
            $mpdf->WriteHTML('<th><img src="img/vb.png" width="15">Volleyball</th>');
            $mpdf->WriteHTML('</tr>');
        }
        $mpdf->WriteHTML('<tr>');
        $mpdf->WriteHTML('<td width="75">'
            . '<span class="t">' . FrozenTime::createFromFormat('Y-m-d H:i:s', $round['matches'][0]->matchStartTime)->i18nFormat('HH:mm') . 'h:</span>'
            . '<br/>Runde ' . $round->id . '</td>');

        foreach ($round['matches'] as $match) {
            $mpdf->WriteHTML('<td class="r" width="200">');

            $mpdf->WriteHTML('<table border="0" cellspacing="0" cellpadding="0" width="100%">');
            $mpdf->WriteHTML('<tr>');
            $mpdf->WriteHTML('<td class="m">');

            $mpdf->WriteHTML('<table border="0" cellspacing="0" cellpadding="2" width="100%">');
            $mpdf->WriteHTML('<tr>');
            $mpdf->WriteHTML('<td>' . ellipsis($match->teams1->name) . '</td>');
            $mpdf->WriteHTML('<td class="g" width="10">' . $match->resultGoals1 . '&nbsp;</td>');
            $mpdf->WriteHTML('</tr>');
            $mpdf->WriteHTML('<tr>');
            $mpdf->WriteHTML('<td>' . ellipsis($match->teams2->name) . '</td>');
            $mpdf->WriteHTML('<td class="g">' . $match->resultGoals2 . '&nbsp;</td>');
            $mpdf->WriteHTML('</tr>');
            $mpdf->WriteHTML('</table>');

            $mpdf->WriteHTML('</td>');
            $mpdf->WriteHTML('</tr>');
            $mpdf->WriteHTML('<tr>');
            $mpdf->WriteHTML('<td class="sr">' . ellipsis('SR: ' . $match->teams3->name, 28) . '</td>');
            $mpdf->WriteHTML('</tr>');
            $mpdf->WriteHTML('</table>');

            $mpdf->WriteHTML('</td>');
        }

        $mpdf->WriteHTML('</tr>');
        if ($round->id % 8 == 0) {
            $mpdf->WriteHTML('</table>');
        }
    }
}

$mpdf->Output();


