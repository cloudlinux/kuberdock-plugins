<?php

namespace Kuberdock\classes\panels\fileManager;


/**
 * Interface FileManagerInterface
 */
interface FileManagerInterface
{
    /**
     * @param string $srcPath
     * @param string $dstPath
     * @return mixed
     */
    public function copy($srcPath, $dstPath);

    /**
     * @param string $path
     * @param int $mode
     * @return mixed
     */
    public function chmod($path, $mode);

    /**
     * @param string $path
     * @param string $user
     * @return mixed
     */
    public function chown($path, $user = '');

    /**
     * @param string $path
     * @return mixed
     */
    public function getFileContent($path);

    /**
     * @param string $path
     * @param string $content
     * @return mixed
     */
    public function putFileContent($path, $content);

    /**
     * @param string $path
     * @param int $mode
     * @return mixed
     */
    public function mkdir($path, $mode = null);

    /**
     * @param string $path
     * @return bool
     */
    public function file_exists($path);
}