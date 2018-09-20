<?php

namespace Horizon\View\Extensions;

use Horizon\Framework\Kernel;

use Twig_SimpleFunction;
use Horizon\Utils\TimeProfiler;

class HorizonExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{

    public function getGlobals()
    {
        $request = Kernel::getRequest();

        return array(
            'request' => $request,
            'route' => $request->getRoute()
        );
    }

    public function getFunctions()
    {
        return array(
            $this->csrf(),
            $this->lang(),
            $this->runtime()
        );
    }

    protected function csrf()
    {
        return new Twig_SimpleFunction('csrf', function () {
            return 'boo';
        });
    }

    protected function lang()
    {
        return new Twig_SimpleFunction('__', function ($context, $text) {
            $bucket = Kernel::getLanguageBucket();
            return $bucket->translate($text, $context);
        }, array('needs_context' => true));
    }

    protected function runtime()
    {
        return new Twig_SimpleFunction('runtime', function () {
            return TimeProfiler::time('kernel');
        });
    }

}
