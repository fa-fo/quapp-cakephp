<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ScoutRatingsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ScoutRatingsTable Test Case
 */
class ScoutRatingsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ScoutRatingsTable
     */
    protected $ScoutRatings;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.ScoutRatings',
        'app.MatcheventLogs',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ScoutRatings') ? [] : ['className' => ScoutRatingsTable::class];
        $this->ScoutRatings = $this->getTableLocator()->get('ScoutRatings', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ScoutRatings);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\ScoutRatingsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\ScoutRatingsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
