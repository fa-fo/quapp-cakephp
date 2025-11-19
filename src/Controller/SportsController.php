<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Client;

/**
 * Sports Controller
 *
 * @property \App\Model\Table\SportsTable $Sports
 * @property \App\Controller\Component\CacheComponent $Cache
 * @property \App\Controller\Component\MatchGetComponent $MatchGet
 * @property \App\Controller\Component\SecurityComponent $Security
 */
class SportsController extends AppController
{
    public function getResourceContent(string $id): void
    {
        $id = (int)$id;
        $return = array();
        $settings = $this->Cache->getSettings();

        if ($settings['useResourceContentApi']) {
            $http = new Client([
                'ssl_verify_host' => false,
                'ssl_verify_peer' => false,
                'ssl_verify_peer_name' => false,
            ]);

            $response = $http->get('https://www.quattfo.de/api/getResource.php?id=' . $id);
            $return = $response->getJson();
        } else {
            $dir = $this->getResourceDir();
            $filename = $this->getResourceFilename($dir, $id);
            $return['content'] = file_get_contents($filename);
        }

        $this->apiReturn($return);
    }

    public function saveResourceContent(string $id): void
    {
        $id = (int)$id;
        $postData = $this->request->getData();
        $return = array();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            if (isset($postData['content'])) {
                $dir = $this->getResourceDir();
                $filename = $this->getResourceFilename($dir, $id);
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
                $return = file_put_contents($filename, $postData['content']);
            }
        }

        $this->apiReturn($return);
    }

    private function getResourceDir(): string
    {
        return __DIR__ . '/../../webroot/resources';
    }

    private function getResourceFilename(string $dir, int $id): string
    {
        return $dir . '/resource' . $id . '.html';
    }


    public function pdfAllFieldsMatches(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->Security->checkUsernamePassword('admin', $postData['password'])) {
            $settings = $this->Cache->getSettings();
            $year = $this->Cache->getCurrentYear()->toArray();

            $sports = $this->Sports->find('all', array(
                'conditions' => array('name !=' => 'Multi'),
                'order' => array('name' => 'ASC')
            ))->toArray();

            $groups = $this->fetchTable('Groups')->find('all', array(
                'conditions' => array('year_id' => $settings['currentYear_id'], 'day_id' => $settings['currentDay_id'], 'name !=' => 'Endrunde'),
                'order' => array('name' => 'ASC')
            ))->toArray();

            foreach ($sports as $sport) {
                $sport['fields'] = array();

                foreach ($groups as $group) {
                    $conditionsArray = array(
                        'Groups.id' => $group['id'],
                        'Sports.id' => $sport['id'],
                    );

                    $sport['fields'][$group['name']]['matches'] = $this->MatchGet->getMatches($conditionsArray);
                }
            }

            $this->viewBuilder()->setTemplatePath('pdf');
            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('sports', $sports);
            $this->viewBuilder()->setVar('year', $year);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }
}
