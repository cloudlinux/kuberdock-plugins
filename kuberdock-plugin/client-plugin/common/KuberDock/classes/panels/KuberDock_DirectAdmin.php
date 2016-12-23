<?php

namespace Kuberdock\classes\panels;

use Kuberdock\classes\api\Response;
use Kuberdock\classes\Base;
use Kuberdock\classes\KDCommonCommand;
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
        $selectedDomain = getenv('SESSION_SELECTED_DOMAIN');

        // User comes on KD page after login
        if (!$selectedDomain) {
            $command = new KDCommonCommand();
            $data = current($command->getUserDomains());
            if ($data) {
                return $data[0];
            }
        }

        return $selectedDomain;
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
     * @param bool $root
     * @return string
     */
    public function getURL($root = true)
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http';
        $host = $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR'];
        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;

        return sprintf('%s://%s:%s/CMD_PLUGINS/KuberDock', $scheme, $host, $port);
    }

    public static function getClientUrl($action = 'index')
    {
        return '/CMD_PLUGINS/KuberDock?a=' . $action;
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

    public static function getAdminInstallAppButton($template)
    {
        $class = $template['search_available'] ? 'btn btn-warning btn-xs' : 'btn btn-success btn-xs';
        $title = $template['search_available'] ? 'Uninstall' : 'Install';
        $action = $template['search_available'] ? 'appUninstall' : 'appInstall';

        return '<a href="?a=' . $action . '&id=' . $template['id'] . '">
                <button type="button" class="' . $class . '" title="Install">' . $title . '</button>
            </a>';
    }

    public static function getAdminUpdateAppButton($template)
    {
        return '<a href="?a=app&id=' . $template['id'] . '">
                <button type="button" class="btn btn-primary btn-xs" title="Update">
                    <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>Update
                </button>
            </a>';
    }

    public static function getAdminDeleteAppButton($template)
    {
        return '<a href="?a=appDelete&id=' . $template['id'] . '" data-id="' . $template['id']
                    . '" data-name="' . $template['name'] . '">
                <button type="button" class="btn btn-danger btn-xs" title="Delete">
                    <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>Delete
                </button>
            </a>';
    }
}