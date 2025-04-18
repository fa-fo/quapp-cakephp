<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PushTokenRatings Model
 *
 * @property \App\Model\Table\YearsTable&\Cake\ORM\Association\BelongsTo $Years
 * @property \App\Model\Table\PushTokensTable&\Cake\ORM\Association\BelongsTo $PushTokens
 * @property \App\Model\Table\MatcheventLogsTable&\Cake\ORM\Association\BelongsTo $MatcheventLogs
 *
 * @method \App\Model\Entity\PushTokenRating newEmptyEntity()
 * @method \App\Model\Entity\PushTokenRating newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\PushTokenRating> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PushTokenRating get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\PushTokenRating findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\PushTokenRating patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\PushTokenRating> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\PushTokenRating|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\PushTokenRating saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\PushTokenRating>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PushTokenRating>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PushTokenRating>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PushTokenRating> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PushTokenRating>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PushTokenRating>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PushTokenRating>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PushTokenRating> deleteManyOrFail(iterable $entities, array $options = [])
 */
class PushTokenRatingsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('push_token_ratings');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Years', [
            'foreignKey' => 'year_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('PushTokens', [
            'foreignKey' => 'push_token_id',
            'joinType' => 'INNER',
        ]);
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
            ->integer('year_id')
            ->notEmptyString('year_id');

        $validator
            ->integer('push_token_id')
            ->notEmptyString('push_token_id');

        $validator
            ->integer('matchevent_log_id')
            ->notEmptyString('matchevent_log_id');

        $validator
            ->integer('points_expected')
            ->allowEmptyString('points_expected');

        $validator
            ->integer('points_confirmed')
            ->allowEmptyString('points_confirmed');

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
        $rules->add($rules->existsIn(['push_token_id'], 'PushTokens'), ['errorField' => 'push_token_id']);
        $rules->add($rules->existsIn(['matchevent_log_id'], 'MatcheventLogs'), ['errorField' => 'matchevent_log_id']);

        return $rules;
    }
}
