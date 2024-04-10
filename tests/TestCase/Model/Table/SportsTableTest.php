<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use App\Model\Table\SportsTable;

/**
 * App\Model\Table\SportsTable Test Case
 */
class SportsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SportsTable
     */
    protected $Sports;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Sports',
        'app.Matches',
        'app.MatchschedulingPattern16',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Sports') ? [] : ['className' => SportsTable::class];
        $this->Sports = $this->getTableLocator()->get('Sports', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Sports);

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
