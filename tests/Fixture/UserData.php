<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture;

/**
 * Represents application data produced by a contract-aware request.
 */
final readonly class UserData
{
    /**
     * Stores the normalized user identifier and source status code.
     */
    public function __construct(
        public string $id,
        public int $status,
    ) {
    }
}
