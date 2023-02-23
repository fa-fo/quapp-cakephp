<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\YearsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\YearsTable Test Case
 */
class YearsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\YearsTable
     */
    protected $Years;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Years',
        'app.Days',
        'app.Groups',
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
        $config = $this->getTableLocator()->exists('Years') ? [] : ['className' => YearsTable::class];
        $this->Years = $this->getTableLocator()->get('Years', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Years);

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
