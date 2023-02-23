<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MatchschedulingPattern16Table;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MatchschedulingPattern16Table Test Case
 */
class MatchschedulingPattern16TableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MatchschedulingPattern16Table
     */
    protected $MatchschedulingPattern16;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.MatchschedulingPattern16',
        'app.Rounds',
        'app.Sports',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('MatchschedulingPattern16') ? [] : ['className' => MatchschedulingPattern16Table::class];
        $this->MatchschedulingPattern16 = $this->getTableLocator()->get('MatchschedulingPattern16', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->MatchschedulingPattern16);

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
