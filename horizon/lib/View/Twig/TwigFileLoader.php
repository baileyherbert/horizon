<?php

namespace Horizon\View\Twig;

use Twig_Loader_Filesystem;
use Twig_Source;

use Horizon\Framework\Kernel;

use Horizon\View\ViewException;
use Horizon\Extend\Extension;

class TwigFileLoader extends Twig_Loader_Filesystem
{

    /**
     * @param string|array $paths A path or an array of paths where to look for templates
     * @param string|null $rootPath The root path common to all relative paths (null for getcwd())
     */
    public function __construct($paths = array(), $rootPath = null)
    {
        parent::__construct($paths);
    }

    public function getSourceContext($name)
    {
        $path = $this->findTemplate($name);

        return new Twig_Source($this->compileHorizonTags(file_get_contents($path), $name), $name, $path);
    }

    public function findTemplate($name)
    {
        $path = Kernel::getTemplatePath($name);

        if ($path === null) {
            throw new ViewException(sprintf('View "%s" not found in any provider.', $name));
        }

        return $path;
    }

    public function compileHorizonTags($text, $templateFileName)
    {
        return (new TwigTranspiler())->precompile($text, $templateFileName);
    }

}
