<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture;

use Saloon\Http\Connector;
use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\SaloonJsonPayloadContract\Traits\EnforcesJsonPayloadContract;
use SoftCreatR\SaloonJsonPayloadContract\Traits\HasJsonPayloadContract;

/**
 * Exercises connector-wide contract enforcement and DTO conversion.
 */
final class ContractConnector extends Connector
{
    use EnforcesJsonPayloadContract;
    use HasJsonPayloadContract;

    /**
     * Stores the connector-wide contract used by the fixture.
     */
    public function __construct(private readonly Contract $contract)
    {
    }

    /**
     * Returns the fixture base URL without performing network I/O.
     */
    public function resolveBaseUrl(): string
    {
        return 'https://example.test';
    }

    /**
     * Returns the contract supplied by the test.
     */
    protected function jsonPayloadContract(): Contract
    {
        return $this->contract;
    }
}
