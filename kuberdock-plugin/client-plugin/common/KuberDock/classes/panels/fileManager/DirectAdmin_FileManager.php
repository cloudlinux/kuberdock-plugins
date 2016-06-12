<?php

namespace Kuberdock\classes\panels\fileManager;

use Kuberdock\classes\panels\KuberDock_DirectAdmin;

class DirectAdmin_FileManager implements FileManagerInterface
{
    /**
     * @param string $srcPath
     * @param string $dstPath
     * @return mixed
     */
    public function copy($srcPath, $dstPath)
    {
        copy($srcPath, $dstPath);
        $this->chmod($dstPath, 0600);
    }

    /**
     * @param string $path
     * @param int $mode
     * @return mixed
     */
    public function chmod($path, $mode)
    {
        chmod($path, $mode);
    }

    /**
     * @param string $path
     * @param string $user
     * @return mixed
     */
    public function chown($path, $user = '')
    {
        if (!$user) {
            return;
        }

        chown($path, $user);
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function getFileContent($path)
    {
        return file_get_contents($path);
    }

    /**
     * @param string $path
     * @param string $content
     * @return mixed
     */
    public function putFileContent($path, $content)
    {
        file_put_contents($path, $content);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function file_exists($path)
    {
        return file_exists($path);
    }

    /**
     * @param string $path
     * @param int $mode
     * @return mixed
     */
    public function mkdir($path, $mode = null)
    {
        mkdir($path, $mode);
    }
}