<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RoundsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RoundsTable Test Case
 */
class RoundsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RoundsTable
     */
    protected $Rounds;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Rounds',
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
        $config = $this->getTableLocator()->exists('Rounds') ? [] : ['className' => RoundsTable::class];
        $this->Rounds = $this->getTableLocator()->get('Rounds', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Rounds);

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
}
