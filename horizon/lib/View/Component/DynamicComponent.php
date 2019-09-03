<?php

namespace Horizon\View\Component;

use Horizon\Support\Facades\Component;

class DynamicComponent extends Component {

    protected $target = array();

    public function __construct($target = array()) {
        $this->target = $target;
    }

    public function __isset($name) {
        if (is_array($this->target)) {
            return array_key_exists($name, $this->target);
        }

        if (is_object($this->target)) {
            return property_exists($this->target, $name);
        }

        return false;
    }

    public function __get($name) {
        if (is_array($this->target)) {
            return array_get($this->target, $name);
        }

        if (is_object($this->target)) {
            return $this->target[$name];
        }

        if (is_string($name) && is_int($name)) {
            return $this->target[$name];
        }
    }

    public function __call($name, $args) {
        if (is_object($this->target)) {
            if (is_callable(array($this->target, $name))) {
                return call_user_func_array(array($this->target, $name), $args);
            }
        }
    }

    public function __set($name, $value) {
        if (is_array($this->target)) {
            array_set($this->target, $name, $value);
            return;
        }

        if (is_object($this->target)) {
            $this->target[$name] = $value;
            return;
        }
    }

    public function __toString() {
        if (is_string($this->target)) return $this->target;
    }

}
