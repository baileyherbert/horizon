<?php

namespace Horizon\View\Twig;

use Twig_Extension;
use Twig_Environment;

use Horizon\Foundation\Framework;
use Horizon\Support\Path;

use Horizon\View\Template;
use Horizon\View\Component;

class TwigLoader
{

    /**
     * @var Template
     */
    protected $template;

    /**
     * @var TwigFileLoader
     */
    protected $loader;

    /**
     * @var Twig_Environment
     */
    protected $environment;

    /**
     * Constructs a new TwigLoader instance.
     *
     * @param Template $template
     */
    public function __construct(Template $template)
    {
        $this->template = $template;
        $this->loader = $this->createTwigLoader();
        $this->environment = $this->createTwigEnvironment();
    }

    /**
     * Compiles and renders the template. Returns the resulting content as a string.
     *
     * @return string
     */
    public function render()
    {
        return $this->environment->render(
            $this->template->getPath(),
            $this->template->getContext()
        );
    }

    /**
     * Creates the twig loader instance.
     *
     * @return TwigFileLoader
     */
    protected function createTwigLoader()
    {
        return new TwigFileLoader($this);
    }

    /**
     * Creates the twig environment instance.
     */
    protected function createTwigEnvironment()
    {
        // An array to store environment options
        $options = array(
            'cache' => $this->getCacheDirectory(),
            'auto_reload' => true
        );

        // Create the environment instance with the options
        $environment = new Twig_Environment($this->loader, $options);

        // Add extensions
        $this->addExtensions($environment, (new TwigExtensionLoader($this->loader))->getExtensions());

        return $environment;
    }

    /**
     * Adds the provided extensions to the Twig environment instance.
     *
     * @param Twig_Environment $environment
     * @param Twig_Extension[] $extensions
     */
    protected function addExtensions(Twig_Environment $environment, array $extensions)
    {
        foreach ($extensions as $extension) {
            $environment->addExtension($extension);
        }
    }

    /**
     * Checks if caching is enabled in the configuration.
     *
     * @return bool
     */
    protected function isCacheEnabled()
    {
        return config('app.view_cache', false);
    }

    /**
     * Gets an absolute path to the cache directory, or false if it doesn't exist and failed to be created.
     *
     * @return string|false
     */
    protected function getCacheDirectory()
    {
        $path = Path::join(Framework::path('app'), 'cache');

        if (!$this->isCacheEnabled()) {
            return false;
        }

        if (!file_exists($path)) {
            if (is_writable(Framework::path('app'))) {
                mkdir($path, 0755);
            }
            else {
                return false;
            }
        }

        return $path;
    }

}
