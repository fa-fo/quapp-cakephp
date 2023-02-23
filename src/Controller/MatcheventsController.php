<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Matchevents Controller
 *
 * @property \App\Model\Table\MatcheventsTable $Matchevents
 */
class MatcheventsController extends AppController
{
    public function all()
    {
        $matchevents = $this->Matchevents->find('all', array(
            'conditions' => array('logsAddableOnLoggedIn' => 1)
        ));

        $this->apiReturn($matchevents);
    }
}
