<?php

namespace Cubes\Uploader\Component;

/**
 * Interface ComponentInterface
 *
 * @package Cubes\Uploader\Component
 */
interface ComponentInterface
{
    /**
     * Method buildComponents used for injecting components into container,
     * so we can use it in inherit classes.
     *
     * @return mixed
     */
    public function buildComponents();
}