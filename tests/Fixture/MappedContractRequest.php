<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture;

use LogicException;
use Saloon\Http\Response;
use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\SaloonJsonPayloadContract\Traits\HasJsonPayloadContract;

/**
 * Exercises application-specific DTO mapping after contract validation.
 */
final class MappedContractRequest extends TestRequest
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

    /**
     * Maps normalized data into the fixture's application DTO.
     *
     * @param array<string, mixed> $data
     */
    protected function mapJsonPayloadContractData(array $data, Response $response): UserData
    {
        $id = $data['id'];

        if (!\is_string($id)) {
            throw new LogicException('The test contract must normalize id to a string.');
        }

        return new UserData($id, $response->status());
    }
}
