<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Matches Model
 *
 * @property \App\Model\Table\GroupsTable&\Cake\ORM\Association\BelongsTo $Groups
 * @property \App\Model\Table\RoundsTable&\Cake\ORM\Association\BelongsTo $Rounds
 * @property \App\Model\Table\SportsTable&\Cake\ORM\Association\BelongsTo $Sports
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $Teams1
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $Teams2
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $Teams3
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $Teams4
 *
 * @method \App\Model\Entity\Match4 newEmptyEntity()
 * @method \App\Model\Entity\Match4 newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Match4[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Match4 get($primaryKey, $options = [])
 * @method \App\Model\Entity\Match4 findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Match4 patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Match4[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Match4|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Match4 saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Match4[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class MatchesTable extends Table
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

        $this->setTable('matches');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Groups', [
            'foreignKey' => 'group_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Rounds', [
            'foreignKey' => 'round_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Sports', [
            'foreignKey' => 'sport_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Teams1', [
            'className' => 'Teams',
            'foreignKey' => 'team1_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Teams2', [
            'className' => 'Teams',
            'foreignKey' => 'team2_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Teams3', [
            'className' => 'Teams',
            'foreignKey' => 'refereeTeam_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Teams4', [
            'className' => 'Teams',
            'foreignKey' => 'refereeTeamSubst_id',
            'joinType' => 'LEFT',
        ]);
        $this->hasMany('MatcheventLogs', [
            'foreignKey' => 'match_id',
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
            ->scalar('refereePIN')
            ->maxLength('refereePIN', 5)
            ->allowEmptyString('refereePIN');

        $validator
            ->scalar('refereeName')
            ->maxLength('refereeName', 64)
            ->allowEmptyString('refereeName');

        $validator
            ->integer('resultTrend')
            ->allowEmptyString('resultTrend');

        $validator
            ->integer('resultGoals1')
            ->allowEmptyString('resultGoals1');

        $validator
            ->integer('resultGoals2')
            ->allowEmptyString('resultGoals2');

        $validator
            ->scalar('remarks')
            ->allowEmptyString('remarks');

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
        $rules->add($rules->existsIn(['round_id'], 'Rounds'), ['errorField' => 'round_id']);
        $rules->add($rules->existsIn(['sport_id'], 'Sports'), ['errorField' => 'sport_id']);
        $rules->add($rules->existsIn(['team1_id'], 'Teams1'), ['errorField' => 'team1_id']);
        $rules->add($rules->existsIn(['team2_id'], 'Teams2'), ['errorField' => 'team2_id']);
        $rules->add($rules->existsIn(['refereeTeam_id'], 'Teams3'), ['errorField' => 'refereeTeam_id']);
        $rules->add($rules->existsIn(['refereeTeamSubst_id'], 'Teams4'), ['errorField' => 'refereeTeamSubst_id']);

        return $rules;
    }
}
