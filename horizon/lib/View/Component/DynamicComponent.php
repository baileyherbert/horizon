<?php

namespace Horizon\View\Component;

use Horizon\Support\Facades\Component;

class DynamicComponent extends Component {

    protected $options = array();

    public function __construct($options = array()) {
        $this->options = $options;
    }

    public function __isset($name) {
        return array_key_exists($name, $this->options);
    }

    public function __get($name) {
        return array_get($this->options, $name);
    }

    public function __set($name, $value) {
        return array_set($this->options, $name, $value);
    }

}
