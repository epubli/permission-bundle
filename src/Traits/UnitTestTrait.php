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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait UnitTestTrait
{
    /** @var UnitTestConfig */
    private static $unitTestConfig;

    /**
     * @return UnitTestDeleteData|null
     */
    abstract public function getDeleteDataForPermissionBundle(): ?UnitTestDeleteData;

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
     * @return UnitTestUpdateData|null
     */
    abstract public function getUpdateDataForPermissionBundle(): ?UnitTestUpdateData;

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
            'PUT',
            $data->getPayload()
        );
        $json = $this->getJson();

        self::assertResponseStatusCodeSame(200);
        if ($data->getJsonKey() !== null) {
            self::assertEquals($json[$data->getJsonKey()], $data->getNewValue());
        }
    }

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
            'PUT',
            $data->getPayload()
        );
        $json = $this->getJson();

        self::assertResponseStatusCodeSame(200);
        if ($data->getJsonKey() !== null) {
            self::assertEquals($json[$data->getJsonKey()], $data->getNewValue());
        }
    }

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
     * @return UnitTestPostData|null
     */
    abstract public function getPostDataForPermissionBundle(): ?UnitTestPostData;

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
     * @return UnitTestGetItemData|null
     */
    abstract public function getGetItemDataForPermissionBundle(): ?UnitTestGetItemData;

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
     * @return UnitTestGetCollectionData|null
     */
    abstract public function getGetCollectionDataForPermissionBundle(): ?UnitTestGetCollectionData;

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

    public function testPermissionBundleSelfGetCollectionDeniedOnInvalidUser(): void
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