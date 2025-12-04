<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Team Entity
 *
 * @property int $id
 * @property string $name
 * @property int|null $calcTotalYears
 * @property int|null $calcTotalRankingPoints
 * @property string|null $calcTotalPointsPerYear
 * @property int|null $calcTotalChampionships
 * @property int|null $calcTotalRanking
 * @property int|null $prevTeam_id
 * @property int $hidden
 * @property int $testTeam
 * @property int|null $calcPowerRankingPoints
 *
 * @property \App\Model\Entity\Team|null $prevTeam
 */
class Team extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     */
    protected array $_accessible = [
        'name' => true,
        'calcTotalYears' => true,
        'calcTotalRankingPoints' => true,
        'calcTotalPointsPerYear' => true,
        'calcTotalChampionships' => true,
        'calcTotalRanking' => true,
        'prevTeam_id' => true,
        'prevTeam' => true,
        'hidden' => true,
        'testTeam' => true,
        'calcPowerRankingPoints' => true,
    ];
}
