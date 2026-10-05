<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Internal;

use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\JsonPayloadContract\Result;
use WeakMap;

/**
 * Holds weak result references for contracts evaluated against one response.
 *
 * @internal
 */
final class ContractResultCache
{
    /**
     * Maps immutable contract identities to their extraction results.
     *
     * @var WeakMap<Contract, Result>
     */
    private WeakMap $results;

    /**
     * Creates an empty identity cache.
     */
    public function __construct()
    {
        $this->results = new WeakMap();
    }

    /**
     * Returns a previously evaluated result when the contract is still alive.
     */
    public function get(Contract $contract): ?Result
    {
        return $this->results[$contract] ?? null;
    }

    /**
     * Stores an extraction result under its immutable contract identity.
     */
    public function put(Contract $contract, Result $result): void
    {
        $this->results[$contract] = $result;
    }
}
