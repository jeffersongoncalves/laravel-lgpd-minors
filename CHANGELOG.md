# Changelog

All notable changes to this project will be documented in this file.

## 1.0.0 - 2026-11-17

First release: ECA Digital (Lei 15.211/2025, Decreto 12.880/2026) building blocks for Laravel.

- Age assurance results only (band, method, provider; never the document or birth date, art. 13), with per-method validity.
- One age band per user: reliable checks outweigh self-declaration, and the most protective reliable band wins (Decreto, art. 25, §4).
- `lgpd.adult` middleware: a reliable adult result on every request; self-declaration never counts (art. 9, §1).
- Guardian links for accounts up to 16 (art. 24), `canLowerProtection()` for §5.
- Suspension with appeal, lift or uphold (art. 24, §4), and the `lgpd.suspended` middleware.
- `allowsProfiling()` and `@lgpdProfiling`: no ad profiling unless the user is a verified adult (arts. 22 and 26).
- `lgpd:minors [--json]`: users up to 16 without a guardian and appeals waiting for a decision.

## [Unreleased]
