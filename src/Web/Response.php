<?php

namespace Web;

use App\Config;

/**
 * Standardized format for HTML responses from Web endpoints
 */
class Response
{
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
     * Whether this is an error response
     *
     * @var bool
     */
    public $isError = false;

    /**
     * Constructor
     *
     * @param int $statusCode HTTP status code.
     * @param string $viewPath="" Path to view template file used for
     *     rendering HTML response, relative to src/Web/view. E.g. layout.phtml.
     * @param array $viewData=[] Key-value pairs to pass to view template file.
     */
    public function __construct(int $statusCode, string $viewPath = '', array $viewData = [])
    {
        $this->statusCode = intval($statusCode);
        $this->viewPath = getcwd() . DIRECTORY_SEPARATOR . 'src/Web/view' . DIRECTORY_SEPARATOR . $viewPath;
        $this->viewData = $viewData;
        $this->isError = ($this->statusCode >= 400);
    }

    /**
     * Output of instance as string
     *
     * View will be rendered and wrapped in layout
     *
     * @link Adapted from render() in https://github.com/zionsg/simple-ui-templating/blob/master/src/functions.php
     * @return string
     */
    public function __toString()
    {
        $sharedViewData = [
            /** @var string Application version. */
            'version' => Config::getVersion(),

            /** @var string 20-char random identifier for HTML "data-render-id" attribute. */
            'renderId' => strval(round(microtime(true) * 1000) . '-' . rand(100000, 999999)),
        ];

        // Import template variables as PHP variables so that they can be
        // accessed directly by their variable names in the view template,
        // e.g. `echo $renderId;` instead of `echo $vars['renderId'];`
        $resolvedViewData = array_merge(
            $this->viewData ?: [],
            $sharedViewData
        );
        extract($resolvedViewData);
        ob_start();
        include $this->viewPath;
        $viewHtml = ob_get_clean();

        // Wrap rendered HTML for view in layout
        $layoutViewData = array_merge(
            [
                'body' => $viewHtml,
            ],
            $sharedViewData
        );
        extract($layoutViewData);
        ob_start();
        include Config::get('web_layout_path');
        $layoutHtml = ob_get_clean();

        return $layoutHtml;
    }

    /**
     * Send out response
     *
     * @return void
     */
    public function send()
    {
        http_response_code($this->statusCode);
        header('Content-Type: text/html; charset=utf-8');
        echo $this->__toString();

        // Must exit for response to be written properly
        exit;
    }
}
