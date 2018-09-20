<?php

namespace Horizon\View\Twig;

use Horizon\Framework\Kernel;

class TwigTranslator
{

    /**
     * Translates the template with the provided namespaces.
     *
     * @param string $value
     * @param array $namespaces
     * @return string
     */
    public function compile($value, array $namespaces)
    {
        if (is_null($namespaces) || empty($namespaces)) {
            return $value;
        }

        $bucket = Kernel::getLanguageBucket();
        return $bucket->autoTranslate($value, array(), $namespaces);
    }

}