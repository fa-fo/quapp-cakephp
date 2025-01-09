<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Login Entity
 *
 * @property int $id
 * @property string $name
 * @property string $password
 * @property int $failedlogincount
 */
class Login extends Entity
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
        'password' => true,
        'failedlogincount' => true
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     */
    protected array $_hidden = [
        'password',
    ];
}
