<?php
ini_set('max_execution_time', '300');

use Cake\I18n\DateTime;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/pdf_functions.php';

$mpdf = new \Mpdf\Mpdf();

try {
    $mpdf->showImageErrors = true;
    $p = 0;
    $teamYears = $teamYears ?? array();
    $settings = $settings ?? array();

    foreach ($teamYears as $ty) {
        if (!isset($ty['infos'])) {
            continue; // not the needed group
        }

        $p++;
        $html = '';
        $mpdf->AddPage('L');
        $mpdf->SetDefaultBodyCSS('background', "url('img/logo2025.png')");
        $mpdf->SetDefaultBodyCSS('background-position', "50px 152px");
        $mpdf->SetDefaultBodyCSS('background-repeat', "no-repeat");

        if ($p == 1) {
            $html .= '<style type="text/css">
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
            </style>';
        }

        if (isset($ty['infos']['matches'][0])) {
            $html .= '<h2>Mannschaftsspielplan am  ' . $ty['day']->i18nFormat('EEEE, d.MM.Y') . '</h2>';
            $html .= getMatchHtml($html, $ty, $settings);
        }
        $mpdf->WriteHTML($html);

        $mpdf->AddPage();
        $html = '<h2>Spielplan Gruppe ' . $ty['group']->name . ' <span>(' . $ty['day']->i18nFormat('EEEE, dd.MM.yyyy') . ')</span></h2>';
        $mpdf->WriteHTML($html);

        foreach ($ty['group']['rounds'] as $round) {
            if (($round->id - 1) % 8 == 0) {
                $html = '<table class="group" border="0"  cellspacing="0" cellpadding="2" align="left" width="100%">';
                $html .= '<tr>';
                $html .= '<th>&nbsp;</th>';
                $html .= '<th><img src="img/bb.png" width="15">Basketball</th>';
                $html .= '<th><img src="img/fb.png" width="15">Fußball</th>';
                $html .= '<th><img src="img/hb.png" width="15">Handball</th>';
                $html .= '<th><img src="img/vb.png" width="15">Volleyball</th>';
                $html .= '</tr>';
            }
            $html .= '<tr>';
            $html .= '<td width="75">'
                . '<span class="t">' . ($round['matches'][0]->matchStartTime ? DateTime::createFromFormat('Y-m-d H:i:s', $round['matches'][0]->matchStartTime)->i18nFormat('HH:mm') : '') . 'h:</span>'
                . '<br/>Runde ' . $round->id . '</td>';

            foreach ($round['matches'] as $match) {
                $html .= '<td class="r" width="200">';

                $html .= '<table border="0" cellspacing="0" cellpadding="0" width="100%">';
                $html .= '<tr>';
                $html .= '<td class="m">';

                $html .= '<table border="0" cellspacing="0" cellpadding="2" width="100%">';
                $html .= '<tr>';
                $html .= '<td>' . ellipsis($match->teams1->name) . '</td>';
                $html .= '<td class="g" width="10">' . $match->resultGoals1 . '&nbsp;</td>';
                $html .= '</tr>';
                $html .= '<tr>';
                $html .= '<td>' . ellipsis($match->teams2->name) . '</td>';
                $html .= '<td class="g">' . $match->resultGoals2 . '&nbsp;</td>';
                $html .= '</tr>';
                $html .= '</table>';

                $html .= '</td>';
                $html .= '</tr>';
                $html .= '<tr>';
                $html .= '<td class="sr">' . ellipsis('SR: ' . $match->teams3->name, 26) . '</td>';
                $html .= '</tr>';
                $html .= '</table>';

                $html .= '</td>';
            }

            $html .= '</tr>';
            if ($round->id % 8 == 0) {
                $html .= '</table>';
                if ($round->id == 8) {
                    $html .= '<br /><br />';
                }
                $mpdf->WriteHTML($html);
            }
        }
    }

    $mpdf->Output();
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}
