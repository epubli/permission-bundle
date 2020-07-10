<?php

namespace Epubli\PermissionBundle\Security;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Annotations\Reader;
use Epubli\PermissionBundle\EndpointWithPermission;
use Epubli\PermissionBundle\Interfaces\SelfPermissionInterface;
use Epubli\PermissionBundle\Service\AuthToken;
use Epubli\PermissionBundle\Service\PermissionDiscovery;
use LogicException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var AuthToken
     */
    private $authToken;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var PermissionDiscovery
     */
    private $permissionDiscovery;

    /**
     * @param Reader $annotationReader
     * @param AuthToken $authToken
     * @param RequestStack $requestStack
     * @param PermissionDiscovery $permissionDiscovery
     */
    public function __construct(
        Reader $annotationReader,
        AuthToken $authToken,
        RequestStack $requestStack,
        PermissionDiscovery $permissionDiscovery
    ) {
        $this->annotationReader = $annotationReader;
        $this->authToken = $authToken;
        $this->requestStack = $requestStack;
        $this->permissionDiscovery = $permissionDiscovery;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     * @throws ReflectionException
     */
    protected function supports($attribute, $subject): bool
    {
        if ($attribute !== null) {
            return false;
        }

        // If the subject is a string check if class exists to support get on collections
        if (is_string($subject) && class_exists($subject)) {
            $subject = new $subject;
        }

        /** @var ApiResource $annotation */
        $annotation = $this->annotationReader->getClassAnnotation(
            new ReflectionClass($subject),
            ApiResource::class
        );

        return $annotation !== null;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     * @throws ReflectionException
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new LogicException('Request should not be null.');
        }

        $isGetRequestOnCollection = false;
        // If the subject is a string check if class exists to support get on collections
        if (is_string($subject) && class_exists($subject)) {
            $subject = new $subject;
            $isGetRequestOnCollection = true;
        }

        $permissionKey = $this->permissionDiscovery->getPermissionKey(
            $subject,
            $request->getMethod(),
            $request->getPathInfo()
        );

        if ($permissionKey === null) {
            //This endpoint does not need permissions to be accessed
            return true;
        }

        $userHasPermission = $this->authToken->hasPermissionKey($permissionKey);
        if ($userHasPermission) {
            return true;
        }

        if ($subject instanceof SelfPermissionInterface) {
            /** @var SelfPermissionInterface $subject */
            $userId = $subject->getUserIdForPermissionBundle();

            $userHasAlternativePermission = $this->authToken->hasPermissionKey(
                $permissionKey . EndpointWithPermission::SELF_PERMISSION
            );

            if ($userHasAlternativePermission && $this->authToken->isValid()) {
                if ($isGetRequestOnCollection) {
                    //At this point SelfPermissionFilter::addFilterConstraint(...) has already been run.
                    //It has filtered the get request so that only entities which belong to the user will be returned.
                    //No further check is required.
                    return true;
                }

                if ($this->authToken->getUserId() === $userId) {
                    return true;
                }
            }
        }

        //Return either Forbidden or Unauthenticated
        if ($this->authToken->isValid()) {
            throw new AccessDeniedHttpException("Missing permission key: $permissionKey");
        }

        return false;
    }

    /**
     * @param SelfPermissionInterface $entity
     * @return bool
     * @throws ReflectionException
     */
    public function needsFilter(SelfPermissionInterface $entity): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            //If $request is null then something in unit tests triggered this
            //so it can be ignored
            return false;
        }

        if ($request->getMethod() !== 'GET') {
            return false;
        }

        $permissionKey = $this->permissionDiscovery->getPermissionKey(
            $entity,
            $request->getMethod(),
            $request->getPathInfo()
        );

        if ($permissionKey === null) {
            //This endpoint does not need permissions to be accessed
            return false;
        }

        $userHasPermission = $this->authToken->hasPermissionKey($permissionKey);
        if ($userHasPermission) {
            return false;
        }

        $userHasAlternativePermission = $this->authToken->hasPermissionKey(
            $permissionKey . EndpointWithPermission::SELF_PERMISSION
        );

        return $userHasAlternativePermission && $this->authToken->isValid();
    }

    /**
     * @return int|null
     */
    public function getAuthTokenUserId(): ?int
    {
        return $this->authToken->getUserId();
    }
}