<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use App\Model\Table\TeamYearsTable;

/**
 * App\Model\Table\TeamYearsTable Test Case
 */
class TeamYearsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TeamYearsTable
     */
    protected $TeamYears;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.TeamYears',
        'app.Years',
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
        $config = $this->getTableLocator()->exists('TeamYears') ? [] : ['className' => TeamYearsTable::class];
        $this->TeamYears = $this->getTableLocator()->get('TeamYears', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->TeamYears);

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
