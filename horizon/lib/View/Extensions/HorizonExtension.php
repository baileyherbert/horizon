<?php

namespace Horizon\View\Extensions;

use Horizon\Framework\Kernel;

use Twig_SimpleFunction;
use Horizon\Utils\TimeProfiler;
use Horizon\View\ViewExtension;
use Horizon\Routing\RouteParameterBinder;
use Horizon\Routing\RouteLoader;
use Horizon\Http\MiniRequest;
use Horizon\Utils\Path;
use Horizon\Utils\Str;

class HorizonExtension extends ViewExtension
{

    public function getGlobals()
    {
        $request = Kernel::getRequest();

        return array(
            'request' => $request,
            'route' => $request->getRoute()
        );
    }

    public function getTranspilers()
    {
        return array(
            'csrf' => 'csrf',
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
            'include' => function($template) { return "{% include $template %}"; },
            'extend' => function($template) { return "{% extends $template %}"; },
            'section' => function($template) {
                $name = trim($template, chr(34) . chr(39));

                return "{% block $name %}";
            },
            'endsection' => function($template) { return "{% endblock %}"; }
        );
    }

    protected function getPublicAssetPath($relativePath, $extensionId = null)
    {
        $request = Kernel::getRequest();
        $currentPath = $request->path();
        $root = rtrim(Path::getRelative($currentPath, '/', $_SERVER['SUBDIRECTORY']), '/');

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

        return $root . '/' . ltrim($relativePath, '/');
    }

    protected function twigCsrf()
    {
        return new Twig_SimpleFunction('csrf', function () {
            return '<input type="hidden" name="csrf" value="{{ csrf() | e(\'html_attr\') }}">';
        });
    }

    protected function twigLang()
    {
        return new Twig_SimpleFunction('__', function ($context, $text) {
            $bucket = Kernel::getLanguageBucket();
            return $bucket->translate($text, $context);
        }, array('needs_context' => true));
    }

    protected function twigRuntime()
    {
        return new Twig_SimpleFunction('runtime', function () {
            return TimeProfiler::time('kernel');
        });
    }

    protected function twigLink()
    {
        return new Twig_SimpleFunction('link', function ($toPath) {
            if (Str::startsWith($toPath, array('//', 'http://', 'https://'))) {
                return $toPath;
            }

            $request = Kernel::getRequest();
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

}
