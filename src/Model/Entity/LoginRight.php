<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * LoginRight Entity
 *
 * @property int $id
 * @property int $login_id
 * @property int $right_id
 *
 * @property \App\Model\Entity\Login $login
 * @property \App\Model\Entity\Right $right
 */
class LoginRight extends Entity
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
        'login_id' => true,
        'right_id' => true,
        'login' => true,
        'right' => true,
    ];
}
