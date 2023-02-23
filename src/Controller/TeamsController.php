<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Teams Controller
 *
 * @property \App\Model\Table\TeamsTable $Teams
 */
class TeamsController extends AppController
{
    public function index()
    {
        $teams = false;

        $this->apiReturn($teams);
    }

    public function byId($id = false)
    {
        $settings = $this->getSettings();
        $conditionsArray = array('Teams.id' => $id);

        $team = $this->getTeams($conditionsArray, array(
            'TeamYears' => array('fields' => array('id', 'team_id', 'year_id', 'endRanking', 'canceled'), 'sort' => array('year_id' => 'DESC')),
            'TeamYears.Years' => array('fields' => array('year_name' => 'name'),
                'conditions' => array('TeamYears.canceled' => 0, 'year_id !=' => $settings['showEndRanking'] ? '' : $settings['currentYear_id'])
            ),
        ));

        $this->apiReturn($team);
    }

    private function getTeams($conditionsArray, $containArray = array())
    {
        $teams = $this->Teams->find('all', array(
            'fields' => array('id', 'team_id' => 'id', 'team_name' => 'name', 'calcTotalYears', 'calcTotalRankingPoints', 'calcTotalPointsPerYear', 'calcTotalChampionships', 'calcTotalRanking'),
            'contain' => $containArray,
            'conditions' => $conditionsArray,
            'order' => array('calcTotalRanking' => 'ASC')
        ));

        return $teams;
    }

    // Ewige Tabelle
    public function all()
    {
        $conditionsArray = array('calcTotalRankingPoints IS NOT' => null, 'hidden' => 0);

        $teams = $this->getTeams($conditionsArray);
        $settings = $this->getSettings();

        if ($settings['currentDay_id'] == 2 && $settings['alwaysAutoUpdateResults'] == 1 && $settings['showEndRanking'] == 0) {
            $teams = null;
            $teams['showRanking'] = 0;
        }

        $this->apiReturn($teams);
    }

    public function balance($id = false)
    {
        $return = array();
        $settings = $this->getSettings();

        $conditionsArray = array(
            'Years.id !=' => $settings['alwaysAutoUpdateResults'] && !$settings['isTest'] ? 0 : $settings['currentYear_id'],
            'canceled' => 0,
            'resultTrend <' => 3,
            'OR' => array(
                'team1_id' => $id,
                'team2_id' => $id,
            ),
        );

        $this->loadModel('Matches');
        $matches = $id ? $this->getMatches($conditionsArray) : false;

        if ($matches) {
            foreach ($matches as $m) {
                // reverse trend for away team
                $trend = $m->resultTrend ? abs($m->resultTrend - ($id == $m->team2_id ? 3 : 0)) : 0;

                $return['total'][$trend] ??= 0;
                $return['total'][$trend]++;
                $return[$m->sport->id] ??= array();
                $return[$m->sport->id][$trend] ??= 0;
                $return[$m->sport->id][$trend]++;
            }

            $this->loadModel('Sports');
            $return['sports'] = $this->Sports->find('all', array(
                'order' => array('name' => 'ASC')
            ))->toArray();
        }

        $this->apiReturn($return);
    }


    public function balanceMatches($id = false, $sport_id = false)
    {
        $settings = $this->getSettings();
        $conditionsArray = array(
            'Years.id !=' => $settings['alwaysAutoUpdateResults'] && !$settings['isTest'] ? 0 : $settings['currentYear_id'],
            'canceled' => 0,
            'resultTrend <' => 3,
            'sport_id' => $sport_id,
            'OR' => array(
                'team1_id' => $id,
                'team2_id' => $id,
            ),
        );

        $this->loadModel('Matches');
        $matches = $id && $sport_id ? $this->getMatches($conditionsArray) : false;

        $this->apiReturn($matches);
    }


    /*
    public function add()
    {
        $team = $this->Teams->newEmptyEntity();
        if ($this->request->is('post')) {
            $team = $this->Teams->patchEntity($team, $this->request->getData());
            if (!$this->Teams->save($team)) {
                $team = false;
            }
        } else {
            $team = false;
        }

        $this->apiReturn($team);
    }

    public function edit($id = false)
    {
        $team = $id ? $this->Teams->find()->where(['id' => $id])->first() : false;

        if ($team) {
            if ($this->request->is(['patch', 'post', 'put'])) {
                $team = $this->Teams->patchEntity($team, $this->request->getData());
                $this->Teams->save($team);
            } else {
                $team = false;
            }
        }

        $this->apiReturn($team);
    }
*/
}
