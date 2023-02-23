<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RankingpointsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RankingpointsTable Test Case
 */
class RankingpointsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RankingpointsTable
     */
    protected $Rankingpoints;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Rankingpoints',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Rankingpoints') ? [] : ['className' => RankingpointsTable::class];
        $this->Rankingpoints = $this->getTableLocator()->get('Rankingpoints', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Rankingpoints);

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
