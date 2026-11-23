# Contributing

Thanks for helping to improve **Webmarka Cookie Consent**! Bug reports, translations, docs fixes and pull requests are all welcome.

## Ground rules

- Be kind. This project follows the [Code of Conduct](CODE_OF_CONDUCT.md).
- Report security problems **privately**. See [SECURITY.md](SECURITY.md).
- By submitting a contribution, you agree that it is licensed under the project's [MIT License](LICENSE) (inbound = outbound).
- Compliance defaults are not negotiable in pull requests. "Reject all" must stay as prominent as "Accept all", nothing may be pre-ticked, and closing the banner must not count as consent. Options that weaken these defaults need a strong legal reference and an issue first.

## Development setup

```bash
git clone https://github.com/hakanispirli/webmarka-cookie-consent.git
cd webmarka-cookie-consent
composer install
npm ci
```

| Task | Command |
|---|---|
| PHP tests (Pest) | `composer test` |
| Static analysis (PHPStan level 8) | `composer analyse` |
| Code style (Pint) | `composer format` |
| JS type check | `npm run typecheck` |
| JS tests (Vitest) | `npm test` |
| Build `dist/` | `npm run build` |

## Pull request checklist

- [ ] Tests added or updated for every behavior change.
- [ ] `composer test`, `composer analyse`, `composer format`, `npm run typecheck` and `npm test` pass.
- [ ] If you changed anything in `resources/js` or `resources/css`, you ran `npm run build` **and committed `dist/`**. CI fails when `dist/` is out of date.
- [ ] If you changed the cookie format or the category → Consent Mode mapping, all three places are updated: `resources/views/head.blade.php`, `resources/js/core/*` and `src/CookieConsent.php`.
- [ ] User-facing changes are documented in `README.md` (and `README.tr.md` if possible) and in `CHANGELOG.md` under *Unreleased*.
- [ ] New texts are added to **both** `lang/en` and `lang/tr`.

## Translations

Copy `lang/en/cookie-consent.php` to `lang/<locale>/cookie-consent.php`, translate the values and open a pull request. Keep the array keys unchanged.

## Commit messages

Use short, imperative subjects (for example "Add German translation" or "Fix focus trap in Safari"). Reference issues where relevant (`Fixes #12`).
