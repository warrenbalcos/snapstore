# Snapstore

[![CI](https://github.com/warrenbalcos/snapstore/actions/workflows/ci.yml/badge.svg)](https://github.com/warrenbalcos/snapstore/actions/workflows/ci.yml)

A version-controlled key-value store with an HTTP API. Built with Laravel 13, PHP 8.4, PostgreSQL.

Every write creates a new version. Reads return the latest value or a historical value by timestamp.

**Live demo:** [https://snapstore-xvpe.onrender.com](https://snapstore-xvpe.onrender.com)

## Quick Start

```bash
# Clone and run
git clone https://github.com/warrenbalcos/snapstore.git
cd snapstore
docker compose up --build -d

# Verify
curl http://localhost:8000/health
# → {"status":"ok"}
```

## Running Tests

```bash
docker compose exec app php artisan test
```

CI runs automatically via GitHub Actions on every push to `master`.

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

## Deployment

### Render.com

The app is deployed to Render using a Docker runtime with nginx + php-fpm and a managed PostgreSQL database.

**Setup:**
1. Create a new **Web Service** on Render, connected to the GitHub repo
2. Set runtime to **Docker**
3. Create a **PostgreSQL** database (free tier)
4. Add the following environment variables:

| Variable | Value |
|----------|-------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:xxxxx` (generate with `php artisan key:generate`) |
| `SESSION_DRIVER` | `array` |
| `CACHE_STORE` | `array` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Render database hostname (just the hostname, not the full URL) |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | Database name from Render |
| `DB_USERNAME` | Database username from Render |
| `DB_PASSWORD` | Database password from Render |

Set **Health Check Path** to `/health`.

### Local Development

```bash
docker compose up -d
```

Uses PostgreSQL in Docker with the local `.env` file. The volume mount provides hot-reload.

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Language | PHP 8.4 |
| Framework | Laravel 13 |
| Database | PostgreSQL |
| Testing | PHPUnit |
| CI/CD | GitHub Actions |
| Deployment | Render.com (Docker) |
| Container | Docker + Docker Compose |
