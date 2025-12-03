# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-03

### Added

- Initial release of TOTPHog
- Web interface for managing TOTP tokens
  - Real-time code display with countdown timer
  - Add tokens manually or via otpauth:// URI
  - QR code scanning support
  - Delete individual or all tokens
- REST API (`/api/v1`)
  - `GET /tokens` - List all tokens
  - `POST /tokens` - Create token (manual or from URI)
  - `GET /tokens/{id}` - Get single token
  - `DELETE /tokens/{id}` - Delete token
  - `DELETE /tokens` - Delete all tokens
  - `GET /tokens/{id}/code` - Get current TOTP code
  - `GET /codes` - Get all current codes
  - `GET /tokens/{id}/qr` - Get QR code image (PNG)
  - `GET /health` - Health check endpoint
- Docker support
  - Single container deployment
  - Docker Compose configuration
  - Volume support for data persistence
- TOTP features
  - Configurable digits (6 or 8)
  - Configurable period (default 30s)
  - Multiple algorithms (SHA1, SHA256, SHA512)
  - QR code generation for token export
- Documentation
  - Getting started guide
  - API reference
  - OpenAPI/Swagger specification
  - Architecture documentation

### Technical Stack

- PHP 8.3
- Symfony 7.0 (micro-kernel)
- spomky-labs/otphp for TOTP generation
- endroid/qr-code for QR code generation
- Bootstrap 5 for UI
- Apache in Docker container

[1.0.0]: https://github.com/damianovsky/totphog/releases/tag/v1.0.0
