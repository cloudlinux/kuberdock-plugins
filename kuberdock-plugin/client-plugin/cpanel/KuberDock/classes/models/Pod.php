<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */


class Pod {
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
    private $command;
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
        $this->command = new KcliCommand($username, $password, $this->token);
    }

    /**
     * @param $values
     * @return array
     */
    public function parsePorts($values)
    {
        $values = is_array($values) ? $values : array();

        $ports = array();
        $attributes = array('containerPort', 'hostPort', 'protocol', 'isPublic');

        foreach($values as $row) {
            $port = array(
                'isPublic' => false,
                'protocol' => 'tcp',
            );

            foreach($attributes as $attr) {
                if(isset($row[$attr])) {
                    $port[$attr] = $row[$attr];
                } else {
                    if($attr == 'hostPort' && isset($row['containerPort'])) {
                        $port[$attr] = $row['containerPort'];
                    }
                }
            }

            if(isset($row['number'])) {
                $port['containerPort'] = $row['number'];
                $port['hostPort'] = $row['number'];
            }

            $ports[] = $port;
        }

        return $ports;
    }

    /**
     * @param $values
     * @return mixed
     */
    public function parseEnv($values)
    {
        $values = is_array($values) ? $values : array();

        return $values;
    }

    /**
     * @param $values
     * @return array
     */
    public function parseVolumeMounts($values)
    {
        $values = is_array($values) ? $values : array();
        $volumes = array();
        $attributes = array('mountPath', 'size', 'persistent', 'name');

        foreach($values as $row) {
            $volume = array();

            if(!is_array($row)) {
                $volume['mountPath'] = $row;
            }

            foreach($attributes as $attr) {
                if(isset($row[$attr])) {
                    $volume[$attr] = $row[$attr];
                }
            }

            $volumes[] = $volume;
        }

        return $volumes;
    }

    /**
     *
     */
    public function setToken()
    {
        //$data = $this->api->searchClientKuberProducts($this->billingClientId);
        //$service = current($data);
        //return $service['token'];

        $data = $this->api->request(array(
            'clientid' => $this->billingClientId,
        ), 'getclientskuberproducts');

        if(isset($data['results']) && $data['results']) {
            $this->token = current($data['results'])['token'];
        }
    }

    /**
     * @param $values
     * @return $this
     */
    public function setContainers($values)
    {
        /*if(isset($values['image'])) {
            $ci = $this->getContainerIndexByImage($values['image']);
            $this->addContainer($ci, $values);
        } else {*/
            $this->addContainer(0, $values);
        /*}*/

        return $this;
    }

    /**
     * @param $value
     * @return $this
     * @throws CException
     */
    public function setKube_type($value)
    {
        if(is_numeric($value)) {
            $this->kubeTypeId = $value;
            foreach($this->kuberProducts as $row) {
                foreach($row['kubes'] as $kube) {
                    if($kube['kuber_kube_id'] == $value && $this->packageId == $row['id']) {
                        $this->kube_type = $kube['kube_name'];
                        break;
                    }
                }
            }

            if(!$this->kube_type) {
                throw new CException('Cannot get WHMCS product');
            }
        } else {
            $this->kube_type = $value;
        }

        return $this;
    }

    /**
     * @return WHMCSApi
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @return KcliCommand
     */
    public function getCommand()
    {
        return $this->command;
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
     * @param string $image
     * @return mixed
     */
    public function getImageInfo($image)
    {
        $imageInfo = $this->command->getImage($image);

        foreach($imageInfo as $k => $row) {
            $methodName = 'parse'.ucfirst($k);
            if(method_exists($this, $methodName)) {
                $rm = new ReflectionMethod($this, $methodName);
                $imageData[$k] = $rm->invokeArgs($this, array($row));
            } else {
                $imageData[$k] = $row;
            }
        }

        return $imageData;
    }

    /**
     * @param string $image
     * @return string
     */
    public function getImageUrl($image)
    {
        $data = $this->command->getImageData($image);

        return sprintf('%s/%s/%s', $this->command->getRegistryUrl(),
            isset($data['is_official']) && $data['is_official'] ? '_' : 'u', $image);
    }

    /**
     * @return array Pods
     */
    public function getPods()
    {
        $data = array();
        $pods = $this->command->getPods();

        foreach($pods as $pod) {
            $pod = $this->loadByName($pod['name']);
            $pod = clone($pod);
            $pod->containers[0]['ports'] = $this->parsePorts($pod->containers[0]['ports']);
            /*$container['env'] = $this->parseEnv($container['env']);
            $container['volumeMounts'] = $this->parseVolumeMounts($container['volumeMounts']);*/
            $data[] = $pod;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getProduct()
    {
        return current($this->userKuberProduct);
    }

    /**
     * @return array
     */
    public function getKubeType()
    {
        $product = $this->getProduct();
        return $this->kuberKubes[$product['id']]['kubes'][$this->kube_type];
    }

    /**
     * @return string
     */
    public function getTotalPrice()
    {
        $product = $this->getProduct();
        $kube = $this->getKubeType();

        return $product['currency']['prefix'] . ($kube['kube_price'] * $this->getKubeCount())
            . $product['currency']['suffix']
            . ' / ' . str_replace('ly', '', $product['paymentType']);
    }

    /**
     * @return int
     */
    public function getKubeCount()
    {
        $count = 0;
        foreach($this->containers as $row) {
            $count =+ $row['kubes'];
        }

        return $count;
    }

    /**
     * @return string
     */
    public function getKuberDockUrl()
    {
        $product = $this->getProduct();

        return isset($product['server']['serverip']) ?
            'https://'. $product['server']['serverip'] : $product['serverFullUrl'];
    }

    /**
     * @param bool $withToken
     * @return string
     */
    public function getPodUrl($withToken = false)
    {
        if($withToken) {
            return sprintf('%s/login?token=%s&next=/#pods/%s', $this->getKuberDockUrl(), $this->token, $this->id);
        } else {
            return sprintf('%s/#pods/%s', $this->getKuberDockUrl(), $this->id);
        }
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $image
     * @return $this
     * @throws CException
     */
    public function loadByImage($image)
    {
        if(empty($image)) {
            throw new CException('Fill image name');
        }

        $imageInfo = $this->getImageInfo($image);
        $ci = $this->getContainerIndexByImage($image);
        $this->addContainer($ci, $imageInfo);

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function loadByName($name)
    {
        $details = $this->command->describePod($name);
        $this->_data = $details;

        return $this;
    }

    /**
     * @return $this
     * @throws CException
     */
    public function create()
    {
        $ownerData = $this->api->getOwnerData();
        $billingLink = sprintf('<a href="%s" target="_blank">%s</a>', $ownerData['server'], $ownerData['server']);

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

        return $this;
    }

    /**
     *
     */
    public function save()
    {
        // Currently work only with 1st container
        $container = current($this->containers);

        $podValues = $this->command->createContainer($this->name, $container['image'], $this->kube_type, $container['kubes']);
        $container['ports'] = $this->parsePorts($container['ports']);
        $container['env'] = $this->parseEnv($container['env']);
        $container['volumeMounts'] = $this->parseVolumeMounts($container['volumeMounts']);

        $this->command->setContainerPorts($this->name, $container['image'], $container['ports']);
        $this->command->setContainerEnvVars($this->name, $container['image'], $container['env']);

        foreach($container['volumeMounts'] as $index => $data) {
            $this->command->setMountPath($this->name, $container['image'], $index, $data);
        }

        $this->command->saveContainer($this->name);
    }

    /**
     *
     */
    public function start()
    {
        $this->command->startContainer($this->name);
    }

    /**
     *
     */
    public function stop()
    {
        $this->command->stopContainer($this->name);
    }

    /**
     *
     */
    public function delete()
    {
        $this->command->deleteContainer($this->name);
    }

    /**
     * @param string $image
     * @param int $page
     * @return array
     */
    public function searchImages($image, $page = 1)
    {
        return $this->command->searchImages($image, $page-1);
    }

    /**
     * @return array
     */
    public function getPersistentDrives()
    {
        return $this->command->getPersistentDrives();
    }

    /**
     * @param string $volumeName
     * @return array
     */
    public function getPersistentStorageByName($volumeName)
    {
        foreach($this->volumes as $volume) {
            if(isset($volume['name']) && $volume['name'] == $volumeName) {
                return $volume;
            }
        }

        return array();
    }

    /**
     * @param int $index
     * @param array $values
     */
    private function addContainer($index, $values)
    {
        if($index < 0) {
            $this->_data['containers'][] = $values;
        } else {
            $this->_data['containers'][$index] = $values;
        }
    }

    /**
     * @param $image
     * @return int|string
     */
    private function getContainerIndexByImage($image)
    {
        foreach($this->containers as $k => $container) {
            if(!isset($container['image'])) continue;

            if($container['image'] == $image) {
                return $k;
            }
        }

        return -1;
    }
} 