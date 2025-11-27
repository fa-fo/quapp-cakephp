<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ScoutRatingsFixture
 */
class ScoutRatingsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'matchevent_log_id' => 1,
                'points' => 1,
                'confirmed' => 1,
            ],
        ];
        parent::init();
    }
}
