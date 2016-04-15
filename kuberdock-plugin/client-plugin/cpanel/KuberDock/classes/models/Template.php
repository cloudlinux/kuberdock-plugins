<?php

/**
 * Created by PhpStorm.
 * User: user
 * Date: 12/27/15
 * Time: 9:47 AM
 */
class Template
{
    /**
     * Parsed template
     * @var array
     */
    public $data = array();
    /**
     * @var KuberDock_cPanel
     */
    private $panel;
    /**
     * @var int
     */
    private $id;

    /**
     * @param KuberDock_cPanel $panel
     */
    public function __construct(KuberDock_cPanel $panel)
    {
        $this->panel = $panel;
    }

    /**
     * @param $id
     * @return array
     * @throws CException
     */
    public function getById($id)
    {
        $this->id = $id;
        $template = $this->panel->getAdminApi()->getTemplate($id);

        if(!$template) {
            throw new CException('Template not exists');
        }

        $this->data = Spyc::YAMLLoadString($template['template']);

        return $this->data;
    }

    /**
     * @param $id
     * @return array
     * @throws CException
     */
    public function getByPath($path, $id)
    {
        $this->id = $id;
        $template = file_get_contents($path);

        if(!$template) {
            throw new CException('Template not exists');
        }

        $this->data = Spyc::YAMLLoadString($template);

        return $this->data;
    }

    /**
     * @param $path
     * @return array|bool
     */
    public static function getTemplateByPath($path)
    {
        if(!file_exists($path)) {
            return false;
        }

        return Spyc::YAMLLoadString(file_get_contents($path));
    }

    /**
     * @return array
     * @throws CException
     */
    public function getAll()
    {
        $data = array();
        $templates = $this->panel->getAdminApi()->getTemplates('cpanel');

        foreach($templates as &$row) {
            $row['template'] = Spyc::YAMLLoadString($row['template']);

            if($this->isPackageExists($row['template']) && (!isset($row['kuberdock']) || !$row['kuberdock'])) {
                $data[] = $row;
            }
        }

        return $data;
    }


    /**
     * @return mixed
     * @throws CException
     */
    public function getKDSection()
    {
        if(isset($this->data['kuberdock']) && is_array($this->data['kuberdock'])) {
            return $this->data['kuberdock'];
        } else {
            throw new CException('"kuberdock" section not exist in the template');
        }
    }

    /**
     * @return bool
     */
    public function getPackageId()
    {
        $defaults = $this->panel->billing->getDefaults();
        $defaultPackageId = isset($defaults['packageId']) ? $defaults['packageId'] : 0;

        return isset($this->data['kuberdock']['packageID']) ?
            $this->data['kuberdock']['packageID'] : $defaultPackageId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return isset($this->data['kuberdock']['name']) ?
            $this->data['kuberdock']['name'] : 'Undefined';
    }

    /**
     * @return string
     */
    public function getPodName()
    {
        return isset($this->data['metadata']['name']) ?
            $this->data['metadata']['name'] : 'Undefined';
    }

    /**
     * @return string
     */
    public function getPreDescription()
    {
        $bbCode = new BBCode();

        return isset($this->data['kuberdock']['preDescription']) ?
            $bbCode->toHTML($this->data['kuberdock']['preDescription']) : '';
    }

    /**
     * @return string
     */
    public function getPostDescription()
    {
        return isset($this->data['kuberdock']['postDescription']) ?
            $this->data['kuberdock']['postDescription'] : 'Application started.';
    }

    /**
     * @return array
     */
    public function getProxy()
    {
        return isset($this->data['kuberdock']['proxy']) ?
            $this->data['kuberdock']['proxy'] : array();
    }

    /**
     * @return mixed
     */
    public function getContainers()
    {
        return isset($this->data['spec']['template']['spec']['containers']) ?
            $this->data['spec']['template']['spec']['containers'] : $this->data['spec']['containers'];
    }

    /**
     * @return mixed
     */
    public function getVolumes()
    {
        return isset($this->data['spec']['template']['spec']['volumes']) ?
            $this->data['spec']['template']['spec']['volumes'] : $this->data['spec']['volumes'];
    }

    /**
     * @param $planId
     * @return mixed
     * @throws CException
     */
    public function getKubeTypeId($planId)
    {
        $defaults = $this->panel->billing->getDefaults();
        $plan = $this->getPlan($planId);
        // TODO: Add few pod support
        $pod = $plan['pods'][0];

        return (int) preg_match('/default:(\d+)/', $pod['kubeType'] , $match) ? $match[1] :
            (isset($pod['kubeType']) ? $pod['kubeType'] : $defaults['kubeType']);
    }

