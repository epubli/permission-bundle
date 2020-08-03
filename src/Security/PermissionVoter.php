<?php

namespace Epubli\PermissionBundle\Security;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Annotations\Reader;
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

        $permissionKeys = $this->permissionDiscovery->getPermissionKeys(
            $subject,
            $request->getMethod(),
            $request->getPathInfo(),
            (string)$request->getContent()
        );

        if (empty($permissionKeys)) {
            //This endpoint does not need permissions to be accessed
            return true;
        }

        $userHasPermission = $this->authToken->hasPermissionKeys($permissionKeys);
        if ($userHasPermission) {
            return true;
        }

        if ($subject instanceof SelfPermissionInterface) {
            $userHasAlternativePermission = $this->authToken->hasPermissionKeys($permissionKeys, true);

            if ($userHasAlternativePermission && $this->authToken->isValid()) {
                if ($isGetRequestOnCollection) {
                    //At this point SelfPermissionFilter::addFilterConstraint(...) has already been run.
                    //It has filtered the get request so that only entities which belong to the user will be returned.
                    //No further check is required.
                    return true;
                }

                $userId = $subject->getUserIdForPermissionBundle();
                if ($this->authToken->getUserId() === $userId) {
                    return true;
                }
            }
        }

        if ($this->authToken->isValid()) {
            //User is authenticated but forbidden
            $missingKeys = $this->authToken->getMissingPermissionKeys($permissionKeys);
            throw new AccessDeniedHttpException('Missing permission keys: ' . implode(', ', $missingKeys));
        }

        //User is Unauthenticated
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
            //If it is not a GET request then no filter is required
            return false;
        }

        //This will always be empty or filled with just one entry
        $permissionKeys = $this->permissionDiscovery->getPermissionKeys(
            $entity,
            $request->getMethod(),
            $request->getPathInfo(),
            (string)$request->getContent()
        );

        if (empty($permissionKeys)) {
            //This endpoint does not need permissions to be accessed
            return false;
        }

        $userHasPermission = $this->authToken->hasPermissionKeys($permissionKeys);
        if ($userHasPermission) {
            //User has permission to see everything
            return false;
        }

        $userHasAlternativePermission = $this->authToken->hasPermissionKeys($permissionKeys, true);
        if ($userHasAlternativePermission) {
            //User has permission to see his own entities so he needs a filter
            return true;
        }

        //User has no permissions so do not apply a filter because "voteOnAttribute" will throw an exception to deny access
        return false;
    }

    /**
     * @return int|null
     */
    public function getAuthTokenUserId(): ?int
    {
        return $this->authToken->getUserId();
    }
}