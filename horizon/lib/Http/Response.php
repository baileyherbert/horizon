<?php

namespace Horizon\Http;

use Horizon\Foundation\Application;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Horizon\View\Template;
use Horizon\Support\Str;

class Response extends SymfonyResponse
{

    /**
     * @var bool If the response has halted and page load should cease.
     */
    protected $halted = false;

    /**
     * @var array Variables to be sent to the view during rendering.
     */
    protected $with = array();

    /**
     * Sets the value of a header in the response.
     *
     * @param string $key
     * @param string $value
     */
    public function setHeader($key, $value)
    {
        $this->headers->set($key, $value);
    }

    /**
     * Gets the value of a header in the response.
     */
    public function getHeader($key, $default = null)
    {
        $this->headers->get($key, $default);
    }

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

        $this->content .= $data;
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

    /**
     * Turns the response into a redirection.
     *
     * @param string $to
     * @param int $code
     */
    public function redirect($to = null, $code = 302)
    {
        if ($to == null) {
            $to = Application::kernel()->http()->request()->getRequestUri();
        }

        if (Str::startsWith($to, '/') && !Str::startsWith($to, '//')) {
            $to = '/' . ltrim(trim($_SERVER['SUBDIRECTORY'], '/') . '/' . ltrim($to, '/'), '/');
        }

        $this->setStatusCode($code);
        $this->setHeader('Location', $to);
    }

    /**
     * Stops page execution gracefully, meaning any currently-running code will continue, but further controllers or
     * middleware will not be executed.
     */
    public function halt()
    {
        $this->halted = true;
    }

    /**
     * Gets whether the page execution is being or has been halted.
     *
     * @return bool
     */
    public function isHalted()
    {
        return $this->halted;
    }

    /**
     * Sets a variable which is sent to views during rendering.
     *
     * @param string $key
     * @param mixed $value
     */
    public function with($key, $value)
    {
        $this->with[$key] = $value;
    }

    /**
     * Removes a variable included in the response if it exists.
     *
     * @param string $key
     */
    public function without($key)
    {
        if (isset($this->with[$key])) {
            unset($this->with[$key]);
        }
    }

    /**
     * Renders a view to the response.
     *
     * @param string $templateName
     * @param array $context
     */
    public function view($templateFile, array $context = array())
    {
        $variables = $this->buildContextVariables($context);
        $view = new Template($templateFile, $variables);
        $content = $view->render();

        if (!$this->isHalted()) {
            $this->content .= $content;
        }
    }

    /**
     * Fills the provided array with variables stored in the response. If any keys already exist, the values are
     * unchanged.
     *
     * @param array $context
     * @return array
     */
    protected function buildContextVariables(array $context)
    {
        foreach ($this->with as $key => $value)
        {
            if (!array_key_exists($key, $context)) {
                $context[$key] = $value;
            }
        }

        return $context;
    }

}
