<?php

namespace Kuberdock\classes\panels;

use Kuberdock\classes\api\Response;
use Kuberdock\classes\panels\assets\DirectAdmin_Assets;
use Kuberdock\classes\panels\fileManager\DirectAdmin_FileManager;
use Kuberdock\classes\exceptions\CException;


class KuberDock_DirectAdmin extends KuberDock_Panel
{
    /**
     * @throws CException
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return DirectAdmin_FileManager
     */
    public function getFileManager()
    {
        if (!$this->fileManager) {
            $this->fileManager = new DirectAdmin_FileManager();
        }

        return $this->fileManager;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return getenv('USER');
    }

    /**
     * @return string
     */
    public function getUserGroup()
    {
        $groupInfo = posix_getgrgid(posix_getegid());
        return $groupInfo['name'];
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return getenv('SESSION_SELECTED_DOMAIN');
    }

    /**
     * @return string
     */
    public function getHomeDir()
    {
        return getenv('HOME');
    }

    /**
     * @return string
     */
    public function getRootUrl()
    {
        return 'KuberDock/index.html';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'KuberDock/api.raw';
    }

    /**
     * @return DirectAdmin_Assets
     */
    public function getAssets()
    {
        if (!$this->assets) {
            $this->assets = DirectAdmin_Assets::model();
        }

        return $this->assets;
    }

    /**
     * Headers for Kuberdock\classes\api\KuberDock\* except get_stream()
     * @param int $code
     */
    public function renderResponseHeaders($code)
    {
        echo "HTTP/1.1 " . $code . " " . Response::requestStatus($code) . "\r\n";
        echo "Content-Type: application/json\r\n\r\n";
    }

    /**
     * Headers for Kuberdock\classes\api\KuberDock\get_stream()
     */
    public function renderStreamHeaders()
    {
        echo "HTTP/1.1 200 OK\r\n";
        echo "Content-Type: text/event-stream\r\n";
        echo "X-Accel-Buffering: no\r\n\r\n";
    }

    /**
     * @return array
     */
    protected function getAdminData()
    {
        $data = array();
        ob_start();
        passthru('/usr/local/directadmin/plugins/KuberDock/bin/read_conf');
        $content = ob_get_contents();
        ob_end_clean();

        foreach (explode("\n", $content) as $line) {
            if (in_array(substr($line, 0, 1), array('#', '/'))) continue;

            if (preg_match('/^(.*)=(.*)$/', $line, $match)) {
                $data[trim($match[1])] = trim($match[2]);
            }
        }

        return $data;
    }
}