<?php

namespace Controller;

use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\TestCase;

class AppControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public function testGet(): void
    {

        $this->configRequest([
            'headers' => ['Accept' => 'application/json']
        ]);
        $this->get('/matches/byId/1');

        $this->assertResponseOk(); // Check that the response was a 200
        $this->assertEquals('null', $this->_getBodyAsString());
        $this->assertHeader('Content-Type', 'application/json');


    }

}
