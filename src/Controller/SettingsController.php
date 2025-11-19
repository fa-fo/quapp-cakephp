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
    public function setSetting(string $name = ''): void
    {
        $setting = false;
        $postData = $this->request->getData();
        $value = $postData['value'] ?? '';

        if ($name != '' && $value != '' && isset($postData['password'])
            && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $setting = $this->Settings->find('all')->where(['name' => $name])->first();
            $setting->set('value', $value);
            $this->Settings->save($setting);

            Cache::delete('app_settings');
        }

        $this->apiReturn($setting);
    }
}
