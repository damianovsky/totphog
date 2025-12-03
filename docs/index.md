# TOTPHog

<p align="center">
  <img src="assets/logo.svg" width="100" alt="TOTPHog Logo">
</p>

**A self-hosted TOTP code manager for development and testing.**  
*Like MailHog, but for 2FA codes.*

---

## What is TOTPHog?

TOTPHog is a lightweight, self-hosted TOTP (Time-based One-Time Password) manager designed for **development and testing** environments.

### Use Cases

- :test_tube: **Testing 2FA flows** - Test your app's two-factor authentication without a real authenticator
- :robot: **Automated testing** - Get TOTP codes via REST API for E2E tests and CI/CD
- :busts_in_silhouette: **Team development** - Share test 2FA accounts without sharing authenticator apps
- :wrench: **Local development** - Quick access to TOTP codes without switching devices

---

## Quick Start

```bash
docker run -d -p 8045:80 --name totphog damianovsky/totphog
```

Open [http://localhost:8045](http://localhost:8045)

---

!!! warning "Development Only"
    TOTPHog is for development only. No authentication, tokens stored in plain JSON. **Never use in production.**
