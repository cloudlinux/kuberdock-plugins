<?php

namespace Kuberdock\classes\panels\assets;


class DirectAdmin_Assets extends Assets {
    /**
     * @param string $fileName
     * @return string
     */
    public function getRelativePath($fileName) {
        return 'KuberDock/images/' . self::ASSET_DIRECTORY . '/' . $fileName;
    }
}