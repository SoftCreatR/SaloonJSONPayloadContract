<?php

/**
 * JSON Payload Contract integration for Saloon.
 *
 * @license https://github.com/SoftCreatR/SaloonJSONPayloadContract/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\SaloonJsonPayloadContract\Tests;

use PHPUnit\Framework\TestCase;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\JsonPayloadContract\Field;
use SoftCreatR\SaloonJsonPayloadContract\Exception\InvalidPayloadException;
use SoftCreatR\SaloonJsonPayloadContract\Http\Middleware\ValidateJsonPayloadContract;
use SoftCreatR\SaloonJsonPayloadContract\JsonPayload;
use SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture\ContractConnector;
use SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture\ContractRequest;
use SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture\EnforcedRequest;
use SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture\MappedContractRequest;
use SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture\TestConnector;
use SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture\TestRequest;
use SoftCreatR\SaloonJsonPayloadContract\Tests\Fixture\UserData;
use stdClass;

/**
 * Verifies direct extraction, middleware enforcement, and Saloon DTO mapping.
 */
final class JsonPayloadTest extends TestCase
{
    /**
     * Returns valid normalized data and reuses the result for identical inputs.
     *
     * @throws FatalRequestException
     * @throws InvalidPayloadException
     * @throws RequestException
     */
    public function testExtractsAndCachesValidResponse(): void
    {
        $response = $this->response('{"data":{"id":"user-1","roles":[]}}');
        $contract = $this->contract();

        $result = JsonPayload::extract($response, $contract);

        self::assertTrue($result->isValid());
        self::assertSame(['id' => 'user-1', 'roles' => []], $result->data());
        self::assertSame($result, JsonPayload::extract($response, $contract));
        self::assertSame($result->data(), JsonPayload::data($response, $contract));
    }

    /**
     * Retains the response and all structured diagnostics on failure.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function testThrowsResponseAwareExceptionForInvalidPayload(): void
    {
        $response = $this->response('{"data":{"id":42,"roles":[]},"secret":"do-not-log"}');
        $contract = $this->contract();

        try {
            JsonPayload::data($response, $contract);
            self::fail('An invalid payload must throw.');
        } catch (InvalidPayloadException $exception) {
            self::assertSame($response, $exception->response());
            self::assertSame(JsonPayload::extract($response, $contract), $exception->result());
            self::assertStringContainsString('id [unexpected_type]', $exception->getMessage());
            self::assertStringNotContainsString('do-not-log', $exception->getMessage());
        }
    }

    /**
     * Reports malformed JSON as a normal contract result in non-throwing mode.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function testReturnsDiagnosticsForMalformedJson(): void
    {
        $result = JsonPayload::extract($this->response('{'), $this->contract());

        self::assertFalse($result->isValid());
        self::assertSame('invalid_json', $result->violations()[0]->code->value);
    }

    /**
     * Preserves the semantic difference between JSON objects and arrays.
     *
     * @throws FatalRequestException
     * @throws InvalidPayloadException
     * @throws RequestException
     */
    public function testPreservesRawJsonCollectionTypes(): void
    {
        $contract = Contract::define([
            'object' => Field::required('$.object')->object(),
            'list' => Field::required('$.list')->list(),
        ]);

        $data = JsonPayload::data($this->response('{"object":{},"list":[]}'), $contract);

        self::assertEquals(new stdClass(), $data['object']);
        self::assertSame([], $data['list']);
    }

    /**
     * Rejects duplicate object members before selecting either value.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function testRejectsDuplicateJsonMembers(): void
    {
        $response = $this->response('{"data":{"id":"first","id":"second","roles":[]}}');
        $result = JsonPayload::extract($response, $this->contract());

        self::assertFalse($result->isValid());
        self::assertSame('duplicate_object_member', $result->violations()[0]->code->value);
    }

    /**
     * Uses normalized arrays as Saloon DTO results by default.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function testMapsContractDataThroughSaloonDtoFlow(): void
    {
        $request = new ContractRequest($this->contract());
        $response = $this->send($request, '{"data":{"id":"user-2","roles":["admin"]}}');

        self::assertSame(
            ['id' => 'user-2', 'roles' => ['admin']],
            $response->dtoOrFail(),
        );
        self::assertTrue($request->jsonPayloadContractResult($response)->isValid());
    }


    /**
     * Allows applications to map normalized data into their own objects.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function testMapsContractDataIntoApplicationObject(): void
    {
        $request = new MappedContractRequest($this->contract());
        $response = $this->send($request, '{"data":{"id":"user-3","roles":[]}}');

        self::assertEquals(new UserData('user-3', 200), $response->dto());
    }

    /**
     * Supports direct middleware use for valid and invalid responses.
     *
     * @throws FatalRequestException
     * @throws InvalidPayloadException
     * @throws RequestException
     */
    public function testValidatesWithStandaloneMiddleware(): void
    {
        $middleware = new ValidateJsonPayloadContract($this->contract());

        $middleware($this->response('{"data":{"id":"user-4","roles":[]}}'));
        $this->addToAssertionCount(1);

        $this->expectException(InvalidPayloadException::class);
        $middleware($this->response('{"data":{"roles":[]}}'));
    }

    /**
     * Enforces contracts automatically and shares evaluation with DTO mapping.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function testEnforcesContractOnceAcrossMiddlewareAndDto(): void
    {
        $evaluations = 0;
        $contract = $this->contract()->validate(
            static function (array $data) use (&$evaluations): bool {
                ++$evaluations;

                return $data['id'] === 'user-5';
            },
        );
        $response = $this->send(
            new EnforcedRequest($contract),
            '{"data":{"id":"user-5","roles":[]}}',
        );

        self::assertSame(['id' => 'user-5', 'roles' => []], $response->dto());
        self::assertSame(1, $evaluations);
    }

    /**
     * Rejects invalid responses during the response middleware pipeline.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function testEnforcedContractRejectsResponseDuringSend(): void
    {
        $this->expectException(InvalidPayloadException::class);

        $this->send(new EnforcedRequest($this->contract()), '{"data":{"roles":[]}}');
    }

    /**
     * Supports contracts declared once for every request on a connector.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function testSupportsConnectorWideContracts(): void
    {
        $connector = new ContractConnector($this->contract());
        $connector->withMockClient(new MockClient([
            new MockResponse('{"data":{"id":"user-6","roles":[]}}'),
        ]));

        $response = $connector->send(new TestRequest());

        self::assertSame(['id' => 'user-6', 'roles' => []], $response->dtoOrFail());
        self::assertTrue($connector->jsonPayloadContractResult($response)->isValid());
    }

    /**
     * Creates the reusable contract used throughout the integration tests.
     */
    private function contract(): Contract
    {
        return Contract::define([
            'id' => Field::required('$.data.id')->string(),
            'roles' => Field::many('$.data.roles[*]')->string(),
        ]);
    }


    /**
     * Sends a mocked Saloon request without performing network I/O.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    private function send(TestRequest $request, string $body): Response
    {
        $connector = new TestConnector();
        $connector->withMockClient(new MockClient([new MockResponse($body)]));

        return $connector->send($request);
    }

    /**
     * Produces a plain response for direct adapter and middleware tests.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    private function response(string $body): Response
    {
        return $this->send(new TestRequest(), $body);
    }
}
