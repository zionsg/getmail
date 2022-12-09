<?php

namespace Web;

use App\Config;
use App\Utils;

/**
 * Standardized format for HTML responses from Web endpoints
 */
class Response
{
    /**
     * @var string[]
     */
    public $headers = [
        'Content-Type: text/html; charset=utf-8',
    ];

    /**
     * @var int
     */
    public $statusCode = 0;

    /**
     * @var string
     */
    public $viewPath = '';

    /**
     * @var array
     */
    public $viewData = [];

    /**
     * @var bool
     */
    public $wrapInLayout = true;

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
     * @param string $viewPath="" Path to view template file used for
     *     rendering HTML response, relative to src/Web/view. E.g. layout.phtml.
     * @param array $viewData=[] Key-value pairs to pass to view template file.
     * @param bool $wrapInLayout=true Whether to wrap the rendered HTML for the
     *     view in the layout template.
     */
    public function __construct(
        Config $config,
        int $statusCode,
        string $viewPath = '',
        array $viewData = [],
        bool $wrapInLayout = true
    ) {
        $this->config = $config;
        $this->statusCode = intval($statusCode);
        $this->viewPath = getcwd() . DIRECTORY_SEPARATOR . 'src/Web/view' . DIRECTORY_SEPARATOR . $viewPath;
        $this->viewData = $viewData;
        $this->wrapInLayout = $wrapInLayout;
        $this->isError = ($this->statusCode >= 400);
    }

    /**
     * Output of instance as string
     *
     * View will be rendered and wrapped in layout template.
     *
     * @link Adapted from render() in https://github.com/zionsg/simple-ui-templating/blob/master/src/functions.php
     * @return string
     */
    public function __toString()
    {
        $sharedViewData = [
            /** @var string Application version. */
            'version' => $this->config->getVersion(),

            /** @var string Unique identifier for HTML "data-render-id" attribute. */
            'renderId' => Utils::makeUniqueId(),
        ];

        // Import template variables as PHP variables so that they can be
        // accessed directly by their variable names in the view template,
        // e.g. `echo $renderId;` instead of `echo $vars['renderId'];`
        $resolvedViewData = array_merge(
            $this->viewData ?: [],
            $sharedViewData
        );
        extract($resolvedViewData);

        // Render HTML for view
        $viewHtml = '';
        if ($this->viewPath) {
            ob_start();
            include $this->viewPath;
            $viewHtml = ob_get_clean();
        }

        // Return if no need to wrap in layout template
        if (! $this->wrapInLayout) {
            return $viewHtml;
        }

        // Wrap rendered HTML for view in layout template
        $layoutViewData = array_merge(
            [
                'body' => $viewHtml,
            ],
            $sharedViewData
        );
        extract($layoutViewData);
        ob_start();
        include $this->config->get('web_layout_path');
        $layoutHtml = ob_get_clean();

        return $layoutHtml;
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
