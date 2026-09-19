# Security Policy

## Supported versions

| Branch | Supported |
|--------|-----------|
| `main` | Yes |
| `develop` | Best-effort (pre-release) |
| Feature branches | No |

## Reporting a vulnerability

If you discover a security issue in Sanabel IQ (auth bypass, tenant data leak, XSS, injection, exposed secrets, etc.):

1. **Do not** open a public GitHub issue.
2. Email the maintainers via the repository owner on GitHub (`yousefbzaqout`) or your agreed private channel.
3. Include: impact, reproduction steps, affected routes/models, and suggested fix if you have one.

We will acknowledge valid reports and coordinate a fix and disclosure timeline.

## Hardening expectations

- Child PIN flows must never expose parent passwords
- Tenant scoping must apply to student, lesson, and analytics queries
- Secrets stay in environment variables — never in git history
- Prefer parameterized queries / Eloquent; avoid raw SQL with user input
