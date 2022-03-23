<?php

namespace Modules\Workshop\Http\Controllers\Admin;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Response;
use Modules\Core\Http\Controllers\Admin\AdminBaseController;
use Modules\Workshop\Manager\ModuleManager;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use Symfony\Component\Console\Output\BufferedOutput;

class ModulesController extends AdminBaseController
{
    /**
     * @var ModuleManager
     */
    private ModuleManager $moduleManager;
    /**
     * @var RepositoryInterface
     */
    private RepositoryInterface $modules;

    public function __construct(ModuleManager $moduleManager, RepositoryInterface $modules)
    {
        parent::__construct();

        $this->moduleManager = $moduleManager;
        $this->modules = $modules;
    }

    /**
     * Display a list of all modules
     * @return View|Application|Factory
     */
    public function index(): Application|Factory|View
    {
        $modules = $this->modules->all();

        return view('workshop::admin.modules.index', compact('modules'));
    }

    /**
     * Display module info
     * @param Module $module
     * @return Application|Factory|View
     */
    public function show(Module $module): View|Factory|Application
    {
        $changelog = $this->moduleManager->changelogFor($module);

        return view('workshop::admin.modules.show', compact('module', 'changelog'));
    }

    /**
     * Disable the given module
     * @param Module $module
     * @return RedirectResponse
     */
    public function disable(Module $module): RedirectResponse
    {
        if ($this->isCoreModule($module)) {
            return redirect()->route('admin.workshop.modules.show', [$module->getLowerName()])
                ->with('error', trans('workshop::modules.module cannot be disabled'));
        }

        $module->disable();

        return redirect()->route('admin.workshop.modules.show', [$module->getLowerName()])
            ->with('success', trans('workshop::modules.module disabled'));
    }

    /**
     * Enable the given module
     * @param Module $module
     * @return RedirectResponse
     */
    public function enable(Module $module): RedirectResponse
    {
        $module->enable();

        return redirect()->route('admin.workshop.modules.show', [$module->getLowerName()])->with(
            'success',
            trans('workshop::modules.module enabled')
        );
    }

    /**
     * Update a given module
     * @param Request $request
     * @return JsonResponse json
     */
    public function update(Request $request): JsonResponse
    {
        $output = new BufferedOutput();
        Artisan::call('asgard:update', ['module' => $request->get('module')], $output);

        return Response::json(['updated' => true, 'message' => $output->fetch()]);
    }

    /**
     * Check if the given module is a core module that should be be disabled
     * @param Module $module
     * @return bool
     */
    private function isCoreModule(Module $module): bool
    {
        $coreModules = array_flip(config('encore.core.config.CoreModules'));

        return isset($coreModules[$module->getLowerName()]);
    }
}
