<?php

namespace Modules\Workshop\Http\Controllers\Admin;

use Illuminate\View\View;
use Modules\Core\Http\Controllers\Admin\AdminBaseController;
use Modules\Workshop\Manager\ThemeManager;
use Tecnodesignc\Stylist\Theme\Theme;

class ThemesController extends AdminBaseController
{
    /**
     * @var ThemeManager
     */
    private ThemeManager $themeManager;

    public function __construct(ThemeManager $themeManager)
    {
        parent::__construct();

        $this->themeManager = $themeManager;
    }

    /**
     * @return View
     */
    public function index(): View
    {
        $themes = $this->themeManager->all();

        return view('workshop::admin.themes.index', compact('themes'));
    }

    /**
     * @param Theme $theme
     * @return View
     */
    public function show(Theme $theme): View
    {
        return view('workshop::admin.themes.show', compact('theme'));
    }
}
