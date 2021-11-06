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
     * @var bool
     */
    private $debugging = false;

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

        // Check for a valid file if the view is an absolute path
        if ($path === null) {
            if (starts_with($name, '/') || !!preg_match("/^[A-Z]:/", $name)) {
                if (file_exists($name)) {
                    $path = $name;
                }
            }
        }

        // Throw an error if no view could be found
        if ($path === null) {
            throw new ViewException(sprintf('View "%s" not found in any provider.', $name));
        }

        return $path;
    }

    public function isDebuggingEnabled() {
        return $this->debugging;
    }

    public function compileHorizonTags($text, $templateFileName)
    {
        $transpiler = new TwigTranspiler($this);
        $data = $transpiler->precompile($text, $templateFileName);
        $this->debugging = $transpiler->isDebuggingEnabled();

        return $data;
    }

}
