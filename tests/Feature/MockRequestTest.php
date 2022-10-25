<?php

use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface;
use Sammyjo20\Saloon\Http\Fixture;
use Sammyjo20\Saloon\Http\MockResponse;
use Sammyjo20\Saloon\Clients\MockClient;
use Sammyjo20\Saloon\Http\Responses\SaloonResponse;
use Sammyjo20\Saloon\Http\SaloonRequest;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\UserRequest;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\ErrorRequest;
use Sammyjo20\Saloon\Tests\Fixtures\Connectors\TestConnector;
use Sammyjo20\Saloon\Tests\Fixtures\Mocking\CallableMockResponse;
use Sammyjo20\Saloon\Exceptions\SaloonNoMockResponseFoundException;
use Sammyjo20\Saloon\Tests\Fixtures\Connectors\QueryParameterConnector;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\DifferentServiceUserRequest;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\QueryParameterConnectorRequest;

$filesystem = new Filesystem(new LocalFilesystemAdapter('tests/Fixtures/Saloon'));

beforeEach(function () use ($filesystem) {
    $filesystem->deleteDirectory('/');
    $filesystem->createDirectory('/');
});

test('a request can be mocked with a sequence', function () {
    $mockClient = new MockClient([
        MockResponse::make(['name' => 'Sam'], 200),
        MockResponse::make(['name' => 'Alex'], 200),
        MockResponse::make(['error' => 'Server Unavailable'], 500),
    ]);

    $responseA = (new UserRequest)->send($mockClient);

    expect($responseA->isMocked())->toBeTrue();
    expect($responseA->json())->toEqual(['name' => 'Sam']);
    expect($responseA->status())->toEqual(200);

    $responseB = (new UserRequest)->send($mockClient);

    expect($responseB->isMocked())->toBeTrue();
    expect($responseB->json())->toEqual(['name' => 'Alex']);
    expect($responseB->status())->toEqual(200);

    $responseC = (new UserRequest)->send($mockClient);

    expect($responseC->isMocked())->toBeTrue();
    expect($responseC->json())->toEqual(['error' => 'Server Unavailable']);
    expect($responseC->status())->toEqual(500);

    $this->expectException(SaloonNoMockResponseFoundException::class);

    (new UserRequest)->send($mockClient);
});

test('a request can be mocked with a connector defined', function () {
    $responseA = MockResponse::make(['name' => 'Sammyjo20'], 200);
    $responseB = MockResponse::make(['name' => 'Alex'], 200);

    $connectorARequest = new UserRequest;
    $connectorBRequest = new QueryParameterConnectorRequest;

    $mockClient = new MockClient([
        TestConnector::class => $responseA,
        QueryParameterConnector::class => $responseB,
    ]);

    $responseA = $connectorARequest->send($mockClient);

    expect($responseA->isMocked())->toBeTrue();
    expect($responseA->json())->toEqual(['name' => 'Sammyjo20']);
    expect($responseA->status())->toEqual(200);

    $responseB = $connectorBRequest->send($mockClient);

    expect($responseB->isMocked())->toBeTrue();
    expect($responseB->json())->toEqual(['name' => 'Alex']);
    expect($responseB->status())->toEqual(200);
});

test('a request can be mocked with a request defined', function () {
    $responseA = MockResponse::make(['name' => 'Sammyjo20'], 200);
    $responseB = MockResponse::make(['name' => 'Alex'], 200);

    $requestA = new UserRequest;
    $requestB = new QueryParameterConnectorRequest;

    $mockClient = new MockClient([
        UserRequest::class => $responseA,
        QueryParameterConnectorRequest::class => $responseB,
    ]);

    $responseA = $requestA->send($mockClient);

    expect($responseA->isMocked())->toBeTrue();
    expect($responseA->json())->toEqual(['name' => 'Sammyjo20']);
    expect($responseA->status())->toEqual(200);

    $responseB = $requestB->send($mockClient);

    expect($responseB->isMocked())->toBeTrue();
    expect($responseB->json())->toEqual(['name' => 'Alex']);
    expect($responseB->status())->toEqual(200);
});

test('a request can be mocked with a url defined', function () {
    $responseA = MockResponse::make(['name' => 'Sammyjo20'], 200);
    $responseB = MockResponse::make(['name' => 'Alex'], 200);
    $responseC = MockResponse::make(['error' => 'Server Broken'], 500);

    $requestA = new UserRequest;
    $requestB = new ErrorRequest;
    $requestC = new DifferentServiceUserRequest;

    $mockClient = new MockClient([
        'tests.saloon.dev/api/user' => $responseA, // Test Exact Route
        'tests.saloon.dev/*' => $responseB, // Test Wildcard Routes
        'google.com/*' => $responseC, // Test Different Route,
    ]);

    $responseA = $requestA->send($mockClient);

    expect($responseA->isMocked())->toBeTrue();
    expect($responseA->json())->toEqual(['name' => 'Sammyjo20']);
    expect($responseA->status())->toEqual(200);

    $responseB = $requestB->send($mockClient);

    expect($responseB->isMocked())->toBeTrue();
    expect($responseB->json())->toEqual(['name' => 'Alex']);
    expect($responseB->status())->toEqual(200);

    $responseC = $requestC->send($mockClient);

    expect($responseC->isMocked())->toBeTrue();
    expect($responseC->json())->toEqual(['error' => 'Server Broken']);
    expect($responseC->status())->toEqual(500);
});

