# Snapstore

A version-controlled key-value store with an HTTP API. Built with Laravel 13, PHP 8.4, MySQL 8.

Every write creates a new version. Reads return the latest value or a historical value by timestamp.

## Quick Start

```bash
# Clone and run
git clone https://github.com/YOUR_USER/snapstore.git
cd snapstore
docker compose up --build -d

# Run migrations
docker compose exec app php artisan migrate

# Verify
curl http://localhost:8000/health
# → {"status":"ok"}
```

## Running Tests

```bash
docker compose exec app php artisan test
```

## API Reference

### POST /object

Store a key-value pair. Creates a new version.

```bash
curl -X POST http://localhost:8000/object \
  -H "Content-Type: application/json" \
  -d '{"mykey":"value1"}'
```

**Response:**
```json
{
  "key": "mykey",
  "value": "value1",
  "timestamp": 1778577846
}
```

The value can be any JSON type — string, object, array, number, boolean.

### GET /object/{key}

Get the latest value for a key.

```bash
curl http://localhost:8000/object/mykey
```

**Response:** `{"value": "value1"}` or `404` if key doesn't exist.

### GET /object/{key}?timestamp={unix_timestamp}

Get the value of a key at a specific point in time.

```bash
curl "http://localhost:8000/object/mykey?timestamp=1778577846"
```

**Response:** `{"value": "value1"}` or `404` if no version existed at that time.

### GET /object/get_all_records

List the current (latest) value of every key.

```bash
curl http://localhost:8000/object/get_all_records
```

**Response:**
```json
[
  {"key": "mykey", "value": "value2", "created_at": 1778577846},
  {"key": "config", "value": {"theme": "dark"}, "created_at": 1778577847}
]
```

## Example Session

```bash
# Store value1 at 6:00pm
curl -X POST http://localhost:8000/object -H "Content-Type: application/json" -d '{"mykey":"value1"}'

# Get latest → value1
curl http://localhost:8000/object/mykey

# Store value2 at 6:05pm
curl -X POST http://localhost:8000/object -H "Content-Type: application/json" -d '{"mykey":"value2"}'

# Get latest → value2
curl http://localhost:8000/object/mykey

# Get at 6:03pm timestamp → value1
curl "http://localhost:8000/object/mykey?timestamp=1440568980"

# Get all records
curl http://localhost:8000/object/get_all_records
```

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Language | PHP 8.4 |
| Framework | Laravel 13 |
| Database | MySQL 8 |
| Testing | PHPUnit |
| Container | Docker + Docker Compose |
