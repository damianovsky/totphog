# API Reference

TOTPHog provides a REST API for programmatic access to TOTP tokens and codes.

**Base URL:** `http://localhost:8045/api/v1`

---

## Interactive API Documentation

<swagger-ui src="openapi.yaml"/>

---

## Response Format

All API responses follow a consistent JSON structure:

```json
{
  "success": true,
  "data": { ... }
}
```

Error responses:

```json
{
  "success": false,
  "error": "Error message"
}
```

## Endpoints

### Health Check

Check if the service is running.

```
GET /health
```

**Response:**

```json
{
  "status": "ok",
  "service": "TOTPHog",
  "version": "1.0.0",
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

---

### List Tokens

Get all stored TOTP tokens.

```
GET /tokens
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "GitHub",
      "secret": "JBSWY3DPEHPK3PXP",
      "issuer": "GitHub",
      "digits": 6,
      "period": 30,
      "algorithm": "sha1",
      "created_at": "2024-01-15T10:30:00+00:00"
    }
  ],
  "count": 1
}
```

---

### Create Token

Create a new TOTP token. Supports two methods:

#### Method 1: From OTPAuth URI

```
POST /tokens
Content-Type: application/json

{
  "uri": "otpauth://totp/GitHub:user@example.com?secret=JBSWY3DPEHPK3PXP&issuer=GitHub"
}
```

#### Method 2: Manual Parameters

```
POST /tokens
Content-Type: application/json

{
  "name": "GitHub",
  "secret": "JBSWY3DPEHPK3PXP",
  "issuer": "GitHub",
  "digits": 6,
  "period": 30,
  "algorithm": "sha1"
}
```

**Parameters:**

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `uri` | string | No* | - | OTPAuth URI (from QR code) |
| `name` | string | No* | - | Token display name |
| `secret` | string | No* | - | Base32-encoded secret |
| `issuer` | string | No | `TOTPHog` | Service name |
| `digits` | integer | No | `6` | Code length (6 or 8) |
| `period` | integer | No | `30` | Validity period (seconds) |
| `algorithm` | string | No | `sha1` | Hash algorithm |

*Either `uri` OR `name`+`secret` must be provided.

**Response (201 Created):**

```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
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

---

### Get Token

Get a single token by ID.

```
GET /tokens/{id}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
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

---

### Delete Token

Delete a token by ID.

```
DELETE /tokens/{id}
```

**Response:**

```json
{
  "success": true,
  "message": "Token deleted"
}
```

---

### Delete All Tokens

Delete all stored tokens.

```
DELETE /tokens
```

**Response:**

```json
{
  "success": true,
  "message": "Deleted 5 tokens",
  "deleted_count": 5
}
```

---

### Get Current Code

Get the current TOTP code for a token.

```
GET /tokens/{id}/code
```

**Response:**

```json
{
  "success": true,
  "data": {
    "code": "123456",
    "remaining_seconds": 15,
    "period": 30,
    "generated_at": "2024-01-15T10:30:00+00:00"
  }
}
```

---

### Get All Codes

Get current codes for all tokens.

```
GET /codes
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "GitHub",
      "secret": "JBSWY3DPEHPK3PXP",
      "issuer": "GitHub",
      "digits": 6,
      "period": 30,
      "algorithm": "sha1",
      "created_at": "2024-01-15T10:30:00+00:00",
      "current_code": {
        "code": "123456",
        "remaining_seconds": 15,
        "period": 30,
        "generated_at": "2024-01-15T10:30:00+00:00"
      }
    }
  ]
}
```

---

### Get QR Code

Get a QR code image (PNG) for a token. This QR code can be scanned by authenticator apps.

```
GET /tokens/{id}/qr
```

**Response:** PNG image (`image/png`)

---

## Error Codes

| HTTP Status | Description |
|-------------|-------------|
| 200 | Success |
| 201 | Created (new token) |
| 400 | Bad Request (invalid input) |
| 404 | Token not found |
| 500 | Server error |

## Examples

### cURL

```bash
# Create a token
curl -X POST http://localhost:8045/api/v1/tokens \
  -H "Content-Type: application/json" \
  -d '{"name": "GitHub", "secret": "JBSWY3DPEHPK3PXP"}'

# Get current code
curl http://localhost:8045/api/v1/tokens/{id}/code

# Get all codes
curl http://localhost:8045/api/v1/codes

# Delete a token
curl -X DELETE http://localhost:8045/api/v1/tokens/{id}
```

### PHP

```php
<?php
// Create a token
$ch = curl_init('http://localhost:8045/api/v1/tokens');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'name' => 'GitHub',
        'secret' => 'JBSWY3DPEHPK3PXP'
    ])
]);
$response = json_decode(curl_exec($ch), true);
$token = $response['data'];
curl_close($ch);

// Get current code
$ch = curl_init("http://localhost:8045/api/v1/tokens/{$token['id']}/code");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$codeResponse = json_decode(curl_exec($ch), true);
$code = $codeResponse['data']['code'];
curl_close($ch);

echo "Current code: $code\n";
```

### JavaScript (fetch)

```javascript
// Create a token
const response = await fetch('http://localhost:8045/api/v1/tokens', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ name: 'GitHub', secret: 'JBSWY3DPEHPK3PXP' })
});
const { data: token } = await response.json();

// Get current code
const codeResponse = await fetch(`http://localhost:8045/api/v1/tokens/${token.id}/code`);
const { data: { code } } = await codeResponse.json();
console.log('Current code:', code);
```

### Go

```go
package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
)

type TokenResponse struct {
	Success bool `json:"success"`
	Data    struct {
		ID     string `json:"id"`
		Name   string `json:"name"`
		Secret string `json:"secret"`
	} `json:"data"`
}

type CodeResponse struct {
	Success bool `json:"success"`
	Data    struct {
		Code             string `json:"code"`
		RemainingSeconds int    `json:"remaining_seconds"`
	} `json:"data"`
}

func main() {
	// Create a token
	payload := []byte(`{"name": "GitHub", "secret": "JBSWY3DPEHPK3PXP"}`)
	resp, _ := http.Post(
		"http://localhost:8045/api/v1/tokens",
		"application/json",
		bytes.NewBuffer(payload),
	)
	defer resp.Body.Close()

	var tokenResp TokenResponse
	json.NewDecoder(resp.Body).Decode(&tokenResp)

	// Get current code
	codeResp, _ := http.Get(
		fmt.Sprintf("http://localhost:8045/api/v1/tokens/%s/code", tokenResp.Data.ID),
	)
	defer codeResp.Body.Close()

	var code CodeResponse
	json.NewDecoder(codeResp.Body).Decode(&code)

	fmt.Printf("Current code: %s\n", code.Data.Code)
}
```

## OpenAPI Specification

For interactive API documentation, see the [OpenAPI specification](openapi.yaml).
