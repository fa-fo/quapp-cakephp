<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Cache\Cache;

/**
 * Settings Controller
 *
 * @property \App\Model\Table\SettingsTable $Settings
 * @property \App\Controller\Component\SecurityComponent $Security
 */
class SettingsController extends AppController
{
    public function adminSet(string $name = ''): void
    {
        $return = $this->Security->setSetting($name);

        $this->apiReturn($return);
    }
}
