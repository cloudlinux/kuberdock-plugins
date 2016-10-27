<?php
/**
 * @project whmcs-plugin
 */

namespace components;

use Exception;

class View {
    const VIEW_DIRECTORY = 'view';
    const LAYOUT_DIRECTORY = 'layout';

    /**
     * @var
     */
    public $controller;
    /**
     * @var string
     */
    public $viewDirectory;
    /**
     * @var string
     */
    public $layoutDirectory;
    /**
     * @var string
     */
    public $layout = 'default';

    /**
     * @param mixed
     */
    public function __construct($controller = '')
    {
        $this->controller = $controller;

        if ($controller instanceof Controller) {
            $controllerName = lcfirst($controller->controller);
        } else {
            $controllerName = lcfirst($controller);
        }

        $this->viewDirectory = KUBERDOCK_ROOT_DIR . DS . self::VIEW_DIRECTORY;
        $this->layoutDirectory = KUBERDOCK_ROOT_DIR . DS . self::VIEW_DIRECTORY . DS . self::LAYOUT_DIRECTORY;

        if ($controllerName) {
            $this->viewDirectory .= DS . $controllerName;
        }
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @param string $path
     */
    public function setViewDirectory($path)
    {
        $this->viewDirectory = $path;
    }

    /**
     * @param string $path
     */
    public function setLayoutDirectory($path)
    {
        $this->layoutDirectory = $path;
    }

    /**
     * Render view file with layout
     *
     * @param string $view
     * @param array $values
     * @param bool $output
     * @return string
     * @throws Exception
     */
    public function render($view, $values = [], $output = true)
    {
        $values['controller'] = $this->controller;

        $viewPath = $this->viewDirectory . DS . $view . '.php';
        $layoutPath = $this->layoutDirectory . DS . $this->layout . '.php';

        if (!file_exists($viewPath)) {
            throw new Exception('View file not found: '. $view);
        }

        if (!file_exists($layoutPath)) {
            throw new Exception('Layout file not found: '. $this->layout);
        }

        ob_start();
        extract($values);
        include $viewPath;
        $content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include_once $layoutPath;
        $content = ob_get_contents();
        $output ? ob_end_flush() : ob_end_clean();

        return $content;
    }

    /**
     * Render view file
     *
     * @param string $view
     * @param array $values
     * @param bool $output
     * @return string
     * @throws Exception
     */
    public function renderPartial($view, $values = [], $output = true)
    {
        $viewPath = $this->viewDirectory . DS . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new Exception('View file not found');
        }

        ob_start();
        extract($values);
        include $viewPath;
        $content = ob_get_contents();
        $output ? ob_end_flush() : ob_end_clean();

        return $content;
    }
}