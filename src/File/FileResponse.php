<?php

namespace File;

use App\Application;
use App\Config;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Standardized format for responses serving static file content
 */
class FileResponse extends Response
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
     * @param string $filePath Absolute path of file to read contents from and
     *     serve out in response.
     * @param array $headers=[] Key-value pairs for additional headers if any.
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        ServerRequestInterface $request,
        int $status = 200,
        string $errorMessage = '',
        string $filePath = '',
        array $headers = []
    ) {
        $this->config = $config;
        $this->logger = $logger;

        $isError = ($status >= 400);
        if ($isError) {
            $headers['Content-Type'] = 'text/plain';
            parent::__construct($errorMessage, $status, $headers);

            return;
        }

        $fileHandle = false;
        if (! $filePath || false === ($fileHandle = fopen($filePath, 'rb'))) {
            parent::__construct('Unable to open file.', 404, $headers);

            return;
        }

        $headers['Content-Type'] = mime_content_type($fileHandle);
        $headers['Content-Length'] = filesize($filePath);

        ob_start();
        while (! feof($fileHandle)) {
            echo fread($fileHandle, 1048576); // read in 1KB chunks
        }
        $contents = ob_get_clean();
        fclose($fileHandle);

        $body = new Stream('php://temp', 'wb+');
        $body->write($contents);
        $body->rewind();

        parent::__construct($body, $status, $headers);
    }
}
