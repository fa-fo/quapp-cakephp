<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Team;

/**
 * Teams Controller
 *
 * @property \App\Model\Table\TeamsTable $Teams
 * @property \App\Controller\Component\MatchGetComponent $MatchGet
 * @property \App\Controller\Component\SecurityComponent $Security
 */
class TeamsController extends AppController
{
    public function index(): void
    {
        $teams = false;

        $this->apiReturn($teams);
    }

    public function add(): void
    {
        $return = array();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $team_id = 0;
            $name = trim($postData['name']);
            $team = $this->Teams->find('all', array(
                'conditions' => array('name' => $name),
            ))->first();
            /**
             * @var Team|null $team
             */
            if ($team) {
                $team_id = $team->id;
            } else {
                $newTeam = $this->Teams->newEmptyEntity();
                $newTeam->set('name', $name);
                if ($this->Teams->save($newTeam)) {
                    $team_id = $newTeam->id;
                }
            }

            $return = array('name' => $name, 'team_id' => $team_id);
        }

        $this->apiReturn($return);
    }

    public function byId(string $id = ''): void
    {
        $settings = $this->Cache->getSettings();
        $conditionsArray = array('Teams.id' => (int)$id);

        $team = $this->Cache->getTeams($conditionsArray, array(
            'TeamYears' => array('fields' => array('id', 'team_id', 'year_id', 'endRanking', 'canceled'), 'sort' => array('year_id' => 'DESC')),
            'TeamYears.Years' => array('fields' => array('year_name' => 'name'),
                'conditions' => array('year_id !=' => $settings['showEndRanking'] ? '' : $settings['currentYear_id'])
            ),
            'PrevTeams' => array('fields' => array('id', 'name', 'calcTotalYears', 'calcTotalRankingPoints', 'calcTotalChampionships', 'prevTeam_id')),
            'PrevTeams.TeamYears' => array('fields' => array('id', 'team_id', 'year_id', 'endRanking', 'canceled'), 'sort' => array('year_id' => 'DESC')),
            'PrevTeams.TeamYears.Years' => array('fields' => array('year_name' => 'name'),
                'conditions' => array()
            ),
            'PrevTeams.PrevTeams' => array('fields' => array('id', 'name', 'calcTotalYears', 'calcTotalRankingPoints', 'calcTotalChampionships')),
            'PrevTeams.PrevTeams.TeamYears' => array('fields' => array('id', 'team_id', 'year_id', 'endRanking', 'canceled'), 'sort' => array('year_id' => 'DESC')),
            'PrevTeams.PrevTeams.TeamYears.Years' => array('fields' => array('year_name' => 'name'),
                'conditions' => array()
            ),
        ));

        $this->apiReturn($team);
    }

    // Ewige Tabelle
    public function all(): void
    {
        $settings = $this->Cache->getSettings();
        $year = $this->Cache->getCurrentYear();

        if ($settings['currentDay_id'] == $year->daysCount && $settings['alwaysAutoUpdateResults'] == 1 && $settings['showEndRanking'] == 0) {
            $teams = null;
            $teams['showRanking'] = 0;
        } else {
            $conditionsArray = array('Teams.calcTotalRanking IS NOT' => null, 'Teams.hidden' => 0, 'Teams.testTeam' => 0);

            $teams = $this->Cache->getTeams($conditionsArray, array(), 'app_calc_total');
        }

        $this->apiReturn($teams);
    }

    public function balance(string $id = ''): void
    {
        $id = (int)$id;
        $return = array();
        $settings = $this->Cache->getSettings();
        $team = $this->Teams->find()->where(['id' => $id])->first();

        $conditionsArray = array(
            'Years.id !=' => $settings['showEndRanking'] && !$settings['isTest'] ? 0 : $settings['currentYear_id'],
            'canceled' => 0,
            'resultTrend <' => 3,
            'OR' => array(
                'team1_id IN' => array($id, $team->prevTeam_id),
                'team2_id IN' => array($id, $team->prevTeam_id),
            ),
        );

        $matches = $id ? $this->MatchGet->getMatches($conditionsArray) : false;

        if (is_array($matches)) {
            foreach ($matches as $m) {
                // reverse trend for away team
                $trend = $m->resultTrend ? (in_array($m->team2_id, array($id, $team->prevTeam_id)) ? 3 - $m->resultTrend : $m->resultTrend) : 0;

                $return['total'][$trend] ??= 0;
                $return['total'][$trend]++;
                $return[$m->sport->id][$trend] ??= 0;
                $return[$m->sport->id][$trend]++;
            }

            $return['sports'] = $this->fetchTable('Sports')->find('all', array(
                'order' => array('id' => 'ASC')
            ))->toArray();
        }

        $this->apiReturn($return);
    }


    public function balanceMatches(string $id = '', string $sport_id = ''): void
    {
        $id = (int)$id;
        $sport_id = (int)$sport_id;
        $settings = $this->Cache->getSettings();
        $team = $this->Teams->find()->where(['id' => $id])->first();

        $conditionsArray = array(
            'Years.id !=' => $settings['alwaysAutoUpdateResults'] && !$settings['isTest'] ? 0 : $settings['currentYear_id'],
            'canceled' => 0,
            'resultTrend <' => 3,
            'sport_id' => $sport_id,
            'OR' => array(
                'team1_id IN' => array($id, $team->prevTeam_id),
                'team2_id IN' => array($id, $team->prevTeam_id),
            ),
        );

        $matches = $id && $sport_id ? $this->MatchGet->getMatches($conditionsArray) : false;

        $this->apiReturn($matches);
    }


    // Admin: get before create teamYears
    public function getTestTeamNames(): void
    {
        $return['teamNames'] = '';
        $settings = $this->Cache->getSettings();
        $year = $this->Cache->getCurrentYear()->toArray();

        $teams = $this->Teams->find('all', array(
            'order' => array('testTeam' => 'DESC', 'calcTotalRanking' => 'ASC', 'id' => 'ASC'),
        ))->limit($year['teamsCount']);

        if ($teams->count() == 0) {
            $teams = array();
            for ($c = 0; $c < $year['teamsCount']; $c++) {
                $nTeam = $this->Teams->newEmptyEntity();
                $nTeam->set('name', 'Test-Team' . str_pad((string)($c + 1), 2, '0', STR_PAD_LEFT));
                $nTeam->set('testTeam', 1);

                if ($this->Teams->save($nTeam)) {
                    $teams[] = $nTeam;
                }
            }
        }

        foreach ($teams as $team) {
            /**
             * @var Team|null $team
             */
            $return['teamNames'] .= $return['teamNames'] != '' ? '\n' : '';
            $return['teamNames'] .= $team->name . ($settings['useRefereePref'] ? $this->randomSuffix() : '');
        }

        $this->apiReturn($return);
    }

    private function randomSuffix(): string
    {
        $vals = [1, 1, 1, 1];
        $pool = [0, 0, 1, 2, 2, 2, 2, 2, 2, 3];
        $zeroIndex = $pool[array_rand($pool)];
        if (rand(0, 9) < 9) $vals[$zeroIndex] = 0;
        return ';' . implode(';', $vals);
    }

    // Admin: check before create teamYears
    public function checkTeamNames(): void
    {
        $return = array();
        $settings = $this->Cache->getSettings();
        $postData = $this->request->getData();
        $lines = preg_split('/\r\n|\r|\n/', $postData['teamNames']);

        if (is_array($lines)) {
            foreach ($lines as $line) {
                $array = explode(';', $line);
                $name = trim($array[0]);
                $name = str_replace(['`', '´'], '\'', $name);
                $name = str_replace(['–', '—', '−'], '-', $name);
                $team = $this->Teams->find('all', array(
                    'conditions' => array('name' => $name),
                ))->first();
                /**
                 * @var Team|null $team
                 */
                $team_id = $team ? $team->id : 0;

                $refereePref = null;
                if ($settings['useRefereePref'] && count($array) > 1) {
                    $refereePref = implode('', array_map(
                        fn($i) => in_array(strtolower($array[$i] ?? '1'), ['nein', '0']) ? 0 : $i,
                        range(1, count($array) - 1)
                    ));
                }

                $return[] = array('name' => $name, 'team_id' => $team_id, 'refereePref' => $refereePref);
            }
        }

        $this->apiReturn($return);
    }

}
