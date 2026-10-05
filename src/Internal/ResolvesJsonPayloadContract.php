<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Internal;

use SoftCreatR\JsonPayloadContract\Contract;

/**
 * Resolves one immutable contract instance per request or connector.
 *
 * @internal
 */
trait ResolvesJsonPayloadContract
{
    /**
     * Retains the lazily resolved contract for middleware and DTO reuse.
     */
    private ?Contract $resolvedJsonPayloadContract = null;

    /**
     * Defines the response contract for this request or connector.
     */
    abstract protected function jsonPayloadContract(): Contract;

    /**
     * Returns the same contract instance throughout the resource lifecycle.
     */
    private function resolveJsonPayloadContract(): Contract
    {
        return $this->resolvedJsonPayloadContract ??= $this->jsonPayloadContract();
    }
}
