<?php

namespace Kuberdock\classes\panels\assets;


class Plesk_Assets extends Assets {
    /**
     * @param string $fileName
     * @return string
     */
    public function getRelativePath($fileName) {
        return \pm_Context::getBaseUrl() . self::ASSET_DIRECTORY . '/' . $fileName;
    }
}