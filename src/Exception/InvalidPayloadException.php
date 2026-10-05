<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Exception;

use Saloon\Exceptions\SaloonException;
use Saloon\Http\Response;
use SoftCreatR\JsonPayloadContract\Result;
use SoftCreatR\JsonPayloadContract\Violation;

/**
 * Retains both the Saloon response and structured contract diagnostics.
 */
final class InvalidPayloadException extends SaloonException
{
    /**
     * Creates a concise message without exposing the response body.
     */
    public function __construct(
        private readonly Response $response,
        private readonly Result $result,
    ) {
        $messages = \array_map(
            static fn(Violation $violation): string => \sprintf(
                '%s [%s]: %s',
                $violation->field,
                $violation->code->value,
                $violation->message,
            ),
            $result->violations(),
        );

        parent::__construct('Response payload does not satisfy the contract: ' . \implode('; ', $messages));
    }

    /**
     * Returns the response whose body failed validation.
     */
    public function response(): Response
    {
        return $this->response;
    }

    /**
     * Returns normalized partial data and every contract violation.
     */
    public function result(): Result
    {
        return $this->result;
    }
}
