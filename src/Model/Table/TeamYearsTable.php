<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TeamYears Model
 *
 * @property \App\Model\Table\YearsTable&\Cake\ORM\Association\BelongsTo $Years
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $Teams
 *
 * @method \App\Model\Entity\TeamYear newEmptyEntity()
 * @method \App\Model\Entity\TeamYear newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TeamYear[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TeamYear get($primaryKey, $options = [])
 * @method \App\Model\Entity\TeamYear findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\TeamYear patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TeamYear[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TeamYear|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TeamYear saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TeamYear[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TeamYear[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\TeamYear[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TeamYear[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class TeamYearsTable extends Table
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

        $this->setTable('team_years');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Years', [
            'foreignKey' => 'year_id',
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
            ->integer('endRanking')
            ->allowEmptyString('endRanking');

        $validator
            ->integer('scrRanking')
            ->allowEmptyString('scrRanking');

        $validator
            ->integer('scrPoints')
            ->allowEmptyString('scrPoints');

        $validator
            ->integer('scrMatchCount')
            ->allowEmptyString('scrMatchCount');

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
        $rules->add($rules->existsIn(['year_id'], 'Years'), ['errorField' => 'year_id']);
        $rules->add($rules->existsIn(['team_id'], 'Teams'), ['errorField' => 'team_id']);

        return $rules;
    }
}
