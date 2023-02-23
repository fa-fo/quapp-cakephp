<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MatcheventLogs Model
 *
 * @property \App\Model\Table\MatchesTable&\Cake\ORM\Association\BelongsTo $Matches
 * @property \App\Model\Table\MatcheventsTable&\Cake\ORM\Association\BelongsTo $Matchevents
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $Teams
 *
 * @method \App\Model\Entity\MatcheventLog newEmptyEntity()
 * @method \App\Model\Entity\MatcheventLog newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\MatcheventLog[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MatcheventLog get($primaryKey, $options = [])
 * @method \App\Model\Entity\MatcheventLog findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\MatcheventLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\MatcheventLog[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MatcheventLog|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\MatcheventLog saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\MatcheventLog[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\MatcheventLog[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\MatcheventLog[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\MatcheventLog[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class MatcheventLogsTable extends Table
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

        $this->setTable('matchevent_logs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Matches', [
            'foreignKey' => 'match_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Matchevents', [
            'foreignKey' => 'matchEvent_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Teams', [
            'foreignKey' => 'team_id',
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
            ->integer('playerNumber')
            ->allowEmptyString('playerNumber');

        $validator
            ->scalar('playerName')
            ->maxLength('playerName', 64)
            ->allowEmptyString('playerName');

        $validator
            ->dateTime('datetimeSent');

        $validator
            ->dateTime('datetime')
            ->notEmptyDateTime('datetime');

        $validator
            ->integer('canceled')
            ->notEmptyString('canceled');

        $validator
            ->dateTime('cancelTime')
            ->allowEmptyDateTime('cancelTime');

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
        $rules->add($rules->existsIn(['match_id'], 'Matches'), ['errorField' => 'match_id']);
        $rules->add($rules->existsIn(['matchEvent_id'], 'Matchevents'), ['errorField' => 'matchEvent_id']);
        $rules->add($rules->existsIn(['team_id'], 'Teams'), ['errorField' => 'team_id']);

        return $rules;
    }
}
