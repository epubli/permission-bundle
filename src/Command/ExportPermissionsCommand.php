<?php

namespace Epubli\PermissionBundle\Command;

use Epubli\PermissionBundle\PermissionExportException;
use Epubli\PermissionBundle\Service\PermissionExporter;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportPermissionsCommand
 * @package Epubli\PermissionBundle\Command
 */
class ExportPermissionsCommand extends Command
{
    protected static $defaultName = 'epubli:export-permissions';

    /** @var PermissionExporter */
    private $permissionExporter;

    /**
     * ExportPermissionsCommand constructor.
     * @param PermissionExporter $permissionExporter
     */
    public function __construct(
        PermissionExporter $permissionExporter
    ) {
        parent::__construct();
        $this->permissionExporter = $permissionExporter;
    }

    protected function configure(): void
    {
        $this->setDescription('Exports permissions and imports them to the user microservice.')
            ->setHelp('Exports all permissions for this microservice and imports them to the user microservice.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $countOfExportedPermissions = $this->permissionExporter->export();
        } catch (PermissionExportException $e) {
            $output->writeln('ERROR\n' . $e->getMessage());
            return 1;
        } catch (ReflectionException $e) {
            throw $e;
        }

        if ($countOfExportedPermissions > 0) {
            $output->writeln($countOfExportedPermissions . ' permissions found.');
            $output->writeln('Successfully exported.');
        } else {
            $output->writeln('No permissions found. Nothing to export.');
        }

        return 0;
    }
}