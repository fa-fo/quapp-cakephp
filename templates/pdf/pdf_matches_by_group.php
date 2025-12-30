<?php

use App\Model\Entity\Group;
use App\Model\Entity\Match4;
use App\Model\Entity\Round;
use Cake\I18n\DateTime;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/pdf_functions.php';

$mpdf = new \Mpdf\Mpdf();

try {
    $p = 0;
    $groups = $groups ?? array();
    $year = $year ?? array();

    foreach ($groups as $group) {
        /**
         * @var Group $group
         */
        $p++;
        $html = '';
        $mpdf->AddPage('L');
        if ($p == 1) {
            $html .= '<style>
            table{border-collapse: collapse}
            th {border: 0; font-size: 14px; vertical-align: top; text-align:left}
            td {border: 0; font-size: 14px; vertical-align: top}
            span.t {font-weight: bold}
            td.r {padding-bottom: 13px}
            td.m {border: solid 1px #aaa}
            td.g {text-align:right; font-weight: bold}
            td.sr {font-style: italic}
            span{font-size: 16px}
            </style>';
        }

        foreach ($group->rounds as $round) {
            /**
             * @var Round $round
             */
            if (is_array($round->matches)) {
                if (($round->id - 1) % 8 == 0) {
                    if ($round->id != 1) {
                        $mpdf->AddPage('L');
                    }
                    $html .= '<h2>Spielplan ' . ($year['teamsCount'] > 24 ? 'Gruppe ' . $group->name : '') . ' <span>(' . $group->date->i18nFormat('EEEE, dd.MM.yyyy') . ')</span></h2>';
                    $html .= '<table border="0"  cellspacing="0" cellpadding="2" align="left" width="100%">';
                    $html .= '<tr>';
                    $html .= '<th>&nbsp;</th>';

                    // todo: make sports dynamic:
                    $html .= '<th><img src="img/bb.png" width="15">Basketball</th>';
                    $html .= '<th><img src="img/fb.png" width="15">Fußball</th>';
                    $html .= '<th><img src="img/hb.png" width="15">Handball</th>';
                    $html .= '<th><img src="img/vb.png" width="15">Volleyball</th>';
                    $html .= '</tr>';
                }
                $html .= '<tr>';
                $html .= '<td width="75">'
                    . '<span class="t">' . ($round->matches[0]->matchStartTime ? DateTime::createFromFormat('Y-m-d H:i:s', $round->matches[0]->matchStartTime)->i18nFormat('HH:mm') : '') . 'h:</span>'
                    . '<br/>Runde ' . $round->id . '</td>';

                for ($sportId = 1; $sportId <= 4; $sportId++) {
                    $html .= '<td class="r" width="200">';
                    $match = getSportsMatch($round->matches, $sportId);

                    if ($match) {
                        /**
                         * @var Match4 $match
                         */
                        $html .= '<table border="0" cellspacing="0" cellpadding="0" width="100%">';
                        $html .= '<tr>';
                        $html .= '<td class="m">';

                        $html .= '<table border="0" cellspacing="0" cellpadding="2" width="100%">';
                        $html .= '<tr>';
                        $html .= '<td>' . ellipsis(!$match->canceled ? $match->teams1->name : '-') . '</td>';
                        $html .= '<td class="g" width="10">' . $match->resultGoals1 . '&nbsp;</td>';
                        $html .= '</tr>';
                        $html .= '<tr>';
                        $html .= '<td>' . ellipsis(!$match->canceled ? $match->teams2->name : '-') . '</td>';
                        $html .= '<td class="g">' . $match->resultGoals2 . '&nbsp;</td>';
                        $html .= '</tr>';
                        $html .= '</table>';

                        $html .= '</td>';
                        $html .= '</tr>';
                        $html .= '<tr>';
                        $html .= '<td class="sr">' . ellipsis('SR: ' . (!$match->canceled ? ($match->refereeName ?: ($match->teams3->name ?? '')) : '-'), 27) . '</td>';
                        $html .= '</tr>';
                        $html .= '</table>';
                    }
                    $html .= '</td>';
                }

                $html .= '</tr>';
                if ($round->id % 8 == 0) {
                    $html .= '</table>';
                    $mpdf->WriteHTML($html);
                    $html = '';
                }
            }
        }
        if ($html != '') {
            $html .= '</table>';
            $mpdf->WriteHTML($html);
        }
    }

    $mpdf->Output();
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}

