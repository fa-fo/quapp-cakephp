<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Client;

/**
 * PushTokens Controller
 *
 * @property \App\Model\Table\PushTokensTable $PushTokens
 * @property \App\Controller\Component\SecurityComponent $Security
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

    public function sendByTeam(string $team_id = ''): void
    {
        $result = false;

        if ($team_id !== '') {
            $team_id = (int)$team_id;
            $postData = $this->request->getData();

            if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
                $pushTokens = $this->PushTokens->find('list', [
                    'valueField' => 'expoPushToken',
                    'conditions' => $team_id ? array('my_team_id' => $team_id) : array()
                ])->toArray();

                $title = trim((string)($postData['title'] ?? ''));
                $body = trim((string)($postData['body'] ?? ''));

                if ($title !== '' && $body !== '') {
                    $messages = array_map(fn($token) => [
                        'to' => 'ExponentPushToken[hCcaelNQlgA5f2Xjt5UggN]', //$token,
                        'title' => $title,
                        'body' => $body,
                        'sound' => 'default',
                        'channelId' => 'default',
                        'ttl' => 3600,
                    ], $pushTokens);

                    $result = $this->sendPush($messages);
                }
            }
        }

        $this->apiReturn($result);
    }

    private function sendPush(array $messages): array
    {
        $http = new Client();
        $chunks = array_chunk($messages, 100); // max 100 messages at once
        $allResults = [];

        foreach ($chunks as $chunk) {
            $response = $http->post(
                'https://api.expo.dev/v2/push/send',
                json_encode($chunk),
                [
                    'type' => 'json',
                    'headers' => [
                        'Accept' => 'application/json',
                        'Accept-Encoding' => 'gzip, deflate',
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            if ($response->isOk()) {
                $decoded = $response->getJson();
                $allResults = array_merge($allResults, $decoded['data'] ?? []);
            } else {
                $this->log('Expo Push Fehler: ' . $response->getStringBody(), 'error');
            }
        }

        return $allResults;
    }

    public function checkReceipts(): void
    {
        $result = [];
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $receiptIds = explode(',', $postData['receiptIds']);

            // max 1000 IDs per request
            foreach (array_chunk($receiptIds, 1000) as $chunk) {
                $http = new Client();

                $response = $http->post(
                    'https://api.expo.dev/v2/push/getReceipts',
                    json_encode(['ids' => $chunk]),
                    [
                        'type' => 'json',
                        'headers' => [
                            'Accept' => 'application/json',
                            'Accept-Encoding' => 'gzip, deflate',
                            'Content-Type' => 'application/json',
                        ],
                    ]
                );

                $receipts = $response->getJson()['data'] ?? [];

                foreach ($chunk as $id) {
                    $receipt = $receipts[$id] ?? null;

                    $result[] = [
                        'id' => $id,
                        'status' => $receipt['status'] ?? 'pending',
                    ];
                }
            }
        }

        $this->apiReturn($result);
    }
}
