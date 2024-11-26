<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Round Entity
 *
 * @property int $id
 * @property \Cake\I18n\DateTime $timeStartDay1
 * @property \Cake\I18n\DateTime $timeStartDay2
 *
 * @property \App\Model\Entity\Match4[] $matches
 * @property \App\Model\Entity\Match4schedulingPattern16[] $matchscheduling_pattern16
 * @property int $autoUpdateResults
 */
class Round extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     */
    protected array $_accessible = [
        'timeStartDay1' => true,
        'timeStartDay2' => true,
        'matches' => true,
        'matchscheduling_pattern16' => true,
        'autoUpdateResults' => true,
    ];
}
