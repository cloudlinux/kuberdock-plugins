<?php

namespace Kuberdock\classes;

use Kuberdock\classes\exceptions\CException;

/**
 * Class KcliCommand
 */
class KcliCommand extends Command {
    /**
     * Global config file path
     */
    const GLOBAL_CONF_FILE = '/etc/kubecli.conf';
    /**
     * Command path
     */
    const COMMAND_PATH = '/usr/bin/kcli';
    /**
     * KCLI pod templates directory
     */
    const KCLI_POD_DIR = '.kube_containers';

    /**
     * @var string
     */
    protected $returnType;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $password;
    /**
     * @var string
     */
    private $token;

    /**
     * @param string $username
     * @param string $password
     * @param string $token
     */
    public function __construct($username = '', $password = '', $token = '')
    {
        $this->commandPath = self::COMMAND_PATH;
        $this->username = $username;
        $this->password = $password;
        $this->token = $token;
        $this->returnType = '--'.self::DATA_TYPE_JSON;

        $this->confPath = $this->getUserConfigPath();
    }

    /**
     * @return array
     */
    protected function getAuth()
    {
        if($this->confPath) {
            return array(
                '-c' => $this->confPath,
            );
        }

        if($this->token) {
            return array(
                '--token' => sprintf("'%s'", $this->token),
            );
        } else {
            return array(
                '--user' => $this->username,
                '--password' => sprintf("'%s'", $this->password),
            );
        }
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return array
     */
    public function getPods()
    {
        try {
            return $this->execute(array(
                $this->returnType,
                'kubectl',
                'get',
                'pods',
            ));
        } catch(CException $e) {
            echo $e->getMessage();
            return array();
        }
    }

    /**
     * @param string $podName
     * @return array
     */
    public function getPod($podName)
    {
        return $this->execute(array(
            $this->returnType,
            'kubectl',
            'get',
            'pods' => sprintf("'%s'", $podName),
        ));
    }

    /**
     * @param string $podName
     * @return array
     */
    public function describePod($podName)
    {
        return $this->execute(array(
            $this->returnType,
            'kubectl',
            'describe',
            'pods' => sprintf("'%s'", $podName),
        ));
    }

    /**
     * @param string $podName
     * @return array
     */
    public function deletePod($podName)
    {
        return $this->execute(array(
            $this->returnType,
            'kubectl',
            'delete' => sprintf("'%s'", $podName),
        ));
    }

    /**
     * @return array
     */
    public function getKubes()
    {
        $kubes = $this->execute(array(
            $this->returnType,
            'kuberdock',
            'kube-types',
        ));

        foreach($kubes as $k => $row) {
            $kubes[$row['id']] = $row;
            unset($kubes[$k]);
        }

        return $kubes;
    }

    /**
     * @return array
     */
    public function getContainers()
    {
        return $this->execute(array(
            $this->returnType,
            'container',
            'list',
        ));
    }

    /**
     * @param string $name
     * @param string $image
     * @param string $kubeName
     * @param int $kubes
     * @return array
     */
    public function createContainer($name, $image, $kubeName, $kubes)
    {
        $values = array(
            $this->returnType,
            'kuberdock',
            'create' => sprintf("'%s'", $name),
            '-C' => $image,
            '--kube-type' => sprintf("'%s'", $kubeName),
            '--kubes' => $kubes,
        );

        $this->execute($values);
        return $values;
    }

    /**
     * @param string $name string
     * @param string $image
     * @param array $values [isPublic => '', containerPort => '', hostPort => '', protocol => ''], [...]
     * @param int $kubes
     * @return array
     */
    public function setContainerPorts($name, $image, $values, $kubes)
    {
        $ports = array_map(function($e) {
            $port = '';
            $port .= isset($e['isPublic']) && (bool) $e['isPublic'] ? '+' : '';
            $port .= $e['containerPort'];
            $port .= isset($e['hostPort']) ? ':'.$e['hostPort'] : '';
            $port .= isset($e['protocol']) && in_array($e['protocol'], array('tcp', 'udp'))
                ? ':'.$e['protocol'] : ':tcp';
            return $port;
        }, $values);

        return $this->execute(array(
            $this->returnType,
            'kuberdock',
            'set' => sprintf("'%s'", $name),
            '-C' => $image,
            '--container-port' => $ports ? implode(',', $ports) : "''",
            '--kubes' => $kubes,
        ));
    }

    /**
     * @param string $name
     * @param string $image
     * @param array $values  [env_name => '', env_value => ''], [...]
     * @param int $kubes
     * @return array
     */
    public function setContainerEnvVars($name, $image, $values, $kubes)
    {
        $env = array_map(function($e) {
            if(isset($e['name']) && isset($e['value'])) {
                return sprintf("%s:%s", $e['name'], $e['value']);
            }
        }, $values);

        return $this->execute(array(
            $this->returnType,
            'kuberdock',
            'set' => sprintf("'%s'", $name),
            '-C' => $image,
            '--env' => sprintf("'%s'", implode(',', $env)),
            '--kubes' => $kubes,
        ));
    }

    /**
     * @param string $name
     * @param string $image
     * @param int $index
     * @param array $params
     * @param int $kubes
     * @return array
     * @throws CException
     */
    public function setMountPath($name, $image, $index, $params, $kubes)
    {
        if(empty($params['mountPath'])) {
            throw new CException('Mount path is empty.');
        }

        $values = array(
            $this->returnType,
            'kuberdock',
            'set' => sprintf("'%s'", $name),
            '-C' => $image,
            '--mount-path' => $params['mountPath'],
            '--index' => $index,
            '--kubes' => $kubes,
        );

        if(isset($params['name']) && isset($params['size']) && $params['name'] && $params['size']) {
            if(!$this->findPersistentDriveByName($params['name'])) {
                $this->addPersistentDrive($params['name'], $params['size']);
            }

            $values = array_merge($values, array(
                '-p' => $params['name'],
                '-s' => $params['size'],
            ));
        }

        return $this->execute($values);
    }

    /**
     * @param $name
     * @return array
     */
    public function saveContainer($name)
    {
        return $this->execute(array(
            $this->returnType,
            'kuberdock',
            'save' => sprintf("'%s'", $name),
        ));
    }

    /**
     * @param $name
     * @return array
     */
    public function startContainer($name)
    {
        return $this->execute(array(
            $this->returnType,
            'kuberdock',
            'start' => sprintf('"%s"', $name),
        ));
    }

    /**
     * @param $name
     * @return array
     */
    public function stopContainer($name)
    {
        return $this->execute(array(
            $this->returnType,
            'kuberdock',
            'stop' => sprintf('"%s"', $name),
        ));
    }

    /**
     * @param $name
     * @return array
     */
    public function deleteContainer($name)
    {
        return $this->execute(array(
            $this->returnType,
            'kuberdock',
            'delete' => sprintf("'%s'", $name),
        ));
    }

    /**
     * @param string $image
     * @param int $page
     * @return array
     */
    public function searchImages($image, $page = 1)
    {
        if(empty($image)) {
            return array();
        }

        if($page < 1) {
            $page = 1;
        }

        return $this->execute(array(
            $this->returnType,
            'kuberdock',
            'search' => $image,
            '-p' => $page,
        ));
    }

    /**
     * @param string $image
     * @return bool|array
     */
    public function getImageData($image)
    {
        $data = $this->searchImages($image);

        foreach($data as $row) {
            if($row['name'] == $image) {
                return $row;
            }
        }

        return false;
    }

    /**
     * @param $image
     * @return array
     */
    public function getImage($image)
    {
        return $this->execute(array(
            $this->returnType,
            'kuberdock',
            'image_info' => sprintf('"%s"', $image),
        ));
    }

    /**
     * @return string
     */
    public function getRegistryUrl()
    {
        $conf = self::getConfig();

        return strpos($conf['registry'], 'http') !== false ?
            $conf['registry'] : sprintf('http://%s', $conf['registry']);
    }

    /**
     * @return string
     */
    public function getKuberDockUrl()
    {
        $conf = self::getConfig();

        return strpos($conf['url'], 'http') !== false ?
            $conf['url'] : sprintf('http://%s', $conf['url']);
    }

    /**
     * @param string $name
     * @param float $size
     * @return array
     */
    public function addPersistentDrive($name, $size)
    {
        return $this->execute(array(
            $this->returnType,
            'kuberdock',
            'drives',
            'add' => $name,
            '--size' => $size,
        ));
    }

    /**
     * @param string $name
     * @return array
     */
    public function deletePersistentDrive($name)
    {
        return $this->execute(array(
            $this->returnType,
            'kuberdock',
            'drives',
            'delete' => $name,
        ));
    }

    /**
     * @return array
     */
    public function getPersistentDrives()
    {
        try {
            return $this->execute(array(
                $this->returnType,
                'kuberdock',
                'drives',
                'list',
            ));
        } catch(CException $e) {
            return array();
        }
    }

    /**
     * @return array
     */
    public function getYAMLTemplate($id)
    {
        return $this->execute(array(
            $this->returnType,
            'kubectl',
            'get',
            'template',
            '--id' => $id,
        ));
    }

    /**
     * @return array
     */
    public function getYAMLTemplates()
    {
        return $this->execute(array(
            $this->returnType,
            'kubectl',
            'get',
            'templates',
            '--origin' => 'cpanel',
        ));
    }

    /**
     * @param string $filePath
     * @return array
     */
    public function createPodFromYaml($filePath)
    {
        return $this->execute(array(
            $this->returnType,
            'kubectl',
            'create',
            'pod',
            '--file' => $filePath,
        ));
    }

    /**
     * Get user config file data
     * @param bool $global
     * @return array
     */
    static public function getConfig($global = false)
    {
        $path = self::getUserConfigPath($global);
        $data = array();
        $fp = fopen($path, 'r');

        while($line = fgets($fp)) {
            if(in_array(substr($line, 0, 1), array('#', '/'))) continue;

            if(preg_match('/^(.*)=(.*)$/', $line, $match)) {
                $data[trim($match[1])] = trim($match[2]);
            }
        }

        fclose($fp);

        return $data;
    }

    /**
     * @throws CException
     */
    public function setConfig()
    {
        $globalConfig = self::getConfig(true);
        $config = self::getConfig();

        $newConfig = array(
            'global' => array(
                'url' => $globalConfig['url'],
            ),
            'defaults' => array(
                'registry' => $config['registry'],
            ),
        );

        if ($this->token) {
            $newConfig['defaults']['token'] = $this->token;
        }

        $data = array();
        array_walk($newConfig, function($row, $section) use (&$data) {
            if(is_array($row)) {
                $data[] = sprintf('[%s]', $section);
                array_walk($row, function($value, $attr) use (&$data) {
                    $data[] = sprintf('%s = %s', $attr, $value);
                });
            }
        });

        $fileManager = Base::model()->getStaticPanel()->getFileManager();
        $fileManager->putFileContent(self::getUserConfigPath(), implode("\n", $data));
        $fileManager->chmod(self::getUserConfigPath(), 0660);
    }

    /**
     * @param bool $global
     * @return string
     * @throws CException
     */
    static private function  getUserConfigPath($global = false)
    {
        if(!file_exists(self::GLOBAL_CONF_FILE)) {
            throw new CException('Global config file not found');
        }

        if($global) {
            return self::GLOBAL_CONF_FILE;
        }

        $panel = Base::model()->getStaticPanel();
        $templatesPath = $panel->getHomeDir() . DS . self::KCLI_POD_DIR;

        if (!is_dir($templatesPath)) {
            $panel->getFileManager()->mkdir($templatesPath, 0770);
        }

        $path = $panel->getHomeDir() . DS . '.kubecli.conf';

        if (!file_exists($path)) {
            $panel->getFileManager()->copy(self::GLOBAL_CONF_FILE, $path);
            $panel->getFileManager()->chmod($path, 0660);
        }

        return $path;
    }

    /**
     * @param string $name
     * @return mixed bool|array
     */
    private function findPersistentDriveByName($name)
    {
        $drives = $this->getPersistentDrives();

        foreach($drives as $row) {
            if($row['name'] == $name) {
                return $row;
            }
        }

        return false;
    }
}