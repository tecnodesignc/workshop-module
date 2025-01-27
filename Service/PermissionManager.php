<?php

namespace Modules\Workshop\Service;

use Illuminate\Support\Arr;
use Nwidart\Modules\Contracts\RepositoryInterface;

class PermissionManager
{
    /**
     * @var RepositoryInterface
     */
    private $module;

    /**
     */
    public function __construct()
    {
        $this->module = app('modules');
    }

    /**
     * Get the permissions from all the enabled modules
     * @return array
     */
    public function all()
    {
        $permissions = [];
        foreach ($this->module->allEnabled() as $enabledModule) {
            if (config(strtolower('encore.' . $enabledModule->getName()) . '.config.permissions',false)){
                $configuration = config(strtolower('encore.' . $enabledModule->getName()) . '.permissions');
                if ($configuration) {
                    $configuration = Arr::map($configuration, function (array $value, string $key) {
                        return Arr::map($value, function (string $value, string $key) {
                            return trans($value);
                        });
                    });
                    $permissions[$enabledModule->getName()] = $configuration;
                }
            }
        }

        return $permissions;
    }

    /**
     * Return a correctly type casted permissions array
     * @param $permissions
     * @return array
     */
    public function clean($permissions)
    {
        if (!$permissions) {
            return [];
        }
        $cleanedPermissions = [];
        foreach ($permissions as $permissionName => $checkedPermission) {
            if ($this->getState($checkedPermission) !== null) {
                $cleanedPermissions[$permissionName] = $this->getState($checkedPermission);
            }
        }

        return $cleanedPermissions;
    }

    /**
     * @param $checkedPermission
     * @return bool
     */
    protected function getState($checkedPermission)
    {
        if ($checkedPermission === '1' || $checkedPermission === 1) {
            return true;
        }

        if ($checkedPermission === '-1' || $checkedPermission === -1) {
            return false;
        }

        return null;
    }

}
