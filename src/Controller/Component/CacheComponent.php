<?php

namespace App\Controller\Component;

use App\Model\Entity\Year;
use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;

class CacheComponent extends Component
{
    public function getSettings(): array
    {
        return Cache::remember('app:settings', function () {
            return FactoryLocator::get('Table')->get('Settings')->find('list', [
                'keyField' => 'name', 'valueField' => 'value'
            ])->toArray();
        });
    }

    public function getCurrentYear(): Year
    {
        $year = Cache::remember('app:year', function () {
            $settings = $this->getSettings();

            return FactoryLocator::get('Table')->get('Years')->find('all', [
                'conditions' => array('id' => $settings['currentYear_id']),
            ])->first();
        });

        /**
         * @var Year $year
         */
        return $year;
    }
}
