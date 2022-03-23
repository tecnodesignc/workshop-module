<?php

namespace Modules\Workshop\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Modules\Workshop\Scaffold\Module\Exception\ModuleExistsException;
use Modules\Workshop\Scaffold\Module\ModuleScaffold;

class ModuleScaffoldCommand extends Command
{
    protected $name = 'encore:module:scaffold';
    protected $description = 'Scaffold a new module';
    /**
     * @var array
     */
    protected array $entities = [];
    /**
     * @var array
     */
    protected array $valueObjects = [];
    /**
     * @var string The type of entities to generate [Eloquent or Doctrine]
     */
    protected string $entityType;
    /**
     * @var ModuleScaffold
     */
    private ModuleScaffold $moduleScaffold;

    public function __construct(ModuleScaffold $moduleScaffold)
    {
        parent::__construct();
        $this->moduleScaffold = $moduleScaffold;
    }

    /**
     *
     * @throws ModuleExistsException|FileNotFoundException
     */
    public function handle()
    {
        $moduleName = $this->ask('Please enter the module name in the following format: vendor/name');
        list($vendor, $name) = $this->separateVendorAndName($moduleName);

        $this->checkForModuleUniqueness($name);

        $this->askForEntities();
        $this->askForValueObjects();

        $this->moduleScaffold
            ->vendor($vendor)
            ->name($name)
            ->setEntityType($this->entityType)
            ->withEntities($this->entities)
            ->withValueObjects($this->valueObjects)
            ->scaffold();

        $this->info('Module generated and is ready to be used.');
    }

    /**
     *
     */
    private function askForEntities()
    {
        $this->entityType = 'Eloquent';

        do {
            $entity = $this->ask('Enter entity name. Leaving option empty will continue script.', '<none>');
            if (!empty($entity) && $entity !== '<none>') {
                $this->entities[] = ucfirst($entity);
            }
        } while ($entity !== '<none>');
    }

    /**
     *
     */
    private function askForValueObjects()
    {
        do {
            $valueObject = $this->ask('Enter value object name. Leaving option empty will continue script.', '<none>');
            if (!empty($valueObject) && $valueObject !== '<none>') {
                $this->valueObjects[] = ucfirst($valueObject);
            }
        } while ($valueObject !== '<none>');
    }

    /**
     * Extract the vendor and module name as two separate values
     * @param string $fullName
     * @return array
     */
    private function separateVendorAndName(string $fullName): array
    {
        $explodedFullName = explode('/', $fullName);

        return [
            $explodedFullName[0],
            ucfirst($explodedFullName[1]),
        ];
    }

    /**
     * Check if the given module name does not already exists
     *
     * @param string $name
     */
    private function checkForModuleUniqueness(string $name)
    {
        /** @var Filesystem $files */
        $files = app('Illuminate\Filesystem\Filesystem');
        /** @var Repository $config */
        $config = app('Illuminate\Contracts\Config\Repository');
        if ($files->isDirectory($config->get('modules.paths.modules') . "/{$name}")) {
            return $this->error("The module [$name] already exists");
        }
    }
}
