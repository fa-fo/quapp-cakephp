<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use App\Model\Table\MatchesTable;

/**
 * App\Model\Table\MatchesTable Test Case
 */
class MatchesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MatchesTable
     */
    protected MatchesTable $Matches;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Matches',
        'app.Groups',
        'app.Rounds',
        'app.Sports',
        'app.Teams1',
        'app.Teams2',
        'app.Teams3',
        'app.Teams4',
        'app.MatcheventLogs',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Matches') ? [] : ['className' => MatchesTable::class];
        $this->Matches = $this->getTableLocator()->get('Matches', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Matches);

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