test('you can create wildcard url mocks', function () {
    $responseA = MockResponse::make(['name' => 'Sammyjo20'], 200);
    $responseB = MockResponse::make(['name' => 'Alex'], 200);
    $responseC = MockResponse::make(['error' => 'Server Broken'], 500);

    $requestA = new UserRequest;
    $requestB = new ErrorRequest;
    $requestC = new DifferentServiceUserRequest;

    $mockClient = new MockClient([
        'tests.saloon.dev/api/user' => $responseA, // Test Exact Route
        'tests.saloon.dev/*' => $responseB, // Test Wildcard Routes
        '*' => $responseC,
    ]);

    $responseA = $requestA->send($mockClient);

    expect($responseA->isMocked())->toBeTrue();
    expect($responseA->json())->toEqual(['name' => 'Sammyjo20']);
    expect($responseA->status())->toEqual(200);

    $responseB = $requestB->send($mockClient);

    expect($responseB->isMocked())->toBeTrue();
    expect($responseB->json())->toEqual(['name' => 'Alex']);
    expect($responseB->status())->toEqual(200);

    $responseC = $requestC->send($mockClient);

    expect($responseC->isMocked())->toBeTrue();
    expect($responseC->json())->toEqual(['error' => 'Server Broken']);
    expect($responseC->status())->toEqual(500);
});

test('you can use a closure for the mock response', function () {
    $sequenceMock = new MockClient([
        function (SaloonRequest $request): MockResponse {
            return new MockResponse(['request' => $request->getRequestUrl()]);
        },
    ]);

    $sequenceResponse = UserRequest::make()->send($sequenceMock);

    expect($sequenceResponse->isMocked())->toBeTrue();
    expect($sequenceResponse->json())->toEqual(['request' => 'https://tests.saloon.dev/api/user']);

    // Connector mock

    $connectorMock = new MockClient([
        TestConnector::class => function (SaloonRequest $request): MockResponse {
            return new MockResponse(['request' => $request->getRequestUrl()]);
        },
    ]);

    $connectorResponse = UserRequest::make()->send($connectorMock);

    expect($connectorResponse->isMocked())->toBeTrue();
    expect($connectorResponse->json())->toEqual(['request' => 'https://tests.saloon.dev/api/user']);

    // Request mock

    $requestMock = new MockClient([
        UserRequest::class => function (SaloonRequest $request): MockResponse {
            return new MockResponse(['request' => $request->getRequestUrl()]);
        },
    ]);

    $requestResponse = UserRequest::make()->send($requestMock);

    expect($requestResponse->isMocked())->toBeTrue();
    expect($requestResponse->json())->toEqual(['request' => 'https://tests.saloon.dev/api/user']);

    // URL mock

    $urlMock = new MockClient([
        'tests.saloon.dev/*' => function (SaloonRequest $request): MockResponse {
            return new MockResponse(['request' => $request->getRequestUrl()]);
        },
    ]);

    $urlResponse = UserRequest::make()->send($urlMock);

    expect($urlResponse->isMocked())->toBeTrue();
    expect($urlResponse->json())->toEqual(['request' => 'https://tests.saloon.dev/api/user']);
});

test('you can use a callable class as the mock response', function () {
    $mockClient = new MockClient([
        UserRequest::class => new CallableMockResponse,
    ]);

    $sequenceResponse = UserRequest::make()->send($mockClient);

    expect($sequenceResponse->isMocked())->toBeTrue();
    expect($sequenceResponse->json())->toEqual(['request_class' => UserRequest::class]);
});

test('a fixture can be used with a mock sequence', function () {
    $mockClient = new MockClient([
        MockResponse::fixture('user'),
        MockResponse::fixture('user'),
    ]);

    $responseA = UserRequest::make()->send($mockClient);

    expect($responseA->isMocked())->toBeFalse();
    expect($responseA->status())->toEqual(200);
    expect($responseA->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);

    $responseB = UserRequest::make()->send($mockClient);

    expect($responseB->isMocked())->toBeTrue();
    expect($responseB->status())->toEqual(200);
    expect($responseB->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);
});

