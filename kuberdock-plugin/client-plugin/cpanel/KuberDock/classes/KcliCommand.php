<?php

class KcliCommand extends Command {
    /**
     * Global config file path
     */
    const CONF_FILE = '/etc/kubecli.conf';
    /**
     * Command path
     */
    const COMMAND_PATH = '/usr/bin/kcli';

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
    public function __construct($username, $password, $token = '')
    {
        $this->commandPath = self::COMMAND_PATH;
        $this->username = $username;
        $this->password = $password;
        $this->token = $token;
        $this->returnType = '--'.self::DATA_TYPE_JSON;
        $this->confPath = '';
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
                '--password' => $this->password,
            );
        }
    }

    /**
     * @return array
     */
    public function getPods()
    {
        return $this->execute(array(
            $this->returnType,
            'kubectl',
            'get',
            'pods',
        ));
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
            'kubes',
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
     * @param $name
     * @param $image
     * @param $kubeName
     * @param $kubeCount
     * @return array
     */
    public function createContainer($name, $image, $kubeName, $kubeCount)
    {
        $values = array(
            $this->returnType,
            'kuberdock',
            'create' => sprintf("'%s'", $name),
            '-C' => $image,
            '--kube-type' => sprintf("'%s'", $kubeName),
            '--kubes' => $kubeCount,
        );

        $this->execute($values);
        return $values;
    }

    /**
     * @param $name string
     * @param $image string
     * @param $values array [isPublic => '', containerPort => '', hostPort => '', protocol => ''], [...]
     * @return array
     */
    public function setContainerPorts($name, $image, $values)
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
        ));
    }

    /**
     * @param $name string
     * @param $image string
     * @param $values array [env_name => '', env_value => ''], [...]
     * @return array
     */
    public function setContainerEnvVars($name, $image, $values)
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
            '--env' => implode(',', $env),
        ));
    }

    /**
     * @param string $name
     * @param string $image
     * @param int $index
     * @param array $params
     * @return array
     * @throws CException
     */
    public function setMountPath($name, $image, $index, $params)
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
            '--read-only' => isset($params['readOnly']) ? (int) $params['readOnly'] : 0,
            '--index' => $index,
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
            'delete' => sprintf('"%s"', $name),
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
        $conf = self::getConfFile();

        return strpos($conf['registry'], 'http') !== false ?
            $conf['registry'] : sprintf('http://%s', $conf['registry']);
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
        return $this->execute(array(
            $this->returnType,
            'kuberdock',
            'drives',
            'list',
        ));
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
     * @param string $path
     * @return $this
     */
    public function setConfPath($path)
    {
        if(file_exists($path)) {
            $this->confPath = $path;
        }

        return $this;
    }

    /**
     * Get config file data. user if exists or global
     * @param bool $isAdmin
     * @return array
     */
    static public function getConfFile($isAdmin = false)
    {
        $userConf = getenv('HOME') .DS . '.kubecli.conf';

        if($isAdmin) {
            $userConf = self::CONF_FILE;
        }

        if(file_exists($userConf)) {
            $path = $userConf;
        } else {
            $path = self::CONF_FILE;
        }

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