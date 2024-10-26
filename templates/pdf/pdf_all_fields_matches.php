<?php

use Cake\I18n\DateTime;

require_once __DIR__ . '/../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();

try {
    $p = 0;
    $sports = $sports ?? array();

    foreach ($sports as $sport) {
        foreach ($sport['fields'] as $fields) {
            $p++;
            $html = '';
            $mpdf->AddPage('L');
            if ($p == 1) {
                $html .= '<style>
            table{border-collapse: collapse}
            th {border: 0}
            td {border: solid 2px #000}
            </style>';
            }

            $html .= '<h1>Feldspielplan am  ' . DateTime::createFromFormat('Y-m-d H:i:s', $fields['matches'][0]->matchStartTime)->i18nFormat('d.MM.Y') . '</h1>';

            $html .= '<table border="0"  cellspacing="0" cellpadding="8" align="center" width="100%">';
            $html .= '<tr>';
            $html .= '<th>Uhrzeit</th>';
            $html .= '<th>Mannschaft 1</th>';
            $html .= '<th>Mannschaft 2</th>';
            $html .= '<th>Schiedsrichter</th>';
            $html .= '<th>Sportart</th>';
            $html .= '<th>Spielfeld</th>';
            $html .= '</tr>';

            foreach ($fields['matches'] as $match) {
                $html .= '<tr>';
                $html .= '<td>' . DateTime::createFromFormat('Y-m-d H:i:s', $match->matchStartTime)->i18nFormat('HH:mm') . ' Uhr</td>';
                $html .= '<td>' . $match->teams1->name . '</td>';
                $html .= '<td>' . $match->teams2->name . '</td>';
                $html .= '<td>' . $match->teams3->name . '</td>';
                $html .= '<td>' . $match->sport->code . '</td>';
                $html .= '<td>' . $match->group_name . '</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';
            $mpdf->WriteHTML($html);
        }
    }

    $mpdf->Output();
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}
