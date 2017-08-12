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

        // Send response data.
        $data = $this->getContent();
        switch ($code):
            case 200:
                header(self::CODE_200);
                $data['code'] = self::CODE_200;
                $data['message'] = 'OK';
                return $data;
                break;
            case 201:
                header(self::CODE_201);
                $data['code'] = self::CODE_201;
                $data['message'] = 'OK';
                return $data;
                break;
            case 204:
                header(self::CODE_204);
                $data['code'] = self::CODE_204;
                $data['message'] = 'Chunk not found.';
                return $data;
                break;
            case 404:
                header(self::CODE_404);
                $data['code'] = self::CODE_404;
                $data['message'] = 'An error occurred.';
                return $data;
                break;
            default:
                header(self::CODE_404);
                $data['code'] = self::CODE_404;
                $data['message'] = 'An error occurred.';
                return $data;
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