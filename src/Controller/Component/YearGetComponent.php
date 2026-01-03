<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;

class YearGetComponent extends Component
{
    public function getYear(int $year_id, int $day_id): array
    {
        $year = FactoryLocator::get('Table')->get('Years')->find('all', array(
            'fields' => array('id', 'name', 'day' => 'day' . ($day_id ?: 1)),
            'conditions' => array('id' => $year_id)
        ))->first()->toArray();

        if ($day_id == 0) {
            $year['daysWithGroups'] = FactoryLocator::get('Table')->get('Groups')->find('all', array(
                'conditions' => array('year_id' => $year['id']),
                'group' => 'day_id'
            ))->count();

            $year['hasPhotos'] = FactoryLocator::get('Table')->get('MatcheventLogs')->find('all', array(
                'conditions' => array('Matchevents.code' => 'PHOTO_UPLOAD', 'Groups.year_id' => $year['id']),
                'contain' => array('Matchevents', 'Matches', 'Matches.Groups'),
            ))->count();
        }

        return $year;
    }
}
