<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Years Model
 *
 * @property \App\Model\Table\DaysTable&\Cake\ORM\Association\BelongsTo $Days
 * @property \App\Model\Table\GroupsTable&\Cake\ORM\Association\HasMany $Groups
 * @property \App\Model\Table\TeamYearsTable&\Cake\ORM\Association\HasMany $TeamYears
 *
 * @method \App\Model\Entity\Year newEmptyEntity()
 * @method \App\Model\Entity\Year newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Year[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Year get($primaryKey, $options = [])
 * @method \App\Model\Entity\Year findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Year patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Year[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Year|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Year saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Year[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Year[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Year[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Year[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class YearsTable extends Table
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

        $this->setTable('years');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Days', [
            'foreignKey' => 'currentDay_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Groups', [
            'foreignKey' => 'year_id',
        ]);
        $this->hasMany('TeamYears', [
            'foreignKey' => 'year_id',
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
            ->integer('name')
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->date('day1')
            ->requirePresence('day1', 'create')
            ->notEmptyDate('day1');

        $validator
            ->date('day2')
            ->requirePresence('day2', 'create')
            ->notEmptyDate('day2');

        $validator
            ->integer('teamsCount')
            ->notEmptyString('teamsCount');

        $validator
            ->integer('daysCount')
            ->notEmptyString('daysCount');

        $validator
            ->integer('alwaysAutoUpdateResults')
            ->notEmptyString('alwaysAutoUpdateResults');

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
