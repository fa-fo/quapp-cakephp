<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Cache\Cache;

/**
 * Settings Controller
 *
 * @property \App\Model\Table\SettingsTable $Settings
 */
class SettingsController extends AppController
{
    public function setSetting(string $name = ''): void
    {
        $setting = false;
        $postData = $this->request->getData();
        $value = $postData['value'] ?? '';

        if ($name != '' && $value != '' && isset($postData['password'])
            && $this->checkUsernamePassword('admin', $postData['password'])) {
            $setting = $this->Settings->find('all')->where(['name' => $name])->first();
            $setting->set('value', $value);
            $this->Settings->save($setting);

            Cache::clear('app:settings');
        }

        $this->apiReturn($setting);
    }
}
