<?php

namespace Cubes\Uploader\Http;

/**
 * Class Response
 *
 * @package Cubes\Uploader\Http
 */
class Response implements ResponseInterface
{
    /**
     * Method send used to send response with appropriate status code and header.
     *
     * @param  $code
     * @return int
     */
    public function send($code)
    {
        switch ($code):
            case 200:
                header(self::CODE_200);
                return response([
                    'message' => 'OK',
                ], 200);
                break;
            case 201:
                header(self::CODE_201);
                return response([
                    'message' => 'OK',
                ], 201);
                break;
            case 204:
                return response([
                    'message' => 'Chunk not found',
                ], 204);
                break;
            case 404:
                header(self::CODE_404);
                return response([
                    'message' => 'An error occurred',
                ], 404);
                break;
            default:
                header(self::CODE_404);
                return response([
                    'message' => 'An error occurred',
                ], 404);;
        endswitch;
    }
}