<?php

namespace Kuberdock\classes\panels\assets;


class cPanel_Assets extends Assets {
    /**
     * @param string $fileName
     * @return string
     */
    public function getRelativePath($fileName) {
        return self::ASSET_DIRECTORY . '/' . $fileName;
    }
}