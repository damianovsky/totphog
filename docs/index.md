---
layout: default
title: Home
---

# TOTPHog Documentation

Welcome to the TOTPHog documentation. TOTPHog is a self-hosted TOTP code manager designed for development and testing environments.

## Table of Contents

- [Getting Started](getting-started.md) - Installation and quick start guide
- [API Reference](api.md) - Complete REST API documentation
- [Configuration](configuration.md) - Configuration options and environment variables
- [Architecture](architecture.md) - Technical architecture and design decisions

## What is TOTPHog?

TOTPHog is a lightweight, self-hosted TOTP (Time-based One-Time Password) manager. Think of it like [MailHog](https://github.com/mailhog/MailHog) - but instead of catching emails, it manages TOTP codes.

### Use Cases

- **Testing 2FA flows** - Test your app's two-factor authentication without a real authenticator
- **Automated testing** - Get TOTP codes via REST API for E2E tests and CI/CD
- **Team development** - Share test 2FA accounts without sharing authenticator apps
- **Local development** - Quick access to TOTP codes without switching devices

> ⚠️ **Warning**: TOTPHog is designed for development only. It has no authentication and stores tokens in plain JSON. Never use in production environments.

## Quick Links

- [OpenAPI/Swagger Specification](openapi.yaml)
- [GitHub Repository](https://github.com/damianovsky/totphog)
