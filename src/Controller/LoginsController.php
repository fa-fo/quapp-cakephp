<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Logins Controller
 *
 * @property \App\Model\Table\LoginsTable $Logins
 */
class LoginsController extends AppController
{

    public function check(): void
    {
        $return = false;
        $postData = $this->request->getData();

        if (isset($postData['name']) && isset($postData['password'])) {
            $return = $this->checkUsernamePassword($postData['name'], $postData['password']);
        }

        $this->apiReturn($return);
    }


}
