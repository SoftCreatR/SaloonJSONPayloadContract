# Changelog

## 1.0.1 - 2026-11-19

- Added PHP 8.2 support to match the supported Saloon 4 runtime range.
- Require JSON Payload Contract 1.0.1 or newer, ensuring the complete dependency chain supports PHP 8.2.
- Added PHPUnit 11 support and PHP 8.2 coverage to the test and lowest-dependency CI jobs.
- Existing DTO, middleware, raw JSON, caching, and exception behavior is unchanged.

## 1.0.0

- Initial Saloon 4 integration with direct extraction, DTO mapping, optional response middleware, structured diagnostics, and weak-reference result caching.
