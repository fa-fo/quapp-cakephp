<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Year Entity
 *
 * @property int $id
 * @property int $name
 * @property \Cake\I18n\Date $day1
 * @property \Cake\I18n\Date $day2
 * @property int $teamsCount
 * @property int $daysCount
 * @property int $alwaysAutoUpdateResults
 *
 * @property \App\Model\Entity\Day $day
 * @property \App\Model\Entity\Group[] $groups
 * @property \App\Model\Entity\TeamYear[] $team_years
 */
class Year extends Entity
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
        'day1' => true,
        'day2' => true,
        'teamsCount' => true,
        'daysCount' => true,
        'day' => true,
        'groups' => true,
        'team_years' => true,
        'alwaysAutoUpdateResults' => true,
    ];
}
