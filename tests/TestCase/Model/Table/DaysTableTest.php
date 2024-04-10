<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\DaysTable;
use Cake\TestSuite\Fixture\FixtureStrategyInterface;
use Cake\TestSuite\Fixture\TransactionStrategy;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\DaysTable Test Case
 */
class DaysTableTest extends TestCase
{
    /**
     * Create the fixtures strategy used for this test case.
     * You can use a base class/trait to change multiple classes.
     */
    protected function getFixtureStrategy(): FixtureStrategyInterface
    {
        echo 'test';
        return new TransactionStrategy();
    }

    /**
     * Test subject
     *
     * @var \App\Model\Table\DaysTable
     */
    protected $Days;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Days',
        'app.Groups',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Days') ? [] : ['className' => DaysTable::class];
        $this->Days = $this->getTableLocator()->get('Days', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Days);

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
