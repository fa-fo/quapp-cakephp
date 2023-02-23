<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TeamsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TeamsTable Test Case
 */
class TeamsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TeamsTable
     */
    protected $Teams;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Teams',
        'app.GroupTeams',
        'app.Matches',
        'app.MatchEventLogs',
        'app.TeamYears',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Teams') ? [] : ['className' => TeamsTable::class];
        $this->Teams = $this->getTableLocator()->get('Teams', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Teams);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
