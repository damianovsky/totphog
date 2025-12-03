<p align="center">
  <img src="assets/logo.svg" width="120" alt="TOTPHog Logo">
</p>

<h1 align="center">TOTPHog</h1>

<h3 align="center">TOTP Codes, Simplified</h3>

<p align="center">
  A self-hosted TOTP code manager for development and testing.<br>
  <strong>Like MailHog, but for 2FA codes.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/Symfony-7.0-black?logo=symfony" alt="Symfony 7.0">
  <img src="https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white" alt="Docker">
  <img src="https://img.shields.io/badge/License-MIT-green" alt="MIT License">
</p>

---

## 🐷 What is TOTPHog?

**TOTPHog** is a lightweight, self-hosted TOTP (Time-based One-Time Password) manager designed for development and testing environments.

Think of it like [MailHog](https://github.com/mailhog/MailHog) - but instead of catching emails, it manages TOTP codes. Perfect for testing 2FA flows without reaching for your phone.

> ⚠️ **For development only** - No authentication, tokens stored in plain JSON. Never use in production.

---

## ✨ Features

- 🔐 Generate valid TOTP codes from secret keys
- 📱 Import tokens from QR codes (otpauth:// URIs)
- 🖥️ Clean web UI with real-time code updates
- 🔌 Full REST API for automation
- 📊 QR code generation for exporting tokens
- 🐳 Single Docker container, zero config

---

## 🚀 Quick Start

### Docker (Recommended)

```bash
docker run -d -p 8045:80 --name totphog damianovsky/totphog
```

Open http://localhost:8045

### Docker Compose

```bash
git clone https://github.com/damianovsky/totphog.git
cd totphog
docker-compose up -d
```

---

## 📖 Documentation

Full documentation is available in the [\`docs/\`](docs/) directory:

- [Getting Started](docs/getting-started.md) - Installation and setup
- [API Reference](docs/api.md) - REST API documentation
- [Configuration](docs/configuration.md) - Configuration options
- [Architecture](docs/architecture.md) - Technical details
- [OpenAPI Spec](docs/openapi.yaml) - Swagger/OpenAPI specification

---

## 📄 License

MIT License - see [LICENSE](LICENSE) for details.

---

<p align="center">
  Made with 🐷 for developers who need TOTP codes without the hassle
</p>
