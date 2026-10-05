# Contributing

Contributions that keep the adapter small, Saloon-native, secure, and framework-independent are welcome.

## Development setup

```bash
git clone https://github.com/SoftCreatR/SaloonJSONPayloadContract.git
cd SaloonJSONPayloadContract
composer install
composer check
```

Use PHP 8.3 when running PHP CS Fixer because it is the package's minimum supported PHP version.

## Pull requests

- Add focused tests for every behavior change.
- Keep executable source at 100% class, method, and line coverage.
- Use Saloon's public extension points rather than framework-specific integration.
- Preserve raw JSON evaluation and structured contract diagnostics.
- Avoid dependencies that duplicate Saloon or JSON Payload Contract behavior.
- Run `composer audit`, `composer coverage`, `composer phpstan`, and `composer cs` before submitting.

Tests must use Saloon's mock client and must not contact external services.

## Reporting bugs

Include the PHP and Saloon versions, a minimal contract, a sanitized response body, the expected normalized result, and the actual violation or exception. Never include production credentials or sensitive payload data.
