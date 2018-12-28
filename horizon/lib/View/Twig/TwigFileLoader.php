<?php

namespace Horizon\View\Twig;

use Horizon\Foundation\Application;
use Twig_Loader_Filesystem;
use Twig_Source;

use Horizon\View\ViewException;

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
     * @var TwigLoader
     */
    private $loader;

    /**
     * @param TwigLoader $loader
     */
    public function __construct(TwigLoader $loader)
    {
        parent::__construct(array());

        $this->loader = $loader;
    }

    public function getSourceContext($name)
    {
        if (starts_with($name, '@component/')) {
            $contents = Application::kernel()->view()->componentManager()->getFileContents($name);
            $contents = $this->compileHorizonTags($contents, $name);

            return new Twig_Source($contents, $name, $name);
        }

        $this->path = $this->findTemplate($name);
        $this->source = new Twig_Source($this->compileHorizonTags(file_get_contents($this->path), $name), $name, $this->path);

        return $this->source;
    }

    public function findTemplate($name)
    {
        if (starts_with($name, '@component/')) {
            return $name;
        }

        $path = Application::kernel()->view()->resolveView($name);

        if ($path === null) {
            throw new ViewException(sprintf('View "%s" not found in any provider.', $name));
        }

        return $path;
    }

    public function compileHorizonTags($text, $templateFileName)
    {
        return (new TwigTranspiler($this))->precompile($text, $templateFileName);
    }

}
