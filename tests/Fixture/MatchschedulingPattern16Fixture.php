<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MatchschedulingPattern16Fixture
 */
class MatchschedulingPattern16Fixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'matchscheduling_pattern16';
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'round_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'placenumberTeam1' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'placenumberTeam2' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'placenumberRefereeTeam' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'sport_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        '_indexes' => [
            'fk_round_id2' => ['type' => 'index', 'columns' => ['round_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'unique_sportId_round_id' => ['type' => 'unique', 'columns' => ['sport_id', 'round_id'], 'length' => []],
            'unique_referee_round_id' => ['type' => 'unique', 'columns' => ['placenumberRefereeTeam', 'round_id'], 'length' => []],
            'fk_sport_id2' => ['type' => 'foreign', 'columns' => ['sport_id'], 'references' => ['sports', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
            'fk_round_id2' => ['type' => 'foreign', 'columns' => ['round_id'], 'references' => ['rounds', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // phpcs:enable
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'round_id' => 1,
                'placenumberTeam1' => 1,
                'placenumberTeam2' => 1,
                'placenumberRefereeTeam' => 1,
                'sport_id' => 1,
            ],
        ];
        parent::init();
    }
}
