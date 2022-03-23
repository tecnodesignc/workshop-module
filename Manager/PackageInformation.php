<?php

namespace Modules\Workshop\Manager;

use Illuminate\Contracts\Filesystem\Filesystem;

class PackageInformation
{
    /**
     * @var Filesystem
     */
    private Filesystem $finder;

    public function __construct(Filesystem $finder)
    {
        $this->finder = $finder;
    }

    /**
     * Get the exact installed version for the specified package
     * @param string $packageName
     * @return string mixed
     */
    public function getPackageInfo(string $packageName): string
    {
        $composerLock = json_decode($this->finder->get('composer.lock'));
        foreach ($composerLock->packages as $package) {
            if ($package->name == $packageName) {
                return $package;
            }
        }
        return '';
    }
}
