<?php

namespace Horizon\View\Extensions;

use Horizon\Foundation\Application;
use Horizon\Foundation\Kernel;

use Horizon\Support\Facades\Component;
use Twig_SimpleFunction;
use Horizon\Support\Profiler;
use Horizon\View\ViewExtension;
use Horizon\Routing\RouteParameterBinder;
use Horizon\Routing\RouteLoader;
use Horizon\Http\MiniRequest;
use Horizon\Support\Path;
use Horizon\Support\Str;

class HorizonExtension extends ViewExtension
{

    public function getGlobals()
    {
        $request = Application::kernel()->http()->request();

        return array(
            'request' => $request,
            'route' => $request->getRoute()
        );
    }

    public function getTranspilers()
    {
        return array(
            'csrf' => 'csrf',
            'token' => 'csrf_token',
            '__' => '__',
            'local' => '__',
            'localize' => '__',
            'runtime' => 'runtime',
            'link' => 'link',
            'asset' => 'asset',
            'json' => 'json',
            'component' => 'component'
        );
    }

    protected function getPublicAssetPath($relativePath)
    {
        return Application::asset($relativePath);
    }

    protected function twigCsrf()
    {
        return new Twig_SimpleFunction('csrf', function () {
            $token = Application::kernel()->http()->request()->session()->csrf();
            return '<input type="hidden" name="_token" value="' . $token . '">';
        }, array(
            'is_safe' => array(
                'html'
            )
        ));
    }

    protected function twigLang()
    {
        return new Twig_SimpleFunction('__', function ($context, $text) {
            $bucket = Application::kernel()->translation()->bucket();
            return $bucket->translate($text, $context);
        }, array('needs_context' => true));
    }

    protected function twigRuntime()
    {
        return new Twig_SimpleFunction('runtime', function () {
            return Profiler::time('kernel');
        });
    }

    protected function twigLink()
    {
        return new Twig_SimpleFunction('link', function ($toPath) {
            if (Str::startsWith($toPath, array('//', 'http://', 'https://'))) {
                return $toPath;
            }

            $request = Application::kernel()->http()->request();
            return $request->getLinkTo($toPath);
        });
    }

    protected function twigAsset()
    {
        $handler = $this;

        return new Twig_SimpleFunction('asset', function ($relativePath, $extensionId = null) use ($handler) {
            if (Str::startsWith($relativePath, array('//', 'http://', 'https://'))) {
                return $relativePath;
            }

            return $handler->getPublicAssetPath($relativePath, $extensionId);
        });
    }

    protected function twigJson()
    {
        return new Twig_SimpleFunction('json', function ($data) {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        });
    }

    protected function twigComponent()
    {
        return new Twig_SimpleFunction('component', function () {
            $args = func_get_args();

            return forward_static_call_array(array('Horizon\Support\Facades\Component', 'compile'), $args);
        }, array(
            'is_safe' => array(
                'html'
            )
        ));
    }

    public function getFilters() {
        return array(
            'evaluate' => new \Twig_Filter_Method($this, 'evaluate', array(
                'needs_environment' => true,
                'needs_context' => true,
                'is_safe' => array(
                    'evaluate' => true
                )
            ))
        );
    }

    /**
     * This function will evaluate $string through the $environment, and return its results.
     *
     * @param array $context
     * @param string $string
     */
    public function evaluate(\Twig_Environment $environment, $context, $string) {
        $loader = $environment->getLoader();
        $parsed = $this->parseString($environment, $context, $string);
        $environment->setLoader($loader);

        return $parsed;
    }

    /**
     * Sets the parser for the environment to Twig_Loader_String, and parsed the string $string.
     *
     * @param \Twig_Environment $environment
     * @param array $context
     * @param string $string
     *
     * @return string
     */
    protected function parseString(\Twig_Environment $environment, $context, $string) {
        $environment->setLoader(new \Twig_Loader_String());
        return $environment->render($string, $context);
    }

}
