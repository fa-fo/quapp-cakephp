<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TeamYear Entity
 *
 * @property int $id
 * @property int $year_id
 * @property int $team_id
 * @property int|null $endRanking
 * @property int $canceled
 *
 * @property \App\Model\Entity\Year $year
 * @property \App\Model\Entity\Team $team
 */
class TeamYear extends Entity
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
        'team_id' => true,
        'endRanking' => true,
        'canceled' => true,
        'year' => true,
        'team' => true,
    ];
}
