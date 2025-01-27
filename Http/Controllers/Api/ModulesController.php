<?php

namespace Modules\Workshop\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use Modules\Workshop\Service\PermissionManager;
use Nwidart\Modules\Module;

class ModulesController extends Controller
{
    protected PermissionManager $permissionManager;

    public function __construct(PermissionManager $permissionManager)
    {
        $this->permissionManager = $permissionManager;
    }

    public function publishAssets(Module $module): void
    {
        try {
            Artisan::call('module:publish', ['module' => $module->getName()]);
        } catch (InvalidArgumentException $e) {
        }
    }

    public function permissions(Request $request): JsonResponse
    {
        return response()->json([
            'permissions' => $this->permissionManager->all(),
        ]);


    }
}
