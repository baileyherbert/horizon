<?php

namespace Horizon\View\Extensions;

use Horizon\Framework\Application;
use Horizon\Framework\Kernel;

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
            'public' => 'public',
            'image' => 'image',
            'file' => 'file',
            'script' => 'script',
            'style' => 'style',
            'json' => 'json'
        );
    }

    protected function getPublicAssetPath($relativePath, $extensionId = null)
    {
        $request = Application::kernel()->http()->request();
        $currentPath = $request->path();
        $root = rtrim($_SERVER['SUBDIRECTORY'], '/');

        $fromExtension = is_string($extensionId) ? $this->getExtension($extensionId) : null;

        if (!is_null($fromExtension)) {
            $relative = ltrim($relativePath, '/');
            $publicPathLegacy = sprintf('%s/%s', $root, ltrim($fromExtension->getMappedLegacyRoute($relative), '/'));
            $publicPathRouted = sprintf('%s/%s', $root, ltrim($fromExtension->getMappedPublicRoute($relative), '/'));

            return (USE_LEGACY_ROUTING) ? $publicPathLegacy : $publicPathRouted;
        }

        if (USE_LEGACY_ROUTING) {
            return $root . '/app/public/' . ltrim($relativePath, '/');
        }

        return '/' . ltrim($root . '/', '/') . ltrim($relativePath, '/');
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

    protected function twigCsrfToken()
    {
        return new Twig_SimpleFunction('csrf_token', function () {
            return Application::kernel()->http()->request()->session()->csrf();
        });
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

    protected function twigPublic()
    {
        $handler = $this;

        return new Twig_SimpleFunction('public', function ($relativePath, $extensionId = null) use ($handler) {
            if (Str::startsWith($relativePath, array('//', 'http://', 'https://'))) {
                return $relativePath;
            }

            return $handler->getPublicAssetPath($relativePath, $extensionId);
        });
    }

    protected function twigImage()
    {
        $handler = $this;

        return new Twig_SimpleFunction('image', function ($relativePath, $extensionId = null) use ($handler) {
            if (Str::startsWith($relativePath, array('//', 'http://', 'https://'))) {
                return $relativePath;
            }

            return $handler->getPublicAssetPath('/images/' . ltrim($relativePath, '/'), $extensionId);
        });
    }

    protected function twigFile()
    {
        $handler = $this;

        return new Twig_SimpleFunction('file', function ($relativePath, $extensionId = null) use ($handler) {
            if (Str::startsWith($relativePath, array('//', 'http://', 'https://'))) {
                return $relativePath;
            }

            return $handler->getPublicAssetPath('/files/' . ltrim($relativePath, '/'), $extensionId);
        });
    }

    protected function twigScript()
    {
        $handler = $this;

        return new Twig_SimpleFunction('script', function ($relativePath, $extensionId = null) use ($handler) {
            if (Str::startsWith($relativePath, array('//', 'http://', 'https://'))) {
                return $relativePath;
            }

            return $handler->getPublicAssetPath('/scripts/' . ltrim($relativePath, '/'), $extensionId);
        });
    }

    protected function twigStyle()
    {
        $handler = $this;

        return new Twig_SimpleFunction('style', function ($relativePath, $extensionId = null) use ($handler) {
            if (Str::startsWith($relativePath, array('//', 'http://', 'https://'))) {
                return $relativePath;
            }

            return $handler->getPublicAssetPath('/styles/' . ltrim($relativePath, '/'), $extensionId);
        });
    }

    protected function twigJson()
    {
        return new Twig_SimpleFunction('json', function ($data) {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        });
    }

}
