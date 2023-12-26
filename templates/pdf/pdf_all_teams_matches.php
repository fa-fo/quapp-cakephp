<?php

use Cake\I18n\FrozenTime;

require_once __DIR__ . '/../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();

try {
    $mpdf->showImageErrors = true;

    $p = 0;
    $teamYears = $teamYears ?? array();

    foreach ($teamYears as $ty) {
        $p++;
        $html = '';
        $mpdf->AddPage('L');
        $mpdf->SetDefaultBodyCSS('background', "url('img/logo2024.png')");
        $mpdf->SetDefaultBodyCSS('background-position', "50px 152px");
        $mpdf->SetDefaultBodyCSS('background-repeat', "no-repeat");

        if ($p == 1) {
            $html .= '<style type="text/css">
            table{border-collapse: collapse}
            th {border: 0}
            td {border: solid 2px #000}
            </style>';
        }

        if (isset($ty['infos']['matches'][0])) {
            $html .= '<h2>Mannschaftsspielplan am  ' . FrozenTime::createFromFormat('Y-m-d H:i:s', $ty['infos']['matches'][0]->matchStartTime)->i18nFormat('d.MM.Y') . '</h2>';

            //$html .= '<img src="img/logo2024.png" style="float:left" width="150">';
            $html .= '<table border="0" cellspacing="0" cellpadding="6" align="center" width="70%">';
            $html .= '<tr>';
            $html .= '<th>Uhrzeit</th>';
            $html .= '<th>Mannschaft</th>';
            $html .= '<th>Sportart</th>';
            $html .= '<th>Spielfeld</th>';
            $html .= '<th>Team-PIN<br/>für SR</th>';
            $html .= '</tr>';

            foreach ($ty['infos']['matches'] as $match) {
                $html .= '<tr>';
                $html .= '<td>' . FrozenTime::createFromFormat('Y-m-d H:i:s', $match->matchStartTime)->i18nFormat('HH:mm') . ' Uhr</td>';
                $html .= '<td>' . $ty->team->name . '</td>';
                //$html .= '<td>' . $match->teams1->name . '</td>';
                //$html .= '<td>' . $match->teams2->name . '</td>';
                $html .= '<td>' . $match->sport->code . ($match->isRefereeJob ? 'SR' : '') . '</td>';
                $html .= '<td>' . $match->group_name . '</td>';
                if ($match->isRefereeJob) {
                    $html .= '<td>' . $ty->refereePIN . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</table>';
            $html .= '<img src="img/qr-codes.png" style="margin:20px 0 0 150px" width="650">';

            $mpdf->WriteHTML($html);
        }
    }

    $mpdf->Output();
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}
