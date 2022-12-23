<?php

namespace Api;

use App\Application;
use App\Config;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Standardized format for JSON responses from API endpoints
 *
 * @link https://blog.intzone.com/designing-developer-friendly-json-for-api-responses/
 */
class ApiResponse extends JsonResponse
{
    /**
     * Application config
     *
     * @var Config
     */
    protected $config = null;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * Constructor
     *
     * @param Config $config Application config.
     * @param LoggerInterface $logger Logger.
     * @param ServerRequestInterface $request Request.
     * @param int $status=200 HTTP status code.
     * @param string $errorMessage="" Error message if error response.
     * @param array $data=[] Key-value pairs to return if success response.
     * @param array $headers=[] Key-value pairs for additional headers if any.
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        ServerRequestInterface $request,
        int $status = 200,
        string $errorMessage = '',
        array $data = [],
        array $headers = []
    ) {
        $this->config = $config;
        $this->logger = $logger;

        $isError = ($status >= 400);
        $body = [
            'data' => $isError ? null : $data,
            'error' => $isError ? ['message' => $errorMessage] : null,
            'meta' => [
                'request_id' => $request->getAttribute(Application::ATTR_REQUEST_ID),
                'status' => $status,
                'version' => $this->config->getVersion(),
            ],
        ];

        parent::__construct($body, $status, $headers);
    }
}