test('a fixture can be used with a connector mock', function () {
    $mockClient = new MockClient([
        TestConnector::class => MockResponse::fixture('connector'),
    ]);

    $responseA = UserRequest::make()->send($mockClient);

    expect($responseA->isMocked())->toBeFalse();
    expect($responseA->status())->toEqual(200);
    expect($responseA->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);

    $responseB = UserRequest::make()->send($mockClient);

    expect($responseB->isMocked())->toBeTrue();
    expect($responseB->status())->toEqual(200);
    expect($responseB->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);

    // Even though it's a different request, it should use the same fixture

    $responseC = ErrorRequest::make()->send($mockClient);

    expect($responseC->isMocked())->toBeTrue();
    expect($responseC->status())->toEqual(200);
    expect($responseC->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);
});

test('a fixture can be used with a request mock', function () use ($filesystem) {
    $mockClient = new MockClient([
        UserRequest::class => MockResponse::fixture('user'),
    ]);

    expect($filesystem->fileExists('user.json'))->toBeFalse();

    $responseA = UserRequest::make()->send($mockClient);

    expect($responseA->isMocked())->toBeFalse();
    expect($responseA->status())->toEqual(200);
    expect($responseA->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);

    expect($filesystem->fileExists('user.json'))->toBeTrue();

    $responseB = UserRequest::make()->send($mockClient);

    expect($responseB->isMocked())->toBeTrue();
    expect($responseB->status())->toEqual(200);
    expect($responseB->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);
});

test('a fixture can be used with a url mock', function () use ($filesystem) {
    $mockClient = new MockClient([
        'tests.saloon.dev/api/user' => MockResponse::fixture('user'), // Test Exact Route
        'tests.saloon.dev/*' => MockResponse::fixture('other'), // Test Wildcard Routes
    ]);

    expect($filesystem->fileExists('user.json'))->toBeFalse();
    expect($filesystem->fileExists('other.json'))->toBeFalse();

    $responseA = UserRequest::make()->send($mockClient);

    expect($filesystem->fileExists('user.json'))->toBeTrue();
    expect($filesystem->fileExists('other.json'))->toBeFalse();

    expect($responseA->isMocked())->toBeFalse();
    expect($responseA->status())->toEqual(200);
    expect($responseA->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);

    $responseB = ErrorRequest::make()->send($mockClient);

    expect($filesystem->fileExists('user.json'))->toBeTrue();
    expect($filesystem->fileExists('other.json'))->toBeTrue();

    expect($responseB->isMocked())->toBeFalse();
    expect($responseB->status())->toEqual(500);
    expect($responseB->json())->toEqual([
        'message' => 'Fake Error',
    ]);

    // This should use the first mock

    $responseC = UserRequest::make()->send($mockClient);

    expect($responseC->isMocked())->toBeTrue();
    expect($responseC->status())->toEqual(200);
    expect($responseC->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);

    // Another error request should use the "other" mock

    $responseD = ErrorRequest::make()->send($mockClient);

    expect($responseD->isMocked())->toBeTrue();
    expect($responseD->status())->toEqual(500);
    expect($responseD->json())->toEqual([
        'message' => 'Fake Error',
    ]);
});

test('a fixture can be used with a wildcard url mock', function () {
    $mockClient = new MockClient([
        '*' => MockResponse::fixture('user'), // Test Exact Route
    ]);

    $responseA = UserRequest::make()->send($mockClient);

    expect($responseA->isMocked())->toBeFalse();
    expect($responseA->status())->toEqual(200);
    expect($responseA->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);

    $responseB = ErrorRequest::make()->send($mockClient);

    expect($responseB->isMocked())->toBeTrue();
    expect($responseB->status())->toEqual(200);
    expect($responseB->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);
});

test('a fixture can be used within a closure mock', function () use ($filesystem) {
    $mockClient = new MockClient([
        '*' => function (SaloonRequest $request) {
            if ($request instanceof UserRequest) {
                return MockResponse::fixture('user');
            }

            return MockResponse::fixture('other');
        },
    ]);

    expect($filesystem->fileExists('user.json'))->toBeFalse();
    expect($filesystem->fileExists('other.json'))->toBeFalse();

    $responseA = UserRequest::make()->send($mockClient);

    expect($responseA->isMocked())->toBeFalse();
    expect($responseA->status())->toEqual(200);
    expect($responseA->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);

    $responseB = UserRequest::make()->send($mockClient);

    expect($responseB->isMocked())->toBeTrue();
    expect($responseB->status())->toEqual(200);
    expect($responseB->json())->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);

    // Now we'll test a different route

    $responseC = ErrorRequest::make()->send($mockClient);

    expect($responseC->isMocked())->toBeFalse();
    expect($responseC->status())->toEqual(500);
    expect($responseC->json())->toEqual([
        'message' => 'Fake Error',
    ]);

    // Another error request should use the "other" mock

    $responseD = ErrorRequest::make()->send($mockClient);

    expect($responseD->isMocked())->toBeTrue();
    expect($responseD->status())->toEqual(500);
    expect($responseD->json())->toEqual([
        'message' => 'Fake Error',
    ]);
});
