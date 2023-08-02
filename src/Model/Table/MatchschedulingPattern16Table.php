<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MatchschedulingPattern16 Model
 *
 * @property \App\Model\Table\RoundsTable&\Cake\ORM\Association\BelongsTo $Rounds
 * @property \App\Model\Table\SportsTable&\Cake\ORM\Association\BelongsTo $Sports
 *
 * @method \App\Model\Entity\Match4schedulingPattern16 newEmptyEntity()
 * @method \App\Model\Entity\Match4schedulingPattern16 newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern16[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern16 get($primaryKey, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern16 findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern16 patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern16[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern16|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern16 saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern16[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern16[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern16[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Match4schedulingPattern16[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class MatchschedulingPattern16Table extends Table
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

        $this->setTable('matchscheduling_pattern16');
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
            ->requirePresence('placenumberRefereeTeam', 'create')
            ->notEmptyString('placenumberRefereeTeam');

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
