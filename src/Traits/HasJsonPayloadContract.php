<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Traits;

use Saloon\Http\Response;
use SoftCreatR\JsonPayloadContract\Result;
use SoftCreatR\SaloonJsonPayloadContract\Exception\InvalidPayloadException;
use SoftCreatR\SaloonJsonPayloadContract\Internal\ResolvesJsonPayloadContract;
use SoftCreatR\SaloonJsonPayloadContract\JsonPayload;

/**
 * Routes Saloon DTO conversion through an immutable JSON payload contract.
 */
trait HasJsonPayloadContract
{
    use ResolvesJsonPayloadContract;

    /**
     * Returns a non-throwing result for custom response handling.
     */
    public function jsonPayloadContractResult(Response $response): Result
    {
        return JsonPayload::extract($response, $this->resolveJsonPayloadContract());
    }

    /**
     * Normalizes the body and maps it into Saloon's DTO result.
     *
     * @throws InvalidPayloadException
     */
    public function createDtoFromResponse(Response $response): mixed
    {
        $data = JsonPayload::data($response, $this->resolveJsonPayloadContract());

        return $this->mapJsonPayloadContractData($data, $response);
    }

    /**
     * Maps valid normalized data to an application object or returns the array unchanged.
     *
     * @param array<string, mixed> $data
     */
    protected function mapJsonPayloadContractData(array $data, Response $response): mixed
    {
        return $data;
    }
}
