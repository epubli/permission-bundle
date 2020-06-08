<?php

namespace Epubli\PermissionBundle\Security;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Annotations\Reader;
use Epubli\PermissionBundle\EndpointWithPermission;
use Epubli\PermissionBundle\Interfaces\SelfPermissionInterface;
use Epubli\PermissionBundle\Service\AuthToken;
use Epubli\PermissionBundle\Service\PermissionDiscovery;
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

        // If the subject is a string check if class exists to support collectionOperations
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
            return false;
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
            if ($userHasAlternativePermission
                && $this->authToken->isValid()
                && $this->authToken->getUserId() === $userId) {
                return true;
            }
        }

        //Return either Forbidden or Unauthenticated
        if ($this->authToken->isValid()) {
            throw new AccessDeniedHttpException("Missing permission key: $permissionKey");
        }

        return false;
    }
}