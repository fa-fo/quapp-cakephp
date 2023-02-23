<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\GroupTeamsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\GroupTeamsTable Test Case
 */
class GroupTeamsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\GroupTeamsTable
     */
    protected $GroupTeams;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.GroupTeams',
        'app.Groups',
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
        $config = $this->getTableLocator()->exists('GroupTeams') ? [] : ['className' => GroupTeamsTable::class];
        $this->GroupTeams = $this->getTableLocator()->get('GroupTeams', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->GroupTeams);

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
