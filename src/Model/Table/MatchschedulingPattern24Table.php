<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MatchschedulingPattern24 Model
 *
 * @property \App\Model\Table\RoundsTable&\Cake\ORM\Association\BelongsTo $Rounds
 * @property \App\Model\Table\SportsTable&\Cake\ORM\Association\BelongsTo $Sports
 *
 * @method \App\Model\Entity\Match4schedulingPattern newEmptyEntity()
 * @method \App\Model\Entity\Match4schedulingPattern newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern get($primaryKey, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class MatchschedulingPattern24Table extends Table
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

        $this->setTable('matchscheduling_pattern24');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Rounds', [
            'foreignKey' => 'round_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Sports', [
            'foreignKey' => 'sport_id',
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
            ->integer('placenumberTeam1')
            ->requirePresence('placenumberTeam1', 'create')
            ->notEmptyString('placenumberTeam1');

        $validator
            ->integer('placenumberTeam2')
            ->requirePresence('placenumberTeam2', 'create')
            ->notEmptyString('placenumberTeam2');

        $validator
            ->integer('placenumberRefereeTeam')
            ->allowEmptyString('placenumberRefereeTeam');

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
        $rules->add($rules->existsIn(['round_id'], 'Rounds'), ['errorField' => 'round_id']);
        $rules->add($rules->existsIn(['sport_id'], 'Sports'), ['errorField' => 'sport_id']);

        return $rules;
    }
}
