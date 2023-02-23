<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Rankingpoints Model
 *
 * @method \App\Model\Entity\Rankingpoint newEmptyEntity()
 * @method \App\Model\Entity\Rankingpoint newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Rankingpoint[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Rankingpoint get($primaryKey, $options = [])
 * @method \App\Model\Entity\Rankingpoint findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Rankingpoint patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Rankingpoint[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Rankingpoint|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Rankingpoint saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Rankingpoint[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Rankingpoint[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Rankingpoint[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Rankingpoint[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class RankingpointsTable extends Table
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

        $this->setTable('rankingpoints');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
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
            ->requirePresence('endRanking', 'create')
            ->notEmptyString('endRanking')
            ->add('endRanking', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->integer('points')
            ->requirePresence('points', 'create')
            ->notEmptyString('points');

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
        $rules->add($rules->isUnique(['endRanking']), ['errorField' => 'endRanking']);

        return $rules;
    }
}
