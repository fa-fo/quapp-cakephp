<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\GroupTeam;
use App\Model\Entity\Match4;
use App\Model\Entity\Setting;
use App\Model\Entity\TeamYear;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;

/**
 * TeamYears Controller
 *
 * @property \App\Model\Table\TeamYearsTable $TeamYears
 */
class TeamYearsController extends AppController
{

    // getCurrentTeams
    public function all(): void
    {
        $year = $this->getCurrentYear();

        $teamYears = $this->TeamYears->find('all', array(
            'fields' => array('id', 'team_id', 'canceled'),
            'conditions' => array('year_id' => $year->id, 'OR' => array('Teams.hidden' => 0, 'canceled' => 0)),
            'contain' => array('Teams' => array('fields' => array('name'))),
            'order' => array('Teams.name' => 'ASC')
        ))->toArray();

        $this->apiReturn($teamYears);
    }

    public function allWithPushTokenCount(): void
    {
        $year = $this->getCurrentYear();

        $teamYears = $this->TeamYears->find('all', array(
            'fields' => array('id', 'team_id', 'canceled'),
            'conditions' => array('year_id' => $year->id, 'OR' => array('Teams.hidden' => 0, 'canceled' => 0)),
            'contain' => array('Teams' => array('fields' => array('name'))),
            'order' => array('Teams.name' => 'ASC')
        ))->toArray();

        foreach ($teamYears as $ty) {
            $ty['countPushTokens'] = $this->fetchTable('PushTokens')->find()->where(['my_team_id' => $ty['team_id']])->count();
        }

        $this->apiReturn($teamYears);
    }

