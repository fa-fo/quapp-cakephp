<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * GroupsFixture
 */
class GroupsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'year_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'day_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'name' => ['type' => 'string', 'length' => 16, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'teamsCount' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => '16', 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        '_indexes' => [
            'fk_day_id' => ['type' => 'index', 'columns' => ['day_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'unique_yearId_dayId_name' => ['type' => 'unique', 'columns' => ['year_id', 'day_id', 'name'], 'length' => []],
            'fk_year_id' => ['type' => 'foreign', 'columns' => ['year_id'], 'references' => ['years', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
            'fk_day_id' => ['type' => 'foreign', 'columns' => ['day_id'], 'references' => ['days', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
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
                'year_id' => 1,
                'day_id' => 1,
                'name' => 'Lorem ipsum do',
                'teamsCount' => 1,
            ],
        ];
        parent::init();
    }
}
