<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Epubli\PermissionBundle\DependencyInjection\Configuration;
use Epubli\PermissionBundle\Exception\PermissionExportException;
use Epubli\PermissionBundle\Service\AccessToken;
use Epubli\PermissionBundle\Service\JWTMockCreator;
use Epubli\PermissionBundle\Service\PermissionExporter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class PermissionExporterTest extends TestCase
{
    public function testPermissionExporter(): void
    {
        $requestContainer = [];
        $mockHandler = new MockHandler(
            [
                new Response(204, [], ''),
            ]
        );

        $permissionExporter = self::createPermissionExporter($requestContainer, $mockHandler);

        $countOfPermissions =
            count(PermissionDiscoveryTest::PERMISSION_KEYS_WITH_DESCRIPTIONS)
            + count(CustomPermissionDiscoveryTest::PERMISSIONS);
        $this->assertEquals($countOfPermissions, $permissionExporter->export());

        $this->assertCount(1, $requestContainer);

        /** @var Request $request */
        $request = $requestContainer[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $json = json_decode((string)$request->getBody(), true);

        $this->assertEquals('test', $json['microservice']);
        $this->assertEqualsCanonicalizing(
            array_merge(
                PermissionDiscoveryTest::PERMISSION_KEYS_WITH_DESCRIPTIONS,
                CustomPermissionDiscoveryTest::PERMISSIONS
            ),
            $json['permissions']
        );

        $this->checkAuthorizationHeader($request->getHeaders()['AUTHORIZATION'][0]);
    }

    /**
     * @param $requestContainer
     * @param MockHandler $mockHandler
     * @param null $permissionDiscovery
     * @param null $customPermissionDiscovery
     * @return PermissionExporter
     */
    private static function createPermissionExporter(
        &$requestContainer,
        MockHandler $mockHandler,
        $permissionDiscovery = null,
        $customPermissionDiscovery = null
    ): PermissionExporter {
        $handlerStack = HandlerStack::create($mockHandler);

        $history = Middleware::history($requestContainer);

        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);


        $permissionDiscovery = $permissionDiscovery ?? PermissionDiscoveryTest::createPermissionDiscovery();
        $customPermissionDiscovery = $customPermissionDiscovery ?? CustomPermissionDiscoveryTest::createCustomPermissionDiscovery(
            );

        $jwtMockCreator = new JWTMockCreator(
            $permissionDiscovery,
            $customPermissionDiscovery
        );

        return new PermissionExporter(
            $client,
            '',
            $permissionDiscovery,
            $customPermissionDiscovery,
            $jwtMockCreator
        );
    }

    /**
     * @param string $tokenStr
     */
    private function checkAuthorizationHeader(string $tokenStr): void
    {
        $requestStack = new RequestStack();
        $request = new \Symfony\Component\HttpFoundation\Request(
            [], [], [], [], [], ['HTTP_AUTHORIZATION' => $tokenStr]
        );
        $requestStack->push($request);
        $accessToken = new AccessToken($requestStack);

        $this->assertTrue($accessToken->exists());
        $this->assertTrue($accessToken->hasPermissionKey('user.permission.create_permissions'));
    }

    public function testPermissionExporterOnInvalidStatusCode(): void
    {
        $requestContainer = [];
        $mockHandler = new MockHandler(
            [
                new Response(200, [], ''),
            ]
        );

        $permissionExporter = self::createPermissionExporter($requestContainer, $mockHandler);

        try {
            $permissionExporter->export();
        } catch (PermissionExportException $e) {
            $this->assertEquals('Expected status code 204. Received instead: 200', $e->getMessage());
        }
    }

    public function testPermissionExporterOnBadRequest(): void
    {
        $requestContainer = [];
        $mockHandler = new MockHandler(
            [
                new Response(400, [], 'body'),
            ]
        );

        $permissionExporter = self::createPermissionExporter($requestContainer, $mockHandler);

        try {
            $permissionExporter->export();
        } catch (PermissionExportException $e) {
            $this->assertEquals('Status Code: 400\nBody: body', $e->getMessage());
        }
    }

    public function testPermissionExporterOnClientError(): void
    {
        $requestContainer = [];
        $mockHandler = new MockHandler(
            [
                new ClientException(
                    'Error Communicating with Server',
                    new Request('GET', 'test'),
                    new Response(400, [], 'body')
                ),
            ]
        );

        $permissionExporter = self::createPermissionExporter($requestContainer, $mockHandler);

        try {
            $permissionExporter->export();
        } catch (PermissionExportException $e) {
            $this->assertEquals('Status Code: 400\nBody: body', $e->getMessage());
        }
    }

    public function testPermissionExporterOnServerError(): void
    {
        $requestContainer = [];
        $mockHandler = new MockHandler(
            [
                new ServerException(
                    'Error Communicating with Server',
                    new Request('GET', 'test'),
                    new Response(500, [], 'body')
                ),
            ]
        );

        $permissionExporter = self::createPermissionExporter($requestContainer, $mockHandler);

        try {
            $permissionExporter->export();
            $this->assertTrue(false);
        } catch (PermissionExportException $e) {
            $this->assertEquals('Status Code: 500\nBody: body', $e->getMessage());
        }
    }

    public function testPermissionExporterRequestBody(): void
    {
        $requestContainer = [];
        $mockHandler = new MockHandler(
            [
                new Response(204, [], ''),
            ]
        );

        $permissionExporter = self::createPermissionExporter($requestContainer, $mockHandler);

        $countOfPermissions =
            count(PermissionDiscoveryTest::PERMISSION_KEYS_WITH_DESCRIPTIONS)
            + count(CustomPermissionDiscoveryTest::PERMISSIONS);
        $this->assertEquals($countOfPermissions, $permissionExporter->export());

        $this->assertCount(1, $requestContainer);

        /** @var Request $request */
        $request = $requestContainer[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $json = json_decode((string)$request->getBody(), true);

        $this->assertEquals('test', $json['microservice']);
        $this->assertEqualsCanonicalizing(
            array_merge(
                PermissionDiscoveryTest::PERMISSION_KEYS_WITH_DESCRIPTIONS,
                CustomPermissionDiscoveryTest::PERMISSIONS
            ),
            $json['permissions']
        );
    }

    public function testPermissionExporterOnDefaultMicroserviceName(): void
    {
        $requestContainer = [];
        $mockHandler = new MockHandler(
            [
                new Response(204, [], ''),
            ]
        );

        $permissionDiscovery = PermissionDiscoveryTest::createPermissionDiscovery(
            'tests/Helpers',
            Configuration::DEFAULT_MICROSERVICE_NAME
        );

        $permissionExporter = self::createPermissionExporter($requestContainer, $mockHandler, $permissionDiscovery);

        try {
            $permissionExporter->export();
            $this->assertTrue(false);
        } catch (PermissionExportException $e) {
            $this->assertEquals(
                'Please make sure to set the name of your microservice in config/packages/epubli_permission.yaml!',
                $e->getMessage()
            );
        }
    }

    public function testPermissionExporterOnNoEntityPermissions(): void
    {
        $requestContainer = [];
        $mockHandler = new MockHandler(
            [
                new Response(204, [], ''),
            ]
        );

        $permissionDiscovery = PermissionDiscoveryTest::createPermissionDiscovery(
            'invalidPath'
        );

        $permissionExporter = self::createPermissionExporter($requestContainer, $mockHandler, $permissionDiscovery);

        $countOfPermissions = count(CustomPermissionDiscoveryTest::PERMISSIONS);
        $this->assertEquals($countOfPermissions, $permissionExporter->export());

        $this->assertCount(1, $requestContainer);

        /** @var Request $request */
        $request = $requestContainer[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $json = json_decode((string)$request->getBody(), true);

        $this->assertEqualsCanonicalizing(
            CustomPermissionDiscoveryTest::PERMISSIONS,
            $json['permissions']
        );
    }

    public function testPermissionExporterOnNoCustomPermissions(): void
    {
        $requestContainer = [];
        $mockHandler = new MockHandler(
            [
                new Response(204, [], ''),
            ]
        );

        $customPermissionDiscovery = CustomPermissionDiscoveryTest::createCustomPermissionDiscovery(
            'invalidPath'
        );

        $permissionExporter = self::createPermissionExporter(
            $requestContainer,
            $mockHandler,
            PermissionDiscoveryTest::createPermissionDiscovery(),
            $customPermissionDiscovery
        );

        $countOfPermissions = count(PermissionDiscoveryTest::PERMISSION_KEYS_WITH_DESCRIPTIONS);
        $this->assertEquals($countOfPermissions, $permissionExporter->export());

        $this->assertCount(1, $requestContainer);

        /** @var Request $request */
        $request = $requestContainer[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $json = json_decode((string)$request->getBody(), true);

        $this->assertEqualsCanonicalizing(
            PermissionDiscoveryTest::PERMISSION_KEYS_WITH_DESCRIPTIONS,
            $json['permissions']
        );
    }

    public function testPermissionExporterOnNoPermissions(): void
    {
        $requestContainer = [];
        $mockHandler = new MockHandler(
            [
                new Response(204, [], ''),
            ]
        );

        $permissionDiscovery = PermissionDiscoveryTest::createPermissionDiscovery(
            'invalidPath'
        );
        $customPermissionDiscovery = CustomPermissionDiscoveryTest::createCustomPermissionDiscovery(
            'invalidPath'
        );

        $permissionExporter = self::createPermissionExporter(
            $requestContainer,
            $mockHandler,
            $permissionDiscovery,
            $customPermissionDiscovery
        );

        $this->assertEquals(0, $permissionExporter->export());

        $this->assertCount(0, $requestContainer);
    }
}
