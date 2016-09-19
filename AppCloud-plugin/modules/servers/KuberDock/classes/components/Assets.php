<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace components;


class Assets extends Component {
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
     * Relative assets path
     */
    const RELATIVE_PATH = 'modules/servers';

    /**
     * JavaScript files repository
     *
     * @var array
     */
    protected $scripts = array();
    /**
     * Style files repository
     *
     * @var array
     */
    protected $styles = array();

    /**
     * Render JavaScript files on the view page
     *
     * @param bool $output
     * @return mixed string|void
     */
    public function renderScriptFiles($output = true)
    {
        $html = '';
        foreach ($this->scripts as $script) {
            $relPath = $this->getRelativePath('js/' . $script). '.' . self::SCRIPT_EXT;
            $html .= sprintf('<script src="%s"></script>', $relPath);
        }

        if ($output) {
            echo $html;
        }

        return $html;
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
        foreach ($this->styles as $style) {
            $relPath = $this->getRelativePath('css/' . $style). '.' . self::STYLE_EXT;
            $html .= sprintf('<link rel="stylesheet" href="%s">', $relPath);
        }

        if ($output) {
            echo $html;
        }

        return $html;
    }

    /**
     * Add JavaScript files to local repository
     *
     * @param array $fileNames
     */
    public function registerScriptFiles($fileNames = array())
    {
        foreach ($fileNames as $row) {
            $this->scripts[] = $row;
        }
    }

    /**
     * Add style files to local repository
     *
     * @param array $fileNames
     */
    public function registerStyleFiles($fileNames = array())
    {
        foreach ($fileNames as $row) {
            $this->styles[] = $row;
        }
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function getRelativePath($fileName)
    {
        return $this->getAbsolutePath($fileName);
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function getAbsolutePath($fileName)
    {
        $config = \models\billing\Config::get();

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            if ($config->SystemSSLURL) {
                $url = $config->SystemSSLURL;
            } else {
                $url = str_replace('http:', 'https:', $config->SystemURL);
            }
        } else {
            $url = str_replace('https:', 'http:', $config->SystemURL);
        }

        $path = array(self::RELATIVE_PATH, KUBERDOCK_MODULE_NAME, self::ASSET_DIRECTORY , $fileName);

        if (strpos($url, '/', strlen($url) - 1) === false) {
            $url .= '/';
        }

        return $url . implode('/', $path);
    }
} 