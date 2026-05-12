<?php

namespace Tests\Feature;

use App\Models\KeyValueStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KeyValueStoreModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_record_with_string_value(): void
    {
        $record = KeyValueStore::create([
            'key' => 'mykey',
            'value' => 'hello world',
        ]);

        $this->assertDatabaseHas('key_value_store', [
            'id' => $record->id,
            'key' => 'mykey',
        ]);
        $this->assertEquals('hello world', $record->value);
    }

    public function test_can_create_record_with_json_value(): void
    {
        $record = KeyValueStore::create([
            'key' => 'config',
            'value' => ['theme' => 'dark', 'lang' => 'en'],
        ]);

        $this->assertIsArray($record->value);
        $this->assertEquals('dark', $record->value['theme']);
        $this->assertEquals('en', $record->value['lang']);
    }

    public function test_value_is_cast_to_and_from_json(): void
    {
        KeyValueStore::create([
            'key' => 'nested',
            'value' => ['a' => ['b' => ['c' => 1]]],
        ]);

        $record = KeyValueStore::where('key', 'nested')->first();
        $this->assertEquals(['a' => ['b' => ['c' => 1]]], $record->value);
    }

    public function test_can_store_multiple_versions_of_same_key(): void
    {
        KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);
        KeyValueStore::create(['key' => 'mykey', 'value' => 'v2']);

        $this->assertEquals(2, KeyValueStore::where('key', 'mykey')->count());
    }

    public function test_can_get_latest_value_for_key(): void
    {
        KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);
        sleep(1);
        KeyValueStore::create(['key' => 'mykey', 'value' => 'v2']);

        $latest = KeyValueStore::where('key', 'mykey')
            ->orderByDesc('created_at')
            ->first();

        $this->assertEquals('v2', $latest->value);
    }

    public function test_can_get_value_at_specific_timestamp(): void
    {
        $queryTime = now()->subMinutes(30);

        $this->travelTo(now()->subHour(), function () {
            KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);
        });

        KeyValueStore::create(['key' => 'mykey', 'value' => 'v2']);

        $atTimestamp = KeyValueStore::where('key', 'mykey')
            ->where('created_at', '<=', $queryTime)
            ->orderByDesc('created_at')
            ->first();

        $this->assertEquals('v1', $atTimestamp->value);
    }

    public function test_returns_null_for_key_at_timestamp_before_any_data(): void
    {
        KeyValueStore::create(['key' => 'mykey', 'value' => 'v1']);

        $result = KeyValueStore::where('key', 'mykey')
            ->where('created_at', '<=', now()->subDay())
            ->orderByDesc('created_at')
            ->first();

        $this->assertNull($result);
    }

    public function test_different_keys_are_independent(): void
    {
        KeyValueStore::create(['key' => 'key1', 'value' => 'val1']);
        KeyValueStore::create(['key' => 'key2', 'value' => 'val2']);

        $key1 = KeyValueStore::where('key', 'key1')->first();
        $key2 = KeyValueStore::where('key', 'key2')->first();

        $this->assertEquals('val1', $key1->value);
        $this->assertEquals('val2', $key2->value);
    }
}
