<?php

namespace Modules\Workshop\Scaffold\Module;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Modules\Workshop\Scaffold\Module\Exception\ModuleExistsException;
use Modules\Workshop\Scaffold\Module\Generators\EntityGenerator;
use Modules\Workshop\Scaffold\Module\Generators\FilesGenerator;
use Modules\Workshop\Scaffold\Module\Generators\ValueObjectGenerator;

class ModuleScaffold
{
    /**
     * Contains the vendor name
     * @var string
     */
    protected string $vendor;
    /**
     * Contains the Module name
     * @var string
     */
    protected string $name;
    /**
     * Contains an array of entities to generate
     * @var array
     */
    protected array $entities;
    /**
     * Contains an array of value objects to generate
     * @var array
     */
    protected array $valueObjects;
    /**
     * @var array of files to generate
     */
    protected array $files = [
        'permissions.stub' => 'Config/permissions',
        'routes.stub' => 'Http/backendRoutes',
        'routes-api.stub' => 'Http/apiRoutes',
        'route-provider.stub' => 'Providers/RouteServiceProvider',
    ];
    /**
     * @var string The type of entities to generate [Eloquent or Doctrine]
     */
    protected string $entityType;
    /**
     * @var Kernel
     */
    private mixed $artisan;
    /**
     * @var Filesystem
     */
    private Filesystem $finder;
    /**
     * @var Repository
     */
    private Repository $config;
    /**
     * @var EntityGenerator
     */
    private EntityGenerator $entityGenerator;
    /**
     * @var ValueObjectGenerator
     */
    private ValueObjectGenerator $valueObjectGenerator;
    /**
     * @var FilesGenerator
     */
    private FilesGenerator $filesGenerator;

    public function __construct(
        Filesystem $finder,
        Repository $config,
        EntityGenerator $entityGenerator,
        ValueObjectGenerator $valueObjectGenerator,
        FilesGenerator $filesGenerator
    ) {
        $this->artisan = app('Illuminate\Contracts\Console\Kernel');
        $this->finder = $finder;
        $this->config = $config;
        $this->entityGenerator = $entityGenerator;
        $this->valueObjectGenerator = $valueObjectGenerator;
        $this->filesGenerator = $filesGenerator;
    }

    /**
     *
     * @throws ModuleExistsException|FileNotFoundException
     */
    public function scaffold()
    {
        if ($this->finder->isDirectory($this->getModulesPath())) {
            throw new ModuleExistsException();
        }

        $this->artisan->call("module:make", ['name' => [$this->name]]);

        $this->addDataToComposerFile();
        $this->removeUnneededFiles();
        $this->addFolders();

        $this->filesGenerator->forModule($this->name)
            ->generateModuleProvider()
            ->generate($this->files);

        $this->cleanUpModuleJson();

        $this->entityGenerator->forModule($this->getName())->type($this->entityType)->generate($this->entities);
        $this->valueObjectGenerator->forModule($this->getName())->type($this->entityType)->generate($this->valueObjects);
    }

    /**
     * @param string $vendor
     * @return $this
     */
    public function vendor(string $vendor): static
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the name of module will created. By default in studly case.
     *
     * @return string
     */
    public function getName(): string
    {
        return Str::studly($this->name);
    }

    /**
     * Set the entity type [Eloquent, Doctrine]
     * @param string $entityType
     * @return $this
     */
    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    /**
     * @param  array $entities
     * @return $this
     */
    public function withEntities(array $entities): static
    {
        $this->entities = $entities;

        return $this;
    }

    /**
     * @param  array $valueObjects
     * @return $this
     */
    public function withValueObjects(array $valueObjects): static
    {
        $this->valueObjects = $valueObjects;

        return $this;
    }

    /**
     * Return the current module path
     * @param string $path
     * @return string
     */
    private function getModulesPath(string $path = ''): string
    {
        return $this->config->get('modules.paths.modules') . "/{$this->getName()}/$path";
    }

    /**
     * Rename the default vendor name 'ping pong-modules'
     * by the input vendor name
     */
    private function renameVendorName()
    {
        $composerJsonContent = $this->finder->get($this->getModulesPath('composer.json'));
        $composerJsonContent = str_replace('nwidart', $this->vendor, $composerJsonContent);
        $this->finder->put($this->getModulesPath('composer.json'), $composerJsonContent);
    }

    /**
     * Remove the default generated view resources
     */
    private function removeViewResources()
    {
        $this->finder->delete($this->getModulesPath('Resources/views/index.blade.php'));
        $this->finder->delete($this->getModulesPath('Resources/views/layouts/master.blade.php'));
        $this->finder->deleteDirectory($this->getModulesPath('Resources/views/layouts'));
    }

    /**
     * Remove all unneeded files
     */
    private function removeUnneededFiles()
    {
        $this->renameVendorName();
        $this->removeViewResources();

        $this->finder->delete($this->getModulesPath('Http/routes.php'));
        $this->finder->delete($this->getModulesPath("Http/Controllers/{$this->name}Controller.php"));
    }

