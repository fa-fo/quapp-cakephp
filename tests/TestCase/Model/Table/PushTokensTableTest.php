<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PushTokensTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PushTokensTable Test Case
 */
class PushTokensTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PushTokensTable
     */
    protected $PushTokens;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.PushTokens',
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
        $config = $this->getTableLocator()->exists('PushTokens') ? [] : ['className' => PushTokensTable::class];
        $this->PushTokens = $this->getTableLocator()->get('PushTokens', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->PushTokens);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\PushTokensTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\PushTokensTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