    public function pdfAllTeamsMatches(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {

            $year = $this->getCurrentYear();

            $teamYears = $this->TeamYears->find('all', array(
                'fields' => array('id', 'team_id', 'year_id', 'refereePIN', 'canceled'),
                'conditions' => array('year_id' => $year->id),
                'contain' => array('Teams' => array('fields' => array('name'))),
                'order' => array('Teams.name' => 'ASC')
            ))->toArray();

            foreach ($teamYears as $ty) {
                $ty['infos'] = $this->getMatchesByTeam($ty['team_id'], $year->id, $this->getCurrentDayId(), 1);
            }

            $this->viewBuilder()->setTemplatePath('pdf');
            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('teamYears', $teamYears);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }

    public function pdfAllTeamsMatchesWithGroupMatches(string $offset = ''): void
    {
        $offset = (int)$offset;
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year = $this->getCurrentYear()->toArray();

            $groups = $this->fetchTable('Groups')->find('all', array(
                'fields' => array('id', 'name', 'year_id', 'day_id'),
                'conditions' => array('year_id' => $this->getCurrentYearId(), 'day_id' => $this->getCurrentDayId()),
                'order' => array('name' => 'ASC')
            ))->toArray();

            foreach ($groups as $group) {
                $group['rounds'] = $this->getMatchesByGroup($group);
            }

            $teamYears = $this->TeamYears->find('all', array(
                'fields' => array('id', 'team_id', 'year_id', 'refereePIN', 'canceled'),
                'conditions' => array('year_id' => $year['id']),
                'contain' => array('Teams' => array('fields' => array('name'))),
                'order' => array('Teams.name' => 'ASC')
            ))->offset($offset * 32)->limit(32); // set limit because of execution timeout

            foreach ($teamYears as $ty) {
                $g = $this->getGroupByTeamId($ty->team_id, $year['id'], $this->getCurrentDayId());
                $gN = $this->getGroupPosNumber($g->id);

                $ty['infos'] = $this->getMatchesByTeam($ty->team_id, $year['id'], $this->getCurrentDayId(), 1);
                $ty['day'] = DateTime::createFromFormat('Y-m-d H:i:s', $year['day' . $this->getCurrentDayId()]->i18nFormat('yyyy-MM-dd HH:mm:ss'));
                $ty['group'] = $groups[$gN];
            }

            $this->viewBuilder()->setTemplatePath('pdf');
            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('teamYears', $teamYears);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }

    public function cancel(string $id = '', string $undo = ''): void
    {
        $id = (int)$id;
        $undo = (int)$undo;
        $teamYear = false;
        $postData = $this->request->getData();

        if ($id && isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year_id = $this->getCurrentYearId();
            $teamYear = $this->TeamYears->find('all', array(
                'conditions' => array('id' => $id, 'year_id' => $year_id)
            ))->first();
            if ($teamYear) {
                /**
                 * @var TeamYear $teamYear
                 */
                $teamYear->set('canceled', $undo ? 0 : 1);
                $this->TeamYears->save($teamYear);

                $day_id = $this->getCurrentDayId();

                $groupTeam = $this->fetchTable('GroupTeams')->find('all', array(
                    'contain' => array('Groups' => array('fields' => array('year_id', 'day_id'))),
                    'conditions' => array('team_id' => $teamYear->get('team_id'), 'Groups.year_id' => $year_id, 'Groups.day_id' => $day_id),
                ))->first();

                if ($groupTeam) {
                    /**
                     * @var GroupTeam $groupTeam
                     */
                    $groupTeam->set('canceled', $undo ? 0 : 1);
                    $this->fetchTable('GroupTeams')->save($groupTeam);

                    $this->getCalcRanking($teamYear->get('team_id'));
                }

                // cancel matches:
                $conditionsArray = array(
                    'resultTrend IS' => null,
                    'Groups.year_id' => $teamYear->get('year_id'),
                    'Groups.day_id' => $day_id,
                    'OR' => array(
                        'team1_id' => $teamYear->get('team_id'),
                        'team2_id' => $teamYear->get('team_id'),
                    )
                );

                $matches = $this->getMatches($conditionsArray);
                if (is_array($matches)) {
                    foreach ($matches as $m) {
                        /**
                         * @var Match4 $m
                         */
                        $a = $m->team1_id == $teamYear->get('team_id') ? 1 : 2;
                        $m->set('canceled', $undo ? $m->canceled - $a : $a + $m->canceled);
                        $this->fetchTable('Matches')->save($m);
                    }
                }
            }
        }

        $this->apiReturn($teamYear);
    }

    // deprecated! use instead:  matches/refereeCanceledMatches
    public function refereeCanceledTeamsMatches(): void
    {
        $return['matches'] = array();

        $teamYears = $this->TeamYears->find('all', array(
            'conditions' => array('canceled' => 1, 'year_id' => $this->getCurrentYearId())
        ))->toArray();

        if ($teamYears) {
            foreach ($teamYears as $ty) {
                $day_id = $this->getCurrentDayId();

                $conditionsArray = array(
                    'resultTrend IS' => null,
                    'canceled' => 0,
                    'Groups.year_id' => $ty->get('year_id'),
                    'Groups.day_id' => $day_id,
                    'OR' => array(
                        'refereeTeam_id' => $ty->get('team_id'),
                        'refereeTeam_id IS' => null,
                    )
                );

                $matches = $this->getMatches($conditionsArray, 0, 1, 1);

                if (is_array($matches)) {
                    $return['matches'] = array_merge($return['matches'], $matches);
                }
            }

            usort($return['matches'], function ($a, $b) {
                return $a['matchStartTime'] <=> $b['matchStartTime'];
            });
        }

        $this->apiReturn($return);
    }

    public function getEndRanking(string $year_id = '', string $adminView = ''): void
    {
        $year_id = (int)$year_id ?: $this->getCurrentYearId();
        $adminView = (int)$adminView;

        $teamYears = $this->TeamYears->find('all', array(
            'fields' => array('id', 'endRanking', 'team_id'),
            'conditions' => array('year_id' => $year_id, 'canceled' => 0),
            'contain' => array('Teams' => array('fields' => array('team_name' => 'name'))),
            'order' => array('endRanking' => 'ASC', 'team_name' => 'ASC')
        ))->toArray();

        if (!$adminView && $year_id == $this->getCurrentYearId()) {
            $showEndRanking = $this->fetchTable('Settings')->find('all')->where(['name' => 'showEndRanking'])->first();
            /**
             * @var Setting $showEndRanking
             */
            if ($showEndRanking->value == 0) {
                $teamYears = null;
                $teamYears['showRanking'] = 0;
            }
        }

        $this->apiReturn($teamYears, $year_id);
    }

    public function pdfEndRanking(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $teamYears = $this->TeamYears->find('all', array(
                'fields' => array('id', 'endRanking', 'team_id', 'canceled'),
                'conditions' => array('year_id' => $this->getCurrentYearId()),
                'contain' => array('Teams' => array('fields' => array('team_name' => 'name'))),
                'order' => array('endRanking' => 'ASC', 'team_name' => 'ASC')
            ))->toArray();

            $this->viewBuilder()->setTemplatePath('pdf');
            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('teamYears', $teamYears);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }

    public function setEndRanking(): void
    {
        $postData = $this->request->getData();
        $rowsCount = 0;

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year = $this->getCurrentYear();

            if ($this->getCurrentDayId() === $year->daysCount) {
                $teamYears = $this->TeamYears->find('all', array(
                    'conditions' => array('year_id' => $year->id)
                ));

                if ($teamYears->count() > 0) {
                    foreach ($teamYears as $ty) { // set null because of unique values
                        /**
                         * @var TeamYear $ty
                         */
                        $ty->set('endRanking', null);
                        $this->TeamYears->save($ty);
                    }

                    foreach ($teamYears as $ty) {
                        /**
                         * @var TeamYear $ty
                         */
                        $groupteam = $this->fetchTable('GroupTeams')->find('all', array(
                            'contain' => array('Groups' => array('fields' => array('year_id', 'day_id', 'teamsCount'))),
                            'conditions' => array('GroupTeams.team_id' => $ty->team_id, 'Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId()),
                        ))->first();

                        if ($groupteam) {
                            /**
                             * @var GroupTeam $groupteam
                             */
                            if ($groupteam->group) {
                                $groupCountTeams = ($groupteam->group)->teamsCount;

                                $ty->set('endRanking', $this->getGroupPosNumber($groupteam->group_id) * $groupCountTeams + $groupteam->calcRanking);
                                $this->TeamYears->save($ty);
                            }
                        }
                    }
                }
            }

            // update all-time ranking
            $rowsCount = $this->updateCalcTotal();
        }

        $this->apiReturn($rowsCount);
    }

    /**
     * @throws \Exception
     */
    public function insert(): void
    {
        $return = array();
        $postData = $this->request->getData();
        $settings = $this->getSettings();

        if (($settings['isTest'] ?? 0) && isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $teamNamesSplit = json_decode($postData['teamNamesSplit'], true);
            foreach ($teamNamesSplit as $team) {
                $newTy = $this->TeamYears->newEmptyEntity();
                $newTy->set('team_id', $team['team_id']);
                $newTy->set('year_id', $settings['currentYear_id']);
                $newTy->set('refereePIN', $this->createUniquePIN($settings['currentYear_id'], $team['team_id']));
                $this->TeamYears->save($newTy);

                $return[] = $newTy;
            }
        }

        $this->apiReturn($return);
    }

    /**
     * @throws \Exception
     */
    public function insertTestValues(): void  // not used anymore
    {
        $postData = $this->request->getData();
        $settings = $this->getSettings();

        if (($settings['isTest'] ?? 0) && isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $conn = ConnectionManager::get('default');
            /**
             * @var \Cake\Database\Connection $conn
             */
            $rc = $conn->execute(file_get_contents(__DIR__ . "/sql/insert_team_years2024.sql"))->rowCount();

            $teamYears = $this->TeamYears->find('all', array(
                'conditions' => array('year_id' => $this->getCurrentYearId()),
            ));

            foreach ($teamYears as $ty) {
                $ty->set('refereePIN', $this->createUniquePIN($ty->year_id, $ty->team_id));
                $this->TeamYears->save($ty);
            }

            $this->apiReturn($rc);
        }

        $this->apiReturn(array());
    }


    /**
     * @throws \Exception
     */
    private function createUniquePIN(int $yearId, int $teamId): int
    {
        $str123 = str_pad((string)(($teamId + 107) % 1000), 3, "0", STR_PAD_LEFT);
        $str45 = $yearId * random_int(1, 3) % 100;

        return (int)($str123 . $str45);
    }

}
