<?php

namespace Horizon\Http\Traits;

trait HasHttpInput
{

    /**
     * Gets the value of the query parameter with the specified key.
     *
     * @param string|null $default Return value to use if the key is not found.
     * @return string|null
     */
    public function query($key = null, $default = null)
    {
        return $this->getWithDefault('query', $key, $default);
    }

    /**
     * Gets the value of the posted parameter with the specified key.
     *
     * @param string|null $default Return value to use if the key is not found.
     * @return string|null
     */
    public function post($key = null, $default = null)
    {
        return $this->getWithDefault('request', $key, $default);
    }

    /**
     * Gets the value of a posted or query parameter with the specified key. Posted values will override query
     * values.
     *
     * @param string|null $default Return value to use if the key is not found.
     * @return string|null
     */
    public function input($key, $default = null)
    {
        $input = ($this->post() + $this->query());

        if (isset($input[$key])) {
            return $input[$key];
        }

        return $default;
    }

    /**
     * Gets the files submitted in the request as an array.
     *
     * @return array[]
     */
    public function files()
    {
        return $this->files->all();
    }

    /**
     * Gets the specified file from the request as an array. If the file does not exist, null is returned.
     * If the property parameter is specified, the value of that parameter in the file is returned. If the
     * parameter does not exist, null is returned.
     *
     * @return mixed|array[]|null
     */
    public function file($key, $property = null)
    {
        $file = $this->files->get($key, null);

        if (!is_null($property) && is_array($file)) {
            if (isset($file[$property])) {
                return $file[$property];
            }

            return null;
        }

        return $file;
    }

    /**
     * Gets a key from the specified bag or returns the default value provided if it doesn't exist. If no key name
     * is specified, the entire bag is returned as an array.
     *
     * @return mixed
     */
    public function getWithDefault($bag, $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->$bag->all();
        }

        return $this->$bag->get($key, $default);
    }

}