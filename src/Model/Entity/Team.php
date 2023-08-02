<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Team Entity
 *
 * @property int $id
 * @property string $name
 * @property string|null $teamLeaderFirstname
 * @property string|null $teamLeaderLastname
 * @property string|null $teamLeaderEmail
 * @property string|null $teamLeaderPhone
 * @property string|null $teamLeaderCity
 * @property int|null $calcTotalYears
 * @property int|null $calcTotalRankingPoints
 * @property string|null $calcTotalPointsPerYear
 * @property int|null $calcTotalRanking
 *
 * @property \App\Model\Entity\GroupTeam[] $group_teams
 * @property \App\Model\Entity\Match4[] $matches
 * @property \App\Model\Entity\Match4eventLog[] $match_event_logs
 * @property \App\Model\Entity\TeamYear[] $team_years
 */
class Team extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'name' => true,
        'teamLeaderFirstname' => true,
        'teamLeaderLastname' => true,
        'teamLeaderEmail' => true,
        'teamLeaderPhone' => true,
        'teamLeaderCity' => true,
        'calcTotalYears' => true,
        'calcTotalRankingPoints' => true,
        'calcTotalPointsPerYear' => true,
        'calcTotalRanking' => true,
        'group_teams' => true,
        'matches' => true,
        'match_event_logs' => true,
        'team_years' => true,
    ];
}
