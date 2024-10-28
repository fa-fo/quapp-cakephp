<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Match Entity
 *
 * @property int $id
 * @property int $group_id
 * @property int $round_id
 * @property int $sport_id
 * @property int $team1_id
 * @property int $team2_id
 * @property int|null $refereeTeam_id
 * @property int|null $refereeTeamSubst_id
 * @property string|null $refereePIN
 * @property int|null $resultTrend
 * @property int|null $resultGoals1
 * @property int|null $resultGoals2
 * @property int $resultAdmin
 * @property string|null $remarks
 * @property int $canceled
 * @property int|null $isRefereeCanceled
 * @property int|null $isTime2login
 * @property int|null $isTime2matchEnd
 * @property int|null $isTime2confirm
 * @property array|null $logsCalc
 *
 * @property \App\Model\Entity\Group $group
 * @property \App\Model\Entity\Round $round
 * @property \App\Model\Entity\Sport $sport
 * @property \App\Model\Entity\Team $team1
 * @property \App\Model\Entity\Team $team2
 * @property \App\Model\Entity\Team $team3
 * @property \App\Model\Entity\Team|null $team4
 * @property \App\Model\Entity\Match4eventLog[] $match_event_logs
 */
class Match4 extends Entity
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
    protected array $_accessible = [
        'group_id' => true,
        'round_id' => true,
        'sport_id' => true,
        'team1_id' => true,
        'team2_id' => true,
        'refereeTeam_id' => true,
        'refereeTeamSubst_id' => true,
        'refereePIN' => true,
        'resultTrend' => true,
        'resultGoals1' => true,
        'resultGoals2' => true,
        'resultAdmin' => true,
        'remarks' => true,
        'canceled' => true,
        'group' => true,
        'round' => true,
        'sport' => true,
        'team1' => true,
        'team2' => true,
        'team3' => true,
        'team4' => true,
        'match_event_logs' => true,
        'isRefereeCanceled' => true,
    ];
}
