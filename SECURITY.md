# Security Policy

## Supported Version

The `main` branch is the only supported production line for Chem Rank.

## Reporting a Vulnerability

Please report security issues privately to the repository owner instead of opening a public issue. Include the affected route, impact, reproduction steps, and any relevant logs that do not contain credentials or personal data.

## Security Baseline

- Pull requests to `main` require review.
- Credentials, local databases, logs, and `.env` files must not be committed.
- Production deployments must use HTTPS, secure session settings, and environment-managed secrets.
