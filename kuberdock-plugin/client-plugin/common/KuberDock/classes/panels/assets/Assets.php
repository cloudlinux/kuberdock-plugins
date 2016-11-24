<?php

namespace Kuberdock\classes\panels\assets;

class Assets {
    /**
     * Default JavaScript file extension
     */
    const SCRIPT_EXT = 'js';
    /**
     * Default Css files extension
     */
    const STYLE_EXT = 'css';

    /**
     * Default assets directory path
     */
    const ASSET_DIRECTORY = 'assets';

    /**
     * JavaScript files repository
     *
     * @var array
     */
    protected $_scripts = array();
    /**
     * Style files repository
     *
     * @var array
     */
    protected $_styles = array();

    /**
     * @var $this
     */
    protected static $model;

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function getRelativePath($fileName) {

    }

    /**
     * Render JavaScript files on the view page
     *
     * @param bool $output
     * @return mixed string|void
     */
    public function renderScripts($output = true)
    {
        $html = '';
        foreach ($this->_scripts as $k => $script) {
            if (is_array($script)) {
                $attributes = array();
                $relPath = $this->getRelativePath($k). '.' . self::SCRIPT_EXT;

                foreach ($script as $attr => $data) {
                    $attributes[] = sprintf('%s="%s"', $attr, $data);
                }
                $html .= sprintf('<script src="%s" %s></script>', $relPath, implode(' ', $attributes));
            } else {
                $relPath = $this->getRelativePath($script). '.' . self::SCRIPT_EXT;
                $html .= sprintf('<script src="%s"></script>', $relPath);
            }
        }

        if ($output) {
            echo $html;
        } else {
            return $html;
        }
    }

    /**
     * Render style files on the view page
     *
     * @param bool $output
     * @return mixed string|void
     */
    public function renderStyles($output = true)
    {
        $html = '';
        foreach($this->_styles as $style) {
            $relPath = $this->getRelativePath($style). '.' . self::STYLE_EXT;
            $html .= sprintf('<link rel="stylesheet" href="%s">', $relPath);
        }

        if($output) {
            echo $html;
        } else {
            return $html;
        }
    }

    /**
     * Add JavaScript files to local repository
     *
     * @param array $fileNames
     */
    public function registerScripts($fileNames = array())
    {
        foreach($fileNames as $k => $row) {
            $this->_scripts[$k] = $row;
        }
    }

    /**
     * Add style files to local repository
     *
     * @param array $fileNames
     */
    public function registerStyles($fileNames = array())
    {
        foreach($fileNames as $row) {
            $this->_styles[] = $row;
        }
    }

    /**
     * Get JavaScript file content
     *
     * @param $fileName
     * @return string
     */
    public function getScriptFileContent($fileName)
    {
        return $this->getFileContent($fileName, self::SCRIPT_EXT);
    }

    /**
     * Get style file content
     *
     * @param $fileName
     * @return string
     */
    public function getStyleFileContent($fileName)
    {
        return $this->getFileContent($fileName, self::STYLE_EXT);
    }

    /**
     * Get file content by extension
     *
     * @param $fileName
     * @param $fileExtension
     * @return string
     */
    private function getFileContent($fileName, $fileExtension)
    {
        $content = '';
        $filePath = KUBERDOCK_ROOT_DIR . DS . self::ASSET_DIRECTORY . DS . $fileName . '.' . $fileExtension;

        if(file_exists($filePath) && (in_array($fileName, $this->_styles) || in_array($fileName, $this->_scripts))) {
            $content = file_get_contents($filePath);
        }

        return $content;
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = __CLASS__)
    {
        if ($class = get_called_class()) {
            $className = $class;
        }

        if (!self::$model[$className]) {
            self::$model[$className] = new $className;
        }

        return self::$model[$className];
    }
}