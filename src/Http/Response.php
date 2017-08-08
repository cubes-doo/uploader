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
     * Array of content to be sent as response.
     *
     * @var array
     */
    protected $content = [
        'message' => '',
        'code'    => '',
        'data'    => []
    ];

    /**
     * Method send used to send response with appropriate status code and header.
     *
     * @param  integer $code
     * @param  boolean $rawResponse
     * @return int|mixed
     */
    public function send($code, $rawResponse = false)
    {
        // Send raw response data for later usage in controllers.
        if ($rawResponse) {
            $this->content['code'] = $code;
            return $this->getContent();
        }

        // Send regular Laravel response object.
        switch ($code):
            case 200:
                header(self::CODE_200);
                return response([
                    'message' => 'OK',
                    $this->getResponseData()
                ], $code);
                break;
            case 201:
                header(self::CODE_201);
                return response([
                    'message' => 'OK',
                    $this->getResponseData()
                ], $code);
                break;
            case 204:
                return response([
                    'message' => 'Chunk not found',
                    $this->getResponseData()
                ], $code);
                break;
            case 404:
                header(self::CODE_404);
                return response([
                    'message' => 'An error occurred',
                    $this->getResponseData()
                ], $code);
                break;
            default:
                header(self::CODE_404);
                return response([
                    'message' => 'An error occurred',
                    $this->getResponseData()
                ], $code);
        endswitch;
    }

    /**
     * @return array
     */
    public function getResponseData()
    {
       return $this->getContent()['data'];
    }

    /**
     * @param  $name
     * @param  $value
     * @return $this
     */
    public function setResponseData($name, $value)
    {
        $this->content['data'][$name] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setContent($name, $value)
    {
        $this->content[$name] = $value;
    }
}