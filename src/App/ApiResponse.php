<?php

namespace App;

/**
 * Standardized format for API responses from API endpoints
 *
 * @link https://blog.intzone.com/designing-developer-friendly-json-for-api-responses/
 */
class ApiResponse
{
    /**
     * @var string
     */
    public $version = '';

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
     * Constructor
     *
     * @param string $version Application version.
     * @param int $statusCode HTTP status code.
     * @param string $errorMessage="" Error message if error response.
     * @param array $data=[] Key-value pairs to return if success response.
     */
    public function __construct($version, $statusCode, $errorMessage = '', $data = [])
    {
        $this->version = strval($version);
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
                'error' => (!$this->isError) ? null : [
                    'message' => $this->errorMessage,
                ],
                'meta' => [
                    'status_code' => $this->statusCode,
                    'version' => $this->version,
                ],
            ],
            JSON_PRETTY_PRINT
        );
    }

    /**
     * Send out response
     *
     * @return void
     */
    public function send()
    {
        http_response_code($this->statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo $this->__toString();

        // Must exit for response to be written properly
        exit;
    }
}
