<?php

class KcliCommand extends Command {
    const CONF_FILE = '/etc/kubecli.conf';

    /**
     * @var string
     */
    protected $returnType = self::DATA_TYPE_JSON;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $password;

    public function __construct($username, $password)
    {
        $this->commandPath = '/usr/bin/kcli';
        $this->username = $username;
        $this->password = $password;

        /*
         * List of available commands & their separators
         * parameter => separator
         *
         */
        $this->paramSeparators = array(
            'container' => array(
                'prefix' => '',
                'separator' => ' ',
            ),
            'image' => array(
                'prefix' => '',
            ),
            'list' => array(
                'prefix' => '',
            ),
            'search' => array(
                'separator' => ' ',
                'prefix' => '',
            ),
            'json',
            'user' => '',
            'password' => '',
             'p' => array(
                 'prefix' => '-',
             ),
            'delete' => array(
                'prefix' => '',
            ),
            'create' => array(
                'prefix' => '',
            ),
            'set' => array(
                'prefix' => '',
            ),
            'stop' => array(
                'prefix' => '',
            ),
            'start' => array(
                'prefix' => '',
            ),
            'save' => array(
                'prefix' => '',
            ),
            'run',
            'kuberdock' => array(
                'prefix' => '',
            ),
            'kubes' => array(
                'prefix' => '',
            ),
            'kube-type',
            '--image' => array(
                'prefix' => '',
            ),
            'kubectl' => array(
                'prefix' => '',
            ),
            'get' => array(
                'prefix' => '',
            ),
            'pods' => array(
                'prefix' => '',
                'separator' => ' ',
            ),
            '--kubes' => array(
                'prefix' => '',
            ),
            'describe' => array(
                'prefix' => '',
                'separator' => ' ',
            ),
            'index' => array(
                'separator' => ' ',
            ),
            'host-port' => array(
                'separator' => ' ',
            ),
            'container-port' => array(
                'separator' => ' ',
            ),
            'protocol' => array(
                'separator' => ' ',
            ),
            'public',
            'image_info' => array(
                'prefix' => '',
            ),
            'env',
            'mount-path',
        );
    }

    /**
     * @return array
     */
    public function getPods()
    {
        return $this->execute(array(
            'user' => $this->username,
            'password' => $this->password,
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
            'user' => $this->username,
            'password' => $this->password,
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
            'user' => $this->username,
            'password' => $this->password,
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
            'user' => $this->username,
            'password' => $this->password,
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
            'user' => $this->username,
            'password' => $this->password,
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
            'user' => $this->username,
            'password' => $this->password,
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
            'user' => $this->username,
            'password' => $this->password,
            $this->returnType,
            'kuberdock',
            'create' => sprintf("'%s'", $name),
            '--image' => $image,
            'kube-type' => sprintf("'%s'", $kubeName),
            '--kubes' => $kubeCount,
        );

        $this->execute($values);
        return $values;
    }

    /**
     * @param $values
     * @param $index
     * @param $params
     * @return array
     */
    public function setContainerPorts($values, $index, $params)
    {
        $values['index'] = $index;
        $attributes = array(
            'containerPort' => 'container-port',
            'hostPort' => 'host-port',
            'protocol' => 'protocol',
        );

        foreach($attributes as $containerAttr => $commandAttr) {
            if(isset($params[$containerAttr]) && $params[$containerAttr]) {
                $values[$commandAttr] = $params[$containerAttr];
            }
        }

        if(isset($params['isPublic']) && $params['isPublic']) {
            $values['public'] = '';
        }

        return $this->execute($values);
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
            'user' => $this->username,
            'password' => $this->password,
            $this->returnType,
            'kuberdock',
            'set' => sprintf("'%s'", $name),
            '--image' => $image,
            'env' => implode(',', $env),
        ));
    }

    /**
     * @param $values
     * @param $index
     * @param $params
     * @return array
     */
    public function setMountPath($values, $index, $params)
    {
        $values['index'] = $index;
        $attributes = array(
            'mountPath' => 'mount-path',
        );

        foreach($attributes as $containerAttr => $commandAttr) {
            if(isset($params[$containerAttr]) && $params[$containerAttr]) {
                $values[$commandAttr] = $params[$containerAttr];
            }
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
            'user' => $this->username,
            'password' => $this->password,
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
            'user' => $this->username,
            'password' => $this->password,
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
            'user' => $this->username,
            'password' => $this->password,
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
            'user' => $this->username,
            'password' => $this->password,
            $this->returnType,
            'kuberdock',
            'delete' => sprintf('"%s"', $name),
        ));
    }

    /**
     * @param $image
     * @param int $page
     * @return array
     */
    public function searchImages($image, $page = 0)
    {
        if($page < 0) {
            $page = 0;
        }

        return $this->execute(array(
            'user' => $this->username,
            'password' => $this->password,
            $this->returnType,
            'kuberdock',
            'search' => $image,
            'p' => $page,
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
            'user' => $this->username,
            'password' => $this->password,
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
        $conf = $this->getConfFile();

        return strpos($conf['registry'], 'http') !== false ?
            $conf['registry'] : sprintf('http://%s', $conf['registry']);
    }

    /**
     * @return array
     */
    private function getConfFile()
    {
        $data = array();
        $fp = fopen(self::CONF_FILE, 'r');

        while($line = fgets($fp)) {
            if(in_array(substr($line, 0, 1), array('#', '/'))) continue;

            if(preg_match('/^(.*)=(.*)$/', $line, $match)) {
                $data[trim($match[1])] = trim($match[2]);
            }
        }

        fclose($fp);

        return $data;
    }
}