<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use App\Model\Table\MatcheventLogsTable;

/**
 * App\Model\Table\MatcheventLogsTable Test Case
 */
class MatcheventLogsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MatcheventLogsTable
     */
    protected $MatcheventLogs;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.MatcheventLogs',
        'app.Matches',
        'app.Matchevents',
        'app.Teams',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('MatcheventLogs') ? [] : ['className' => MatcheventLogsTable::class];
        $this->MatcheventLogs = $this->getTableLocator()->get('MatcheventLogs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->MatcheventLogs);

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
