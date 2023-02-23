<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Sport Entity
 *
 * @property int $id
 * @property string $name
 * @property int $goalFactor
 * @property string|null $color
 * @property string|null $icon
 *
 * @property \App\Model\Entity\Match[] $matches
 * @property \App\Model\Entity\MatchschedulingPattern16[] $matchscheduling_pattern16
 */
class Sport extends Entity
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
        'goalFactor' => true,
        'color' => true,
        'icon' => true,
        'matches' => true,
        'matchscheduling_pattern16' => true,
    ];
}