    /**
     * @throws FileNotFoundException
     */
    private function cleanUpModuleJson()
    {
        $moduleJson = $this->finder->get($this->getModulesPath('module.json'));

        $moduleJson = $this->loadProviders($moduleJson);
        $moduleJson = $this->setModuleOrderOrder($moduleJson);
        $moduleJson = $this->setModuleVersion($moduleJson);
        $moduleJson = $this->removeStartPhpFile($moduleJson);
        $moduleJson = $this->addDataToModuleFile($moduleJson);
        $this->finder->put($this->getModulesPath('module.json'), $moduleJson);
    }

    /**
     * Load the routing service provider
     * @param string $content
     * @return string
     */
    private function loadProviders(string $content): string
    {
        $newProviders = <<<JSON
"Modules\\\\{$this->name}\\\Providers\\\\{$this->name}ServiceProvider",
        "Modules\\\\{$this->name}\\\Providers\\\RouteServiceProvider"
JSON;

        $oldProvider = '"Modules\\\\' . $this->name . '\\\\Providers\\\\' . $this->name . 'ServiceProvider"';

        return  str_replace($oldProvider, $newProviders, $content);
    }

    /**
     * Set the module order to 1
     * @param string $content
     * @return string
     */
    private function setModuleOrderOrder(string $content): string
    {
        return str_replace('"priority": 0,', '"priority": 1,', $content);
    }

    /**
     * Set the module version to 1.0.0 by default
     * @param string $content
     * @return string
     */
    private function setModuleVersion(string $content): string
    {
        return str_replace("\"active\"", "\"version\": \"1.0.0\",\n\t\"active\"", $content);
    }

    /**
     * Add more data in module json
     * - title
     * - author
     * - minimum Core Version
     * @param string $content
     * @return string
     */
    private function addDataToModuleFile(string $content): string
    {
        $name = ucfirst($this->name);

        $search = <<<JSON
"name": "$name",
JSON;
        $replace = <<<JSON
"title": "$name",
    "name": "$name",
    "version":"1.0.0",
    "minimumCoreVersion": "2.0",
    "author": "Encore CMS",
JSON;
        return str_replace($search, $replace, $content);
    }


    /**
     * Remove the start.php start file
     * Also removes the auto-loading of that file
     * @param string $content
     * @return string
     */
    private function removeStartPhpFile(string $content): string
    {
        $this->finder->delete($this->getModulesPath('start.php'));

        return str_replace('"start.php"', '', $content);
    }

    /**
     * Add required folders
     */
    private function addFolders()
    {
        //$this->finder->makeDirectory($this->getModulesPath('Sidebar'));
        $this->finder->makeDirectory($this->getModulesPath('Repositories/Cache'));
        $this->finder->makeDirectory($this->getModulesPath('Presenters'));
    }

    /**
     * Add more data in composer json
     * - a encore/module type
     * - package requirements
     * - minimum stability
     * - prefer stable
     * @throws FileNotFoundException
     */
    private function addDataToComposerFile()
    {
        $composerJson = $this->finder->get($this->getModulesPath('composer.json'));

        $name = ucfirst($this->name);

        $search = <<<JSON
"description": "",
JSON;
        $replace = <<<JSON
"description": "The $name module for EncoreCMS 2.0",
    "type": "encore-module",
    "license": "MIT",
    "keywords": [
        "encore cms",
        "$name"
    ],
     "authors": [
        {
            "name": "Marcos Rativa",
            "email": "marcos21.009@gmail.com"
        }
    ],
    "support": {
        "email": "support@tecnodesign.com.co",
        "issues": "https://github.com/tecnodesignc/encorecms/issues",
        "source": "https://github.com/tecnodesignc/$name-module"
    },
    "require": {
        "php": ">=8.0",
        "composer/installers": "^2.1",
        "tecnodesignc/core-module": "^2.0"
    },
    "require-dev": {
        "phpunit/phpunit": "~7.0",
        "orchestra/testbench": "3.8.*"
    },
    "autoload-dev": {
        "psr-4": {
            "Modules\\\\$name\\\\": ".",
            "Modules\\\\": "modules/"
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true,
JSON;
        $composerJson = str_replace($search, $replace, $composerJson);
        $this->finder->put($this->getModulesPath('composer.json'), $composerJson);
    }

    /**
     * Adding the module name to the .gitignore file so that it can be committed
     * @throws FileNotFoundException
     */
    private function addModuleToIgnoredExceptions()
    {
        $modulePath = $this->config->get('modules.paths.modules');

        if ($this->finder->exists($modulePath . '/.gitignore') === false) {
            return;
        }
        $moduleGitIgnore = $this->finder->get($modulePath . '/.gitignore');
        $moduleGitIgnore .= '!' . $this->getName() . PHP_EOL;
        $this->finder->put($modulePath . '/.gitignore', $moduleGitIgnore);
    }
}
