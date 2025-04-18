<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PushTokenRatingsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PushTokenRatingsTable Test Case
 */
class PushTokenRatingsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PushTokenRatingsTable
     */
    protected $PushTokenRatings;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.PushTokenRatings',
        'app.Years',
        'app.PushTokens',
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
        $config = $this->getTableLocator()->exists('PushTokenRatings') ? [] : ['className' => PushTokenRatingsTable::class];
        $this->PushTokenRatings = $this->getTableLocator()->get('PushTokenRatings', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->PushTokenRatings);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\PushTokenRatingsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\PushTokenRatingsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
