<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * GroupTeam Entity
 *
 * @property int $id
 * @property int $group_id
 * @property int $placeNumber
 * @property int $team_id
 * @property int|null $calcRanking
 * @property int|null $calcCountMatches
 * @property int|null $calcGoalsScored
 * @property int|null $calcGoalsReceived
 * @property int|null $calcGoalsDiff
 * @property int|null $calcPointsPlus
 * @property int|null $calcPointsMinus
 *
 * @property \App\Model\Entity\Group $group
 * @property \App\Model\Entity\Team $team
 */
class GroupTeam extends Entity
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
        'group_id' => true,
        'placeNumber' => true,
        'team_id' => true,
        'calcRanking' => true,
        'calcCountMatches' => true,
        'calcGoalsScored' => true,
        'calcGoalsReceived' => true,
        'calcGoalsDiff' => true,
        'calcPointsPlus' => true,
        'calcPointsMinus' => true,
        'group' => true,
        'team' => true,
    ];
}
