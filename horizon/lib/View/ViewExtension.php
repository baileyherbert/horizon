<?php

namespace Horizon\View;

use Horizon\Framework\Kernel;

use Twig_SimpleFunction;
use Twig_Extension;
use Twig_Extension_GlobalsInterface;
use Horizon\Utils\Str;
use Horizon\Extend\Extension;

class ViewExtension extends Twig_Extension implements Twig_Extension_GlobalsInterface
{

    /**
     * @var Extension
     */
    private $extension;

    /**
     * Constructs a new ViewExtension instance.
     *
     * @param Extension $extension
     */
    public function __construct(Extension $extension = null)
    {
        $this->extension = $extension;
    }

    /**
     * Gets the extension instance responsible for loading the current view, or null if it's a global view.
     *
     * @return Extension|null
     */
    protected function getExtension()
    {
        return $this->extension;
    }

    /**
     * Gets an array of global values to make available to template files. These can be overridden by variables
     * set in the response instance.
     *
     * @return array
     */
    public function getGlobals()
    {
        return array();
    }

    /**
     * Gets an array of Twig functions which can be called from within template files. By default this method scans
     * the local class for all methods starting with the word "Twig" and calls them to get an extension instance.
     *
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        $functions = array();
        $methods = get_class_methods($this);

        foreach ($methods as $methodName) {
            $methodNameLower = strtolower($methodName);

            if (Str::startsWith($methodNameLower, 'twig')) {
                $o = $this->$methodName();

                if ($o !== null && ($o instanceof Twig_SimpleFunction)) {
                    $functions[] = $o;
                }
            }
        }

        return $functions;
    }

    /**
     * Gets an array of Horizon tags (in @tag format) to transpile into Twig tags (in {{ tag() }} format).
     *
     * @return array
     */
    public function getTranspilers()
    {
        return array();
    }

}
