<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Matchevents Model
 *
 * @property \App\Model\Table\MatcheventLogsTable&\Cake\ORM\Association\HasMany $MatcheventLogs
 *
 * @method \App\Model\Entity\Match4event newEmptyEntity()
 * @method \App\Model\Entity\Match4event newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Match4event[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Match4event get($primaryKey, $options = [])
 * @method \App\Model\Entity\Match4event findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Match4event patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Match4event[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Match4event|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Match4event saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Match4event[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4event[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4event[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4event[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class MatcheventsTable extends Table
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

        $this->setTable('matchevents');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('MatcheventLogs', [
            'foreignKey' => 'matchEvent_id',
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
            ->scalar('code')
            ->maxLength('code', 16)
            ->requirePresence('code', 'create')
            ->notEmptyString('code')
            ->add('code', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('name')
            ->maxLength('name', 64)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('needsTeamAssoc')
            ->notEmptyString('needsTeamAssoc');

        $validator
            ->integer('needsPlayerAssoc')
            ->notEmptyString('needsPlayerAssoc');

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
        $rules->add($rules->isUnique(['code']), ['errorField' => 'code']);

        return $rules;
    }
}
