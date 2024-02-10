<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Setting;
use Cake\Datasource\ConnectionManager;

/**
 * Years Controller
 *
 * @property \App\Model\Table\YearsTable $Years
 */
class YearsController extends AppController
{
    public function getCurrent(): void
    {
        $year = $this->getCurrentYear()->toArray();

        $year['settings'] = $this->getSettings();

        $year['currentDay_id'] = $year['settings']['currentDay_id'];
        $year['day'] = $year['settings']['currentDay_id'] == 1 ? $year['day1'] : $year['day2'];
        $year['isStart'] = 1;

        $this->apiReturn($year);

        // todo: after V2.0 complete rollout: simplify: $this->apiReturn(array('isStart' => '1'));  // SIC!
        // todo: deprecated: after V2.0.1 complete rollout: function not needed anymore
    }

    public function updateTeamsCount(): void
    {
        $conn = ConnectionManager::get('default');
        $stmt = $conn->execute(file_get_contents(__DIR__ . "/sql/update_years_teamsCount.sql"));

        $this->apiReturn($stmt->rowCount());
    }

    public function all(): void
    {
        $settings = $this->getSettings();

        $years = $this->Years->find('all', array(
            'fields' => array('id', 'year_id' => 'id', 'year_name' => 'name'),
            'order' => array('id' => 'DESC')
        ))
            ->where(['id <=' => $settings['currentYear_id']])
            ->limit($settings['currentYear_id'])  // limit needed for offset!
            ->offset($settings['showEndRanking'] ? 0 : 1) // as an index not to show current without ranking
        ;

        $this->apiReturn($years);
    }

    public function setCurrentDayIncrement(): void
    {
        $year = $this->getCurrentYear();
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            if ($this->getCurrentDayId() < $year->daysCount) {
                $currentDay_id = $this->fetchTable('Settings')->find('all')->where(['name' => 'currentDay_id'])->first();
                /**
                 * @var Setting $currentDay_id
                 */
                $currentDay_id->set('value', $this->getCurrentDayId() + 1);
                $this->fetchTable('Settings')->save($currentDay_id);

                $alwaysAutoUpdateResults = $this->fetchTable('Settings')->find('all')->where(['name' => 'alwaysAutoUpdateResults'])->first();
                /**
                 * @var Setting $alwaysAutoUpdateResults
                 */
                $alwaysAutoUpdateResults->set('value', 0);
                $this->fetchTable('Settings')->save($alwaysAutoUpdateResults);
            }
        }

