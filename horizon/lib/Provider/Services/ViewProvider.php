<?php

namespace Horizon\Provider\Services;

use Horizon;
use Horizon\Support\Str;
use Horizon\Support\Path;
use Horizon\Provider\ServiceProvider;

class ViewProvider extends ServiceProvider
{

    /**
     * Returns the absolute path to the view template, or null if the provider cannot find that template.
     *
     * @param string $viewName Relative path to the template file.
     * @return string|null
     */
    public function __invoke()
    {
        list($viewName) = func_get_args();

        $viewPath = Path::join(Horizon::APP_DIR, 'views', $viewName);

        if (file_exists($viewPath) && is_file($viewPath)) {
            return $viewPath;
        }

        if (!Str::endsWith($viewName, '.twig')) {
            return $this($viewName . '.twig');
        }
    }

}
