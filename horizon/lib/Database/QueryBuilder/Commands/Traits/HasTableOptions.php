<?php

namespace Horizon\Database\QueryBuilder\Commands\Traits;

use Horizon\Support\Str;

trait HasTableOptions
{

    /**
     * @var string[]
     */
    protected $options = array();

    /**
     * Compiles table options.
     *
     * @return string
     */
    protected function compileOptions()
    {
        $compiled = array();

        foreach ($this->options as $option => $value) {
            $compiled[] = $option . ' = ' . $value;
        }

        return Str::join($compiled);
    }

    /**
     * Sets the engine.
     *
     * @param string $name
     * @return $this
     */
    public function engine($name)
    {
        $this->options['ENGINE'] = $name;
        return $this;
    }

    /**
     * Sets the character set.
     *
     * @param string $charset
     * @return $this
     */
    public function charset($charset)
    {
        $this->options['CHARACTER SET'] = $charset;
        return $this;
    }

    /**
     * Sets the collation.
     *
     * @param string $collate
     * @return $this
     */
    public function collate($collate)
    {
        $this->options['COLLATE'] = $collate;
        return $this;
    }

    /**
     * Sets an option manully.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function opt($name, $value)
    {
        $this->options[strtoupper($name)] = $value;
        return $this;
    }

}
