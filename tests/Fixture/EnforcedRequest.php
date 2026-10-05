<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture;

use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\SaloonJsonPayloadContract\Traits\EnforcesJsonPayloadContract;
use SoftCreatR\SaloonJsonPayloadContract\Traits\HasJsonPayloadContract;

/**
 * Exercises automatic response enforcement together with DTO extraction.
 */
final class EnforcedRequest extends TestRequest
{
    use EnforcesJsonPayloadContract;
    use HasJsonPayloadContract;

    /**
     * Stores the contract used by both integration traits.
     */
    public function __construct(private readonly Contract $contract)
    {
    }

    /**
     * Returns the contract supplied by the test.
     */
    protected function jsonPayloadContract(): Contract
    {
        return $this->contract;
    }
}
