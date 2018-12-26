<?php

namespace Horizon\View\Twig;

use Horizon\Framework\Core;
use Horizon\Support\Path;

class TwigExtensionLoader
{

    /**
     * @var TwigFileLoader
     */
    protected $loader;

    /**
     * Constructs a new TwigExtensionLoader instance.
     *
     * @param TwigFileLoader $loader
     */
    public function __construct(TwigFileLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Loads all extensions in the application and returns an array of instances.
     *
     * @return \Twig_Extension[]
     */
    public function getExtensions()
    {
        $collection = app()->all('Horizon\View\ViewExtension', $this->loader);
        return $collection->all();
    }

}
