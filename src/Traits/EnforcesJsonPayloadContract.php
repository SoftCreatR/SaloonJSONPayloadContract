<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Traits;

use Saloon\Enums\PipeOrder;
use Saloon\Http\PendingRequest;
use SoftCreatR\SaloonJsonPayloadContract\Http\Middleware\ValidateJsonPayloadContract;
use SoftCreatR\SaloonJsonPayloadContract\Internal\ResolvesJsonPayloadContract;

/**
 * Automatically validates every response for a request or connector.
 */
trait EnforcesJsonPayloadContract
{
    use ResolvesJsonPayloadContract;

    /**
     * Registers fail-fast validation at the end of the response pipeline.
     */
    public function bootEnforcesJsonPayloadContract(PendingRequest $pendingRequest): void
    {
        $pendingRequest->middleware()->onResponse(
            callable: new ValidateJsonPayloadContract($this->resolveJsonPayloadContract()),
            name: 'jsonPayloadContract:' . static::class,
            order: PipeOrder::LAST,
        );
    }
}
