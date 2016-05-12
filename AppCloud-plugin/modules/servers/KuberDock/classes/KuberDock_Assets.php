<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Assets;

class KuberDock_Assets extends CL_Assets {
    /**
     * Relative assets path
     */
    const RELATIVE_PATH = 'modules/servers';

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
        global $CONFIG;

        $url = $CONFIG['SystemURL'];
        $path = array(self::RELATIVE_PATH, KUBERDOCK_MODULE_NAME, self::ASSET_DIRECTORY , $fileName);

        if (strpos($url, '/', strlen($url) - 1) === false) {
            $url .= '/';
        }

        return $url . implode('/', $path);
    }
} 