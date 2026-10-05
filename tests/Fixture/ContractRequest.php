<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture;

use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\SaloonJsonPayloadContract\Traits\HasJsonPayloadContract;

/**
 * Exercises Saloon DTO conversion with the default normalized array.
 */
final class ContractRequest extends TestRequest
{
    use HasJsonPayloadContract;

    /**
     * Stores the contract used by the fixture.
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
