---
layout: default
title: Architecture
---

# Architecture

This document describes the technical architecture of TOTPHog.

## Tech Stack

| Component | Technology |
|-----------|------------|
| Language | PHP 8.3 |
| Framework | Symfony 7.0 (micro-kernel) |
| TOTP Library | [spomky-labs/otphp](https://github.com/Spomky-Labs/otphp) |
| QR Code Library | [endroid/qr-code](https://github.com/endroid/qr-code) |
| Frontend | Bootstrap 5, Vanilla JavaScript |
| Server | Apache (Docker) |
| Container | Docker with Alpine-based PHP image |

## Project Structure

```
totphog/
├── config/                 # Symfony configuration
│   ├── bundles.php        # Registered bundles
│   ├── routes.yaml        # Route configuration
│   ├── services.yaml      # Service container configuration
│   └── packages/          # Package-specific config
├── docs/                   # Documentation
├── public/                 # Web root
│   └── index.php          # Front controller
├── src/                    # Application source code
│   ├── Controller/        # HTTP controllers
│   │   ├── ApiController.php   # REST API
│   │   └── WebController.php   # Web interface
│   ├── Service/           # Business logic
│   │   └── TotpStorage.php     # Token storage service
│   └── Kernel.php         # Symfony kernel
├── templates/              # Twig templates
├── var/                    # Runtime files (cache, logs, storage)
├── docker-compose.yml     # Docker Compose configuration
├── Dockerfile             # Container definition
└── composer.json          # PHP dependencies
```

## Components

### Controllers

#### ApiController (`/api/v1/*`)

REST API for programmatic access. All endpoints return JSON responses with a consistent structure:

```json
{
  "success": true|false,
  "data": {...},
  "error": "Error message (if success=false)"
}
```

#### WebController (`/`)

Serves the web interface. Renders Twig templates with current token data.

### Services

#### TotpStorage

Manages TOTP tokens with file-based persistence:

- **Storage**: JSON file at `var/tokens.json`
- **Token IDs**: UUID v4 (RFC 4122)
- **TOTP Generation**: Uses `spomky-labs/otphp` library

Key methods:
- `add()` - Create token with manual parameters
- `addFromUri()` - Create token from `otpauth://` URI
- `generateCode()` - Get current TOTP code for a token
- `generateAllCodes()` - Get all tokens with current codes
- `getProvisioningUri()` - Get `otpauth://` URI for QR generation

### TOTP Algorithm

TOTPHog implements RFC 6238 (TOTP) via the `spomky-labs/otphp` library:

1. Takes a shared secret (Base32 encoded)
2. Combines it with current Unix timestamp
3. Applies HMAC with configured algorithm (SHA1/SHA256/SHA512)
4. Truncates result to configured number of digits

Default parameters (compatible with Google Authenticator):
- Period: 30 seconds
- Digits: 6
- Algorithm: SHA1

## Docker Setup

The Docker image is based on `php:8.3-apache` and includes:

- PHP 8.3 with required extensions
- Apache with mod_rewrite
- Composer for dependency management

The container exposes port 80 internally, mapped to the host port via Docker configuration.

## Security Considerations

⚠️ **Development Only** - TOTPHog is designed for development/testing:

- No authentication or authorization
- Secrets stored in plain text JSON
- No encryption at rest
- No rate limiting
- No audit logging

**Never use TOTPHog in production environments.**
