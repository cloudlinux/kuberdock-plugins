<?php

namespace Kuberdock\classes\panels;

use Kuberdock\classes\components\KuberDock_Api;
use Kuberdock\classes\KcliCommand;
use Kuberdock\classes\KDCommonCommand;
use Kuberdock\classes\panels\fileManager\Plesk_FileManager;
use Kuberdock\classes\Tools;
use Kuberdock\classes\Base;
use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\panels\assets\Plesk_Assets;

class KuberDock_Plesk extends KuberDock_Panel
{
    /**
     * @throws CException
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return Plesk_FileManager
     */
    public function getFileManager()
    {
        if (!$this->fileManager) {
            $this->fileManager = new Plesk_FileManager();
        }

        return $this->fileManager;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        $session = new \pm_Session();
        $client = $session->getClient();

        $sql = sprintf('SELECT s.login FROM sys_users s
            LEFT JOIN hosting h on s.id=h.sys_user_id
            LEFT JOIN domains d on d.id=h.dom_id
            LEFT JOIN clients c on c.id=d.cl_id
            WHERE c.id = %d LIMIT 1', $client->getProperty('id'));

        ob_start();
        passthru('/usr/sbin/plesk db "' . $sql . '" 2>&1');
        $response = ob_get_contents();
        ob_end_clean();

        if (preg_match_all('|[\.\w-]+|', $response, $match) && isset($match[0][3])) {
            return $match[0][3];
        } else {
            return $client->getProperty('login');
        }
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
        $session = new \pm_Session();
        $domain = $session->getCurrentDomain();
        return $domain->getName();
    }

    /**
     * @return string
     */
    public function getHomeDir()
    {
        $data = posix_getpwnam($this->getUser());

        if (isset($data['dir'])) {
            return $data['dir'];
        } else {
            $dir = \pm_Context::getVarDir() . $this->getUser();
            if (!is_dir($dir)) {
                $this->getFileManager($dir, 0700);
            }

            return $dir;
        }
    }

    /**
     * @return string
     */
    public function getRootUrl()
    {
        return 'index';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'api';
    }

    /**
     * @return Plesk_Assets
     */
    public function getAssets()
    {
        if (!$this->assets) {
            $this->assets = Plesk_Assets::model();
        }

        return $this->assets;
    }

    /**
     * @return array
     */
    protected function getAdminData()
    {
        $data = array();
        $fileManager = new \pm_ServerFileManager();
        $content = $fileManager->fileGetContents('/root/.kubecli.conf');

        foreach (explode("\n", $content) as $line) {
            if (in_array(substr($line, 0, 1), array('#', '/'))) continue;

            if (preg_match('/^(.*)=(.*)$/', $line, $match)) {
                $data[trim($match[1])] = trim($match[2]);
            }
        }

        return $data;
    }

    public static function getAdminUpdateAppButton($template)
    {
        return '<a href="' . \pm_Context::getActionUrl('admin', 'application')
            . '?id=' . $template['id']
            . '" class="btn">Update</a>';
    }

    public static function getAdminDeleteAppButton($template)
    {
        return '<a href="' . \pm_Context::getActionUrl('admin', 'delete')
            . '" data-id="'  . $template['id']
            . '" data-name="'  . $template['name']
            . '" class="btn btn_delete">Delete</a>';
    }

    /**
     * @return array
     */
    public function getUserDomains()
    {
        return $this->kdCommon->getUserDomains($this->getUser());
    }

    /**
     * @return mixed
     */
    public function getUserMainDomain()
    {
        list($domain, $directory) = $this->kdCommon->getUserMainDomain($this->getUser());
        return $domain;
    }
}