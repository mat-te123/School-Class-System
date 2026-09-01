<?php

namespace Tests\Feature;

use Tests\TestCase;

class ServerClockControllerTest extends TestCase
{
    public function test_can_get_server_clock_time(): void
    {
        $response = $this->getJson('/server-clock');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'timestamp',
                    'timestamp_ms',
                    'datetime',
                    'iso8601',
                    'timezone',
                    'offset_seconds',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertIsInt($response->json('data.timestamp'));
        $this->assertIsInt($response->json('data.timestamp_ms'));
    }

    public function test_can_get_server_time_alias_route(): void
    {
        $response = $this->getJson('/server-time');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
