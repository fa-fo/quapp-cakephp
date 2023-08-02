<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MatchschedulingPattern16 Entity
 *
 * @property int $id
 * @property int $round_id
 * @property int $placenumberTeam1
 * @property int $placenumberTeam2
 * @property int $placenumberRefereeTeam
 * @property int $sport_id
 *
 * @property \App\Model\Entity\Round $round
 * @property \App\Model\Entity\Sport $sport
 */
class Match4schedulingPattern16 extends Entity
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
        'round_id' => true,
        'placenumberTeam1' => true,
        'placenumberTeam2' => true,
        'placenumberRefereeTeam' => true,
        'sport_id' => true,
        'round' => true,
        'sport' => true,
    ];
}
