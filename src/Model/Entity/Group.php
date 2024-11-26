<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Group Entity
 *
 * @property int $id
 * @property int $year_id
 * @property int $day_id
 * @property string $name
 * @property int $teamsCount
 *
 * @property \App\Model\Entity\Year $year
 * @property \App\Model\Entity\Day $day
 * @property \App\Model\Entity\GroupTeam[] $group_teams
 * @property \App\Model\Entity\Match4[] $matches
 */
class Group extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     */
    protected array $_accessible = [
        'year_id' => true,
        'day_id' => true,
        'name' => true,
        'teamsCount' => true,
        'year' => true,
        'day' => true,
        'group_teams' => true,
        'matches' => true,
    ];
}
