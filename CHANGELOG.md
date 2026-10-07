# Changelog

## 1.2.0 - 2026-10-06

### Added

- `reserveSpace`: the full-width banner becomes sticky, so it takes its own
  height at the end of the page instead of covering the footer, and that space
  opens and closes with it. Off by default.

## 1.1.0 - 2026-09-23

### Added

- **How to record consents**, in [docs/recording-consents.md](docs/recording-consents.md).
  The package keeps no register — where decisions are stored, for how long and
  who may read them are decisions an application makes for itself — but the
  core now carries what a register is built from: the fingerprint of what the
  banner was showing, the reconciliation of a reported answer, and the script
  that reports it. The page walks through the table, the endpoint, and the CSRF
  exemption `sendBeacon` needs.

### Changed

- Requires `quebecstudio-mods/consent-kit-core` 1.1, for those primitives.

## 1.0.0 - 2026-09-22

- Cookie consent banner for Laravel 11 to 13, in `QuebecStudioMods\ConsentKit\Laravel`,
  on `quebecstudio-mods/consent-kit-core`.
- `InjectConsent` middleware on the `web` group: bootstrap, stylesheet, banner
  and script added to HTML responses.
- Publishable config, assets, wording files and templates.
- `CookieConsent` facade: banner, cookie table and markers, YouTube facade.
- YouTube posters served by the application.
- `displayMode`: full width, a floating box, or a bottom corner.
- `reopenPosition`: the reopen tab on the left or the right.
