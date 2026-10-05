<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Supplies a minimal GET request for response-oriented tests.
 */
class TestRequest extends Request
{
    /**
     * Uses GET because only response behavior is under test.
     */
    protected Method $method = Method::GET;

    /**
     * Returns the fixed fixture endpoint.
     */
    public function resolveEndpoint(): string
    {
        return '/payload';
    }
}
