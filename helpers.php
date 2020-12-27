<?php

if (! function_exists('module_version')) {
    function module_version(\Nwidart\Modules\Laravel\Module $module)
    {
        if (is_core_module($module->getName()) === true) {
            return \Modules\Core\Foundation\EncoreCms::VERSION;
        }

        return $module->version;
    }
}

if (! function_exists('theme_version')) {
    function theme_version(\Tecnodesignc\Stylist\Theme\Theme $theme)
    {
        if (is_core_theme($theme->getName()) === true) {
            return \Modules\Core\Foundation\EncoreCms::VERSION;
        }

        return $theme->version;
    }
}
