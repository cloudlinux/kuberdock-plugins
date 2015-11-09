<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base;

abstract class CL_Assets {
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
     *
     */
    public function __construct()
    {
    }

    /**
     * @param string $fileName
     * @return string
     */
    abstract public function getRelativePath($fileName);

    /**
     * Render JavaScript files on the view page
     *
     * @param bool $output
     * @return mixed string|void
     */
    public function renderScriptFiles($output = true)
    {
        $html = '';
        foreach($this->_scripts as $script) {
            $relPath = $this->getRelativePath('js/' . $script). '.' . self::SCRIPT_EXT;
            $html .= sprintf('<script src="%s"></script>', $relPath);
        }

        if($output) {
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
    public function renderStyleFiles($output = true)
    {
        $html = '';
        foreach($this->_styles as $style) {
            $relPath = $this->getRelativePath('css/' . $style). '.' . self::STYLE_EXT;
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
    public function registerScriptFiles($fileNames = array())
    {
        foreach($fileNames as $row) {
            $this->_scripts[] = $row;
        }
    }

    /**
     * Add style files to local repository
     *
     * @param array $fileNames
     */
    public function registerStyleFiles($fileNames = array())
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
}