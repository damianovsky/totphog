---
layout: default
title: Getting Started
---

# Getting Started

This guide will help you get TOTPHog up and running quickly.

## Prerequisites

- Docker and Docker Compose (recommended)
- OR PHP 8.3+ with Composer (for manual installation)

## Installation

### Docker (Recommended)

The easiest way to run TOTPHog is using Docker:

```bash
docker run -d -p 8045:80 --name totphog damianovsky/totphog
```

Open http://localhost:8045 in your browser.

### Docker Compose

For a more configurable setup:

```bash
git clone https://github.com/yourusername/totphog.git
cd totphog
docker-compose up -d
```

#### Custom Port

You can change the port using environment variables:

```bash
TOTPHOG_PORT=9000 docker-compose up -d
```

### Manual Installation (Development)

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/totphog.git
   cd totphog
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Start the development server:
   ```bash
   php -S localhost:8045 -t public
   ```

## Persisting Data

By default, tokens are stored in `/var/www/html/var/tokens.json` inside the container. To persist data between container restarts:

```bash
docker run -d -p 8045:80 \
  -v totphog-data:/var/www/html/var \
  damianovsky/totphog
```

Or with Docker Compose, the `docker-compose.yml` already includes a volume configuration.

## First Steps

### Web Interface

1. Open http://localhost:8045
2. Click "Add Token" to add a new TOTP token
3. Enter the token name and secret (or paste an `otpauth://` URI)
4. View your codes in real-time

### API

Add a token via API:

```bash
curl -X POST http://localhost:8045/api/v1/tokens \
  -H "Content-Type: application/json" \
  -d '{"name": "My App", "secret": "JBSWY3DPEHPK3PXP"}'
```

Get the current code:

```bash
curl http://localhost:8045/api/v1/tokens/{id}/code
```

See the [API Reference](api.md) for complete documentation.
