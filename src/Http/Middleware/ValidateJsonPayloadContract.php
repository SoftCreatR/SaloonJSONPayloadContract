<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Http\Middleware;

use Saloon\Contracts\ResponseMiddleware;
use Saloon\Http\Response;
use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\SaloonJsonPayloadContract\Exception\InvalidPayloadException;
use SoftCreatR\SaloonJsonPayloadContract\JsonPayload;

/**
 * Rejects a response before it leaves Saloon's response pipeline.
 */
final readonly class ValidateJsonPayloadContract implements ResponseMiddleware
{
    /**
     * Stores the immutable contract used for response validation.
     */
    public function __construct(private Contract $contract)
    {
    }

    /**
     * Validates the response and leaves successful responses unchanged.
     *
     * @throws InvalidPayloadException
     */
    public function __invoke(Response $response): void
    {
        JsonPayload::data($response, $this->contract);
    }
}
