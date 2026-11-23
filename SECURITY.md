# Security Policy

## Supported versions

| Version | Supported |
|---|---|
| 1.x | ✅ |

## Reporting a vulnerability

**Please do not open a public issue for security problems.**

Report vulnerabilities privately through GitHub:
**[Report a vulnerability](https://github.com/hakanispirli/webmarka-cookie-consent/security/advisories/new)** (repository → *Security* → *Report a vulnerability*).

Please include:

- the affected version and configuration,
- steps to reproduce or a proof of concept,
- the impact (for example XSS, consent bypass, or data exposure in consent records).

What to expect:

- We acknowledge reports within **5 business days**.
- We keep you updated while we work on a fix and credit you in the release notes, unless you prefer to stay anonymous.
- Please give us reasonable time to release a fix before any public disclosure.

## Scope

Examples of issues we treat as security issues:

- scripts or Google tags running before consent, or consent signals that are sent incorrectly,
- XSS through configuration, translations or the consent cookie,
- unauthorized access to, or injection into, consent records (including CSV export),
- path traversal in the asset route.
