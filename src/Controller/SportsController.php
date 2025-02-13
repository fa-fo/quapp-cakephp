<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Client;

/**
 * Sports Controller
 *
 * @property \App\Model\Table\SportsTable $Sports
 */
class SportsController extends AppController
{
    public function getRules(): void
    {
        $this->getResourceContent(16);
        // todo: deprecated: after V3.0.0 complete rollout: function not needed anymore
    }

    public function getResourceContent(int $id): void
    {
        $http = new Client([
            'ssl_verify_host' => false,
            'ssl_verify_peer' => false,
            'ssl_verify_peer_name' => false,
        ]);

        $response = $http->get('https://www.quattfo.de/api/getResource.php?id=' . $id);
        $json = $response->getJson();

        $this->apiReturn($json);
    }

    public function pdfAllFieldsMatches(): void
    {
        $postData = $this->request->getData();

        if (isset($postData['password']) && $this->checkUsernamePassword('admin', $postData['password'])) {
            $year = $this->getCurrentYear();
            $sports = $this->Sports->find('all', array(
                'conditions' => array('name !=' => 'Multi'),
                'order' => array('name' => 'ASC')
            ))->toArray();

            $groups = $this->fetchTable('Groups')->find('all', array(
                'conditions' => array('year_id' => $year->id, 'day_id' => $this->getCurrentDayId(), 'name !=' => 'Play-Off'),
                'order' => array('name' => 'ASC')
            ))->toArray();

            foreach ($sports as $sport) {
                $sport['fields'] = array();

                foreach ($groups as $group) {
                    $conditionsArray = array(
                        'Groups.id' => $group['id'],
                        'Sports.id' => $sport['id'],
                    );

                    $sport['fields'][$group['name']]['matches'] = $this->getMatches($conditionsArray);
                }
            }

            $this->viewBuilder()->setTemplatePath('pdf');
            $this->viewBuilder()->enableAutoLayout(false);
            $this->viewBuilder()->setVar('sports', $sports);

            $this->pdfReturn();
        } else {
            $this->apiReturn(array());
        }
    }
}
