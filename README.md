# Saloon JSON Payload Contract

[![Tests](https://github.com/SoftCreatR/SaloonJSONPayloadContract/actions/workflows/tests.yml/badge.svg)](https://github.com/SoftCreatR/SaloonJSONPayloadContract/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/softcreatr/saloon-json-payload-contract.svg)](https://packagist.org/packages/softcreatr/saloon-json-payload-contract)
[![License](https://img.shields.io/github/license/SoftCreatR/SaloonJSONPayloadContract.svg)](LICENSE)
[![PHP Version Require](https://img.shields.io/packagist/dependency-v/softcreatr/saloon-json-payload-contract/php.svg)](https://packagist.org/packages/softcreatr/saloon-json-payload-contract)

Validate and normalize Saloon responses at the point where an external API enters your application.

This package connects [Saloon](https://docs.saloon.dev/) with [JSON Payload Contract](https://github.com/SoftCreatR/JSONPayloadContract). It turns changing, deeply nested, or only partly trustworthy JSON responses into a stable typed shape while retaining Saloon's normal request, middleware, mock, pool, and DTO workflows.

```php
use Saloon\Enums\Method;
use Saloon\Http\Request;
use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\JsonPayloadContract\Field;
use SoftCreatR\SaloonJsonPayloadContract\Traits\HasJsonPayloadContract;

final class GetUser extends Request
{
    use HasJsonPayloadContract;

    protected Method $method = Method::GET;

    private static ?Contract $responseContract = null;

    public function resolveEndpoint(): string
    {
        return '/users/me';
    }

    protected function jsonPayloadContract(): Contract
    {
        return self::$responseContract ??= Contract::define([
            'id' => Field::required('$.data.id')
                ->fallback('$.user.id')
                ->string()
                ->nonEmpty(),
            'email' => Field::optional('$.data.email')
                ->fallback('$.user.email')
                ->nullable()
                ->string()
                ->email(),
            'roles' => Field::many('$.data.roles[*]')
                ->fallback('$.user.roles[*]')
                ->string()
                ->maxMatches(50),
        ]);
    }
}

$user = $connector->send(new GetUser())->dtoOrFail();

// Stable application-facing data, regardless of which selector matched:
// [
//     'id' => 'usr_123',
//     'email' => 'person@example.com',
//     'roles' => ['admin'],
// ]
```

## Why this integration exists

Saloon already gives PHP applications an excellent HTTP and SDK layer. JSON Payload Contract addresses the next boundary: deciding which response values the application trusts and what shape they must have.

- Extract only the fields your application uses with RFC 9535 JSONPath.
- Keep one output shape while upstream APIs move or rename fields.
- Distinguish a missing value, JSON `null`, an empty list, and an empty object.
- Enforce type, cardinality, format, and resource limits.
- Normalize transport values explicitly instead of relying on loose PHP comparisons.
- Report every independent violation with stable machine-readable codes.
- Map normalized data through Saloon's existing `dto()` and `dtoOrFail()` methods.
- Validate automatically in Saloon's response middleware pipeline when fail-fast behavior is required.

The adapter always evaluates the raw response body. It deliberately does not pass `Response::json()` to the contract, because associative decoding would make JSON objects and arrays indistinguishable and would prevent duplicate-member detection.

## Requirements

- PHP 8.3 or newer
- Saloon 4.x
- JSON Payload Contract 1.x

## Installation

```bash
composer require softcreatr/saloon-json-payload-contract
```

No service provider, container binding, macro registration, or framework bootstrap is required.

## Three integration levels

### 1. Evaluate any response explicitly

Use `JsonPayload` when a contract belongs to a call site rather than a request class:

```php
use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\JsonPayloadContract\Field;
use SoftCreatR\SaloonJsonPayloadContract\JsonPayload;

$contract = Contract::define([
    'nextCursor' => Field::optional('$.meta.next_cursor')->nullable()->string(),
    'ids' => Field::many('$.data[*].id')->string()->maxMatches(100),
]);

$response = $connector->send(new ListUsers());
$result = JsonPayload::extract($response, $contract);

if (!$result->isValid()) {
    foreach ($result->violations() as $violation) {
        logger()->warning($violation->message, $violation->jsonSerialize());
    }
}

$data = JsonPayload::data($response, $contract); // throws when invalid
```

`extract()` never throws for malformed JSON or contract violations. It returns a `Result` containing partial normalized data, violations, and selector-match metadata. `data()` returns the complete normalized array or throws `InvalidPayloadException`.

### 2. Use Saloon's DTO flow

Add `HasJsonPayloadContract` to a request or connector. The trait implements Saloon's `createDtoFromResponse()` extension point:

```php
$response = $connector->send(new GetUser());

$data = $response->dto();
$data = $response->dtoOrFail();
```

`dtoOrFail()` first applies Saloon's normal HTTP failure handling and then evaluates the payload contract. `dto()` evaluates the contract regardless of HTTP status, matching Saloon's existing DTO semantics.

The non-throwing result remains available when an application needs custom recovery:

```php
$request = new GetUser();
$response = $connector->send($request);
$result = $request->jsonPayloadContractResult($response);
```

When a connector and request both implement Saloon DTO conversion, Saloon gives the request precedence. This package does not change that behavior.

### 3. Enforce the contract during every send

Use `EnforcesJsonPayloadContract` when an invalid body must never leave the response pipeline:

```php
use SoftCreatR\SaloonJsonPayloadContract\Traits\EnforcesJsonPayloadContract;

final class GetUser extends Request
{
    use EnforcesJsonPayloadContract;

    // Define resolveEndpoint() and jsonPayloadContract() as above.
}

$response = $connector->send(new GetUser());
// Invalid JSON or an invalid payload throws before send() returns.
```

The trait is a native Saloon plugin: Saloon discovers its `bootEnforcesJsonPayloadContract()` method and registers response middleware at `PipeOrder::LAST`.

For local or conditional enforcement, register the middleware directly:

```php
use SoftCreatR\SaloonJsonPayloadContract\Http\Middleware\ValidateJsonPayloadContract;

$request = new GetUser();
$request->middleware()->onResponse(
    new ValidateJsonPayloadContract($contract),
);

$response = $connector->send($request);
```

Middleware enforcement runs for every HTTP status. This is useful when successful and error responses both have a defined contract. If only successful responses should be validated, use `dtoOrFail()` instead or register conditional application middleware around `JsonPayload::data()`.

## Mapping to application DTOs

Override `mapJsonPayloadContractData()` to map already validated data into any application type:

```php
use Saloon\Http\Response;

final readonly class UserData
{
    /**
     * Creates an application-owned user representation.
     *
     * @param list<string> $roles
     */
    public function __construct(
        public string $id,
        public ?string $email,
        public array $roles,
        public int $status,
    ) {
    }
}

final class GetUser extends Request
{
    use HasJsonPayloadContract;

    // Define the request and contract as above.

    /**
     * Maps trusted normalized values into the SDK's public DTO.
     *
     * @param array{id: string, email: ?string, roles: list<string>} $data
     */
    protected function mapJsonPayloadContractData(array $data, Response $response): UserData
    {
        return new UserData(
            $data['id'],
            $data['email'],
            $data['roles'],
            $response->status(),
        );
    }
}

/** @var UserData $user */
$user = $connector->send(new GetUser())->dtoOrFail();
```

Saloon still performs its normal `WithResponse` handling after the mapper returns. A mapped DTO implementing `Saloon\Contracts\DataObjects\WithResponse` receives the original response automatically.

## Failure handling

`InvalidPayloadException` extends Saloon's `SaloonException` and retains both sides of the failed boundary:

```php
use SoftCreatR\SaloonJsonPayloadContract\Exception\InvalidPayloadException;

try {
    $user = $connector->send(new GetUser())->dtoOrFail();
} catch (InvalidPayloadException $exception) {
    $response = $exception->response();
    $result = $exception->result();

    foreach ($result->violations() as $violation) {
        reportContractFailure(
            field: $violation->field,
            code: $violation->code->value,
            message: $violation->message,
            selector: $violation->selector,
        );
    }
}
```

Exception messages include field names, violation codes, and explanations. They never include the response body or rejected values, reducing the risk of leaking tokens, personal data, or third-party secrets into logs.

## Versioned API example

Fallback selectors let an SDK support an upstream migration without branching around response versions:

```php
protected function jsonPayloadContract(): Contract
{
    return self::$responseContract ??= Contract::define([
        'customerId' => Field::required('$.v3.customer.id')
            ->fallback('$.v2.customer_id')
            ->fallback('$.customerId')
            ->string()
            ->nonEmpty(),
        'amount' => Field::required('$.v3.amount.value')
            ->fallback('$.amount')
            ->number(),
        'currency' => Field::required('$.v3.amount.currency')
            ->fallback('$.currency')
            ->string()
            ->oneOf(['EUR', 'USD', 'GBP']),
    ]);
}
```

A fallback is used only when earlier selectors match nothing. An earlier selector that finds an invalid value does not silently fall through, so malformed current-version data cannot masquerade as an older response.

## Security

The package introduces no expression evaluator, callback registry, deserializer, or dynamic code execution. JSON selection, normalization, validation, and resource budgets are delegated to JSON Payload Contract.

- Raw JSON is evaluated with object/list semantics intact.
- Duplicate object members are rejected by default.
- Malformed JSON becomes a structured violation.
- JSON byte size, nesting, input nodes, selector work, matches, output fan-out, and diagnostics are bounded.
- Contract selectors are parsed and validated when contracts are created.
- Exception messages omit body content and rejected values.
- Results are cached only by object identity in weak maps; cache entries cannot outlive their response and contract objects.
- Composer conflicts prevent installation with Guzzle transport versions covered by known host, cookie, redirect, proxy, or URI-validation advisories.

Reuse contract instances, as the examples do, to avoid rebuilding and reparsing immutable definitions for each request. Review JSON Payload Contract's security and resource-limit documentation when accepting unusually large third-party responses.

## Performance

One response/contract pair is evaluated at most once in the current process. `JsonPayload`, middleware enforcement, `jsonPayloadContractResult()`, and DTO mapping share a weak-reference cache. Using both integration traits therefore does not decode or validate the same response twice.

The cache:

- compares actual object identity rather than hashes or serialized data;
- does not retain responses or contracts after the application releases them;
- stores no response globally beyond those weak references;
- works without a framework cache, filesystem, or network call.

Contract resolution inside either trait is also lazy and memoized for the lifetime of the Saloon request or connector.

## Choosing an integration style

| Need                                     | Use                                                        |
|------------------------------------------|------------------------------------------------------------|
| Evaluate one response at a call site     | `JsonPayload::extract()`                                   |
| Return normalized data or throw          | `JsonPayload::data()`                                      |
| Use Saloon's DTO API                     | `HasJsonPayloadContract`                                   |
| Map normalized data to a DTO             | Override `mapJsonPayloadContractData()`                    |
| Reject invalid responses during `send()` | `EnforcesJsonPayloadContract`                              |
| Enforce only selected calls              | `ValidateJsonPayloadContract` middleware                   |
| Inspect violations without throwing      | `Result` from `extract()` or `jsonPayloadContractResult()` |

## Compatibility policy

The package supports the maintained Saloon 4 line. Public integration points are limited to Saloon's documented response, DTO, middleware, mock-client, and plugin-boot APIs.

## Development

```bash
composer install
composer test
composer coverage
composer phpstan
composer cs
composer check
```

Coverage is required to remain at 100% of executable classes, methods, and lines. The test suite uses Saloon's `MockClient`; it performs no external HTTP requests.

## License

[ISC](LICENSE)
