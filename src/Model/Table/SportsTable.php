<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Sports Model
 *
 * @property \App\Model\Table\MatchesTable&\Cake\ORM\Association\HasMany $Matches
 * @property \App\Model\Table\MatchschedulingPattern16Table&\Cake\ORM\Association\HasMany $MatchschedulingPattern16
 *
 * @method \App\Model\Entity\Sport newEmptyEntity()
 * @method \App\Model\Entity\Sport newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Sport[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Sport get($primaryKey, $options = [])
 * @method \App\Model\Entity\Sport findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Sport patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Sport[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Sport|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Sport saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Sport[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Sport[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Sport[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Sport[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class SportsTable extends Table
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

        $this->setTable('sports');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('Matches', [
            'foreignKey' => 'sport_id',
        ]);
        $this->hasMany('MatchschedulingPattern16', [
            'foreignKey' => 'sport_id',
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
            ->maxLength('name', 32)
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->integer('goalFactor')
            ->requirePresence('goalFactor', 'create')
            ->notEmptyString('goalFactor');

        $validator
            ->scalar('color')
            ->maxLength('color', 8)
            ->allowEmptyString('color');

        $validator
            ->scalar('icon')
            ->maxLength('icon', 64)
            ->allowEmptyString('icon');

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

        return $rules;
    }
}
