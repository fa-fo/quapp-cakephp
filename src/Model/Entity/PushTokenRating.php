<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PushTokenRating Entity
 *
 * @property int $id
 * @property int $year_id
 * @property int $push_token_id
 * @property int $matchevent_log_id
 * @property int|null $points_expected
 * @property int|null $points_confirmed
 *
 * @property \App\Model\Entity\Year $year
 * @property \App\Model\Entity\PushToken $push_token
 * @property \App\Model\Entity\MatcheventLog $matchevent_log
 */
class PushTokenRating extends Entity
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
        'year_id' => true,
        'push_token_id' => true,
        'matchevent_log_id' => true,
        'points_expected' => true,
        'points_confirmed' => true,
        'year' => true,
        'push_token' => true,
        'matchevent_log' => true,
    ];
}
