<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Teams Model
 *
 * @property \App\Model\Table\MatchesTable&\Cake\ORM\Association\HasMany $Matches
 * @property \App\Model\Table\GroupTeamsTable&\Cake\ORM\Association\HasMany $GroupTeams
 * @property \App\Model\Table\TeamYearsTable&\Cake\ORM\Association\HasMany $TeamYears
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\HasOne $NewTeams
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $PrevTeams
 *
 * @method \App\Model\Entity\Team newEmptyEntity()
 * @method \App\Model\Entity\Team newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Team[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Team get($primaryKey, $options = [])
 * @method \App\Model\Entity\Team findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Team patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Team[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Team|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Team saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class TeamsTable extends Table
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

        $this->setTable('teams');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('Matches', [
            'foreignKey' => 'team1_id',
        ]);
        $this->hasMany('Matches', [
            'foreignKey' => 'team2_id',
        ]);
        $this->hasMany('Matches', [
            'foreignKey' => 'refereeTeam_id',
        ]);
        $this->hasMany('Matches', [
            'foreignKey' => 'refereeTeamSubst_id',
        ]);
        $this->hasMany('GroupTeams', [
            'foreignKey' => 'team_id',
        ]);
        $this->hasMany('MatcheventLogs', [
            'foreignKey' => 'team_id',
        ]);
        $this->hasMany('TeamYears', [
            'foreignKey' => 'team_id',
        ]);
        $this->hasOne('NewTeams', [
            'foreignKey' => 'prevTeam_id',
        ]);
        $this->belongsTo('PrevTeams', [
            'className' => 'Teams',
            'foreignKey' => 'prevTeam_id',
            'joinType' => 'LEFT',
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
            ->scalar('name')
            ->maxLength('name', 64)
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->integer('calcTotalYears')
            ->allowEmptyString('calcTotalYears');

        $validator
            ->integer('calcTotalRankingPoints')
            ->allowEmptyString('calcTotalRankingPoints');

        $validator
            ->decimal('calcTotalPointsPerYear')
            ->allowEmptyString('calcTotalPointsPerYear');

        $validator
            ->integer('calcTotalRanking')
            ->allowEmptyString('calcTotalRanking');

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
        $rules->add($rules->isUnique(['name']), ['errorField' => 'name']);
        $rules->add($rules->existsIn(['prevTeam_id'], 'PrevTeams'), ['errorField' => 'prevTeam_id']);

        return $rules;
    }
}
