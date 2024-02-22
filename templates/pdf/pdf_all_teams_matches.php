<?php

use Cake\I18n\FrozenTime;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/pdf_functions.php';

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
            $html .= getMatchHtml($html, $ty);

            $mpdf->WriteHTML($html);
        }
    }

    $mpdf->Output();
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}
