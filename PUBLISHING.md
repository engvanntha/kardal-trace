# Publishing engvanntha/kardal-trace

## 1) Push to its own Git repository

- Create repository `engvanntha/kardal-trace`.
- Push this folder as repository root.

## 2) Tag release

```bash
git tag v1.0.0
git push origin v1.0.0
```

## 3) Optional: submit to Packagist

- Go to Packagist and submit repository URL.
- Enable auto-update webhook.

After this, projects can install with:

```bash
composer require engvanntha/kardal-trace:^1.0
```
