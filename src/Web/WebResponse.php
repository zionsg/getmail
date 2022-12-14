<?php

namespace Web;

use App\Application;
use App\Config;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Standardized format for HTML responses from Web endpoints
 */
class WebResponse extends HtmlResponse
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
     * @var ServerRequestInterface
     */
    protected $request = null;

    /**
     * @var string
     */
    protected $viewPath = '';

    /**
     * @var array
     */
    protected $viewData = [];

    /**
     * @var bool
     */
    protected $wrapInLayout = true;

    /**
     * Constructor
     *
     * @param Config $config Application config.
     * @param LoggerInterface $logger Logger.
     * @param ServerRequestInterface $request Request.
     * @param int $status=200 HTTP status code.
     * @param string $viewPath="" Path to view template file used for
     *     rendering HTML response, relative to src/Web/view. E.g. layout.phtml.
     * @param array $viewData=[] Key-value pairs to pass to view template file.
     * @param array $headers=[] Key-value pairs for additional headers if any.
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        ServerRequestInterface $request,
        int $status = 200,
        string $viewPath = '',
        array $viewData = [],
        array $headers = []
    ) {
        $this->config = $config;
        $this->logger = $logger;

        $this->request = $request;
        $this->viewPath = $viewPath
            ? getcwd() . DIRECTORY_SEPARATOR . 'src/Web/view' . DIRECTORY_SEPARATOR . $viewPath
            : '';
        $this->viewData = $viewData;

        // Whether to wrap HTML for view in layout template, true by default. See src/Web/view/layout.phtml.
        $this->wrapInLayout = (1 === $request->getAttribute(Application::ATTR_LAYOUT, 1));

        $body = $this->render();
        parent::__construct($body, $status, $headers);
    }

    /**
     * Render HTML body
     *
     * View will be rendered and optionally wrapped in layout template.
     *
     * @link Adapted from render() in https://github.com/zionsg/simple-ui-templating/blob/master/src/functions.php
     * @return string
     */
    protected function render(): string
    {
        // Variables that are passed to layout and view templates
        // $renderId is useful in identifying unique HTML elements in scripts, especially when
        // they are loaded via an client-side AJAX call (not so strict on collisions hence uniqid).
        $sharedViewData = [
            'renderId' => uniqid(microtime(true) . '-', true), // unique identifier for HTML "data-render-id" attribute
            'requestId' => $this->request->getAttribute(Application::ATTR_REQUEST_ID),
            'version' => $this->config->getVersion(), // version to be appended to public assets for cache busting
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
}