        $this->apiReturn($year);
    }

    public function setAlwaysAutoUpdateResults(): void
    {
        $postData = $this->request->getData();
        $alwaysAutoUpdateResults = false;

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $alwaysAutoUpdateResults = $this->fetchTable('Settings')->find('all')->where(['name' => 'alwaysAutoUpdateResults'])->first();
            /**
             * @var Setting $alwaysAutoUpdateResults
             */
            $alwaysAutoUpdateResults->set('value', 1);
            $this->fetchTable('Settings')->save($alwaysAutoUpdateResults);

            $this->getCalcRanking();
        }

        $this->apiReturn($alwaysAutoUpdateResults);
    }

    public function showEndRanking(string $show = ''): void
    {
        $postData = $this->request->getData();
        $showEndRanking = false;

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $showEndRanking = $this->fetchTable('Settings')->find('all')->where(['name' => 'showEndRanking'])->first();
            /**
             * @var Setting $showEndRanking
             */
            $showEndRanking->set('value', (int)$show);
            $this->fetchTable('Settings')->save($showEndRanking);

            $this->getCalcRanking();
        }

        $this->apiReturn($showEndRanking);
    }

    // get Status of current Day
    public function getStatus(): void
    {
        $year = $this->getCurrentYear();

        $teamYears = $this->fetchTable('TeamYears')->find('all', array(
            'conditions' => array('year_id' => $year->id),
        ))->toArray();
        $teamYearsEndRanking = $this->fetchTable('TeamYears')->find('all', array(
            'conditions' => array('year_id' => $year->id, 'endRanking IS NOT' => null),
        ))->toArray();
        $teamYearsPins = $this->fetchTable('TeamYears')->find('all', array(
            'conditions' => array('year_id' => $year->id, 'refereePIN IS NOT' => null),
        ))->toArray();

        $groups = $this->fetchTable('Groups')->find('all', array(
            'conditions' => array('year_id' => $year->id, 'day_id' => $this->getCurrentDayId()),
        ))->toArray();

        $groupTeams = $this->fetchTable('GroupTeams')->find('all', array(
            'contain' => array(
                'Groups' => array('fields' => array('year_id', 'day_id')),
            ),
            'conditions' => array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId()),
        ))->toArray();

        $sumCalcMatches = 0;
        foreach ($groupTeams as $gt) {
            $sumCalcMatches += $gt->calcCountMatches;
        }

        $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId());
        $matches = $this->getMatches($conditionsArray, 0, 0, 1);
        $matchesPins = $this->getMatches(array_merge($conditionsArray, array('refereePIN IS NOT' => null)), 0, 0, 0);

        $matchesRefChangeable = array();
        $matchesTeamsChangeable = array();
        $missingRefereesCount = 0;
        $matchesWith1CanceledCount = 0;
        $matchResultCount = 0;
        if (is_array($matches)) {
            foreach ($matches as $m) {
                // search for minimize missing referees
                if ($m->isRefereeCanceled && !$m->canceled && $m->resultTrend === null) {
                    $missingRefereesCount++;

                    // search for available refs from same sport with canceled match
                    $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId(), 'sport_id' => $m->sport_id, 'Matches.canceled >' => 0);
                    $matches1 = $this->getMatches($conditionsArray, 0, 0, 1);
                    if (is_array($matches1)) {
                        foreach ($matches1 as $m1) {
                            if (!$m1->isRefereeCanceled) {
                                // check if ref's team is already in play in same round with non-canceled match
                                $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId(), 'round_id' => $m->round_id, 'Matches.canceled' => 0,
                                    'OR' => array(
                                        'team1_id' => $m1->refereeTeam_id,
                                        'team2_id' => $m1->refereeTeam_id,
                                        'refereeTeam_id' => $m1->refereeTeam_id,
                                        'refereeTeamSubst_id' => $m1->refereeTeam_id,
                                    )
                                );
                                $matches2 = $this->getMatches($conditionsArray, 0, 0, 0);
                                if (!$matches2) {
                                    $matchesRefChangeable[] = array($m, $m1);
                                    break;
                                }
                            }
                        }
                    }
                }

                // search for minimize canceled matches
                if ($this->getCurrentDayId() == 2 && ($m->canceled == 1 || $m->canceled == 2) && $m->resultTrend === null) {
                    $matchesWith1CanceledCount++;

                    // search for available teams from same group and same sport with canceled match
                    $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId(), 'group_id' => $m->group_id, 'sport_id' => $m->sport_id, 'Matches.canceled >' => 0, 'Matches.canceled <' => 3, 'Matches.id !=' => $m->id);
                    $matches1 = $this->getMatches($conditionsArray, 0, 0, 1);
                    if (is_array($matches1)) {
                        foreach ($matches1 as $m1) {
                            // check if other team is already in play in same round with non-canceled match
                            $otherTeam = $m1->canceled == 1 ? $m1->team2_id : $m1->team1_id;
                            $conditionsArray = array('Groups.year_id' => $year->id, 'Groups.day_id' => $this->getCurrentDayId(), 'round_id' => $m->round_id, 'Matches.canceled' => 0,
                                'OR' => array(
                                    'team1_id' => $otherTeam,
                                    'team2_id' => $otherTeam,
                                    'refereeTeam_id' => $otherTeam,
                                    'refereeTeamSubst_id' => $otherTeam,
                                )
                            );
                            $matches2 = $this->getMatches($conditionsArray, 0, 0, 0);
                            if (!$matches2) {
                                $matchesTeamsChangeable[] = array($m, $m1);
                                break;
                            }
                        }
                    }
                }

                $matchResultCount += ($m->resultTrend !== null ? 1 : 0);
            }
        }

        $status['teamYearsCount'] = count($teamYears);
        $status['teamYearsEndRankingCount'] = count($teamYearsEndRanking);
        $status['teamYearsPins'] = count($teamYearsPins);
        $status['groupsCount'] = count($groups);
        $status['groupTeamsCount'] = count($groupTeams);
        $status['sumCalcMatchesGroupTeams'] = $sumCalcMatches / 2;
        $status['matchesCount'] = is_array($matches) ? count($matches) : 0;
        $status['matchesPins'] = is_array($matchesPins) ? count($matchesPins) : 0;
        $status['matchResultCount'] = $matchResultCount;

        $status['missingRefereesCount'] = $missingRefereesCount;
        $status['matchesRefChangeable'] = $matchesRefChangeable;

        $status['matchesWith1CanceledCount'] = $matchesWith1CanceledCount;
        $status['matchesTeamsChangeable'] = $matchesTeamsChangeable;

        $this->apiReturn($status);
    }
}
