<?php

namespace Tests\Feature;

use App\Models\KeyValueStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeyValueStoreApiTest extends TestCase
{
    use RefreshDatabase;

    // --- Core behavior ---

    public function test_store_and_retrieve_value(): void
    {
        $this->postJson('/object', ['mykey' => 'value1'])->assertStatus(200);

        $this->getJson('/object/mykey')
            ->assertStatus(200)
            ->assertJson(['value' => 'value1']);
    }

    public function test_store_json_value_and_retrieve(): void
    {
        $this->postJson('/object', ['config' => ['theme' => 'dark', 'lang' => 'en']]);

        $this->getJson('/object/config')
            ->assertJson(['value' => ['theme' => 'dark', 'lang' => 'en']]);
    }

    public function test_latest_value_overwrites_previous(): void
    {
        $this->postJson('/object', ['mykey' => 'v1']);
        $this->postJson('/object', ['mykey' => 'v2']);

        $this->getJson('/object/mykey')
            ->assertJson(['value' => 'v2']);
    }

    public function test_historical_lookup_returns_older_version(): void
    {
        $this->travelTo(now()->subHour(), function () {
            KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);
        });

        $queryTime = now()->subMinutes(30)->timestamp;

        KeyValueStore::create(['key' => 'mykey', 'value' => 'v2']);

        $this->getJson('/object/mykey?timestamp=' . $queryTime)
            ->assertJson(['value' => 'v1']);
    }

    public function test_get_all_returns_latest_version_per_key(): void
    {
        KeyValueStore::create(['key' => 'key1', 'value' => 'v1']);
        KeyValueStore::create(['key' => 'key1', 'value' => 'v2']);
        KeyValueStore::create(['key' => 'key2', 'value' => 'only']);

        $response = $this->getJson('/object/get_all_records');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['key' => 'key1', 'value' => 'v2'])
            ->assertJsonFragment(['key' => 'key2', 'value' => 'only']);
    }

    public function test_timestamp_exact_match_returns_that_version(): void
    {
        $record = KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);

        $this->getJson('/object/mykey?timestamp=' . $record->created_at->timestamp)
            ->assertJson(['value' => 'v1']);
    }

    // --- Error handling ---

    public function test_missing_key_returns_404(): void
    {
        $this->getJson('/object/nonexistent')->assertStatus(404);
    }

    public function test_timestamp_before_any_data_returns_404(): void
    {
        KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);

        $this->getJson('/object/mykey?timestamp=' . now()->subDay()->timestamp)
            ->assertStatus(404);
    }

    public function test_empty_body_returns_400(): void
    {
        $this->postJson('/object', [])->assertStatus(400);
    }

    public function test_empty_key_returns_400(): void
    {
        $this->postJson('/object', ['' => 'value'])->assertStatus(400);
    }

    public function test_invalid_timestamp_returns_400(): void
    {
        $this->getJson('/object/mykey?timestamp=not-a-number')->assertStatus(400);
    }
}
