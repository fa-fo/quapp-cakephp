<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * PushTokens Controller
 *
 * @property \App\Model\Table\PushTokensTable $PushTokens
 */
class PushTokensController extends AppController
{
    public function add(): void
    {
        $return = false;
        $postData = $this->request->getData();
        if (($postData['expoPushToken'] ?? '') !== '') {
            $pushToken = $this->PushTokens->find('all', array(
                'conditions' => array('expoPushToken' => $postData['expoPushToken'])
            ))->first();

            $pushToken = $pushToken ?: $this->PushTokens->newEmptyEntity();
            $pushToken = $this->PushTokens->patchEntity($pushToken, $postData);

            if ($this->PushTokens->save($pushToken)) {
                $return = $pushToken;
            }
        }
        $this->apiReturn($return);
    }

    public function byTeam(string $team_id = ''): void
    {
        $pushTokens = false;

        if ($team_id !== '') {
            $team_id = (int)$team_id;
            $postData = $this->request->getData();

            if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
                $pushTokens = $this->PushTokens->find('all', array(
                    'conditions' => $team_id ? array('my_team_id' => $team_id) : array()
                ));
            }
        }

        $this->apiReturn($pushTokens);
    }
}
