<?php

namespace Kuberdock\classes\panels\assets;

use Kuberdock\classes\KuberDock_Assets;

class KuberDock_cPanel_Assets extends KuberDock_Assets {
    /**
     * @param string $fileName
     * @return string
     */
    public function getRelativePath($fileName) {
        return self::ASSET_DIRECTORY . '/' . $fileName;
    }
}