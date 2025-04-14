<?php
declare(strict_types=1);

namespace App\Controller;

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
        }

        $this->apiReturn($setting);
    }
}
