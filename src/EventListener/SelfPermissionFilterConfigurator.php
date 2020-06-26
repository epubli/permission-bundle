<?php

namespace Epubli\PermissionBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Epubli\PermissionBundle\Security\PermissionVoter;

/**
 * Class SelfPermissionFilterConfigurator
 * @package Epubli\PermissionBundle\EventListener
 */
final class SelfPermissionFilterConfigurator
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var PermissionVoter */
    private $permissionVoter;

    public function __construct(EntityManagerInterface $entityManager, PermissionVoter $permissionVoter)
    {
        $this->entityManager = $entityManager;
        $this->permissionVoter = $permissionVoter;
    }

    public function onKernelRequest(): void
    {
        $filter = $this->entityManager->getFilters()->enable('epubli_permission_bundle_self_permission_filter');

        $filter->setPermissionVoter($this->permissionVoter);
    }
}