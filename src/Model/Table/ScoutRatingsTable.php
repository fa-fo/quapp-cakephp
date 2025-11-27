<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ScoutRatings Model
 *
 * @property \App\Model\Table\PushTokensTable&\Cake\ORM\Association\BelongsTo $PushTokens
 * @property \App\Model\Table\MatcheventLogsTable&\Cake\ORM\Association\BelongsTo $MatcheventLogs
 *
 * @method \App\Model\Entity\ScoutRating newEmptyEntity()
 * @method \App\Model\Entity\ScoutRating newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\ScoutRating> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ScoutRating get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ScoutRating findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ScoutRating patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\ScoutRating> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ScoutRating|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ScoutRating saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\ScoutRating>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ScoutRating>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ScoutRating>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ScoutRating> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ScoutRating>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ScoutRating>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ScoutRating>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ScoutRating> deleteManyOrFail(iterable $entities, array $options = [])
 */
class ScoutRatingsTable extends Table
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

        $this->setTable('scout_ratings');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('MatcheventLogs', [
            'foreignKey' => 'matchevent_log_id',
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
            ->integer('matchevent_log_id')
            ->notEmptyString('matchevent_log_id');

        $validator
            ->integer('points')
            ->allowEmptyString('points');

        $validator
            ->decimal('confirmed', 1)
            ->allowEmptyString('confirmed');

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
        $rules->add($rules->existsIn(['matchevent_log_id'], 'MatcheventLogs'), ['errorField' => 'matchevent_log_id']);

        return $rules;
    }
}
