<?php
ini_set('max_execution_time', '300');

use Cake\I18n\FrozenTime;

require_once __DIR__ . './../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();
$mpdf->showImageErrors = true;

function ellipsis($input, $maxLength = 25)
{
    return strlen($input) > $maxLength ? mb_convert_encoding(substr($input, 0, $maxLength), 'UTF-8', 'UTF-8') . "..." : $input;
}

$p = 0;
foreach ($teamYears as $ty) {
    if (!isset($ty['infos'])) {
        continue; // not the needed group
    }

    $p++;
    $mpdf->AddPage('L');
    if ($p == 1) {
        $mpdf->WriteHTML('<style type="text/css">
            table{border-collapse: collapse}
            th {border: 0}
            td {border: solid 2px #000}
            table.group {border-collapse: collapse}
            table.group th {border: 0; font-size: 13px; vertical-align: top; text-align:left}
            table.group td {border: 0; font-size: 13px; vertical-align: top}
            span.t {font-weight: bold}
            table.group td.r {padding-bottom: 9px}
            table.group td.m {border: solid 1px #aaa}
            table.group td.g {text-align:right; font-weight: bold}
            table.group td.sr {font-style: italic}
            span{font-size: 16px}
            </style>');
    }

    if (isset($ty['infos']['matches'][0])) {
        $mpdf->WriteHTML('<h2>Mannschaftsspielplan am  ' . $ty['day']->i18nFormat('EEEE, d.MM.Y') . '</h2>');

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


    $mpdf->AddPage();
    $mpdf->WriteHTML('<h2>Spielplan Gruppe ' . $ty['group']->name . ' <span>(' . $ty['day']->i18nFormat('EEEE, dd.MM.yyyy') . ')</span></h2>');

    foreach ($ty['group']['rounds'] as $round) {
        if (($round->id - 1) % 8 == 0) {
            $mpdf->WriteHTML('<table class="group" border="0"  cellspacing="0" cellpadding="2" align="left" width="100%">');
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
            $mpdf->WriteHTML('<td class="sr">' . ellipsis('SR: ' . $match->teams3->name, 26) . '</td>');
            $mpdf->WriteHTML('</tr>');
            $mpdf->WriteHTML('</table>');

            $mpdf->WriteHTML('</td>');
        }

        $mpdf->WriteHTML('</tr>');
        if ($round->id % 8 == 0) {
            $mpdf->WriteHTML('</table>');
            if ($round->id == 8) {
                $mpdf->WriteHTML('<br /><br />');
            }
        }
    }
}


$mpdf->Output();


