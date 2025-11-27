<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ScoutRating Entity
 *
 * @property int $id
 * @property int $matchevent_log_id
 * @property int|null $points
 * @property float|null $confirmed
 *
 * @property \App\Model\Entity\Match4eventLog $matchevent_log
 */
class ScoutRating extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'matchevent_log_id' => true,
        'points' => true,
        'confirmed' => true,
        'matchevent_log' => true,
    ];
}
