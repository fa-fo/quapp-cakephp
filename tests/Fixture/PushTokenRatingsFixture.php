<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PushTokenRatingsFixture
 */
class PushTokenRatingsFixture extends TestFixture
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
                'year_id' => 1,
                'push_token_id' => 1,
                'matchevent_log_id' => 1,
                'points_expected' => 1,
                'points_confirmed' => 1,
            ],
        ];
        parent::init();
    }
}
