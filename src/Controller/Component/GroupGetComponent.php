<?php

namespace App\Controller\Component;

use App\Model\Entity\Group;
use App\Model\Entity\GroupTeam;
use App\Model\Entity\Match4;
use App\Model\Entity\Year;
use Cake\Controller\Component;
use Cake\Datasource\FactoryLocator;

/**
 * @property CacheComponent $Cache
 */
class GroupGetComponent extends Component
{
    protected array $components = ['Cache'];

    public function getGroupName(int $number): string|bool
    {
        $alphabet = range('A', 'Z');

        return $alphabet[$number] ?? false;
    }

    public function getGroupByMatchId(int $id): Group
    {
        $match = FactoryLocator::get('Table')->get('Matches')->find()->where(['id' => $id])->first();
        /**
         * @var Match4 $match
         */
        $group = FactoryLocator::get('Table')->get('Groups')->find()->where(['id' => $match->get('group_id')])->first();
        /**
         * @var Group $group
         */
        return $group;
    }

    public function getGroupByTeamId(int $team_id, int $year_id, int $day_id): Group
    {
        $groupteam = FactoryLocator::get('Table')->get('GroupTeams')->find('all', array(
            'contain' => array('Groups' => array('fields' => array('id', 'year_id', 'day_id'))),
            'conditions' => array('team_id' => $team_id, 'Groups.year_id' => $year_id, 'Groups.day_id' => $day_id),
        ))->first();
        /**
         * @var GroupTeam $groupteam
         */
        $group = FactoryLocator::get('Table')->get('Groups')->find()->where(['id' => $groupteam->get('group_id')])->first();
        /**
         * @var Group $group
         */
        return $group;
    }

    public function getPrevAndNextGroup(int $group_id): array|null
    {
        $group_id = $group_id ?: $this->getCurrentGroupId(0);

        if ($group_id) {
            $group = FactoryLocator::get('Table')->get('Groups')->find()->where(['id' => $group_id])->first();
            /**
             * @var Group|null $group
             */
            if ($group) {
                $countGroups = FactoryLocator::get('Table')->get('Groups')->find('all', array(
                    'conditions' => array('year_id' => $group->year_id, 'day_id' => $group->day_id)
                ))->count();

                $group = $group->toArray();
                $groupPosNumber = $this->getGroupPosNumber($group_id);
                if ($groupPosNumber + 1 > 1) {
                    $group['prevGroup'] = FactoryLocator::get('Table')->get('Groups')->find('all', array(
                        'fields' => array('group_id' => 'id', 'group_name' => 'name', 'id', 'name'),
                        'conditions' => array('id' => $group_id - 1)
                    ))->first();
                }
                if ($groupPosNumber + 1 < $countGroups) {
                    $group['nextGroup'] = FactoryLocator::get('Table')->get('Groups')->find('all', array(
                        'fields' => array('group_id' => 'id', 'group_name' => 'name', 'id', 'name'),
                        'conditions' => array('id' => $group_id + 1, 'name !=' => 'Endrunde')
                    ))->first();
                }
            }
        }

        return $group ?? null;
    }

    public function getRefereeGroup(Group $playGroup): Group|null
    {
        // groupName: A->B, B->A, C->D, D->C, E->F, F->E, ...
        // groupPosNumber: 0->1, 1->0, 2->3, 3->2, 4->5, 5->4, ...
        $playGroupPosNumber = $this->getGroupPosNumber($playGroup->id);
        $refereeGroupPosNumber = $playGroupPosNumber % 2 ? $playGroupPosNumber - 1 : $playGroupPosNumber + 1;

        $name = $this->getGroupName($refereeGroupPosNumber);

        $refereeGroup = FactoryLocator::get('Table')->get('Groups')->find('all', array(
            'conditions' => array('year_id' => $playGroup->year_id, 'day_id' => $playGroup->day_id, 'name' => $name)
        ))->first();
        /**
         * @var Group|null $refereeGroup
         */
        return $refereeGroup;
    }

    public function getGroupPosNumber(int $group_id): int
    {
        $group = FactoryLocator::get('Table')->get('Groups')->get($group_id);
        /**
         * @var Group $group
         */
        return ord(strtoupper($group->name)) - ord('A');
    }


    public function getCurrentGroupId(int $number): int|false
    {
        $settings = $this->Cache->getSettings();
        $year = $this->Cache->getCurrentYear();
        /**
         * @var Year $year
         */
        $name = chr(ord('A') + $number);

        $group = FactoryLocator::get('Table')->get('Groups')->find('all', array(
            'conditions' => array('name' => $name, 'year_id' => $year->id, 'day_id' => $settings['currentDay_id']),
            'order' => array('id' => 'ASC')
        ))->first();
        /**
         * @var Group|null $group
         */

        return $group ? $group->id : false;
    }
}