    /**
     * @param null|int $planId
     * @return int
     */
    public function getTotalKubes($planId = null)
    {
        $total = 0;

        if(is_numeric($planId)) {
            // TODO: Add few pod support
            $containers = $this->getPlan($planId)['pods'][0]['containers'];
        } else {
            $containers = $this->getContainers();
        }

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
     * @return int
     */
    public function getPublicIP()
    {
        $containers = $this->getContainers();

        foreach($containers as $container) {
            if(isset($container['ports']) && is_array($container['ports'])) {
                foreach($container['ports'] as $port) {
                    if(isset($port['isPublic']) && (bool) $port['isPublic']) {
                        return 1;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * @return float|int
     */
    public function getPersistentStorageSize($planId)
    {
        $size = 0;
        $volumes = $this->getVolumes();
        // TODO: Add few pod support
        $pd = $this->getPlan($planId)['pods'][0]['persistentDisks'];

        foreach($volumes as $volume) {
            if(isset($volume['persistentDisk']) && isset($volume['persistentDisk']['pdSize'])) {
                $size += (int) $volume['persistentDisk']['pdSize'];
            }
        }

        foreach($pd as $row) {
            if(isset($row['pdSize'])) {
                $size += (int) $row['pdSize'];
            }
        }

        return $size;
    }

    /**
     * @return mixed
     */
    public function getPlans()
    {
        $kdSection = $this->getKDSection();
        if(isset($kdSection['appPackages']) && is_array($kdSection['appPackages'])) {
            return $kdSection['appPackages'];
        } else {
            return array();
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws CException
     */
    public function getPlan($id)
    {
        $plans = $this->getPlans();

        if(!isset($plans[$id])) {
            throw new CException(sprintf('Plan with key "%s" does not exist', $id));
        }

        return $plans[$id];
    }

    /**
     * @param $id
     * @return string
     * @throws CException
     */
    public function renderTotalByPlanId($id, $sum = false)
    {
        $plan = $this->getPlan($id);
        $totalPDSize = 0;
        $totalMemory = 0;
        $totalHDD = 0;
        $totalCPU = 0;
        $publicIp = (bool) isset($plan['publicIP']) ? $plan['publicIP'] : false;
        $defaults = $this->panel->billing->getDefaults();

        foreach($plan['pods'] as $pod) {
            $totalKubes = 0;
            $kubeType = preg_match('/default:(\d+)/', $pod['kubeType'], $match) ? $match[1] :
                (isset($pod['kubeType']) ? $pod['kubeType'] : $defaults['kubeType']);

            $kube = array_map(function($e) use ($kubeType) {
                foreach($e['kubes'] as $row) {
                    if($row['kuber_kube_id'] == $kubeType) {
                        return $row;
                    }
                }
            }, $this->panel->billing->getKubes());

            if(!$kube) {
                throw new CException(sprintf('KubeType %s is not available for your current package', $kubeType));
            }

            array_map(function($e) use (&$totalKubes) {
                $totalKubes += (int) preg_match('/default:(\d+)/', $e['kubes'], $match) ? $match[1] : $e['kubes'];
            }, $pod['containers']);

            array_map(function($e) use (&$totalPDSize) {
                $totalPDSize += (int) preg_match('/default:(\d+)/', $e['pdSize'], $match) ? $match[1] : $e['pdSize'];
            }, $pod['persistentDisks']);

            $kube = current($kube);
            $totalMemory += $totalKubes * $kube['memory_limit'];
            $totalCPU += $totalKubes * $kube['cpu_limit'];
            $totalHDD += $totalKubes * $kube['hdd_limit'];
        }

        if($sum) {
            $product = $this->panel->billing->getProductByKuberId($this->getPackageId());
            $currency = $this->panel->billing->getCurrency();
            $price = $totalKubes * $kube['kube_price']
                + $publicIp * (float) $product['priceIP']
                + $totalPDSize * (float) $product['pricePersistentStorage'];

            return $currency['prefix'] . number_format($price, 2) . '<wbr>'
                . '<span>'
                . $currency['suffix'] . '/' . str_replace('ly', '', $product['paymentType'])
                . '</span>';
        }

        $view = new KuberDock_View();
        return $view->renderPartial('app/plan_details', array(
            'totalMemory' => $totalMemory,
            'totalCPU' => $totalCPU,
            'totalHDD' => $totalHDD,
            'totalPDSize' => $totalPDSize,
            'publicIp' => $publicIp,
        ), false);
    }

    /**
     * @return bool
     */
    public function isPackageExists()
    {
        $templatePackageId = $this->getPackageId();

        if(!$templatePackageId) {
            return true;
        }

        $service = $this->panel->billing->getService();

        return !($service && $templatePackageId != $service['kuber_product_id']);
    }

    /**
     * @param $containers
     * @return $this
     */
    public function setContainers($containers)
    {
        if(isset($this->data['spec']['template']['spec']['containers'])) {
            $this->data['spec']['template']['spec']['containers'] = $containers;
        } else {
            $this->data['spec']['containers'] = $containers;
        }

        return $this;
    }

    /**
     * @param $volumes
     * @return $this
     */
    public function setVolumes($volumes)
    {
        if(isset($this->data['spec']['template']['spec']['volumes'])) {
            $this->data['spec']['template']['spec']['volumes'] = $volumes;
        } else {
            $this->data['spec']['volumes'] = $volumes;
        }

        return $this;
    }

    /**
     * @param int $id
     */
    public function setKubeType($id)
    {
        $this->data['kuberdock']['kube_type'] = $id;
    }

    /**
     * @param int|null $id
     * @throws CException
     */
    public function setPackageId($id = null)
    {
        $defaults = $this->panel->billing->getDefaults();

        if($id) {
            $this->data['kuberdock']['packageID'] = $id;
        } elseif(!isset($this->data['kuberdock']['packageID'])) {
            $defaultPackageId = isset($defaults['packageId']) ? $defaults['packageId'] : 0;
            $this->data['kuberdock']['packageID'] = $defaultPackageId;
        }
    }

    /**
     * @param string $description
     */
    public function addPackagePostDescription($description)
    {
        $this->data['kuberdock']['postDescription'] .= "\n" . $description;
    }
}
