<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Login;

/**
 * Logins Controller
 *
 * @property \App\Model\Table\LoginsTable $Logins
 * @property \App\Controller\Component\SecurityComponent $Security
 */
class LoginsController extends AppController
{
    public function check(): void
    {
        $return = false;
        $postData = $this->request->getData();

        if (isset($postData['name']) && isset($postData['password'])) {
            $return = $this->Security->checkUsernamePassword($postData['name'], $postData['password']);
        }

        $this->apiReturn($return);
    }

    public function changePassword(): void
    {
        $login = false;
        $postData = $this->request->getData();

        if (isset($postData['name']) && isset($postData['password']) && isset($postData['newPassword'])
            && $this->Security->checkUsernamePassword($postData['name'], $postData['password'])) {
            $login = $this->Logins->find('all')->where(['name' => $postData['name']])->first();
            /**
             * @var Login|null $login
             */

            if ($login) {
                $login->set('password', md5($postData['newPassword']));
                $this->Logins->save($login);
            }
        }

        $this->apiReturn($login);
    }
}
