<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MatcheventLog Entity
 *
 * @property int $id
 * @property int $match_id
 * @property int $matchEvent_id
 * @property int|null $team_id
 * @property int|null $playerNumber
 * @property string|null $playerName
 * @property \Cake\I18n\DateTime $datetimeSent
 * @property \Cake\I18n\DateTime $datetime
 * @property int $canceled
 * @property \Cake\I18n\DateTime|null $cancelTime
 *
 * @property \App\Model\Entity\Match4 $match
 * @property \App\Model\Entity\Match4event $matchevent
 * @property \App\Model\Entity\Team $team
 */
class Match4eventLog extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     */
    protected array $_accessible = [
        'match_id' => true,
        'matchEvent_id' => true,
        'team_id' => true,
        'playerNumber' => true,
        'playerName' => true,
        'datetimeSent' => true,
        'datetime' => true,
        'canceled' => true,
        'cancelTime' => true,
        'match' => true,
        'matchevent' => true,
        'team' => true,
    ];
}
