<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use App\Model\Table\MatcheventsTable;

/**
 * App\Model\Table\MatcheventsTable Test Case
 */
class MatcheventsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MatcheventsTable
     */
    protected $Matchevents;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Matchevents',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Matchevents') ? [] : ['className' => MatcheventsTable::class];
        $this->Matchevents = $this->getTableLocator()->get('Matchevents', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Matchevents);

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
