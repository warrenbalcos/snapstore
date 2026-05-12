<?php

namespace App\Http\Controllers;

use App\Models\KeyValueStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeyValueStoreController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        if (empty($data)) {
            return response()->json(['error' => 'Request body cannot be empty'], 400);
        }

        $key = array_key_first($data);
        $value = $data[$key];

        if ($key === '' || $key === null) {
            return response()->json(['error' => 'Key cannot be empty'], 400);
        }

        $record = KeyValueStore::create([
            'key' => $key,
            'value' => $value,
        ]);

        return response()->json([
            'key' => $record->key,
            'value' => $record->value,
            'timestamp' => $record->created_at->timestamp,
        ]);
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $timestamp = $request->query('timestamp');

        if ($timestamp !== null) {
            if (!is_numeric($timestamp)) {
                return response()->json(['error' => 'Timestamp must be a valid number'], 400);
            }

            $datetime = now()->setTimestamp((int) $timestamp);

            $record = KeyValueStore::where('key', $key)
                ->where('created_at', '<=', $datetime)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();
        } else {
            $record = KeyValueStore::where('key', $key)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();
        }

        if (!$record) {
            return response()->json(['error' => 'Key not found'], 404);
        }

        return response()->json(['value' => $record->value]);
    }

    public function index(): JsonResponse
    {
        $keys = KeyValueStore::query()
            ->select('key')
            ->distinct()
            ->pluck('key');

        $records = $keys->map(function (string $key) {
            $record = KeyValueStore::where('key', $key)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();

            return [
                'key' => $record->key,
                'value' => $record->value,
                'created_at' => $record->created_at->timestamp,
            ];
        });

        return response()->json($records);
    }
}
