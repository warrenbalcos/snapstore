<?php

namespace Tests\Feature;

use App\Models\KeyValueStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeyValueStoreApiTest extends TestCase
{
    use RefreshDatabase;

    // --- POST /object ---

    public function test_post_stores_key_value_pair(): void
    {
        $response = $this->postJson('/object', ['mykey' => 'value1']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['key', 'value', 'timestamp']);
        $response->assertJson(['key' => 'mykey', 'value' => 'value1']);
    }

    public function test_post_stores_json_object_value(): void
    {
        $response = $this->postJson('/object', ['config' => ['theme' => 'dark']]);

        $response->assertStatus(200);
        $response->assertJson(['key' => 'config', 'value' => ['theme' => 'dark']]);
    }

    public function test_post_with_empty_body_returns_error(): void
    {
        $response = $this->postJson('/object', []);

        $response->assertStatus(400);
    }

    public function test_post_with_empty_key_returns_error(): void
    {
        $response = $this->postJson('/object', ['' => 'value1']);

        $response->assertStatus(400);
    }

    public function test_post_creates_record_in_database(): void
    {
        $this->postJson('/object', ['mykey' => 'value1']);

        $this->assertDatabaseHas('key_value_store', ['key' => 'mykey']);
    }

    public function test_post_multiple_versions_creates_multiple_rows(): void
    {
        $this->postJson('/object', ['mykey' => 'v1']);
        $this->postJson('/object', ['mykey' => 'v2']);

        $this->assertEquals(2, KeyValueStore::where('key', 'mykey')->count());
    }

    // --- GET /object/{key} ---

    public function test_get_returns_latest_value(): void
    {
        KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);
        KeyValueStore::create(['key' => 'mykey', 'value' => 'v2']);

        $response = $this->getJson('/object/mykey');

        $response->assertStatus(200);
        $response->assertJson(['value' => 'v2']);
    }

    public function test_get_returns_json_object_value(): void
    {
        KeyValueStore::create(['key' => 'config', 'value' => ['theme' => 'dark']]);

        $response = $this->getJson('/object/config');

        $response->assertStatus(200);
        $response->assertJson(['value' => ['theme' => 'dark']]);
    }

    public function test_get_nonexistent_key_returns_404(): void
    {
        $response = $this->getJson('/object/nonexistent');

        $response->assertStatus(404);
    }

    // --- GET /object/{key}?timestamp=X ---

    public function test_get_with_timestamp_returns_historical_value(): void
    {
        $this->travelTo(now()->subHour(), function () {
            KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);
        });

        $queryTime = now()->subMinutes(30)->timestamp;

        KeyValueStore::create(['key' => 'mykey', 'value' => 'v2']);

        $response = $this->getJson('/object/mykey?timestamp=' . $queryTime);

        $response->assertStatus(200);
        $response->assertJson(['value' => 'v1']);
    }

    public function test_get_with_timestamp_before_any_data_returns_404(): void
    {
        KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);

        $oldTimestamp = now()->subDay()->timestamp;

        $response = $this->getJson('/object/mykey?timestamp=' . $oldTimestamp);

        $response->assertStatus(404);
    }

    public function test_get_with_timestamp_exactly_at_creation_returns_value(): void
    {
        $record = KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);

        $response = $this->getJson('/object/mykey?timestamp=' . $record->created_at->timestamp);

        $response->assertStatus(200);
        $response->assertJson(['value' => 'v1']);
    }

    public function test_get_with_invalid_timestamp_returns_400(): void
    {
        $response = $this->getJson('/object/mykey?timestamp=not-a-number');

        $response->assertStatus(400);
    }

    // --- GET /object/get_all_records ---

    public function test_get_all_returns_all_current_values(): void
    {
        KeyValueStore::create(['key' => 'key1', 'value' => 'val1']);
        KeyValueStore::create(['key' => 'key2', 'value' => 'val2']);

        $response = $this->getJson('/object/get_all_records');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['key' => 'key1', 'value' => 'val1']);
        $response->assertJsonFragment(['key' => 'key2', 'value' => 'val2']);
    }

    public function test_get_all_returns_latest_version_per_key(): void
    {
        KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);
        KeyValueStore::create(['key' => 'mykey', 'value' => 'v2']);

        $response = $this->getJson('/object/get_all_records');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['key' => 'mykey', 'value' => 'v2']);
    }

    public function test_get_all_returns_empty_array_when_no_data(): void
    {
        $response = $this->getJson('/object/get_all_records');

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }
}
