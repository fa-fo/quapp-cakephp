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
    public function all(int $withPhotoAdd = 0): void

    {
        $matchevents = $this->Matchevents->find('all', array(
            'conditions' => array('logsAddableOnLoggedIn' => 1, 'code !=' => $withPhotoAdd ? '' : 'PHOTO_ADD')
        ));

        $this->apiReturn($matchevents);
    }
}
