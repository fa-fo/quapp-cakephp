<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * GroupTeams Model
 *
 * @property \App\Model\Table\GroupsTable&\Cake\ORM\Association\BelongsTo $Groups
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $Teams
 *
 * @method \App\Model\Entity\GroupTeam newEmptyEntity()
 * @method \App\Model\Entity\GroupTeam newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\GroupTeam[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\GroupTeam get($primaryKey, $options = [])
 * @method \App\Model\Entity\GroupTeam findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\GroupTeam patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\GroupTeam[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\GroupTeam|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\GroupTeam saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\GroupTeam[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\GroupTeam[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\GroupTeam[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\GroupTeam[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class GroupTeamsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('group_teams');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Groups', [
            'foreignKey' => 'group_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Teams', [
            'foreignKey' => 'team_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('placeNumber')
            ->requirePresence('placeNumber', 'create')
            ->notEmptyString('placeNumber');

        $validator
            ->integer('calcRanking')
            ->allowEmptyString('calcRanking');

        $validator
            ->integer('calcCountMatches')
            ->allowEmptyString('calcCountMatches');

        $validator
            ->integer('calcGoalsScored')
            ->allowEmptyString('calcGoalsScored');

        $validator
            ->integer('calcGoalsReceived')
            ->allowEmptyString('calcGoalsReceived');

        $validator
            ->integer('calcGoalsDiff')
            ->allowEmptyString('calcGoalsDiff');

        $validator
            ->integer('calcPointsPlus')
            ->allowEmptyString('calcPointsPlus');

        $validator
            ->integer('calcPointsMinus')
            ->allowEmptyString('calcPointsMinus');

        $validator
            ->integer('canceled')
            ->notEmptyString('canceled');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['group_id'], 'Groups'), ['errorField' => 'group_id']);
        $rules->add($rules->existsIn(['team_id'], 'Teams'), ['errorField' => 'team_id']);

        return $rules;
    }
}
