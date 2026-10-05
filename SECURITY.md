# Security policy

## Supported versions

The latest stable release receives security fixes. Update to the newest release before reporting an issue.

## Reporting a vulnerability

Please use GitHub's private security-advisory reporting for this repository. Do not open a public issue for a suspected vulnerability.

Include a minimal reproduction, affected versions, practical impact, and any proposed mitigation. Remove credentials, personal data, and proprietary response payloads before submitting the report.

## Scope

Security reports are especially useful for issues involving response-body disclosure, contract bypasses, unsafe parsing, unbounded work, cache lifetime, or unexpected interaction with Saloon middleware. Vulnerabilities in Saloon, Guzzle, or JSON Payload Contract should also be reported to their respective maintainers when the issue exists independently of this adapter.
