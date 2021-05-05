<?php

namespace Epubli\PermissionBundle\Traits;

use Epubli\PermissionBundle\EndpointWithPermission;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestConfig;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestDeleteData;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestGetCollectionData;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestGetItemData;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestPostData;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestUpdateData;
use Negotiation\Exception\InvalidArgument;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Trait UnitTestTrait
 * @package Epubli\PermissionBundle\Traits
 * @method static void assertTrue($condition, string $message = '')
 * @method static void assertResponseStatusCodeSame(int $expectedCode, string $message = '')
 * @method static void assertEquals($expected, $actual, string $message = '', float $delta = 0.0, int $maxDepth = 10, bool $canonicalize = false, bool $ignoreCase = false)
 * @method static void assertArrayHasKey($key, $array, string $message = '')
 * @method static void assertNotCount(int $expectedCount, $haystack, string $message = '')
 * @method static void assertCount(int $expectedCount, $haystack, string $message = '')
 * @method array getJson()
 * @method Response request(string $uri, string $method = 'GET', $content = null, array $files = [], array $parameters = [], array $headers = [], bool $changeHistory = true)
 * @method void expectException(string $exception)
 */
trait UnitTestTrait
{
    /** @var UnitTestConfig */
    private static $unitTestConfig;

    /**
     * This method needs to return the data used for testing the delete route.
     * @return UnitTestDeleteData|null
     */
    abstract public function getDeleteDataForPermissionBundle(): ?UnitTestDeleteData;

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleDelete(): void
    {
        if (!self::$unitTestConfig->hasDeleteRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getDeleteDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Delete-Data should not be null!');
        }

        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            self::$unitTestConfig->hasSecurityOnDeleteRoute,
            [$data->getPermissionKey()],
            -1,
            'DELETE',
            null
        );
        self::assertResponseStatusCodeSame(204);
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfDelete(): void
    {
        if (!self::$unitTestConfig->hasSelfDelete()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getDeleteDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Delete-Data should not be null!');
        }

        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            true,
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            $data->getUserId(),
            'DELETE',
            null
        );
        self::assertResponseStatusCodeSame(204);
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleDeleteDenied(): void
    {
        if (!self::$unitTestConfig->hasDeleteRoute
            || !self::$unitTestConfig->hasSecurityOnDeleteRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getDeleteDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Delete-Data should not be null!');
        }
        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            -1,
            'DELETE',
            null
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfDeleteDeniedOnInvalidUser(): void
    {
        if (!self::$unitTestConfig->hasSelfDelete()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getDeleteDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Delete-Data should not be null!');
        }
        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            -1,
            'DELETE',
            null
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfDeleteDeniedOnInvalidPermission(): void
    {
        if (!self::$unitTestConfig->hasSelfDelete()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getDeleteDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Delete-Data should not be null!');
        }

        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            $data->getUserId(),
            'DELETE',
            null
        );
    }

    /**
     * This method needs to return the data used for testing the put and patch route.
     * @return UnitTestUpdateData|null
     */
    abstract public function getUpdateDataForPermissionBundle(): ?UnitTestUpdateData;

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundlePut(): void
    {
        if (!self::$unitTestConfig->hasPutRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getUpdateDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Update-Data should not be null!');
        }

        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            self::$unitTestConfig->hasSecurityOnPutRoute,
            [$data->getPermissionKey()],
            -1,
            'PUT',
            $data->getPayload()
        );
        $json = $this->getJson();

        self::assertResponseStatusCodeSame(200);
        if ($data->getJsonKey() !== null) {
            self::assertEquals($json[$data->getJsonKey()], $data->getNewValue());
        }
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfPut(): void
    {
        if (!self::$unitTestConfig->hasSelfPut()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getUpdateDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Update-Data should not be null!');
        }
        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            true,
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            $data->getUserId(),
            'PUT',
            $data->getPayload()
        );
        $json = $this->getJson();

        self::assertResponseStatusCodeSame(200);
        if ($data->getJsonKey() !== null) {
            self::assertEquals($json[$data->getJsonKey()], $data->getNewValue());
        }
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundlePutDenied(): void
    {
        if (!self::$unitTestConfig->hasPutRoute
            || !self::$unitTestConfig->hasSecurityOnPutRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getUpdateDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Update-Data should not be null!');
        }
        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            -1,
            'PUT',
            $data->getPayload()
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfPutDeniedOnInvalidUser(): void
    {
        if (!self::$unitTestConfig->hasSelfPut()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getUpdateDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Update-Data should not be null!');
        }
        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            -1,
            'PUT',
            $data->getPayload()
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfPutDeniedOnInvalidPermission(): void
    {
        if (!self::$unitTestConfig->hasSelfPut()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getUpdateDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Update-Data should not be null!');
        }

        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            $data->getUserId(),
            'PUT',
            $data->getPayload()
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundlePatch(): void
    {
        if (!self::$unitTestConfig->hasPatchRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getUpdateDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Update-Data should not be null!');
        }

        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            self::$unitTestConfig->hasSecurityOnPatchRoute,
            [$data->getPermissionKey()],
            -1,
            'PATCH',
            $data->getPayload()
        );
        $json = $this->getJson();

        self::assertResponseStatusCodeSame(200);
        if ($data->getJsonKey() !== null) {
            self::assertEquals($json[$data->getJsonKey()], $data->getNewValue());
        }
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfPatch(): void
    {
        if (!self::$unitTestConfig->hasSelfPatch()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getUpdateDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Update-Data should not be null!');
        }
        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            true,
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            $data->getUserId(),
            'PATCH',
            $data->getPayload()
        );
        $json = $this->getJson();

        self::assertResponseStatusCodeSame(200);
        if ($data->getJsonKey() !== null) {
            self::assertEquals($json[$data->getJsonKey()], $data->getNewValue());
        }
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundlePatchDenied(): void
    {
        if (!self::$unitTestConfig->hasPatchRoute
            || !self::$unitTestConfig->hasSecurityOnPatchRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getUpdateDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Update-Data should not be null!');
        }
        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            -1,
            'PATCH',
            $data->getPayload()
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfPatchDeniedOnInvalidUser(): void
    {
        if (!self::$unitTestConfig->hasSelfPatch()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getUpdateDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Update-Data should not be null!');
        }
        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            -1,
            'PATCH',
            $data->getPayload()
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfPatchDeniedOnInvalidPermission(): void
    {
        if (!self::$unitTestConfig->hasSelfPatch()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getUpdateDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Update-Data should not be null!');
        }

        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            $data->getUserId(),
            'PATCH',
            $data->getPayload()
        );
    }

    /**
     * This method needs to return the data used for testing the post route.
     * @return UnitTestPostData|null
     */
    abstract public function getPostDataForPermissionBundle(): ?UnitTestPostData;

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundlePost(): void
    {
        if (!self::$unitTestConfig->hasPostRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getPostDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Post-Data should not be null!');
        }

        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            self::$unitTestConfig->hasSecurityOnPostRoute,
            [$data->getPermissionKey()],
            -1,
            'POST',
            $data->getPayload()
        );
        self::assertResponseStatusCodeSame(201);
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfPost(): void
    {
        if (!self::$unitTestConfig->hasSelfPost()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getPostDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Post-Data should not be null!');
        }

        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            true,
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            $data->getUserId(),
            'POST',
            $data->getPayload()
        );
        self::assertResponseStatusCodeSame(201);
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundlePostDenied(): void
    {
        if (!self::$unitTestConfig->hasPostRoute
            || !self::$unitTestConfig->hasSecurityOnPostRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getPostDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Post-Data should not be null!');
        }
        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            -1,
            'POST',
            $data->getPayload()
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfPostDeniedOnInvalidUser(): void
    {
        if (!self::$unitTestConfig->hasSelfPost()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getPostDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Post-Data should not be null!');
        }
        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            -1,
            'POST',
            $data->getPayload()
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfPostDeniedOnInvalidPermission(): void
    {
        if (!self::$unitTestConfig->hasSelfPost()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getPostDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Post-Data should not be null!');
        }

        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            $data->getUserId(),
            'POST',
            $data->getPayload()
        );
    }

    /**
     * This method needs to return the data used for testing the get item route.
     * @return UnitTestGetItemData|null
     */
    abstract public function getGetItemDataForPermissionBundle(): ?UnitTestGetItemData;

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleGetItem(): void
    {
        if (!self::$unitTestConfig->hasGetItemRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getGetItemDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Get-Item-Data should not be null!');
        }

        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            self::$unitTestConfig->hasSecurityOnGetItemRoute,
            [$data->getPermissionKey()],
            -1,
            'GET',
            null
        );
        self::assertResponseStatusCodeSame(200);
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfGetItem(): void
    {
        if (!self::$unitTestConfig->hasSelfGetItem()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getGetItemDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Get-Item-Data should not be null!');
        }

        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            true,
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            $data->getUserId(),
            'GET',
            null
        );
        self::assertResponseStatusCodeSame(200);
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleGetItemDenied(): void
    {
        if (!self::$unitTestConfig->hasGetItemRoute
            || !self::$unitTestConfig->hasSecurityOnGetItemRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getGetItemDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Get-Item-Data should not be null!');
        }
        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            -1,
            'GET',
            null
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfGetItemNotFoundOnInvalidUser(): void
    {
        if (!self::$unitTestConfig->hasSelfGetItem()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getGetItemDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Get-Item-Data should not be null!');
        }
        $this->sendRequestWithCookieAndExpectNotFound(
            $data->getResourceURI(),
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            -1,
            'GET',
            null
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfGetItemDeniedOnInvalidPermission(): void
    {
        if (!self::$unitTestConfig->hasSelfGetItem()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getGetItemDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Get-Item-Data should not be null!');
        }

        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            $data->getUserId(),
            'GET',
            null
        );
    }

    /**
     * This method needs to return the data used for testing the get collection route.
     * @return UnitTestGetCollectionData|null
     */
    abstract public function getGetCollectionDataForPermissionBundle(): ?UnitTestGetCollectionData;

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleGetCollection(): void
    {
        if (!self::$unitTestConfig->hasGetCollectionRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getGetCollectionDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Get-Collection-Data should not be null!');
        }

        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            self::$unitTestConfig->hasSecurityOnGetCollectionRoute,
            [$data->getPermissionKey()],
            -1,
            'GET',
            null
        );

        self::assertResponseStatusCodeSame(200);
        $json = $this->getJson();
        self::assertArrayHasKey('hydra:member', $json);
        self::assertNotCount(0, $json['hydra:member']);
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfGetCollection(): void
    {
        if (!self::$unitTestConfig->hasSelfGetCollection()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getGetCollectionDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Get-Collection-Data should not be null!');
        }

        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            true,
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            $data->getUserId(),
            'GET',
            null
        );

        self::assertResponseStatusCodeSame(200);
        $json = $this->getJson();
        self::assertArrayHasKey('hydra:member', $json);
        self::assertCount($data->getExpectedCount(), $json['hydra:member']);
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleGetCollectionDenied(): void
    {
        if (!self::$unitTestConfig->hasGetCollectionRoute
            || !self::$unitTestConfig->hasSecurityOnGetCollectionRoute) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getGetCollectionDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Get-Collection-Data should not be null!');
        }
        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            -1,
            'GET',
            null
        );
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfGetCollectionEmptyOnInvalidUser(): void
    {
        if (!self::$unitTestConfig->hasSelfGetCollection()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getGetCollectionDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Get-Collection-Data should not be null!');
        }
        $this->sendRequestWithCookie(
            $data->getResourceURI(),
            true,
            [$data->getPermissionKey() . EndpointWithPermission::SELF_PERMISSION],
            -1,
            'GET',
            null
        );

        self::assertResponseStatusCodeSame(200);
        $json = $this->getJson();
        self::assertArrayHasKey('hydra:member', $json);
        self::assertCount(0, $json['hydra:member']);
    }

    /**
     * Do NOT call this method. It will be called automatically by PHPUnit
     */
    public function testPermissionBundleSelfGetCollectionDeniedOnInvalidPermission(): void
    {
        if (!self::$unitTestConfig->hasSelfGetCollection()) {
            self::assertTrue(true);
            return;
        }

        $data = $this->getGetCollectionDataForPermissionBundle();
        if ($data === null) {
            throw new InvalidArgument('Get-Collection-Data should not be null!');
        }

        $this->sendRequestWithCookieAndExpectDenied(
            $data->getResourceURI(),
            [],
            $data->getUserId(),
            'GET',
            null
        );
    }

    /**
     * Sends a request with a specific cookie.
     * @param string $resourceURI
     * @param bool $setNewCookie
     * @param string[] $permissionKeys
     * @param int $userId
     * @param string $httpMethod
     * @param string|null $payload
     */
    private function sendRequestWithCookie(
        string $resourceURI,
        bool $setNewCookie,
        array $permissionKeys,
        int $userId,
        string $httpMethod,
        ?string $payload
    ): void {
        if ($setNewCookie) {
            $this->clearOldCookiesAndSetNewCookie(
                $permissionKeys,
                $userId
            );
        } else {
            self::$kernelBrowser->getCookieJar()->clear();
        }

        $this->request(
            $resourceURI,
            $httpMethod,
            $payload
        );
    }

    /**
     * Sends a request and expects a AccessDeniedHttpException.
     * This needs to be last call in the method.
     * Nothing will be executed after this.
     * @param string $resourceURI
     * @param string[] $permissionKeys
     * @param int $userId
     * @param string $httpMethod
     * @param string|null $payload
     */
    private function sendRequestWithCookieAndExpectDenied(
        string $resourceURI,
        array $permissionKeys,
        int $userId,
        string $httpMethod,
        ?string $payload
    ): void {
        $this->clearOldCookiesAndSetNewCookie(
            $permissionKeys,
            $userId
        );

        self::$kernelBrowser->catchExceptions(false);
        $this->expectException(AccessDeniedHttpException::class);
        $this->request(
            $resourceURI,
            $httpMethod,
            $payload
        );
    }

    /**
     * Sends a request and expects a NotFoundHttpException.
     * This needs to be last call in the method.
     * Nothing will be executed after this.
     * @param string $resourceURI
     * @param string[] $permissionKeys
     * @param int $userId
     * @param string $httpMethod
     * @param string|null $payload
     */
    private function sendRequestWithCookieAndExpectNotFound(
        string $resourceURI,
        array $permissionKeys,
        int $userId,
        string $httpMethod,
        ?string $payload
    ): void {
        $this->clearOldCookiesAndSetNewCookie(
            $permissionKeys,
            $userId
        );

        self::$kernelBrowser->catchExceptions(false);
        $this->expectException(NotFoundHttpException::class);
        $this->request(
            $resourceURI,
            $httpMethod,
            $payload
        );
    }

    /**
     * Clears all current cookies in the <code>self::$kernelBrowser</code>
     * and adds a new one with the specified permission keys and user id.
     * @param string[] $permissionKeys
     * @param int $userId
     */
    private function clearOldCookiesAndSetNewCookie(array $permissionKeys, int $userId = -1): void
    {
        self::$kernelBrowser->getCookieJar()->clear();
        self::$kernelBrowser->getCookieJar()->set(
            self::$jwtMockCreator->createBrowserKitCookie(
                self::$jwtMockCreator->createJsonWebToken(
                    $permissionKeys,
                    $userId
                )
            )
        );
    }
}