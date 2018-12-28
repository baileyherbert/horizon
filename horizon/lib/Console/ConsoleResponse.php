<?php

namespace Horizon\Console;

use Horizon\Console;
use Horizon\Foundation\Application;
use Horizon\Http\Response;
use Exception;
use Horizon\Foundation\Kernel;

class ConsoleResponse extends Response
{

    public function setHeader($key, $value) { throw new Exception('Headers cannot be set in console mode.'); }
    public function getHeader($key, $default = null) { throw new Exception('Headers are not set in console mode.'); }

    public function redirect($to = null, $code = 302) { throw new Exception('Cannot redirect in console mode.'); }
    public function halt() { throw new Exception('Cannot halt in console mode.'); }
    public function isHalted() { throw new Exception('Cannot halt in console mode.'); }

    /**
     * Writes to the response content.
     *
     * @param mixed $data
     */
    protected function writeObject($data = '')
    {
        if (is_bool($data)) {
            $data = $data ? 'true' : 'false';
        }

        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        if (is_object($data)) {
            $data = (string)$data;
        }

        if (is_null($data)) {
            $data = 'NULL';
        }

        Application::kernel()->console()->output()->write($data);
    }

    /**
     * Writes to the response content.
     *
     * @param mixed $data
     */
    public function write()
    {
        $args = func_get_args();

        if (empty($args)) {
            $args = array('');
        }

        foreach ($args as $i => $arg) {
            $this->writeObject($arg);

            if ($i < (count($args) - 1)) {
                $this->writeObject(' ');
            }
        }
    }

    /**
     * Writes a new line to the response content.
     */
    public function writeLine()
    {
        $args = func_get_args();

        if (empty($args)) {
            $args = array('');
        }

        foreach ($args as $i => $arg) {
            $this->write($arg);

            if ($i < (count($args) - 1)) {
                $this->write(' ');
            }
        }

        $this->write("\n");
    }

    public function view($templateFile, array $context = array())
    {
        $view = new Template($templateFile, $this->buildContextVariables($context));
        $content = $view->render();

        $this->write($content);
    }

}
