<?php

namespace Cubes\Uploader\Http;

/**
 * Interface ResponseInterface
 *
 * @package Cubes\Uploader\Http
 */
interface ResponseInterface
{
    /**
     * Response header messages related to status codes.
     */
    const CODE_200 = 'HTTP/1.0 200 Ok';
    const CODE_201 = 'HTTP/1.0 201 Accepted';
    const CODE_204 = 'HTTP/1.0 204 No Content';
    const CODE_404 = 'HTTP/1.0 404 Not Found';

    /**
     * @return mixed
     */
    public function send($code);
}