<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

namespace Kuberdock\classes\models;

use Kuberdock\classes\exceptions\ApiException;
use Kuberdock\classes\exceptions\WithoutBillingException;
use Kuberdock\classes\panels\KuberDock_cPanel;
use Kuberdock\classes\Base;
use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\KcliCommand;
use Kuberdock\classes\components\Proxy;
use Kuberdock\classes\exceptions\PaymentRequiredException;

class Pod {
    /**
     * @var KuberDock_cPanel
     */
    private $panel;
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
     * @return \ArrayObject|mixed
     */
    public function __get($name)
    {
        $methodName = 'get'.ucfirst($name);

        if(method_exists($this, $methodName)) {
            $rm = new \ReflectionMethod($this, $methodName);
            return $rm->invoke($this);
        } elseif(isset($this->_data[$name])) {
            if(is_array($this->_data[$name])) {
                return new \ArrayObject($this->_data[$name]);
            } else {
                return $this->_data[$name];
            }
        }
    }

    /**
     * @param $name
     * @param $value
     * @return void|mixed
     */
    public function __set($name, $value)
    {
        $methodName = 'set'.ucfirst($name);

        if(method_exists($this, $methodName)) {
            $rm = new \ReflectionMethod($this, $methodName);
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
        $this->panel = Base::model()->getPanel();
    }

    /**
     * @return string
     */
    public function asJSON()
    {
        return json_encode($this->_data);
    }

    /**
     * @return array
     */
    public function asArray()
    {
        return $this->_data;
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
     * @return array
     */
    public function getVolumeMounts() {
        $volumes = array();

        foreach($this->containers as $k => $row) {
            foreach($row['volumeMounts'] as $volume) {
                $volumes[$k] = $volume;
            }
        }

        return $volumes;
    }

    /**
     * @return array
     */
    public function getPorts()
    {
        $ports = array();

        foreach($this->containers as $k => $row) {
            if(!isset($row['ports'])) continue;

            foreach($row['ports'] as $p) {
                if(isset($p['hostPort'])) {
                    $ports[] = $p['hostPort'];
                }
            }
        }

        return $ports;
    }

    /**
     * @param $values
     * @return $this
     */
    public function setContainers($values)
    {
        $this->addContainer(0, $values);

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
            $package = $this->panel->billing->getPackage() ?
                $this->panel->billing->getPackage(): $this->panel->billing->getPackageById($this->packageId);
            foreach($package['kubes'] as $kube) {
                if($kube['id'] == $value) {
                    $this->kube_type = $kube['name'];
                    break;
                }
            }

            if(!$this->kube_type) {
                throw new CException('Cannot get kube type');
            }
        } else {
            $this->kube_type = $value;
        }

        return $this;
    }

    /**
     * @return KuberDock_CPanel
     */
    public function getPanel()
    {
        return $this->panel;
    }

    /**
     * @return KcliCommand
     */
    public function getCommand()
    {
        return $this->panel->getCommand();
    }

    /**
     * @param string $image
     * @return mixed
     */
    public function getImageInfo($image)
    {
        $panel = Base::model()->getPanel();
        $imageData = array();

        if ($panel->isUserExists()) {
            $imageInfo = $this->command->getImage($image);
        } else {
            $imageInfo = $panel->getAdminApi()->getImage($image);
        }

        foreach($imageInfo as $k => $row) {
            $methodName = 'parse'.ucfirst($k);
            if(method_exists($this, $methodName)) {
                $rm = new \ReflectionMethod($this, $methodName);
                $imageData[$k] = $rm->invokeArgs($this, array($row));
            } else {
                $imageData[$k] = $row;
            }
        }

        return $imageData;
    }

    /**
     * @return Pod[] Pods
     */
    public function getPods()
    {
        $data = array();

        if (!Base::model()->getPanel()->isUserExists()) {
            return $data;
        }

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
     * @return \ArrayObject|mixed|string
     */
    public function getPublicIp()
    {
        if(isset($this->public_ip) && $this->public_ip) {
            return $this->public_ip;
        } elseif(isset($this->labels['kuberdock-public-ip'])) {
            return $this->labels['kuberdock-public-ip'];
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getKuberDockUrl()
    {
        return Base::model()->getPanel()->getCommand()->getKuberDockUrl();
    }

    /**
     * @param bool $withToken
     * @return string
     */
    public function getPodUrl($withToken = false)
    {
        if ($withToken) {
            $token2 = Base::model()->getPanel()->getApi()->requestToken2();
            return sprintf('%s/?token2=%s#pods/%s', $this->getKuberDockUrl(), $token2, $this->id);
        } else {
            return sprintf('%s/#pods/%s', $this->getKuberDockUrl(), $this->id);
        }
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
        $this->addContainer(0, $imageInfo);

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function loadByName($name)
    {
        $details = $this->command->describePod($name);
        $this->_data = $details;

        if (isset($details['template_id']) && $details['template_id']) {
            $app = new PredefinedApp($details['template_id']);
            $this->_data['postDescription'] = $app->getPostInstallPostDescription($details);
        }

        return $this;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function loadById($id)
    {
        $details = $this->panel->getApi()->getPod($id);
        $this->_data = $details;

        return $this;
    }

    /**
     * @return $this
     * @throws CException
     */
    public function createProduct()
    {
        // Create order with kuberdock product
        $panel = Base::model()->getPanel();

        if ($panel->isUserExists()) {
            return $this;
        }

        if (!$panel->isNoBilling()) {
            $panel->getAdminApi()->orderProduct($panel->user, $panel->domain, $this->packageId);
        } else {
            $product = $panel->billing->getPackageById($this->packageId);
            $panel->createUser($product['name']);
        }

        return $this;
    }

    /**
     * @param string $referer
     * @return array
     * @throws PaymentRequiredException
     */
    public function order($referer = '')
    {
        $response = Base::model()->getPanel()->getApi()->orderPod($this->_data, $referer);

        if($response['status'] == 'Unpaid') {
            throw new PaymentRequiredException($response);
        } else {
            $this->status = 'pending';
        }

        return $response;
    }

    /**
     * @param array $params
     * @param string $referer
     * @return array
     * @throws PaymentRequiredException
     */
    public function orderKubes($params, $referer = '')
    {
        $response = Base::model()->getPanel()->getApi()->orderKubes($params, $referer);

        if($response['status'] == 'Unpaid') {
            throw new PaymentRequiredException($response);
        }

        return $response;
    }

    /**
     * @param string $podId
     * @param string $plan
     * @param string $referer
     * @return array
     * @throws PaymentRequiredException
     */
    public function orderSwitchPlan($podId, $plan, $referer = '')
    {
        $response = Base::model()->getPanel()->getApi()->orderSwitchPlan($podId, $plan, $referer);

        if ($response['status'] == 'Unpaid') {
            throw new PaymentRequiredException($response);
        }

        return $response;
    }

    /**
     * @param array $params
     * @param string $referer
     * @return array
     * @throws PaymentRequiredException
     */
    public function orderEdit($params, $referer)
    {
        $response = Base::model()->getPanel()->getApi()->orderEdit($params, $referer);

        if ($response['status'] == 'Unpaid') {
            throw new PaymentRequiredException($response);
        }

        return $response;
    }

    /**
     *
     */
    public function save()
    {
        // Currently work only with 1st container
        $container = current($this->containers);

        $this->command->createContainer($this->name, $container['image'], $this->kube_type, $container['kubes']);
        $this->command->setContainerPorts($this->name, $container['image'], $container['ports'], $container['kubes']);
        $this->command->setContainerEnvVars($this->name, $container['image'], $container['env'], $container['kubes']);

        foreach($container['volumeMounts'] as $index => $data) {
            $this->command->setMountPath($this->name, $container['image'], $index, $data, $container['kubes']);
        }

        $this->command->saveContainer($this->name);
    }

    /**
     *
     */
    public function start()
    {
        if ($this->isUnPaid()) {
            $this->order();
            $message = 'Application started';
        } elseif (in_array($this->status, array('stopped', 'terminated', 'failed', 'succeeded'))) {
            $this->command->startContainer($this->name);
            $message = 'Application started';
        } else {
            $message = 'Application is already running';
        }

        return $message;
    }

    /**
     *
     */
    public function stop()
    {
        if (in_array($this->status, array('running', 'pending'))) {
            $this->command->stopContainer($this->name);

            if ($this->template_id) {
                $proxy = new Proxy();
                $proxy->removeRuleFromPod($this);
            }
            $message = 'Application stopped';
        } else {
            $message = 'Application is already stopped';
        }

        return $message;
    }

    /**
     *
     */
    public function delete()
    {
        if($this->template_id) {
            $proxy = new Proxy();
            $proxy->removeRuleFromPod($this);
        }

        $this->command->deleteContainer($this->name);
    }

    /**
     * @return string
     */
    public function edit()
    {
        return $this->getPodUrl(true);
    }

    /**
     * @param $data
     * @return string
     */
    public function redeploy($data)
    {
        $commandOptions = $data->commandOptions;
        Base::model()->getPanel()->getApi()->redeployPod($this->id, $commandOptions);

        return 'Application restarted';
    }

    /**
     * @param array $data
     * @return string
     * @throws CException
     * @throws WithoutBillingException
     * @throws PaymentRequiredException
     */
    public function upgrade($data)
    {
        $package = Base::model()->getPanel()->billing->getPackage();
        Base::model()->getPanel()->getApi()->editPod($data->id, $data->edited_config);

        if (Base::model()->getPanel()->billing->isFixedPrice($package['id'])) {
            $this->orderEdit($data, $this->getLink());
        } else {
            Base::model()->getPanel()->getAdminApi()->applyEdit($this->id);
        }

        return 'Application upgraded';
    }

    /**
     * @param array $data
     * @return string
     */
    public function changePlan($data)
    {
        $package = Base::model()->getPanel()->billing->getPackage();

        if (Base::model()->getPanel()->billing->isFixedPrice($package['id'])) {
            $this->orderSwitchPlan($data->id, $data->plan, $this->getLink());
        } else {
            Base::model()->getPanel()->getAdminApi()->switchPlan($data->id, $data->plan);
        }

        return 'Plan changed';
    }

    /**
     * @param string $command
     * @param array $data
     * @return mixed
     * @throws CException
     */
    public function processCommand($command, $data = array())
    {
        if (method_exists($this, $command)) {
            $rm = new \ReflectionMethod($this, $command);
            return $rm->invoke($this, $data);
        }

        throw new CException('Unknown command ' . $command);
    }

    /**
     * @param string $image
     * @param int $page
     * @return array
     */
    public function searchImages($image, $page = 1)
    {
        $panel = Base::model()->getPanel();

        if ($panel->isUserExists()) {
            return $this->command->searchImages($image, $page);
        } else {
            return $panel->getAdminApi()->getImages($image, $page);
        }
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
        if(!isset($this->volumes)) {
            return array();
        }

        foreach($this->volumes as $volume) {
            if(isset($volume['name']) && $volume['name'] == $volumeName) {
                return $volume;
            }
        }

        return array();
    }

    /**
     * @param string $name
     * @return array
     * @throws CException
     */
    public function getContainerByName($name)
    {
        foreach($this->containers as $container) {
            if($container['name'] == $name) {
                return $container;
            }
        }

        throw new CException('Can not find container by name in the template');
    }

    /**
     * @return bool
     */
    public function isUnPaid()
    {
        return $this->status == 'unpaid';
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return Base::model()->getPanel()->getURL() . '#pod/' . $this->name;
    }

    /**
     * @param $kubeCount
     * @throws ApiException
     */
    public function checkMaxKubes($kubeCount)
    {
        $sysapi = Base::model()->getPanel()->getAdminApi()->getSysApi('name');
        $maxKubes = $sysapi['max_kubes_per_container']['value'];

        if ($kubeCount > $maxKubes) {
            throw new ApiException('Only ' . $maxKubes . ' kubes allowed');
        }
    }

    /**
     * @param int $index
     * @param array $values
     */
    private function addContainer($index, $values)
    {
        $this->_data['containers'][$index] = $values;
    }
}
