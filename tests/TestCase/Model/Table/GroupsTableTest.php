<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use App\Model\Table\GroupsTable;

/**
 * App\Model\Table\GroupsTable Test Case
 */
class GroupsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\GroupsTable
     */
    protected $Groups;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Groups',
        'app.Years',
        'app.Days',
        'app.GroupTeams',
        'app.Matches',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Groups') ? [] : ['className' => GroupsTable::class];
        $this->Groups = $this->getTableLocator()->get('Groups', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Groups);

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
