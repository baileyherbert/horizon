<?php

namespace Horizon\Provider;

use Horizon\Extend\Extension;

class ServiceProvider
{

    /**
     * @var Extension
     */
    private $extension;

    /**
     * Constructs a new ServiceProvider instance, and optionally binds it to an extension.
     *
     * @param Extension|null $extension
     */
    public function __construct(Extension $extension = null)
    {
        $this->extension = $extension;
    }

    /**
     * Invokes the provider and returns the data.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return false;
    }

    /**
     * Gets the extension which this service provider is bound to, or null.
     *
     * @return Extension|null
     */
    public function getExtension()
    {
        return $this->extension;
    }

}
