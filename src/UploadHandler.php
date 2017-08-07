<?php

namespace Cubes\Uploader;

/**
 * Class UploadHandler
 *
 * @property null|string  identifier
 * @property null|string  filename
 * @property null|string  chunkNumber
 * @property null|string  chunkSize
 * @property null|string  totalSize
 *
 * @method getIdentifier()
 * @method setIdentifier(string $identifier)
 * @method getFilename()
 * @method setFilename(string $filename)
 * @method getChunkNumber()
 * @method setChunkNumber(integer|string $chunkNumber)
 * @method getChunkSize()
 * @method setChunkSize(integer|string $size)
 * @method getTotalSize()
 * @method setTotalSize(integer|string $totalSize)
 *
 * @package Cubes\Uploader
 */
class UploadHandler
{
    /**
     * Accessor property used for resumable.js for prefixing request parameters.
     *
     * @var null $accessor
     */
    protected $accessor = null;

    /**
     * Array of parameters that are expected in request.
     *
     * @var array
     */
    protected $parameters = [
        'identifier'  => '',
        'filename'    => '',
        'chunkNumber' => '',
        'chunkSize'   => '',
        'totalSize'   => '',
        'type'        => ''
    ];

    /**
     * Sets parameters property from passed array.
     *
     * @param  array                         $parameters
     * @return \Cubes\Uploader\UploadHandler $this
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Returns array of currently defined parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Returns specified parameter from parameters property.
     *
     * @param  $parameter
     * @return mixed
     */
    public function getParameter($parameter)
    {
        if ($this->hasParameter($parameter)) {
            return $this->parameters[$parameter];
        }
    }

    /**
     * Sets parameter to passed value.
     *
     * @param  $parameter
     * @return mixed
     */
    public function setParameter($parameter, $value)
    {
        return $this->parameters[$parameter] = $value;
    }

    /**
     * Checks if there is parameter in parameters property with passed key/name.
     *
     * @param  $parameter
     * @return boolean
     */
    public function hasParameter($parameter)
    {
        if (array_key_exists($parameter, $this->parameters)) {
            return true;
        }
    }

    /**
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->parameters);
    }

    /**
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (isset($this->parameters[$offset])) {
            return $this->__get($offset);
        }
    }

    /**
     * @param  mixed $offset
     * @param  mixed $value
     *
     * @throws \Exception
     */
    public function offsetSet($offset, $value)
    {
        if (empty($value)) {
            throw new \Exception(
                'You must provide valid value for class attribute: ' . $offset . ' current value is empty.'
            );
        }

        $this->__set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        if (isset($this->parameters[$offset])) {
            unset($this->parameters[$offset]);
        }
    }

    /**
     * Magic method __set().
     *
     * @param  $name
     * @param  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') !== false) {
            return $this->__get(lcfirst(
                ucwords(str_replace('get', '', $name))
            ));
        }

        if (strpos($name, 'set') !== false) {
            return $this->__set($name, $arguments);
        }
    }

    /**
     * Magic method __set().
     *
     * @param  $name
     * @param  $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        return $this->parameters[$name] = $value;
    }

    /**
     * Magic method __get().
     *
     * @param  $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->parameters[$name];
    }

    /**
     * Magic method __unset().
     *
     * @param  $name
     * @return mixed
     */
    public function __unset($name)
    {
        unset($this->parameters[$name]);
    }

    /**
     * Magic method __isset().
     *
     * @param  $name
     * @return mixed
     */
    public function __isset($name)
    {
        return isset($this->parameters[$name]);
    }

    /**
     * @return null
     */
    public function getAccessor()
    {
        return $this->accessor;
    }

    /**
     * @param null $accessor
     * @return UploadHandler
     */
    public function setAccessor($accessor)
    {
        $this->accessor = $accessor;
        return $this;
    }
}