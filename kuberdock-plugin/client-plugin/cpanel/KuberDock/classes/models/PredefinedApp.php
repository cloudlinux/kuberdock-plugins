<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */


class PredefinedApp {
    /**
     *
     */
    const TEMPLATE_REGEXP = '/\$(?<variable>\w+)\|default:(?<default>[[:alnum:]\s]+)\|(?<description>[[:alnum:]\s]+)\$/';

    /**
     * @var array
     */
    public $kuberProducts = array();
    /**
     * @var array
     */
    public $kuberKubes = array();
    /**
     * @var array
     */
    public $userKuberProduct = array();
    /**
     * @var int
     */
    public $billingClientId;

    /**
     * @var int
     */
    private $templateId;
    /**
     * Yaml template
     * @var string
     */
    private $template;
    /**
     * @var array
     */
    private $parsedTemplate = array();
    /**
     * @var array
     */
    private $variables = array();
    /**
     * KuberDock token
     * @var string
     */
    private $token;

    /**
     * @var WHMCSApi
     */
    private $api;
    /**
     * @var KcliCommand
     */
    private $userCommand;
    /**
     * @var KcliCommand
     */
    private $adminCommand;
    /**
     * @var array
     */
    private $_data = array(
        'containers' => array(),
    );

    /**
     *
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @param $name
     * @return ArrayObject|mixed
     */
    public function __get($name)
    {
        $methodName = 'get'.ucfirst($name);

        if(method_exists($this, $methodName)) {
            $rm = new ReflectionMethod($this, $methodName);
            return $rm->invoke($this);
        } elseif(isset($this->_data[$name])) {
            if(is_array($this->_data[$name])) {
                return new ArrayObject($this->_data[$name]);
            } else {
                return $this->_data[$name];
            }
        }
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        $methodName = 'set'.ucfirst($name);

        if(method_exists($this, $methodName)) {
            $rm = new ReflectionMethod($this, $methodName);
            return $rm->invoke($this, $value);
        } else {
            $this->_data[$name] = $value;
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    /**
     *
     */
    public function init()
    {
        $this->api = WHMCSApi::model();
        $this->userKuberProduct = $this->api->getUserKuberDockProduct();
        $this->kuberProducts = $this->api->getKuberDockProducts();
        $this->kuberKubes = $this->api->getKuberKubes();
        $this->billingClientId = $this->api->getWHMCSClientId();
        $this->setToken();

        list($username, $password) = $this->api->getAuthData();
        $this->userCommand = new KcliCommand($username, $password, $this->token);

        list($username, $password) = $this->api->getAdminAuthData();
        $this->adminCommand = new KcliCommand($username, $password);
        $this->adminCommand->setConfPath(KcliCommand::CONF_FILE);
    }

    /**
     *
     */
    public function setToken()
    {
        $data = $this->api->request(array(
            'clientid' => $this->billingClientId,
        ), 'getclientskuberproducts');

        if(isset($data['results']) && $data['results']) {
            $this->token = current($data['results'])['token'];
        }
    }

    /**
     * @param $id
     * @return array
     * @throws CException
     */
    public function getTemplate($id)
    {
        $this->templateId = $id;
        $template = $this->adminCommand->getYAMLTemplate($id);

        if(!$template) {
            throw new CException('Template not exists');
        }

        $this->template = Spyc::YAMLLoadString($template['template']);

        return $this->template;
    }

    /**
     * @param $template
     * @return array
     */
    public function getVariables($template)
    {
        $this->parsedTemplate = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($template));

        foreach($iterator as $row) {
            $path = array();
            foreach(range(0, $iterator->getDepth()) as $depth) {
                $path[] = $iterator->getSubIterator($depth)->key();
            }

            $variable = $this->parseTemplateString($row, join('.', $path));
            $this->variables = array_merge($this->variables, $variable);
        }

        return $this->variables;
    }

    /**
     * @param string $value
     * @param string $path
     * @return array
     */
    public function parseTemplateString($value, $path = '')
    {
        $data = array();

        if(preg_match_all(self::TEMPLATE_REGEXP, $value, $match)) {
            foreach($match['variable'] as $k => $row) {
                $default = $this->getDefault($match['default'][$k]);
                $data[$row] = array(
                    'replace' => $match[0][$k],
                    'default' => $default,
                    'description' => $this->getDescription($match['description'][$k]),
                    'path' => $path,
                );

                if($row == 'KUBE_TYPE') {
                    $data[$row]['data'] = $this->getKubeTypes(is_string($row));
                }
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @throws CException
     */
    public function createApp($data = array())
    {
        $template = $this->template;
        $ownerData = $this->api->getOwnerData();
        $billingLink = sprintf('<a href="%s" target="_blank">%s</a>', $ownerData['server'], $ownerData['server']);

        foreach($data as $k => $v) {
            if(isset($this->variables[$k])) {
                $this->setByPath($template, $this->variables[$k]['path'], $this->variables[$k]['replace'], $v);
            }
        }

        // Create order with kuberdock product
        if(!isset($this->userKuberProduct[$this->packageId])) {
            $data = $this->api->addOrder($this->billingClientId, $this->packageId);
            if($data['invoiceid'] > 0) {
                $invoice = $this->api->getInvoice($data['invoiceid']);
                if($invoice['status'] == 'Unpaid') {
                    throw new CException('You have no enough funds.
                                Please make payment in billing system at '.$billingLink);
                }
            }
            $this->api->acceptOrder($data['orderid']);

            $this->userKuberProduct = $this->api->getUserKuberDockProduct();
            list($username, $password) = $this->api->getAuthData($this->packageId);
            $this->command = new KcliCommand($username, $password);
        }

        if(stripos($this->userKuberProduct[$this->packageId]['server']['status'], 'Active') === false) {
            throw new CException('You already have pending product.
                        Please activate your product in billing system at '.$billingLink);
        }

        file_put_contents($this->getAppPath(), Spyc::YAMLDump($template));
        $this->userCommand->createPodFromYaml($this->getAppPath());
    }

    /**
     *
     */
    public function start()
    {
        $this->userCommand->startContainer($this->getPodName());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return isset($this->template['kuberdock']['name']) ?
            $this->template['kuberdock']['name'] : 'Undefined';
    }

    /**
     * @return string
     */
    public function getPodName()
    {
        return isset($this->template['metadata']['name']) ?
            $this->template['metadata']['name'] : 'Undefined';
    }

    /**
     * @return string
     */
    public function getPreDescription()
    {
        return isset($this->template['kuberdock']['preDescription']) ?
            $this->template['kuberdock']['preDescription'] : '';
    }

    /**
     * @return array
     */
    public function getUnits()
    {
        return array(
            'cpu' => Units::getCPUUnits(),
            'memory' => Units::getMemoryUnits(),
            'hdd' => Units::getHDDUnits(),
            'traffic' => Units::getTrafficUnits(),
        );
    }

    /**
     * @param $value
     * @return string
     */
    private function getDefault($value)
    {
        $value = strtolower($value);

        switch($value) {
            case 'autogen':
                return $this->generatePassword();
            default:
                return $value;
        }
    }

    /**
     * @param $value
     * @return mixed
     */
    private function getDescription($value)
    {
        return $value;
    }

    /**
     * @return string
     */
    private function generatePassword()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890@$#*%<>';
        $pass = array();

        for($i=0; $i<8; $i++) {
            $n = rand(0, strlen($alphabet)-1);
            $pass[] = $alphabet[$n];
        }

        return implode($pass);
    }

    /**
     * @param bool|false $nameAsKey
     * @return array
     */
    private function getKubeTypes($nameAsKey = false)
    {
        $data = array();

        foreach($this->kuberProducts as $product) {
            foreach($product['kubes'] as $kube) {
                $key = $nameAsKey ? 'kube_name' : 'kuber_kube_id';
                $data[$kube[$key]] = array(
                    'id' => $kube['kuber_kube_id'],
                    'product_id' => $product['id'],
                    'name' => sprintf('%s (%s)', $kube['kube_name'], $product['name']),
                );
            }
        }

        return $data;
    }

    /**
     * @param $data
     * @param $path
     * @param $replace
     * @param $value
     * @return mixed
     */
    private function setByPath(&$data, $path, $replace, $value)
    {
        $temp = &$data;

        foreach(explode('.', $path) as $key) {
            $temp = &$temp[$key];
        }

        $temp = str_replace($replace, $value, $temp);

        if(is_numeric($temp))
            $temp = (int) $temp;

        return $data;
    }

    /**
     * @return string
     */
    private function getAppPath()
    {
        $path = array('.kuberdock_pre_apps', 'kuberdock_'. $this->templateId);
        $appDir = getenv('HOME');

        foreach($path as $row) {
            $appDir .= DS . $row;

            if(!file_exists($appDir)) {
                mkdir($appDir);
            }
        }

        return $appDir. DS . 'app.yaml';
    }
} 