# Contributing to Sanabel IQ

Thanks for helping build Sanabel IQ. This guide keeps the repository consistent and reviewable.

## Branching strategy

```text
feat/* | fix/* | docs/* | test/* | chore/* | refactor/*
        │
        ▼
     develop   ← integration
        │
        ▼
      main     ← production + GitHub Releases
```

1. Sync `develop`: `git checkout develop && git pull origin develop`
2. Create a branch: `git checkout -b feat/your-change`
3. Push and open a **Pull Request into `develop`**
4. After QA, promote `develop` → `main` and cut a release

Never commit application features directly to `main`.

## Commit messages

Use [Conventional Commits](https://www.conventionalcommits.org/) in English:

```text
feat: add child PIN login for student portal
fix: prevent celebration page redirect without session payload
test: cover tenant isolation for interactive lessons
docs: explain how the mastery pipeline works
chore: refresh .env.example for OpenRouter keys
```

## Coding standards

- PHP 8.5 with `declare(strict_types=1);` and PSR-12
- TDD: add or update Pest/PHPUnit tests **before** production code
- Prefer small, focused PRs over large catch-all branches when possible
- Do not commit `.env`, credentials, or generated TTS caches

## Pull requests

- Fill in the PR template
- Ensure CI is green
- Link related tickets / docs when relevant
- Include screenshots for UI changes (student RTL, Filament, landing)

## Local verification

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail npm run build
```

## Security

Report vulnerabilities privately — see [SECURITY.md](SECURITY.md). Do not open public issues for sensitive findings.
