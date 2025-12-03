# Configuration

TOTPHog is designed to work out of the box with minimal configuration. This document covers all available configuration options.

## Environment Variables

### Docker Compose

| Variable | Default | Description |
|----------|---------|-------------|
| `TOTPHOG_PORT` | `8045` | Host port for the web interface |

Example:

```bash
TOTPHOG_PORT=9000 docker-compose up -d
```

## Storage

### Token Storage

Tokens are stored in a JSON file at `var/tokens.json`. This file is automatically created on first use.

**Storage format:**

```json
{
  "uuid-here": {
    "id": "uuid-here",
    "name": "GitHub",
    "secret": "JBSWY3DPEHPK3PXP",
    "issuer": "GitHub",
    "digits": 6,
    "period": 30,
    "algorithm": "sha1",
    "created_at": "2024-01-15T10:30:00+00:00"
  }
}
```

### Persisting Data

#### Docker

Mount a volume to persist tokens:

```bash
docker run -d -p 8045:80 \
  -v totphog-data:/var/www/html/var \
  damianovsky/totphog
```

#### Docker Compose

The provided `docker-compose.yml` includes a named volume for persistence.

## TOTP Parameters

When creating tokens, you can configure:

| Parameter | Default | Description |
|-----------|---------|-------------|
| `name` | (required) | Display name for the token |
| `secret` | (required) | Base32-encoded secret key |
| `issuer` | `TOTPHog` | Service/application name |
| `digits` | `6` | Number of digits in the code |
| `period` | `30` | Code validity period (seconds) |
| `algorithm` | `sha1` | Hash algorithm: sha1, sha256, sha512 |

Example:

```bash
curl -X POST http://localhost:8045/api/v1/tokens \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Account",
    "secret": "JBSWY3DPEHPK3PXP",
    "issuer": "MyService",
    "digits": 6,
    "period": 30,
    "algorithm": "sha1"
  }'
```
