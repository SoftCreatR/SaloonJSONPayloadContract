<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture;

use Saloon\Http\Connector;

/**
 * Provides a network-free connector for integration tests.
 */
final class TestConnector extends Connector
{
    /**
     * Returns a syntactically valid base URL that is never contacted.
     */
    public function resolveBaseUrl(): string
    {
        return 'https://example.test';
    }
}
