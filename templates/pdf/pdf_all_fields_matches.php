<?php

use Cake\I18n\DateTime;

require_once __DIR__ . '/../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();

try {
    $p = 0;
    $sports = $sports ?? array();
    $year = $year ?? array();
    $fontSize = 14;
    $padding = 4;

    foreach ($sports as $sport) {
        foreach ($sport['fields'] as $fields) {
            $p++;
            $html = '';
            $mpdf->AddPage('L');
            if ($p == 1) {
                $html .= '<style>
            h1 {margin: 0; padding: 0; font-size: 22px}
            table{border-collapse: collapse}
            th {border: 0; padding: 2px; font-size: ' . $fontSize . 'px}
            td {border: solid 1px #000; padding: ' . $padding . 'px; font-size: ' . $fontSize . 'px}
            </style>';
            }

            $html .= '<h1>'
                . $fields['matches'][0]->sport->code
                . ($year['teamsCount'] > 24 ? ' ' . $fields['matches'][0]->group_name . ' ' : '')
                . '-Feldspielplan am  '
                . ($fields['matches'][0]->matchStartTime ? DateTime::createFromFormat('Y-m-d H:i:s', $fields['matches'][0]->matchStartTime)->i18nFormat('d.MM.Y') : '') . '</h1>';

            $html .= '<table border="0"  cellspacing="0" cellpadding="8" align="center" width="100%">';
            $html .= '<tr>';
            $html .= '<th width="5%">Runde</th>';
            $html .= '<th width="10%">Uhrzeit</th>';
            $html .= '<th width="29%">Mannschaft 1</th>';
            $html .= '<th width="29%">Mannschaft 2</th>';
            $html .= '<th width="27%">Schiedsrichter</th>';
            $html .= '</tr>';

            foreach ($fields['matches'] as $match) {
                $html .= '<tr>';
                $html .= '<td>' . $match->round->id . '</td>';
                $html .= '<td>' . DateTime::createFromFormat('Y-m-d H:i:s', $match->matchStartTime)->i18nFormat('HH:mm') . ' Uhr</td>';
                $html .= '<td>' . $match->teams1->name . '</td>';
                $html .= '<td>' . $match->teams2->name . '</td>';
                $html .= '<td>' . ($match->refereeName ?: $match->teams3->name) . '</td>';
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
