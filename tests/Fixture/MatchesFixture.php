<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MatchesFixture
 */
class MatchesFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'group_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'round_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'sport_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'team1_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'team2_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'refereeTeam_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'refereePIN' => ['type' => 'string', 'length' => 5, 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'resultTrend' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'resultGoals1' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'resultGoals2' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'resultAdmin' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'remarks' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'canceled' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        '_indexes' => [
            'fk_round_id' => ['type' => 'index', 'columns' => ['round_id'], 'length' => []],
            'fk_sport_id' => ['type' => 'index', 'columns' => ['sport_id'], 'length' => []],
            'fk_team1_id' => ['type' => 'index', 'columns' => ['team1_id'], 'length' => []],
            'fk_team2_id' => ['type' => 'index', 'columns' => ['team2_id'], 'length' => []],
            'fk_refereeTeam_id' => ['type' => 'index', 'columns' => ['refereeTeam_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'unique_groupId_roundId_sport_id' => ['type' => 'unique', 'columns' => ['group_id', 'round_id', 'sport_id'], 'length' => []],
            'unique_groupId_roundId_referee' => ['type' => 'unique', 'columns' => ['group_id', 'round_id', 'refereeTeam_id'], 'length' => []],
            'unique_refereePIN' => ['type' => 'unique', 'columns' => ['refereePIN'], 'length' => []],
            'fk_team2_id' => ['type' => 'foreign', 'columns' => ['team2_id'], 'references' => ['teams', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
            'fk_team1_id' => ['type' => 'foreign', 'columns' => ['team1_id'], 'references' => ['teams', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
            'fk_sport_id' => ['type' => 'foreign', 'columns' => ['sport_id'], 'references' => ['sports', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
            'fk_round_id' => ['type' => 'foreign', 'columns' => ['round_id'], 'references' => ['rounds', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
            'fk_refereeTeam_id' => ['type' => 'foreign', 'columns' => ['refereeTeam_id'], 'references' => ['teams', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
            'fk2_group_id' => ['type' => 'foreign', 'columns' => ['group_id'], 'references' => ['groups', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
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
                'group_id' => 1,
                'round_id' => 1,
                'sport_id' => 1,
                'team1_id' => 1,
                'team2_id' => 1,
                'refereeTeam_id' => 1,
                'refereePIN' => 'Lor',
                'resultTrend' => 1,
                'resultGoals1' => 1,
                'resultGoals2' => 1,
                'resultAdmin' => 1,
                'remarks' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'canceled' => 1,
            ],
        ];
        parent::init();
    }
}
