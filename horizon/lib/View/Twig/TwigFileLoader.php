<?php

namespace Horizon\View\Twig;

use Horizon\Framework\Application;
use Twig_Loader_Filesystem;
use Twig_Source;

use Horizon\Framework\Kernel;

use Horizon\View\ViewException;
use Horizon\Extension\Extension;

class TwigFileLoader extends Twig_Loader_Filesystem
{

    /**
     * @var Twig_Source
     */
    private $source;

    /**
     * @var string
     */
    private $path;

    /**
     * @var Extension[]
     */
    private $extensions;

    /**
     * @var \Twig_Environment
     */
    private $environment;

    /**
     * @param \Twig_Environment $environment The Twig environment
     * @param string|array $paths A path or an array of paths where to look for templates
     * @param string|null $rootPath The root path common to all relative paths (null for getcwd())
     */
    public function __construct($paths = array(), $rootPath = null)
    {
        parent::__construct($paths);
    }

    public function getSourceContext($name)
    {
        $this->path = $this->findTemplate($name);
        $this->source = new Twig_Source($this->compileHorizonTags(file_get_contents($this->path), $name), $name, $this->path);

        return $this->source;
    }

    public function findTemplate($name)
    {
        $path = Application::kernel()->view()->resolve($name);

        if ($path === null) {
            throw new ViewException(sprintf('View "%s" not found in any provider.', $name));
        }

        $this->extensions[md5($name)] = Application::kernel()->view()->extension();

        return $path;
    }

    public function compileHorizonTags($text, $templateFileName)
    {
        return (new TwigTranspiler($this))->precompile($text, $templateFileName);
    }

    /**
     * Gets the extension instance associated with the specified ID. If there is no extension, returns null.
     *
     * @return Extension|null
     */
    public function getExtension($id)
    {
        if (isset($this->extensions[$id])) {
            return $this->extensions[$id];
        }

        return null;
    }

}
