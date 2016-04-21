<?php

namespace Kuberdock\classes\panels\fileManager;

use Kuberdock\classes\Base;

class Plesk_FileManager implements FileManagerInterface
{
    /**
     * @var \pm_ServerFileManager
     */
    private $driver;

    /**
     *
     */
    public function __construct()
    {
        $this->driver = new \pm_ServerFileManager();
    }
    /**
     * @param string $srcPath
     * @param string $dstPath
     * @return mixed
     */
    public function copy($srcPath, $dstPath)
    {
        $this->driver->removeFile($dstPath);
        $this->driver->filePutContents($dstPath, $this->getFileContent($srcPath));
        $this->chown($dstPath, $this->getUser());
        $this->chmod($dstPath, 0660);
    }

    /**
     * @param string $path
     * @param int $mode
     * @return mixed
     */
    public function chmod($path, $mode)
    {
        $mode = intval(decoct($mode));
        return $this->driver->chmod($path, $mode);
    }

    /**
     * @param string $path
     * @param string $user
     * @return mixed
     */
    public function chown($path, $user = '')
    {
        if (!$user) {
            $user = $this->getUser();
        }

        return \pm_ApiCli::callSbin('ch_owner', array($user, $path));
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function getFileContent($path)
    {
        if (!$this->file_exists($path)) {
            return '';
        }

        return $this->driver->fileGetContents($path);
    }

    /**
     * @param string $path
     * @param string $content
     * @return mixed
     */
    public function putFileContent($path, $content)
    {
        if ($this->file_exists($path)) {
            $this->driver->removeFile($path);
        }
        $this->driver->filePutContents($path, $content);
        $this->chown($path, $this->getUser());
    }

    /**
     * @param string $path
     * @param null $mode
     * @return mixed
     */
    public function mkdir($path, $mode = null)
    {
        $mode = intval(decoct($mode));
        $data = $this->driver->mkdir($path, $mode);
        $this->chown($path, $this->getUser());

        return $data;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function file_exists($path)
    {
        return $this->driver->fileExists($path);
    }

    /**
     * @return string
     * @throws \Kuberdock\classes\exceptions\CException
     */
    private function getUser()
    {
        $panel = Base::model()->getStaticPanel();
        return $panel->getUser() . ':' . $panel->getUserGroup();
    }
}