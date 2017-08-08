<?php

namespace Cubes\Uploader\Http;

/**
 * Class Request
 *
 * @package Cubes\Uploader\Http
 */
class Request implements RequestInterface
{
    /**
     * Request parameters bag.
     *
     * @var array
     */
    protected $bag = [];

    /**
     * Array of Files from request.
     *
     * @var mixed
     */
    protected $files;

    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->bag = $this->getParameters();
    }

    /**
     * Returns true if parameter exist in parameters bag.
     *
     * @param  $parameter
     * @return bool
     */
    public function has($parameter)
    {
        if (array_key_exists($parameter, $this->bag)) {
            return true;
        }
    }

    /**
     * Returns parameter from parameter bag if exists.
     *
     * @param  $parameter
     * @return mixed
     */
    public function get($parameter)
    {
        if ($this->has($parameter)) {
            return $this->bag[$parameter];
        }
    }

    /**
     * Sets specified parameter name to passed value.
     *
     * @param  $parameter
     * @param  $value
     * @return \Cubes\Uploader\Http\Request
     */
    public function set($parameter, $value)
    {
        $this->bag[$parameter] = $value;
        return $this;
    }

    /**
     * Returns all parameter for current request method.
     *
     * @return mixed
     */
    public function getParameters()
    {
        if ($this->isGet()) {
            return $_GET;
        } elseif ($this->isPost()) {
            return $_POST;
        }
    }

    /**
     * Return array of files from $_FILEs super global.
     *
     * @return array|mixed
     */
    public function getFiles()
    {
        $this->files = $this->injectFilesFromSuperGlobal();
        return $this->files;
    }

    /**
     * Returns specified parameter from parameter bag.
     *
     * @param  $parameter
     * @return mixed
     */
    public function getParameter($parameter)
    {
        $parameters = $this->bag;
        if (!empty($parameters) && array_key_exists($parameter, $parameters)) {
            return $parameters[$parameter];
        }
    }

    /**
     * Method injectFilesFromSuperGlobal used to inject data from
     * $_FILES super global to Request class property files.
     *
     * @return array|mixed
     */
    protected function injectFilesFromSuperGlobal()
    {
        // Return empty array if files not found.
        if (!isset($_FILES) || empty($_FILES)) {
            return [];
        }

        // Return shifted file values.
        $files = array_values($_FILES);
        return array_shift($files);
    }

    /**
     * {@inheritdoc}
     */
    public function isPost()
    {
        // If global $_POST is set in request and
        // is not empty return true.
        if (isset($_POST) && !empty($_POST)) {
            return true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isGet()
    {
        // If global $_GET is set in request and
        // is not empty return true.
        if (isset($_GET) && !empty($_GET)) {
            return true;
        }
    }
}