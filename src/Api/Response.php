<?php

namespace Api;

use App\Config;
use App\Utils;

/**
 * Standardized format for JSON responses from API endpoints
 *
 * @link https://blog.intzone.com/designing-developer-friendly-json-for-api-responses/
 */
class Response
{
    /**
     * @var string[]
     */
    public $headers = [
        'Content-Type: application/json; charset=utf-8',
    ];

    /**
     * @var int
     */
    public $statusCode = 0;

    /**
     * @var string
     */
    public $errorMessage = '';

    /**
     * @var array
     */
    public $data = [];

    /**
     * Whether this is an error response
     *
     * @var bool
     */
    public $isError = false;

    /**
     * Application config
     *
     * @var Config
     */
    protected $config = null;

    /**
     * Constructor
     *
     * @param Config $config Application config.
     * @param int $statusCode HTTP status code.
     * @param string $errorMessage="" Error message if error response.
     * @param array $data=[] Key-value pairs to return if success response.
     */
    public function __construct(Config $config, int $statusCode, string $errorMessage = '', array $data = [])
    {
        $this->config = $config;
        $this->statusCode = intval($statusCode);
        $this->errorMessage = strval($errorMessage);
        $this->data = $data;
        $this->isError = ($this->statusCode >= 400);
    }

    /**
     * Output of instance as string
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode(
            [
                'data' => $this->isError ? null : $this->data,
                'error' => (! $this->isError) ? null : [
                    'message' => $this->errorMessage,
                ],
                'meta' => [
                    'request_id' => Utils::getRequestId(),
                    'status_code' => $this->statusCode,
                    'version' => $this->config->getVersion(),
                ],
            ]
        );
    }

    /**
     * Send out response
     *
     * @return void
     */
    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $header) {
            header($header);
        }
        echo $this->__toString();

        // Must exit for response to be written properly
        exit;
    }
}
