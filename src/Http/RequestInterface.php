<?php

namespace Cubes\Uploader\Http;

/**
 * Interface RequestInterface
 *
 * @package Cubes\Uploader\Http
 */
interface RequestInterface
{
    /**
     * Checks if parameter exist in parameters bag.
     *
     * @param  $parameter
     * @return mixed
     */
    public function has($parameter);

    /**
     * Gets parameter bag.
     *
     * @param  $parameter
     * @return mixed
     */
    public function get($parameter);

    /**
     * Sets new parameter to parameter bag.
     *
     * @param  $parameter
     * @param  $value
     * @return mixed
     */
    public function set($parameter, $value);

    /**
     * Returns true if current request is POST.
     *
     * @return mixed
     */
    public function isPost();

    /**
     * Returns true if current request is GET.
     *
     * @return mixed
     */
    public function isGet();

    /**
     * Returns all parameters from parameter bag.
     *
     * @return mixed
     */
    public function getParameters();

    /**
     * Returns specified parameter from current request method.
     *
     * @param  $parameter
     * @return mixed
     */
    public function getParameter($parameter);
}