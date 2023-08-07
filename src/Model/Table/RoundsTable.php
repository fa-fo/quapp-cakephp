<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Rounds Model
 *
 * @property \App\Model\Table\MatchesTable&\Cake\ORM\Association\HasMany $Matches
 * @property \App\Model\Table\MatchschedulingPattern16Table&\Cake\ORM\Association\HasMany $MatchschedulingPattern16
 *
 * @method \App\Model\Entity\Round newEmptyEntity()
 * @method \App\Model\Entity\Round newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Round[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Round get($primaryKey, $options = [])
 * @method \App\Model\Entity\Round findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Round patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Round[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Round|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Round saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Round[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Round[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Round[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Round[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class RoundsTable extends Table
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

        $this->setTable('rounds');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->hasMany('Matches', [
            'foreignKey' => 'round_id',
        ]);
        $this->hasMany('MatchschedulingPattern16', [
            'foreignKey' => 'round_id',
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
            ->time('timeStartDay1')
            ->requirePresence('timeStartDay1', 'create')
            ->notEmptyTime('timeStartDay1');

        $validator
            ->time('timeStartDay2')
            ->requirePresence('timeStartDay2', 'create')
            ->notEmptyTime('timeStartDay2');

        $validator
            ->integer('autoUpdateResults')
            ->notEmptyString('autoUpdateResults');

        return $validator;
    }
}
