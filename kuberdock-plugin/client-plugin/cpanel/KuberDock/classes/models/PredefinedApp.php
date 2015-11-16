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
     *
     */
    const VARIABLE_REGEXP = '/\$(?<variable>\w+)\$/';
    /**
     *
     */
    const SPECIAL_VARIABLE_REGEXP = '/\%(?<variable>\w+)\%/';

    /**
     * @var int
     */
    private $packageId;
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
     * @var KcliCommand
     */
    private $hostingCommand;
    /**
     * @var array
     */
    private $_data = array(
        'containers' => array(),
    );

    /**
     * @param int $templateId
     */
    public function __construct($templateId = null)
    {
        $this->templateId = $templateId;
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

        list($username, $password, $token) = $this->api->getAuthData();
        $this->userCommand = new KcliCommand($username, $password, $token);

        list($username, $password) = $this->api->getHostingAuthData();
        $this->hostingCommand = new KcliCommand($username, $password);

        list($username, $password, $token) = $this->api->getAdminAuthData();
        $this->adminCommand = new KcliCommand($username, $password, $token);

        // Create local conf without admin token
        if(!KcliCommand::isLocalConfigExist()) {
            $this->hostingCommand->getImage('nginx');
        }

        if($this->templateId) {
            $this->getTemplate($this->templateId);
        }
    }

    /**
     * @return WHMCSApi
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @param $packageId
     * @throws CException
     */
    public function setPackageId($packageId)
    {
        if(!isset($this->api->getKubes()[$packageId])) {
            throw new CException('Unknown package');
        }

        $this->packageId = $packageId;
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
     * @return array
     * @throws CException
     */
    public function getTemplates()
    {
        $data = array();
        $templates = $this->adminCommand->getYAMLTemplates();

        foreach($templates as &$row) {
            $row['template'] = Spyc::YAMLLoadString($row['template']);

            if($this->isPackageExists($row['template'])) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        $this->parsedTemplate = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($this->template));

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
                $data[$row] = array(
                    'replace' => $match[0][$k],
                    'default' => $this->getDefault($match['default'][$k]),
                    'type' => $this->getType($row, $match['default'][$k]),
                    'description' => $this->getDescription($match['description'][$k]),
                    'path' => $path,
                );

                if($row == 'KUBETYPE') {
                    $data[$row]['data'] = $this->getKubeTypes();
                }
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     * @throws CException
     */
    public function createApp($data = array())
    {
        $ownerData = $this->api->getOwnerData();
        $billingLink = sprintf('<a href="%s" target="_blank">%s</a>', $ownerData['server'], $ownerData['server']);
        $this->setVariables($data);

        // Create order with kuberdock product
        $product = $this->api->getProductById($this->packageId);
        if(!$this->api->getService()) {
            if($this->api->getUserCredit() < $product['firstDeposit']) {
                $currency = $this->api->getCurrency();
                $rest = abs($this->api->getUserCredit() - $product['firstDeposit']);
                throw new CException(sprintf('You have not enough funds for first deposit.
                    You need to make payment %.2f %s at %s', $rest, $currency['suffix'], $billingLink));
            }

            $data = $this->api->addOrder($this->api->getUserId(), $this->packageId);
            if($data['invoiceid'] > 0) {
                $invoice = $this->api->getInvoice($data['invoiceid']);
                if($invoice['status'] == 'Unpaid') {
                    throw new CException('You have no enough funds.
                                Please make payment in billing system at ' . $billingLink);
                }
            }
            $this->api->acceptOrder($data['orderid']);

            $this->api->setKuberDockInfo();
            list($username, $password, $token) = $this->api->getAuthData();
            $this->userCommand = new KcliCommand($username, $password, $token);
        }

        file_put_contents($this->getAppPath(), Spyc::YAMLDump($this->template));
        $response = $this->userCommand->createPodFromYaml($this->getAppPath());

        $this->setPostInstallVariables($response);
        file_put_contents($this->getAppPath(), Spyc::YAMLDump($this->template));

        return $response;
    }

    /**
     *
     */
    public function start()
    {
        $this->userCommand->startContainer($this->getPodName());
    }

    /**
     * @param $variable string
     * @param $controller KuberDock_Controller
     * @throws CException
     */
    public function renderVariable($variable, $controller)
    {
        $data = $this->variables[$variable];

        if(!in_array($data['type'], array('autogen', 'select', 'kube_count', 'input'))) {
            throw new CException('Undefined variable type: ' . $data['type']);
        }

        $controller->renderPartial('fields/' . $data['type'], array(
            'variable' => $variable,
            'data' => $data
        ));
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
     * @return string
     */
    public function getPostDescription()
    {
        return isset($this->template['kuberdock']['postDescription']) ?
            $this->template['kuberdock']['postDescription'] : 'Application started.';
    }

    /**
     * @param bool $fromBilling
     * @return string
     * @throws CException
     */
    public function getPackageId($fromBilling = false)
    {
        $defaults = $this->api->getDefaults();
        $defaultPackageId = isset($defaults['packageId']) ? $defaults['packageId'] : 0;

        $packageId = isset($this->template['kuberdock']['package_id']) ?
            $this->template['kuberdock']['package_id'] : $defaultPackageId;

        if(!$fromBilling) {
            return $packageId;
        }

        foreach($this->api->getProducts() as  $row) {
            if(!$row['kubes']) continue;

            if($row['kubes'][0]['kuber_product_id'] == $packageId) return $row['id'];
        }

        throw new CException('Cannot get KuberDock package');
    }

    /**
     * @param string $template
     * @return bool
     */
    public function isPackageExists($template)
    {
        if(!isset($template['kuberdock']['package_id'])) {
            return true;
        }

        $service = $this->api->getService();
        $templateProductId = $template['kuberdock']['package_id'];

        if($service && $templateProductId != $service['kuber_product_id'] ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return string
     */
    public function getKubeTypeId()
    {
        return isset($this->template['kuberdock']['kube_type']) ?
            $this->template['kuberdock']['kube_type'] : 0;
    }

    /**
     * @return int
     */
    public function getTotalKubes()
    {
        $total = 0;
        $containers = isset($this->template['spec']['template']['spec']['containers']) ?
            $this->template['spec']['template']['spec']['containers'] : $this->template['spec']['containers'];

        foreach($containers as $image) {
            if(isset($image['kubes'])) {
                $total += $image['kubes'];
            } else {
                $total++;
            }
        }

        return $total;
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
     *
     */
    public function getExistingPods()
    {
        try {
            $pods = $this->userCommand->getPods();
        } catch(CException $e) {
            return array();
        }

        $existingPods = array_filter($pods, function($e) {
            $pod = $this->userCommand->describePod($e['name']);
            //echo '<pre>';print_r($pod);
            if(isset($pod['template_id']) && $pod['template_id'] == $this->templateId) {
                return $pod;
            }
        });

        return $existingPods;
    }

    /**
     * @return int
     */
    public function getTemplateId()
    {
        return $this->templateId;
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
     * @param string $var
     * @param string|int $default
     * @return string
     */
    private function getType($var, $default)
    {
        if($default == 'autogen') {
            return $default;
        } elseif(stripos($var, 'KUBE_COUNT') !== false) {
            return 'kube_count';
        } elseif(stripos($var, 'KUBETYPE') !== false) {
            return 'select';
        } else {
            return 'input';
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
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890@$#*%';
        $pass = array();

        for($i=0; $i<8; $i++) {
            $n = rand(0, strlen($alphabet)-1);
            $pass[] = $alphabet[$n];
        }

        return implode($pass);
    }

    /**
     * @return array
     */
    private function getKubeTypes()
    {
        $data = array();
        $userProduct = $this->api->getProduct();

        foreach($this->api->getProducts() as $product) {
            foreach($product['kubes'] as $kube) {
                if(($userProduct && $userProduct['id'] == $product['id']) || !$userProduct) {
                    $data[] = array(
                        'id' => $kube['kuber_kube_id'],
                        'product_id' => $product['id'],
                        'name' => sprintf('%s (%s)', $kube['kube_name'], $product['name']),
                    );
                }
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

    /**
     * @param string $string
     * @param array $data
     * @param string $pattern
     * @return mixed
     */
    private function replaceTemplateVariable($string, $data, $pattern = self::VARIABLE_REGEXP)
    {
        $variables = $this->variables;
        return preg_replace_callback($pattern, function($matches) use ($variables, $data) {
            if(isset($variables[$matches['variable']]) && isset($data[$matches['variable']])) {
                return $data[$matches['variable']];
            } else {
                return $matches[0];
            }
        }, $string);
    }

    /**
     * @param array $data
     */
    private function setVariables($data)
    {
        array_walk_recursive($this->template, function(&$e) use ($data) {
            // bug with returned type of value (preg_replace_callback)
            if(preg_match(self::VARIABLE_REGEXP, $e)) {
                $e = $this->replaceTemplateVariable($e, $data);
            }
        });

        foreach($data as $k => $v) {
            if(isset($this->variables[$k])) {
                $this->variables[$k]['value'] = $v;
                $this->setByPath($this->template, $this->variables[$k]['path'], $this->variables[$k]['replace'], $v);
            }
        }
    }

    /**
     * @param array $data
     */
    private function setPostInstallVariables($data)
    {
        $variables = array();

        $publicIp = isset($data['public_ip']) ? $data['public_ip'] : '"IP address not set"';
        $variables['PUBLIC_ADDRESS'] = $publicIp;
        $this->variables['PUBLIC_ADDRESS'] = $publicIp;

        array_walk_recursive($this->template, function(&$e) use ($variables) {
            // bug with returned type of value (preg_replace_callback)
            if(preg_match(self::SPECIAL_VARIABLE_REGEXP, $e)) {
                $e = $this->replaceTemplateVariable($e, $variables, self::SPECIAL_VARIABLE_REGEXP);
            }
        });
    }
}