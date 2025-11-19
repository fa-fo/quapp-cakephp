<?php

namespace App\Controller\Component;

use App\Model\Entity\Login;
use App\Model\Entity\Match4event;
use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;

/**
 * @property CacheComponent $Cache
 */
class SecurityComponent extends Component
{
    protected array $components = ['Cache'];

    public function checkUsernamePassword(string $name, string $password): int|bool
    {
        $return = false;

        if ($this->getController()->getRequest()->is('post')) {
            $login = FactoryLocator::get('Table')->get('Logins')->find('all', array(
                'conditions' => array('name' => $name),
            ))->first();
            /**
             * @var Login|null $login
             */
            if ($login && ($login->id ?? 0) > 0) {
                if ($login->failedlogincount < 100 && md5($password) == $login->password) {
                    $return = $login->id;
                    $login->set('failedlogincount', 0);
                } else {
                    $login->set('failedlogincount', $login->failedlogincount + 1);
                }
                FactoryLocator::get('Table')->get('Logins')->save($login);
            }
        }

        return $return;
    }


    public function checkPostData(array $match, array $postData, \Cake\ORM\Entity|null $matchEvent, bool $cancel = false): bool
    {
        if (!$this->getController()->getRequest()->is('post')) {
            return false;
        }

        $settings = $this->Cache->getSettings();

        $refereePIN = FactoryLocator::get('Table')->get('TeamYears')->find()
            ->where(['team_id' => $match['refereeTeam_id'], 'year_id' => $settings['currentYear_id']])->first()->get('refereePIN'); // get it here cause of security reason nowhere else!

        if ($settings['isTest'] ?? 0) {
            if ($refereePIN === null || $refereePIN < 1 || (int)$postData['refereePIN'] !== 12345) {
                return false;
            }
        } else {
            if ($refereePIN === null || $refereePIN < 1) {
                return false;
            }
            if ((int)$postData['refereePIN'] != $refereePIN) { // check 2nd PIN (match pin)
                $refereePIN = FactoryLocator::get('Table')->get('Matches')->find()->where(['id' => $match['id']])->first()->get('refereePIN'); // get it here cause of security reason nowhere else!
                if ($refereePIN === null || $refereePIN < 1) {
                    return false;
                }

                if ((int)$postData['refereePIN'] != $refereePIN) {  // check 3rd PIN possibility (ref subst team pin)
                    if ($match['refereeTeamSubst_id'] === null) {
                        return false;
                    }

                    $refereePIN = FactoryLocator::get('Table')->get('TeamYears')->find()
                        ->where(['team_id' => $match['refereeTeamSubst_id'], 'year_id' => $settings['currentYear_id']])->first()->get('refereePIN'); // get it here cause of security reason nowhere else!
                    if ($refereePIN === null || $refereePIN < 1) {
                        return false;
                    }

                    if ((int)$postData['refereePIN'] != $refereePIN) {
                        return false;
                    }
                }
            }
        }
        if (!isset($postData['matchEvent_id']) || is_null($matchEvent)) {
            return $cancel; // allow cancellation without knowing event
        }

        /**
         * @var Match4event $matchEvent
         */
        if ($matchEvent->needsTeamAssoc == 1 && (!isset($postData['team_id']) || ($match['team1_id'] != (int)$postData['team_id'] && $match['team2_id'] != (int)$postData['team_id']))) {
            return false;
        }
        if ($matchEvent->needsPlayerAssoc == 1 && !isset($postData['playerNumber'])) {
            return false;
        }

        return true;
    }

}
