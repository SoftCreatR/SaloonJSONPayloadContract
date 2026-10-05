<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract;

use Saloon\Http\Response;
use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\JsonPayloadContract\Result;
use SoftCreatR\SaloonJsonPayloadContract\Exception\InvalidPayloadException;
use SoftCreatR\SaloonJsonPayloadContract\Internal\ContractResultCache;
use WeakMap;

/**
 * Evaluates Saloon response bodies without losing raw JSON semantics.
 */
final class JsonPayload
{
    /**
     * Caches results weakly by response and immutable contract identity.
     *
     * @var WeakMap<Response, ContractResultCache>|null
     */
    private static ?WeakMap $results = null;

    /**
     * Evaluates a response and returns normalized data plus diagnostics.
     */
    public static function extract(Response $response, Contract $contract): Result
    {
        $results = self::$results ??= new WeakMap();
        $contracts = $results[$response] ?? null;

        if (!$contracts instanceof ContractResultCache) {
            $contracts = new ContractResultCache();
            $results[$response] = $contracts;
        }

        $result = $contracts->get($contract);

        if ($result instanceof Result) {
            return $result;
        }

        $result = $contract->extractJson($response->body());
        $contracts->put($contract, $result);

        return $result;
    }

    /**
     * Evaluates a response and returns normalized data or a response-aware exception.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidPayloadException
     */
    public static function data(Response $response, Contract $contract): array
    {
        $result = self::extract($response, $contract);

        if (!$result->isValid()) {
            throw new InvalidPayloadException($response, $result);
        }

        return $result->data();
    }
}
